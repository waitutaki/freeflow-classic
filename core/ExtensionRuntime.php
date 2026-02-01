<?php
namespace Core;

class ExtensionRuntime
{
    private static ?string $currentKey = null;

    public static function enter(string $extKey): void
    {
        self::$currentKey = $extKey;
        NetworkGuard::enable();
    }

    public static function exit(): void
    {
        self::$currentKey = null;
        NetworkGuard::disable();
    }

    public static function currentKey(): ?string
    {
        return self::$currentKey;
    }

    public static function isActive(): bool
    {
        return self::$currentKey !== null;
    }
}
