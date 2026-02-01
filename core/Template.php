<?php
namespace Core;

class Template
{
    public static function render(string $template, array $regions = [], array $data = []): string
    {
        $path = self::templatePath($template);
        if (!is_file($path)) {
            return '';
        }

        $regions = self::normalizeRegions($regions);
        extract($data, EXTR_SKIP);
        $theme_key = strtolower(preg_replace('/[^A-Za-z0-9_-]/', '', $template));

        ob_start();
        require $path;
        return ob_get_clean();
    }

    public static function region(array $regions, string $key): string
    {
        return $regions[$key] ?? '';
    }

    public static function hasRegion(array $regions, string $key): bool
    {
        return isset($regions[$key]) && trim($regions[$key]) !== '';
    }

    public static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private static function templatePath(string $template): string
    {
        $safe = preg_replace('/[^A-Za-z0-9_-]/', '', $template);
        $safe = strtolower($safe);
        return __DIR__ . '/../themes/' . $safe . '/index.php';
    }

    private static function normalizeRegions(array $regions): array
    {
        foreach ($regions as $key => $value) {
            if (!is_string($value)) {
                $regions[$key] = '';
            }
        }
        return $regions;
    }
}
