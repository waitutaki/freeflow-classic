<?php
namespace Core;

class NetworkGuard
{
    private static bool $enabled = false;
    private static array $unregistered = [];

    public static function enable(): void
    {
        if (self::$enabled) {
            return;
        }
        self::$enabled = true;
        self::$unregistered = [];
        foreach (['http', 'https', 'ftp', 'ftps'] as $wrapper) {
            if (in_array($wrapper, stream_get_wrappers(), true)) {
                stream_wrapper_unregister($wrapper);
                self::$unregistered[] = $wrapper;
            }
        }
    }

    public static function disable(): void
    {
        if (!self::$enabled) {
            return;
        }
        foreach (self::$unregistered as $wrapper) {
            if (!in_array($wrapper, stream_get_wrappers(), true)) {
                @stream_wrapper_restore($wrapper);
            }
        }
        self::$unregistered = [];
        self::$enabled = false;
    }
}
