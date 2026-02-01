<?php
namespace Core\Models;

use Core\Connection;

class MailWrappersModel
{
    public static function all(): array
    {
        $stmt = Connection::prepare('SELECT * FROM #__mail_wrappers ORDER BY id ASC');
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public static function find(int $id): ?array
    {
        $stmt = Connection::prepare('SELECT * FROM #__mail_wrappers WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(array $data, int $userId): int
    {
        $stmt = Connection::prepare('INSERT INTO #__mail_wrappers (wrapper_key, name, wrapper_doc_xml, html_cache, text_cache, is_default, is_enabled, created_at, updated_at) VALUES (:wrapper_key, :name, :wrapper_doc_xml, :html_cache, :text_cache, :is_default, :is_enabled, :created_at, :updated_at)');
        $stmt->execute([
            ':wrapper_key' => $data['wrapper_key'],
            ':name' => $data['name'],
            ':wrapper_doc_xml' => $data['wrapper_doc_xml'],
            ':html_cache' => $data['html_cache'],
            ':text_cache' => $data['text_cache'],
            ':is_default' => $data['is_default'],
            ':is_enabled' => $data['is_enabled'],
            ':created_at' => date('Y-m-d H:i:s'),
            ':updated_at' => date('Y-m-d H:i:s'),
        ]);

        return (int)Connection::pdo()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Connection::prepare('UPDATE #__mail_wrappers SET name = :name, wrapper_doc_xml = :wrapper_doc_xml, html_cache = :html_cache, text_cache = :text_cache, is_default = :is_default, is_enabled = :is_enabled, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            ':name' => $data['name'],
            ':wrapper_doc_xml' => $data['wrapper_doc_xml'],
            ':html_cache' => $data['html_cache'],
            ':text_cache' => $data['text_cache'],
            ':is_default' => $data['is_default'],
            ':is_enabled' => $data['is_enabled'],
            ':updated_at' => date('Y-m-d H:i:s'),
            ':id' => $id,
        ]);
    }

    public static function delete(int $id): bool
    {
        $stmt = Connection::prepare('DELETE FROM #__mail_wrappers WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    public static function existsKey(string $key): bool
    {
        $stmt = Connection::prepare('SELECT id FROM #__mail_wrappers WHERE wrapper_key = :key LIMIT 1');
        $stmt->execute([':key' => $key]);
        return (bool)$stmt->fetch();
    }

    public static function hasDefault(int $excludeId = 0): bool
    {
        $sql = 'SELECT id FROM #__mail_wrappers WHERE is_default = 1 AND is_enabled = 1';
        $params = [];
        if ($excludeId > 0) {
            $sql .= ' AND id != :id';
            $params[':id'] = $excludeId;
        }
        $sql .= ' LIMIT 1';
        $stmt = Connection::prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetch();
    }

    public static function setDefault(int $id): void
    {
        $stmt = Connection::prepare('UPDATE #__mail_wrappers SET is_default = 0');
        $stmt->execute();
        $stmt = Connection::prepare('UPDATE #__mail_wrappers SET is_default = 1 WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
