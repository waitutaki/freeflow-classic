<?php
namespace Core;

class View
{
    public static function render(string $view, array $data = []): array
    {
        $path = __DIR__ . '/views/' . $view . '.php';
        if (!is_file($path)) {
            return ['content' => '', 'return' => null];
        }

        extract($data, EXTR_SKIP);
        ob_start();
        $returned = require $path;
        $content = ob_get_clean();

        return ['content' => $content, 'return' => $returned];
    }
}
