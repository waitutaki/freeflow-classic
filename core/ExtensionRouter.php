<?php
namespace Core;

class ExtensionRouter
{
    public static function resolveControllerClass(string $extKey, string $controller): string
    {
        $extKey = strtolower($extKey);
        $ns = self::studly($extKey);
        if (str_contains($controller, '\\')) {
            return $controller;
        }
        $controller = self::studly($controller);
        return 'extension\\' . $ns . '\\Controllers\\' . $controller;
    }

    public static function dispatchAdmin(Request $request): Response
    {
        $extKey = strtolower((string)$request->param('ext_key'));
        $controller = (string)($request->param('controller') ?? 'Dashboard');
        $action = (string)($request->param('action') ?? 'index');

        if (!preg_match('/^[a-z0-9_-]+$/', $extKey)) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $ext = \Core\Models\extensionModel::findByKey($extKey);
        if (!$ext) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        if (empty($ext['is_active'])) {
            return \Core\Controllers\Admin\AdminextensionController::inactiveResponse($extKey);
        }

        $class = self::resolveControllerClass($extKey, $controller);
        if (!class_exists($class)) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        ExtensionRuntime::enter($extKey);
        try {
            $instance = new $class();
            if (!method_exists($instance, $action)) {
                return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
            }
            $result = $instance->$action($request);
            if ($result instanceof Response) {
                return $result;
            }
            return new Response((string)$result, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
        } finally {
            ExtensionRuntime::exit();
        }
    }

    private static function studly(string $value): string
    {
        $value = preg_replace('/[^a-z0-9]+/i', ' ', $value);
        $value = ucwords(strtolower($value));
        return str_replace(' ', '', $value);
    }
}
