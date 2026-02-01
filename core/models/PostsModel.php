<?php
namespace Core\Models;

use Core\Connection;

class PostsModel
{
    public static function all(): array
    {
        $stmt = Connection::prepare('SELECT p.*, u.username AS author_name, m.username AS modified_by_name, c.title AS category_title FROM #__posts p LEFT JOIN #__users u ON u.id = p.author_id LEFT JOIN #__users m ON m.id = p.modified_by LEFT JOIN #__categories c ON c.id = p.category_id ORDER BY p.modified_at DESC, p.id DESC');
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public static function allForUser(int $userId): array
    {
        $stmt = Connection::prepare('SELECT p.*, u.username AS author_name, m.username AS modified_by_name, c.title AS category_title FROM #__posts p LEFT JOIN #__users u ON u.id = p.author_id LEFT JOIN #__users m ON m.id = p.modified_by LEFT JOIN #__categories c ON c.id = p.category_id WHERE p.author_id = :user_id ORDER BY p.modified_at DESC, p.id DESC');
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll() ?: [];
    }

    public static function listForMenu(): array
    {
        $stmt = Connection::prepare('SELECT id, title, slug FROM #__posts ORDER BY title ASC');
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public static function find(int $id): ?array
    {
        $stmt = Connection::prepare('SELECT * FROM #__posts WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $stmt = Connection::prepare('SELECT * FROM #__posts WHERE slug = :slug AND status = :status LIMIT 1');
        $stmt->execute([':slug' => $slug, ':status' => 'published']);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function slugExists(string $slug, int $excludeId = 0): bool
    {
        $sql = 'SELECT id FROM #__posts WHERE slug = :slug';
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
        $stmt = Connection::prepare('INSERT INTO #__posts (title, slug, content_xml, excerpt, status, featured_image_path, category_id, author_id, created_at, modified_by, modified_at, published_at) VALUES (:title, :slug, :content_xml, :excerpt, :status, :featured_image_path, :category_id, :author_id, :created_at, :modified_by, :modified_at, :published_at)');
        $stmt->execute([
            ':title' => $data['title'],
            ':slug' => $data['slug'],
            ':content_xml' => $data['content_xml'],
            ':excerpt' => $data['excerpt'],
            ':status' => $data['status'],
            ':featured_image_path' => $data['featured_image_path'],
            ':category_id' => $data['category_id'],
            ':author_id' => $data['author_id'],
            ':created_at' => date('Y-m-d H:i:s'),
            ':modified_by' => $data['modified_by'],
            ':modified_at' => date('Y-m-d H:i:s'),
            ':published_at' => $data['published_at'],
        ]);
        return (int)Connection::pdo()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Connection::prepare('UPDATE #__posts SET title = :title, slug = :slug, content_xml = :content_xml, excerpt = :excerpt, status = :status, featured_image_path = :featured_image_path, category_id = :category_id, modified_by = :modified_by, modified_at = :modified_at, published_at = :published_at WHERE id = :id');
        $stmt->execute([
            ':title' => $data['title'],
            ':slug' => $data['slug'],
            ':content_xml' => $data['content_xml'],
            ':excerpt' => $data['excerpt'],
            ':status' => $data['status'],
            ':featured_image_path' => $data['featured_image_path'],
            ':category_id' => $data['category_id'],
            ':modified_by' => $data['modified_by'],
            ':modified_at' => date('Y-m-d H:i:s'),
            ':published_at' => $data['published_at'],
            ':id' => $id,
        ]);
    }

    public static function updateStatus(int $id, string $status, ?string $publishedAt, int $modifiedBy): void
    {
        $stmt = Connection::prepare('UPDATE #__posts SET status = :status, published_at = :published_at, modified_by = :modified_by, modified_at = :modified_at WHERE id = :id');
        $stmt->execute([
            ':status' => $status,
            ':published_at' => $publishedAt,
            ':modified_by' => $modifiedBy,
            ':modified_at' => date('Y-m-d H:i:s'),
            ':id' => $id,
        ]);
    }

    public static function delete(int $id): void
    {
        $stmt = Connection::prepare('DELETE FROM #__posts WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
