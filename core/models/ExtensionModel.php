<?php
namespace Core\Models;

use Core\Connection;

class ExtensionModel
{
    public static function all(): array
    {
        $stmt = Connection::prepare('SELECT * FROM #__extension ORDER BY ext_key ASC');
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public static function findByKey(string $extKey): ?array
    {
        $stmt = Connection::prepare('SELECT * FROM #__extension WHERE ext_key = :ext_key LIMIT 1');
        $stmt->execute([':ext_key' => $extKey]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function activeKeys(): array
    {
        $stmt = Connection::prepare('SELECT ext_key FROM #__extension WHERE is_active = 1 ORDER BY ext_key ASC');
        $stmt->execute();
        $rows = $stmt->fetchAll() ?: [];
        return array_map(fn($row) => (string)$row['ext_key'], $rows);
    }

    public static function setActive(string $extKey, bool $active): void
    {
        $stmt = Connection::prepare('UPDATE #__extension SET is_active = :active, updated_at = :updated_at WHERE ext_key = :ext_key');
        $stmt->execute([
            ':active' => $active ? 1 : 0,
            ':updated_at' => date('Y-m-d H:i:s'),
            ':ext_key' => $extKey,
        ]);
    }

    public static function upsert(array $data): void
    {
        $stmt = Connection::prepare('INSERT INTO #__extension (ext_key, type, name, version, is_active, is_core, author, description, admin_menu_xml, installed_at, installed_by, updated_at, updated_by) VALUES (:ext_key, :type, :name, :version, :is_active, :is_core, :author, :description, :admin_menu_xml, :installed_at, :installed_by, :updated_at, :updated_by) ON DUPLICATE KEY UPDATE type = VALUES(type), name = VALUES(name), version = VALUES(version), is_active = VALUES(is_active), is_core = VALUES(is_core), author = VALUES(author), description = VALUES(description), admin_menu_xml = VALUES(admin_menu_xml), updated_at = VALUES(updated_at), updated_by = VALUES(updated_by)');
        $stmt->execute([
            ':ext_key' => $data['ext_key'],
            ':type' => $data['type'] ?? 'component',
            ':name' => $data['name'] ?? '',
            ':version' => $data['version'] ?? null,
            ':is_active' => (int)($data['is_active'] ?? 0),
            ':is_core' => (int)($data['is_core'] ?? 0),
            ':author' => $data['author'] ?? null,
            ':description' => $data['description'] ?? null,
            ':admin_menu_xml' => $data['admin_menu_xml'] ?? null,
            ':installed_at' => $data['installed_at'] ?? date('Y-m-d H:i:s'),
            ':installed_by' => $data['installed_by'] ?? null,
            ':updated_at' => $data['updated_at'] ?? date('Y-m-d H:i:s'),
            ':updated_by' => $data['updated_by'] ?? null,
        ]);
    }

    public static function delete(string $extKey): void
    {
        $stmt = Connection::prepare('DELETE FROM #__extension WHERE ext_key = :ext_key');
        $stmt->execute([':ext_key' => $extKey]);
    }
}
