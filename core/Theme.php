<?php
namespace Core;

use Core\Models\ThemesModel;
use Core\Models\ThemeOverridesModel;
use Core\Models\ElementPositionsModel;

class Theme
{
    public static function activeKey(string $context): string
    {
        $context = $context === 'admin' ? 'admin' : 'site';
        if ($context === 'admin') {
            return 'zulu';
        }

        $active = ThemesModel::activeTheme('site');
        if ($active && isset($active['theme_key'])) {
            $key = (string)$active['theme_key'];
            $path = __DIR__ . '/../themes/' . strtolower($key) . '/index.php';
            if (is_file($path)) {
                return $key;
            }
        }
        return 'alpha';
    }

    public static function overridesUrl(string $themeKey, string $context): string
    {
        $themeKey = strtolower(preg_replace('/[^a-z0-9_-]/', '', $themeKey));
        $context = $context === 'admin' ? 'admin' : 'site';
        $rev = self::overridesRevision($themeKey, $context);
        $suffix = $rev > 0 ? '&rev=' . $rev : '';
        return '/themes/' . $themeKey . '/overrides.css?ctx=' . $context . $suffix;
    }

    public static function overridesRevision(string $themeKey, string $context): int
    {
        $theme = ThemesModel::findByKey($themeKey, $context);
        if (!$theme) {
            return 0;
        }
        return ThemeOverridesModel::latestRevision((int)$theme['id'], $context);
    }

    public static function loadDefinition(string $themeKey): ?array
    {
        $themeKey = strtolower(preg_replace('/[^a-z0-9_-]/', '', $themeKey));
        $path = __DIR__ . '/../themes/' . $themeKey . '/theme.xml';
        if (!is_file($path)) {
            Logger::error('error', 'Theme XML missing', ['theme_key' => $themeKey]);
            return null;
        }

        $xml = self::loadXml($path);
        if (!$xml) {
            Logger::error('error', 'Theme XML invalid', ['theme_key' => $themeKey]);
            return null;
        }

        $meta = [
            'key' => self::xmlValue($xml->meta->key ?? null),
            'name' => self::xmlValue($xml->meta->name ?? null),
            'context' => self::xmlValue($xml->meta->context ?? null),
            'description' => self::xmlValue($xml->meta->description ?? null),
            'author' => self::xmlValue($xml->meta->author ?? null),
            'version' => self::xmlValue($xml->meta->version ?? null),
        ];

        $regions = [];
        $tokens = [];
        if (isset($xml->regions->region)) {
            foreach ($xml->regions->region as $region) {
                $regionKey = self::attr($region, 'key');
                if ($regionKey === '') {
                    continue;
                }
                $regionEntry = [
                    'key' => $regionKey,
                    'label' => self::attr($region, 'label'),
                    'help' => self::attr($region, 'help'),
                    'tokens' => [],
                ];
                if (isset($region->token)) {
                    foreach ($region->token as $token) {
                        $tokenKey = self::attr($token, 'key');
                        if ($tokenKey === '' || !self::validToken($tokenKey)) {
                            continue;
                        }
                        $tokenDef = [
                            'key' => $tokenKey,
                            'label' => self::attr($token, 'label'),
                            'help' => self::attr($token, 'help'),
                            'type' => self::attr($token, 'type') ?: 'text',
                            'format' => self::attr($token, 'format'),
                            'default' => self::attr($token, 'default'),
                            'min' => self::attr($token, 'min'),
                            'max' => self::attr($token, 'max'),
                            'step' => self::attr($token, 'step'),
                            'pattern' => self::attr($token, 'pattern'),
                            'options' => [],
                        ];
                        if (isset($token->option)) {
                            foreach ($token->option as $option) {
                                $tokenDef['options'][] = [
                                    'value' => self::attr($option, 'value'),
                                    'label' => self::attr($option, 'label'),
                                ];
                            }
                        }
                        $regionEntry['tokens'][] = $tokenDef;
                        $tokens[$tokenKey] = $tokenDef;
                    }
                }
                $regions[] = $regionEntry;
            }
        }

        return [
            'meta' => $meta,
            'regions' => $regions,
            'tokens' => $tokens,
        ];
    }

    public static function syncPositions(string $themeKey, string $context): void
    {
        $context = $context === 'admin' ? 'admin' : 'site';
        $positions = self::loadPositions($themeKey);
        if (!$positions) {
            return;
        }
        $existing = ElementPositionsModel::forTheme($themeKey, $context);
        $existingMap = [];
        foreach ($existing as $row) {
            $existingMap[$row['position']] = true;
        }
        $newPositions = [];
        foreach ($positions as $position) {
            if (!isset($existingMap[$position])) {
                $newPositions[] = $position;
            }
        }
        if ($newPositions) {
            ElementPositionsModel::upsertPositions($themeKey, $context, $newPositions);
        }
    }

