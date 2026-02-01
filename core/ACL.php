<?php
namespace Core;

class ACL
{
    private static array $cache = [];

    public static function can(string $permKey, ?int $userId = null): bool
    {
        $permKey = trim($permKey);
        if ($permKey === '') {
            return false;
        }

        if ($userId === null) {
            $userId = (int)Session::get('user_id', 0);
        }
        if ($userId <= 0) {
            return false;
        }

        $roles = self::userRoles($userId);
        if (in_array(998, $roles, true)) {
            return true;
        }

        $perms = self::rolePermissions($roles);
        return isset($perms[$permKey]);
    }

    public static function userRoles(int $userId): array
    {
        $cacheKey = 'roles:' . $userId;
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $roles = [];
        $stmt = Connection::prepare('SELECT role_id FROM #__user_roles WHERE user_id = :user_id');
        $stmt->execute([':user_id' => $userId]);
        $rows = $stmt->fetchAll();
        foreach ($rows as $row) {
            $roles[] = (int)$row['role_id'];
        }

        if (!$roles) {
            $stmt = Connection::prepare('SELECT role_id FROM #__users WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $userId]);
            $row = $stmt->fetch();
            if ($row) {
                $roles[] = (int)$row['role_id'];
            }
        }

        $roles = array_values(array_unique(array_filter($roles)));
        self::$cache[$cacheKey] = $roles;
        return $roles;
    }

    private static function rolePermissions(array $roles): array
    {
        sort($roles);
        $cacheKey = 'perms:' . implode(',', $roles);
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $perms = [];
        foreach ($roles as $roleId) {
            $explicit = self::roleExplicitPermissions($roleId);
            foreach ($explicit as $perm) {
                $perms[$perm] = true;
            }

            $parents = self::roleParents($roleId);
            foreach ($parents as $parentId) {
                $parentPerms = self::roleExplicitPermissions($parentId);
                foreach ($parentPerms as $perm) {
                    $perms[$perm] = true;
                }
            }
        }

        self::$cache[$cacheKey] = $perms;
        return $perms;
    }

    private static function roleExplicitPermissions(int $roleId): array
    {
        $cacheKey = 'roleperm:' . $roleId;
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $perms = [];
        $stmt = Connection::prepare('SELECT p.perm_key FROM #__role_permissions rp JOIN #__permissions p ON p.id = rp.permission_id WHERE rp.role_id = :role_id');
        $stmt->execute([':role_id' => $roleId]);
        $rows = $stmt->fetchAll();
        foreach ($rows as $row) {
            $perms[] = $row['perm_key'];
        }

        self::$cache[$cacheKey] = $perms;
        return $perms;
    }

    private static function roleParents(int $roleId): array
    {
        $parents = [];
        $seen = [];
        $current = $roleId;
        while ($current > 0 && !isset($seen[$current])) {
            $seen[$current] = true;
            $stmt = Connection::prepare('SELECT parent_role_id FROM #__roles WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $current]);
            $row = $stmt->fetch();
            $parent = $row ? (int)$row['parent_role_id'] : 0;
            if ($parent > 0) {
                $parents[] = $parent;
                $current = $parent;
                continue;
            }
            break;
        }
        return $parents;
    }

    public static function clearCache(): void
    {
        self::$cache = [];
    }
}
