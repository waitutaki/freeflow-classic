<?php
namespace Core\Models;

use Core\Connection;
use Core\DocumentService;

class DocumentsModel
{
    public static function all(): array
    {
        $stmt = Connection::prepare('SELECT d.*, u.username AS owner_username FROM #__documents d LEFT JOIN #__users u ON u.id = d.owner_user_id ORDER BY d.id DESC');
        $stmt->execute();
        $rows = $stmt->fetchAll() ?: [];
        return self::filterExisting($rows);
    }

    public static function forUser(int $userId): array
    {
        $stmt = Connection::prepare('SELECT d.*, u.username AS owner_username FROM #__documents d LEFT JOIN #__users u ON u.id = d.owner_user_id WHERE d.owner_user_id = :id ORDER BY d.id DESC');
        $stmt->execute([':id' => $userId]);
        $rows = $stmt->fetchAll() ?: [];
        return self::filterExisting($rows);
    }

    public static function find(int $id): ?array
    {
        $stmt = Connection::prepare('SELECT d.*, u.username AS owner_username FROM #__documents d LEFT JOIN #__users u ON u.id = d.owner_user_id WHERE d.id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        if (!DocumentService::fileExists($row)) {
            return null;
        }
        return $row;
    }

    public static function create(array $data): int
    {
        $stmt = Connection::prepare('INSERT INTO #__documents (owner_user_id, original_name, stored_name, display_name, mime_type, file_ext, file_size, storage_path, uploaded_at, uploaded_by) VALUES (:owner_user_id, :original_name, :stored_name, :display_name, :mime_type, :file_ext, :file_size, :storage_path, :uploaded_at, :uploaded_by)');
        $stmt->execute([
            ':owner_user_id' => $data['owner_user_id'],
            ':original_name' => $data['original_name'],
            ':stored_name' => $data['stored_name'],
            ':display_name' => $data['display_name'],
            ':mime_type' => $data['mime_type'],
            ':file_ext' => $data['file_ext'],
            ':file_size' => $data['file_size'],
            ':storage_path' => $data['storage_path'],
            ':uploaded_at' => date('Y-m-d H:i:s'),
            ':uploaded_by' => $data['uploaded_by'],
        ]);
        return (int)Connection::pdo()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $fields = [
            'display_name' => $data['display_name'],
            'stored_name' => $data['stored_name'],
            'owner_user_id' => $data['owner_user_id'],
            'modified_at' => date('Y-m-d H:i:s'),
            'modified_by' => $data['modified_by'],
        ];
        if (isset($data['storage_path'])) {
            $fields['storage_path'] = $data['storage_path'];
        } else {
            $stmt = Connection::prepare('SELECT storage_path FROM #__documents WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch();
            $fields['storage_path'] = $row['storage_path'] ?? '';
        }
        $stmt = Connection::prepare('UPDATE #__documents SET display_name = :display_name, stored_name = :stored_name, owner_user_id = :owner_user_id, storage_path = :storage_path, modified_at = :modified_at, modified_by = :modified_by WHERE id = :id');
        $stmt->execute([
            ':display_name' => $fields['display_name'],
            ':stored_name' => $fields['stored_name'],
            ':owner_user_id' => $fields['owner_user_id'],
            ':storage_path' => $fields['storage_path'],
            ':modified_at' => $fields['modified_at'],
            ':modified_by' => $fields['modified_by'],
            ':id' => $id,
        ]);
    }

    public static function delete(int $id): void
    {
        $stmt = Connection::prepare('DELETE FROM #__documents WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    private static function filterExisting(array $rows): array
    {
        $filtered = [];
        foreach ($rows as $row) {
            if (DocumentService::fileExists($row)) {
                $filtered[] = $row;
            }
        }
        return $filtered;
    }
}