    public static function validateTokenValue(array $def, string $value): ?string
    {
        $value = trim($value);
        $type = $def['type'] ?? 'text';
        if ($type === 'color') {
            $format = $def['format'] ?? 'any';
            return self::validateColor($value, $format) ? $value : null;
        }
        if ($type === 'range') {
            if (!is_numeric($value)) {
                return null;
            }
            $num = (float)$value;
            if ($def['min'] !== '' && $num < (float)$def['min']) {
                return null;
            }
            if ($def['max'] !== '' && $num > (float)$def['max']) {
                return null;
            }
            return (string)$num;
        }
        if ($type === 'select') {
            foreach ($def['options'] ?? [] as $option) {
                if ((string)$option['value'] === $value) {
                    return $value;
                }
            }
            return null;
        }
        if ($type === 'text' && ($def['pattern'] ?? '') !== '') {
            if (!preg_match('/' . $def['pattern'] . '/', $value)) {
                return null;
            }
        }
        return $value;
    }

    public static function overridesCss(string $themeKey, string $context): Response
    {
        if (!in_array($context, ['site', 'admin'], true)) {
            Logger::error('error', 'Invalid theme context', ['context' => $context]);
            return new Response('/* invalid context */', 400, ['Content-Type' => 'text/css; charset=utf-8']);
        }
        $def = self::loadDefinition($themeKey);
        if (!$def) {
            return new Response('/* theme not found */', 404, ['Content-Type' => 'text/css; charset=utf-8']);
        }

        $theme = ThemesModel::findByKey($themeKey, $context);
        if (!$theme) {
            return new Response('/* theme not registered */', 404, ['Content-Type' => 'text/css; charset=utf-8']);
        }

        $overrides = ThemeOverridesModel::allForTheme((int)$theme['id'], $context);
        $lines = [];
        foreach ($overrides as $token => $row) {
            if (!isset($def['tokens'][$token])) {
                Logger::error('error', 'Unknown theme token', ['theme_key' => $themeKey, 'token' => $token]);
                continue;
            }
            $value = (string)($row['value'] ?? '');
            $validated = self::validateTokenValue($def['tokens'][$token], $value);
            if ($validated === null) {
                Logger::error('error', 'Invalid theme override', ['theme_key' => $themeKey, 'token' => $token]);
                continue;
            }
            $lines[] = '  --' . $token . ': ' . self::escapeCssValue($validated) . ';';
        }

        $css = ":root {\n" . implode("\n", $lines) . "\n}\n";
        return new Response($css, 200, ['Content-Type' => 'text/css; charset=utf-8']);
    }

    private static function validateColor(string $value, string $format): bool
    {
        if (preg_match('/^#([a-fA-F0-9]{3}|[a-fA-F0-9]{6}|[a-fA-F0-9]{8})$/', $value)) {
            return true;
        }
        if (self::matchRgb($value, false)) {
            return true;
        }
        return self::matchRgb($value, true);
    }

    private static function matchRgb(string $value, bool $alpha): bool
    {
        $pattern = $alpha
            ? '/^rgba\\(\\s*(\\d{1,3})\\s*,\\s*(\\d{1,3})\\s*,\\s*(\\d{1,3})\\s*,\\s*(0|1|0?\\.\\d+)\\s*\\)$/'
            : '/^rgb\\(\\s*(\\d{1,3})\\s*,\\s*(\\d{1,3})\\s*,\\s*(\\d{1,3})\\s*\\)$/';
        if (!preg_match($pattern, $value, $m)) {
            return false;
        }
        if ((int)$m[1] > 255 || (int)$m[2] > 255 || (int)$m[3] > 255) {
            return false;
        }
        if ($alpha) {
            $a = (float)$m[4];
            return $a >= 0 && $a <= 1;
        }
        return true;
    }

    private static function escapeCssValue(string $value): string
    {
        return str_replace(['\\', ';', "\n", "\r"], '', $value);
    }

    private static function validToken(string $token): bool
    {
        return (bool)preg_match('/^[a-z0-9_-]+$/', $token);
    }

    private static function loadXml(string $path): ?\SimpleXMLElement
    {
        if (!is_readable($path)) {
            return null;
        }
        $content = @file_get_contents($path);
        if ($content === false) {
            return null;
        }
        $prev = libxml_disable_entity_loader(true);
        $prevErrors = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NONET);
        libxml_use_internal_errors($prevErrors);
        libxml_disable_entity_loader($prev);
        if ($xml === false) {
            return null;
        }
        return $xml;
    }

    private static function loadPositions(string $themeKey): array
    {
        $themeKey = strtolower(preg_replace('/[^a-z0-9_-]/', '', $themeKey));
        $path = __DIR__ . '/../themes/' . $themeKey . '/theme.xml';
        if (!is_file($path)) {
            return [];
        }
        $xml = self::loadXml($path);
        if (!$xml || !isset($xml->positions->position)) {
            return [];
        }
        $positions = [];
        foreach ($xml->positions->position as $position) {
            $key = self::attr($position, 'key');
            if ($key !== '') {
                $positions[] = $key;
            }
        }
        return $positions;
    }

    private static function xmlValue(?\SimpleXMLElement $node): string
    {
        if ($node === null) {
            return '';
        }
        return trim((string)$node);
    }

    private static function attr(\SimpleXMLElement $node, string $name): string
    {
        $value = (string)$node[$name];
        return trim($value);
    }
}
