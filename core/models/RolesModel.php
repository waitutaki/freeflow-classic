<?php
namespace Core\Models;

use Core\Connection;

class RolesModel
{
    public static function all(): array
    {
        $stmt = Connection::prepare('SELECT * FROM #__roles ORDER BY id ASC');
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public static function find(int $id): ?array
    {
        $stmt = Connection::prepare('SELECT * FROM #__roles WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function hasChildren(int $id): bool
    {
        $stmt = Connection::prepare('SELECT id FROM #__roles WHERE parent_role_id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        return (bool)$stmt->fetch();
    }

    public static function inUse(int $id): bool
    {
        $stmt = Connection::prepare('SELECT user_id FROM #__user_roles WHERE role_id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        return (bool)$stmt->fetch();
    }

    public static function nextCustomId(int $parentId): int
    {
        $reserved = [100, 200, 300, 400, 500, 998];
        $stmt = Connection::prepare('SELECT id FROM #__roles ORDER BY id ASC');
        $stmt->execute();
        $rows = $stmt->fetchAll() ?: [];
        $used = [];
        foreach ($rows as $row) {
            $used[(int)$row['id']] = true;
        }

        $id = max(1, $parentId + 1);
        while (isset($used[$id]) || in_array($id, $reserved, true)) {
            $id++;
        }
        return $id;
    }

    public static function create(array $data): int
    {
        $stmt = Connection::prepare('INSERT INTO #__roles (id, title, is_system, parent_role_id, created_at, updated_at) VALUES (:id, :title, :is_system, :parent_role_id, :created_at, :updated_at)');
        $stmt->execute([
            ':id' => $data['id'],
            ':title' => $data['title'],
            ':is_system' => $data['is_system'],
            ':parent_role_id' => $data['parent_role_id'],
            ':created_at' => date('Y-m-d H:i:s'),
            ':updated_at' => date('Y-m-d H:i:s'),
        ]);
        return (int)$data['id'];
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Connection::prepare('UPDATE #__roles SET title = :title, parent_role_id = :parent_role_id, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            ':title' => $data['title'],
            ':parent_role_id' => $data['parent_role_id'],
            ':updated_at' => date('Y-m-d H:i:s'),
            ':id' => $id,
        ]);
    }

    public static function delete(int $id): void
    {
        $stmt = Connection::prepare('DELETE FROM #__roles WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $stmt = Connection::prepare('DELETE FROM #__role_permissions WHERE role_id = :id');
        $stmt->execute([':id' => $id]);
    }

    public static function hasCycle(int $roleId, ?int $parentId): bool
    {
        if ($parentId === null || $parentId === 0) {
            return false;
        }
        if ($parentId === $roleId) {
            return true;
        }
        $seen = [];
        $current = $parentId;
        while ($current > 0) {
            if (isset($seen[$current])) {
                return true;
            }
            $seen[$current] = true;
            $row = self::find($current);
            if (!$row) {
                return false;
            }
            $current = (int)$row['parent_role_id'];
            if ($current === $roleId) {
                return true;
            }
        }
        return false;
    }
}
