<?php
namespace Core;

class Asset
{
    private const BASES = [
        'core' => __DIR__ . '/assets/',
        'system' => __DIR__ . '/../storage/media/system/',
    ];

    public static function serve(string $assetPath): Response
    {
        $assetPath = ltrim($assetPath, '/');
        $parts = explode('/', $assetPath, 2);
        $scope = $parts[0] ?? '';
        $subPath = $parts[1] ?? '';

        $root = null;
        if ($scope === 'core') {
            $root = self::BASES['core'];
        } elseif ($scope === 'theme') {
            $themeParts = explode('/', $subPath, 2);
            $themeKey = $themeParts[0] ?? '';
            $subPath = $themeParts[1] ?? '';
            if ($themeKey !== '' && preg_match('/^[a-z0-9_-]+$/', $themeKey)) {
                $root = __DIR__ . '/../themes/' . $themeKey . '/assets/';
            }
        } elseif ($scope === 'system') {
            $root = self::BASES['system'];
        }

        if ($root === null) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $fullPath = self::safePath($root, $subPath);
        if ($fullPath === null || !is_file($fullPath)) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $content = file_get_contents($fullPath);
        if ($content === false) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $mime = self::mimeType($fullPath);
        return new Response($content, 200, ['Content-Type' => $mime]);
    }

    public static function url(string $scope, string $path): string
    {
        $scope = trim($scope, '/');
        $path = ltrim($path, '/');
        return '/assets/' . $scope . '/' . $path;
    }

    private static function safePath(string $root, string $relative): ?string
    {
        $relative = str_replace(['..', '\\'], ['', '/'], $relative);
        $full = rtrim($root, '/') . '/' . ltrim($relative, '/');
        $real = realpath($full);
        if ($real === false || !str_starts_with($real, realpath($root))) {
            return null;
        }
        return $real;
    }

    private static function mimeType(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return match ($ext) {
            'css' => 'text/css; charset=utf-8',
            'js' => 'application/javascript; charset=utf-8',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            default => 'application/octet-stream',
        };
    }
}
