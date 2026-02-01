<?php
namespace Core;

class Session
{
    private static bool $started = false;
    private static string $context = '';

    public static function startSite(): void
    {
        self::start('site', 'ff_site', '/');
    }

    public static function startAdmin(): void
    {
        self::start('admin', 'ff_admin', '/');
    }

    private static function start(string $context, string $name, string $path): void
    {
        if (self::$started) {
            return;
        }

        self::$context = $context;
        $lifetime = 7200;

        session_name($name);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => $path,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        $handler = new DbSessionHandler($context, $lifetime);
        session_set_save_handler($handler, true);

        session_start();
        self::$started = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (!isset($_SESSION) || !is_array($_SESSION)) {
            return $default;
        }
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        if (!isset($_SESSION) || !is_array($_SESSION)) {
            $_SESSION = [];
        }
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        if (!isset($_SESSION) || !is_array($_SESSION)) {
            return false;
        }
        return array_key_exists($key, $_SESSION);
    }

    public static function remove(string $key): void
    {
        if (!isset($_SESSION) || !is_array($_SESSION)) {
            return;
        }
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        if (!self::$started) {
            return;
        }
        session_destroy();
        self::$started = false;
    }

    public static function context(): string
    {
        return self::$context;
    }

    public static function setFlash(string $type, string $message): void
    {
        self::set('flash_' . $type, $message);
    }

    public static function getFlash(string $type): ?string
    {
        $message = self::get('flash_' . $type);
        if ($message !== null) {
            self::remove('flash_' . $type);
        }
        return $message;
    }
}
