<?php
namespace Core;

class Autoloader
{
    private static array $prefixes = [];

    public static function register(): void
    {
        if (self::$prefixes) {
            return;
        }

        self::$prefixes = [
            'Core\\Controllers\\Site\\' => __DIR__ . '/controllers/site/',
            'Core\\Controllers\\Admin\\' => __DIR__ . '/controllers/admin/',
            'Core\\Controllers\\Api\\' => __DIR__ . '/controllers/api/',
            'Core\\Models\\' => __DIR__ . '/models/',
            'Core\\Views\\' => __DIR__ . '/views/',
            'Core\\' => __DIR__ . '/',
            'extension\\' => __DIR__ . '/../extension/',
        ];

        spl_autoload_register([self::class, 'load']);
    }

    private static function load(string $class): void
    {
        if (strncmp($class, 'extension\\', 11) === 0) {
            $relative = substr($class, 11);
            $parts = explode('\\', $relative);
            if (!$parts || $parts[0] === '') {
                return;
            }
            $parts[0] = strtolower($parts[0]);
            $path = __DIR__ . '/../extension/' . implode('/', $parts) . '.php';
            if (is_file($path)) {
                require $path;
            }
            return;
        }

        foreach (self::$prefixes as $prefix => $baseDir) {
            $len = strlen($prefix);
            if (strncmp($class, $prefix, $len) !== 0) {
                continue;
            }

            $relative = substr($class, $len);
            $path = $baseDir . str_replace('\\', '/', $relative) . '.php';
            if (is_file($path)) {
                require $path;
            }
            return;
        }
    }
}
