<?php
namespace Core;

class Config
{
    private static ?array $data = null;

    public static function load(): array
    {
        if (self::$data !== null) {
            return self::$data;
        }

        $path = self::path();
        if (!is_file($path)) {
            self::$data = [];
            return self::$data;
        }

        $config = require $path;
        self::$data = is_array($config) ? $config : [];

        return self::$data;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $data = self::load();
        $segments = explode('.', $key);
        foreach ($segments as $segment) {
            if (!is_array($data) || !array_key_exists($segment, $data)) {
                return $default;
            }
            $data = $data[$segment];
        }

        return $data;
    }

    public static function hasDbConfig(): bool
    {
        $data = self::load();
        return isset($data['db']) && is_array($data['db']);
    }

    public static function path(): string
    {
        return __DIR__ . '/admin/config/config.php';
    }
}
