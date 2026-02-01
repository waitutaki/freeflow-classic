<?php
namespace Core\Models;

use Core\Connection;

class MenusModel
{
    public static function all(): array
    {
        $stmt = Connection::prepare('SELECT m.*, (SELECT COUNT(*) FROM #__menu_items mi WHERE mi.menu_id = m.id) AS item_count FROM #__menus m ORDER BY m.modified_at DESC, m.id DESC');
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public static function find(int $id): ?array
    {
        $stmt = Connection::prepare('SELECT * FROM #__menus WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findByKey(string $key): ?array
    {
        $stmt = Connection::prepare('SELECT * FROM #__menus WHERE `key` = :key LIMIT 1');
        $stmt->execute([':key' => $key]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = Connection::prepare('INSERT INTO #__menus (title, `key`, description, created_by, created_at, modified_by, modified_at) VALUES (:title, :key, :description, :created_by, :created_at, :modified_by, :modified_at)');
        $stmt->execute([
            ':title' => $data['title'],
            ':key' => $data['key'],
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
        $stmt = Connection::prepare('UPDATE #__menus SET title = :title, `key` = :key, description = :description, modified_by = :modified_by, modified_at = :modified_at WHERE id = :id');
        $stmt->execute([
            ':title' => $data['title'],
            ':key' => $data['key'],
            ':description' => $data['description'],
            ':modified_by' => $data['modified_by'],
            ':modified_at' => date('Y-m-d H:i:s'),
            ':id' => $id,
        ]);
    }

    public static function delete(int $id): void
    {
        $stmt = Connection::prepare('DELETE FROM #__menus WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $stmt = Connection::prepare('DELETE FROM #__menu_items WHERE menu_id = :id');
        $stmt->execute([':id' => $id]);
    }
}
