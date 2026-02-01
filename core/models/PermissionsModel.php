<?php
namespace Core\Models;

use Core\Connection;

class PermissionsModel
{
    public static function all(): array
    {
        $stmt = Connection::prepare('SELECT * FROM #__permissions ORDER BY id ASC');
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public static function rolePermissions(int $roleId): array
    {
        $stmt = Connection::prepare('SELECT p.perm_key FROM #__role_permissions rp JOIN #__permissions p ON p.id = rp.permission_id WHERE rp.role_id = :role_id');
        $stmt->execute([':role_id' => $roleId]);
        $rows = $stmt->fetchAll() ?: [];
        $keys = [];
        foreach ($rows as $row) {
            $keys[$row['perm_key']] = true;
        }
        return $keys;
    }

    public static function permissionId(string $permKey): ?int
    {
        $stmt = Connection::prepare('SELECT id FROM #__permissions WHERE perm_key = :perm_key LIMIT 1');
        $stmt->execute([':perm_key' => $permKey]);
        $row = $stmt->fetch();
        return $row ? (int)$row['id'] : null;
    }

    public static function setRolePermission(int $roleId, int $permId, bool $enabled): void
    {
        if ($enabled) {
            $stmt = Connection::prepare('INSERT IGNORE INTO #__role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)');
            $stmt->execute([':role_id' => $roleId, ':permission_id' => $permId]);
        } else {
            $stmt = Connection::prepare('DELETE FROM #__role_permissions WHERE role_id = :role_id AND permission_id = :permission_id');
            $stmt->execute([':role_id' => $roleId, ':permission_id' => $permId]);
        }
    }
}
