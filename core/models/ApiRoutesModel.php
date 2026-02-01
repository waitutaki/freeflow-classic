<?php
namespace Core\Models;

use Core\Connection;

class ApiRoutesModel
{
    public static function find(string $extKey, string $method, string $routePath): ?array
    {
        $stmt = Connection::prepare('SELECT * FROM #__api_routes WHERE ext_key = :ext_key AND http_method = :method AND route_path = :route_path AND is_enabled = 1 LIMIT 1');
        $stmt->execute([
            ':ext_key' => $extKey,
            ':method' => strtoupper($method),
            ':route_path' => $routePath,
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function match(string $extKey, string $method, string $routePath): ?array
    {
        $stmt = Connection::prepare('SELECT * FROM #__api_routes WHERE ext_key = :ext_key AND http_method = :method AND is_enabled = 1');
        $stmt->execute([
            ':ext_key' => $extKey,
            ':method' => strtoupper($method),
        ]);
        $rows = $stmt->fetchAll() ?: [];
        foreach ($rows as $row) {
            if (($row['route_path'] ?? '') === $routePath) {
                return $row;
            }
            $params = self::matchParams((string)($row['route_path'] ?? ''), $routePath);
            if ($params !== null) {
                $row['_params'] = $params;
                return $row;
            }
        }
        return null;
    }

    private static function matchParams(string $pattern, string $path): ?array
    {
        if (!str_contains($pattern, '{')) {
            return null;
        }
        $keys = [];
        $regex = preg_replace_callback('/\\{([a-zA-Z0-9_]+)\\}/', function ($matches) use (&$keys) {
            $keys[] = $matches[1];
            return '([^/]+)';
        }, $pattern);
        if ($regex === null) {
            return null;
        }
        $regex = '#^' . $regex . '$#';
        if (!preg_match($regex, $path, $matches)) {
            return null;
        }
        array_shift($matches);
        $params = [];
        foreach ($keys as $idx => $key) {
            $params[$key] = $matches[$idx] ?? '';
        }
        return $params;
    }

    public static function deleteForExtension(string $extKey): void
    {
        $stmt = Connection::prepare('DELETE FROM #__api_routes WHERE ext_key = :ext_key');
        $stmt->execute([':ext_key' => $extKey]);
    }

    public static function insert(array $data): void
    {
        $stmt = Connection::prepare('INSERT INTO #__api_routes (ext_key, route_path, http_method, controller, action, is_public, required_permission, is_enabled, created_at, created_by) VALUES (:ext_key, :route_path, :http_method, :controller, :action, :is_public, :required_permission, :is_enabled, :created_at, :created_by) ON DUPLICATE KEY UPDATE controller = VALUES(controller), action = VALUES(action), is_public = VALUES(is_public), required_permission = VALUES(required_permission), is_enabled = VALUES(is_enabled)');
        $stmt->execute([
            ':ext_key' => $data['ext_key'],
            ':route_path' => $data['route_path'],
            ':http_method' => strtoupper($data['http_method']),
            ':controller' => $data['controller'],
            ':action' => $data['action'],
            ':is_public' => (int)($data['is_public'] ?? 0),
            ':required_permission' => $data['required_permission'] ?? null,
            ':is_enabled' => (int)($data['is_enabled'] ?? 1),
            ':created_at' => $data['created_at'] ?? date('Y-m-d H:i:s'),
            ':created_by' => $data['created_by'] ?? null,
        ]);
    }
}
