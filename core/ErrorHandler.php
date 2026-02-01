<?php
namespace Core;

class ErrorHandler
{
    public static function register(): void
    {
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleError(int $severity, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $severity)) {
            return true;
        }

        Logger::error('error', 'PHP error', [
            'severity' => $severity,
            'file' => $file,
            'line' => $line,
            'message' => $message,
        ]);

        return true;
    }

    public static function handleException(\Throwable $exception): void
    {
        Logger::error('error', 'Uncaught exception', [
            'type' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'message' => $exception->getMessage(),
        ]);

        echo 'An unexpected error occurred.';
    }

    public static function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error === null) {
            return;
        }

        Logger::error('error', 'Shutdown error', [
            'type' => $error['type'] ?? 0,
            'file' => $error['file'] ?? '',
            'line' => $error['line'] ?? 0,
            'message' => $error['message'] ?? '',
        ]);
    }
}
