<?php
namespace Core;

class Lang
{
    private static string $active = 'en-GB';
    private static string $fallback = 'en-GB';
    private static array $cache = [];

    public static function init(): void
    {
        $language = self::$fallback;

        if (class_exists(Session::class) && Session::has('lang')) {
            $language = (string)Session::get('lang');
        }

        // Check if force_language is set and override the language if necessary
        if (class_exists(Settings::class)) {
            $forceLanguage = Settings::get('force_language', 'off', 'global');
            if ($forceLanguage !== 'off') {
                $language = $forceLanguage;
                // Update the session to reflect the forced language
                if (class_exists(Session::class)) {
                    Session::set('lang', $forceLanguage);
                }
            }
        }

        self::setActive($language);
    }

    public static function setActive(string $language): void
    {
        $language = self::normalize($language);
        if ($language === '') {
            $language = self::$fallback;
        }
        if (self::$active !== $language) {
            self::$active = $language;
            self::$cache = [];
        }
    }

    public static function get(string $key): string
    {
        if ($key === '') {
            return $key;
        }

        $active = self::load(self::$active);
        if (isset($active[$key])) {
            return $active[$key];
        }

        $fallback = self::load(self::$fallback);
        if (isset($fallback[$key])) {
            return $fallback[$key];
        }

        return $key;
    }

    private static function load(string $language): array
    {
        if (isset(self::$cache[$language])) {
            return self::$cache[$language];
        }

        $translations = [];
        $coreFile = __DIR__ . '/languages/' . $language . '.ini';
        if (is_file($coreFile)) {
            $translations = array_merge($translations, self::loadFromFile($coreFile));
        }

        $extRoot = __DIR__ . '/../extension';
        if (is_dir($extRoot)) {
            $extKeys = [];
            if (class_exists(\Core\Models\extensionModel::class)) {
                $extKeys = \Core\Models\extensionModel::activeKeys();
            }
            sort($extKeys, SORT_STRING);
            foreach ($extKeys as $extKey) {
                $extKey = strtolower($extKey);
                if (!preg_match('/^[a-z0-9_-]+$/', $extKey)) {
                    continue;
                }
                $langFile = $extRoot . '/' . $extKey . '/Languages/' . $language . '.ini';
                if (!is_file($langFile)) {
                    $langFile = $extRoot . '/' . $extKey . '/Languages/' . self::$fallback . '.ini';
                }
                if (is_file($langFile)) {
                    $translations = self::mergeExtensionLang($translations, $extKey, $langFile);
                }
            }
        }

        self::$cache[$language] = $translations;
        return $translations;
    }

    private static function mergeExtensionLang(array $translations, string $extKey, string $file): array
    {
        $data = @parse_ini_file($file, false, INI_SCANNER_RAW);
        if ($data === false) {
            Logger::error('error', 'Language file unreadable', ['file' => $file]);
            return $translations;
        }

        $prefix = strtoupper($extKey) . '.';
        foreach ($data as $key => $value) {
            $key = strtoupper((string)$key);
            if (!str_starts_with($key, $prefix)) {
                Logger::warning('admin', 'Extension language key ignored', ['ext_key' => $extKey, 'key' => $key]);
                continue;
            }
            if (isset($translations[$key])) {
                Logger::warning('admin', 'Extension language key collision', ['ext_key' => $extKey, 'key' => $key]);
                continue;
            }
            $translations[$key] = (string)$value;
        }

        return $translations;
    }

    private static function loadFromFile(string $file): array
    {
        $translations = [];
        $data = @parse_ini_file($file, false, INI_SCANNER_RAW);
        if ($data === false) {
            Logger::error('error', 'Language file unreadable', ['file' => $file]);
            return $translations;
        }
        foreach ($data as $key => $value) {
            $key = strtoupper((string)$key);
            $translations[$key] = (string)$value;
        }
        return $translations;
    }

    private static function normalize(string $language): string
    {
        $language = trim($language);
        if (!preg_match('/^[a-z]{2}-[A-Z]{2}$/', $language)) {
            return '';
        }
        return $language;
    }
}
