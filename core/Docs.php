<?php
namespace Core;

class Docs
{
    public static function serve(string $path): Response
    {
        $relative = ltrim($path, '/');
        if (str_contains($relative, '..')) {
            return new Response('Forbidden', 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $filePath = dirname(__DIR__) . '/' . $relative;
        if (is_dir($filePath)) {
            $filePath = rtrim($filePath, '/') . '/index.html';
        }

        if (!is_file($filePath)) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        if (str_starts_with($relative, 'docs-html/sysadmin')) {
            if (!empty($_COOKIE['ff_admin'])) {
                Session::startAdmin();
            } else {
                Session::startSite();
            }
            if (!Auth::isSuper()) {
                return new Response('Forbidden', 403, ['Content-Type' => 'text/plain; charset=utf-8']);
            }
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        return new Response($content, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }
}
