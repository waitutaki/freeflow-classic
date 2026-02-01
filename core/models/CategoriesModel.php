<?php
namespace Core\Models;

use Core\Connection;

class CategoriesModel
{
    public static function all(): array
    {
        $stmt = Connection::prepare('SELECT c.*, u.username AS created_by_name, m.username AS modified_by_name, p.title AS parent_title FROM #__categories c LEFT JOIN #__users u ON u.id = c.created_by LEFT JOIN #__users m ON m.id = c.modified_by LEFT JOIN #__categories p ON p.id = c.parent_id ORDER BY c.title ASC');
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public static function listAll(): array
    {
        $stmt = Connection::prepare('SELECT id, title, parent_id FROM #__categories ORDER BY title ASC');
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public static function find(int $id): ?array
    {
        $stmt = Connection::prepare('SELECT * FROM #__categories WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function slugExists(string $slug, int $excludeId = 0): bool
    {
        $sql = 'SELECT id FROM #__categories WHERE slug = :slug';
        $params = [':slug' => $slug];
        if ($excludeId > 0) {
            $sql .= ' AND id != :id';
            $params[':id'] = $excludeId;
        }
        $sql .= ' LIMIT 1';
        $stmt = Connection::prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetch();
    }

    public static function create(array $data): int
    {
        $stmt = Connection::prepare('INSERT INTO #__categories (title, slug, parent_id, description, created_by, created_at, modified_by, modified_at) VALUES (:title, :slug, :parent_id, :description, :created_by, :created_at, :modified_by, :modified_at)');
        $stmt->execute([
            ':title' => $data['title'],
            ':slug' => $data['slug'],
            ':parent_id' => $data['parent_id'],
            ':description' => $data['description'],
            ':created_by' => $data['created_by'],
            ':created_at' => date('Y-m-d H:i:s'),
            ':modified_by' => $data['modified_by'],
            ':modified_at' => date('Y-m-d H:i:s'),
        ]);
        return (int)Connection::pdo()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Connection::prepare('UPDATE #__categories SET title = :title, slug = :slug, parent_id = :parent_id, description = :description, modified_by = :modified_by, modified_at = :modified_at WHERE id = :id');
        $stmt->execute([
            ':title' => $data['title'],
            ':slug' => $data['slug'],
            ':parent_id' => $data['parent_id'],
            ':description' => $data['description'],
            ':modified_by' => $data['modified_by'],
            ':modified_at' => date('Y-m-d H:i:s'),
            ':id' => $id,
        ]);
    }

    public static function delete(int $id): void
    {
        $stmt = Connection::prepare('DELETE FROM #__categories WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public static function isDescendant(int $categoryId, int $ancestorId): bool
    {
        $current = $categoryId;
        $seen = [];
        while ($current > 0 && !isset($seen[$current])) {
            $seen[$current] = true;
            $stmt = Connection::prepare('SELECT parent_id FROM #__categories WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $current]);
            $row = $stmt->fetch();
            $parentId = $row ? (int)$row['parent_id'] : 0;
            if ($parentId === $ancestorId) {
                return true;
            }
            $current = $parentId;
        }
        return false;
    }
}
