<?php
namespace Core;

class Logger
{
    private const CHANNELS = [
        'error' => 'error.log',
        'auth' => 'auth.log',
        'mail' => 'mail.log',
        'updates' => 'updates.log',
        'admin' => 'admin.log',
        'devstore' => 'devstore.log',
    ];

    public static function files(): array
    {
        return self::CHANNELS;
    }

    public static function info(string $channel, string $message, array $context = []): bool
    {
        return self::write('INFO', $channel, $message, $context);
    }

    public static function warning(string $channel, string $message, array $context = []): bool
    {
        return self::write('WARNING', $channel, $message, $context);
    }

    public static function error(string $channel, string $message, array $context = []): bool
    {
        return self::write('ERROR', $channel, $message, $context);
    }

    private static function write(string $level, string $channel, string $message, array $context): bool
    {
        if (!isset(self::CHANNELS[$channel])) {
            return false;
        }

        $timestamp = self::timestamp();
        $contextLine = self::formatContext($context);
        $safeMessage = self::sanitize($message);
        $line = $timestamp . ' | ' . $level . ' | ' . $channel . ' | ' . $contextLine . ' | ' . $safeMessage;

        $path = __DIR__ . '/../storage/logs/' . self::CHANNELS[$channel];
        file_put_contents($path, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
        return true;
    }

    private static function timestamp(): string
    {
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $month = $months[(int)date('n') - 1] ?? 'Jan';
        return sprintf(
            '%02d-%s-%04d %02d:%02d:%02d',
            (int)date('d'),
            $month,
            (int)date('Y'),
            (int)date('H'),
            (int)date('i'),
            (int)date('s')
        );
    }

    private static function formatContext(array $context): string
    {
        if ($context === []) {
            return '-';
        }

        $pairs = [];
        foreach ($context as $key => $value) {
            $safeKey = self::sanitize((string)$key);
            $safeValue = self::sanitize(self::stringify($value));
            if (self::isSensitiveKey($safeKey)) {
                $safeValue = '[REDACTED]';
            }
            $pairs[] = $safeKey . '=' . $safeValue;
        }

        return $pairs ? implode(' ', $pairs) : '-';
    }

    private static function sanitize(string $value): string
    {
        $value = str_replace(["\r", "\n", '|'], ' ', $value);
        return trim($value);
    }

    private static function stringify(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_scalar($value) || $value === null) {
            return (string)$value;
        }
        return '[complex]';
    }

    private static function isSensitiveKey(string $key): bool
    {
        $key = strtolower($key);
        $needles = ['pass', 'password', 'token', 'secret', 'key', 'session', 'cookie', 'auth'];
        foreach ($needles as $needle) {
            if (str_contains($key, $needle)) {
                return true;
            }
        }
        return false;
    }
}
