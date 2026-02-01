<?php
namespace Core;

use Core\Models\ApiRoutesModel;
use Core\Models\extensionModel;

class ApiDispatcher
{
    public static function handle(Request $request, string $context): ?Response
    {
        $path = $request->path();
        if (!str_starts_with($path, '/api/ext/')) {
            return null;
        }

        $segments = explode('/', trim($path, '/'));
        $extKey = $segments[2] ?? '';
        $routePath = implode('/', array_slice($segments, 3));
        if ($extKey === '' || $routePath === '') {
            return ApiResponder::error('Not Found', 404);
        }

        $isDevstore = $extKey === 'devstore';
        if (!$isDevstore) {
            $ext = extensionModel::findByKey($extKey);
            if (!$ext || empty($ext['is_active'])) {
                return ApiResponder::error('Not Found', 404);
            }
        }

        $route = ApiRoutesModel::match($extKey, $request->method(), $routePath);
        if (!$route) {
            Logger::warning('admin', 'API route missing', ['ext_key' => $extKey, 'route' => $routePath]);
            return ApiResponder::error('Not Found', 404);
        }

        if (empty($route['is_public'])) {
            if ($context === Application::CONTEXT_ADMIN) {
                Session::startAdmin();
                if (!Auth::isAuthenticated()) {
                    return ApiResponder::error('Forbidden', 403);
                }
            } else {
                Session::startSite();
                if (!Auth::isAuthenticated()) {
                    return ApiResponder::error('Forbidden', 403);
                }
            }

            $permKey = trim((string)($route['required_permission'] ?? ''));
            if ($permKey !== '' && !ACL::can($permKey)) {
                return ApiResponder::error('Forbidden', 403);
            }
        }

        $controller = (string)($route['controller'] ?? '');
        $action = (string)($route['action'] ?? '');
        if ($controller === '' || $action === '') {
            Logger::error('error', 'API route invalid', ['ext_key' => $extKey, 'route' => $routePath]);
            return ApiResponder::error('Invalid route', 400);
        }

        if (isset($route['_params']) && is_array($route['_params'])) {
            $request->setParams($route['_params']);
        }

        $class = $isDevstore
            ? ('Core\\Controllers\\Api\\' . $controller)
            : ExtensionRouter::resolveControllerClass($extKey, $controller);
        if (!class_exists($class)) {
            Logger::error('error', 'API controller missing', ['ext_key' => $extKey, 'controller' => $class]);
            return ApiResponder::error('Not Found', 404);
        }

        if (!$isDevstore) {
            ExtensionRuntime::enter($extKey);
        }
        try {
            $instance = new $class();
            if (!method_exists($instance, $action)) {
                return ApiResponder::error('Not Found', 404);
            }
            $result = $instance->$action($request);
            if ($result instanceof Response) {
                return $result;
            }
            return ApiResponder::ok(Template::escape((string)$result));
        } finally {
            if (!$isDevstore) {
                ExtensionRuntime::exit();
            }
        }
    }
}
