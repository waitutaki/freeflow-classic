<?php
namespace Core\Models;

use Core\Connection;

class ThemesModel
{
    public static function all(): array
    {
        $stmt = Connection::prepare('SELECT * FROM #__themes ORDER BY context ASC, name ASC');
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public static function findByKey(string $key, string $context = ''): ?array
    {
        $key = trim($key);
        if ($key === '') {
            return null;
        }
        if ($context !== '') {
            $stmt = Connection::prepare('SELECT * FROM #__themes WHERE theme_key = :key AND context = :context LIMIT 1');
            $stmt->execute([':key' => $key, ':context' => $context]);
        } else {
            $stmt = Connection::prepare('SELECT * FROM #__themes WHERE theme_key = :key LIMIT 1');
            $stmt->execute([':key' => $key]);
        }
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findById(int $id): ?array
    {
        $stmt = Connection::prepare('SELECT * FROM #__themes WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function activeTheme(string $context): ?array
    {
        $stmt = Connection::prepare('SELECT * FROM #__themes WHERE context = :context AND is_active = 1 LIMIT 1');
        $stmt->execute([':context' => $context]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function setActive(int $id, string $context): void
    {
        $stmt = Connection::prepare('UPDATE #__themes SET is_active = 0 WHERE context = :context');
        $stmt->execute([':context' => $context]);

        $stmt = Connection::prepare('UPDATE #__themes SET is_active = 1 WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public static function upsert(array $data): int
    {
        $stmt = Connection::prepare('INSERT INTO #__themes (theme_key, name, context, is_enabled, is_active, is_core, version, author, description, installed_at, installed_by, updated_at, updated_by) VALUES (:theme_key, :name, :context, :is_enabled, :is_active, :is_core, :version, :author, :description, :installed_at, :installed_by, :updated_at, :updated_by) ON DUPLICATE KEY UPDATE name = VALUES(name), is_enabled = VALUES(is_enabled), is_core = VALUES(is_core), version = VALUES(version), author = VALUES(author), description = VALUES(description), updated_at = VALUES(updated_at), updated_by = VALUES(updated_by)');
        $stmt->execute([
            ':theme_key' => $data['theme_key'],
            ':name' => $data['name'],
            ':context' => $data['context'],
            ':is_enabled' => $data['is_enabled'] ?? 1,
            ':is_active' => $data['is_active'],
            ':is_core' => $data['is_core'],
            ':version' => $data['version'],
            ':author' => $data['author'],
            ':description' => $data['description'],
            ':installed_at' => $data['installed_at'],
            ':installed_by' => $data['installed_by'],
            ':updated_at' => $data['updated_at'] ?? date('Y-m-d H:i:s'),
            ':updated_by' => $data['updated_by'] ?? null,
        ]);
        return (int)Connection::pdo()->lastInsertId();
    }
}
