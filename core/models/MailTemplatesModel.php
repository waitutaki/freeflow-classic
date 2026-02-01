<?php
namespace Core\Models;

use Core\Connection;

class MailTemplatesModel
{
    public static function all(): array
    {
        $stmt = Connection::prepare('SELECT * FROM #__mail_templates ORDER BY id ASC');
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public static function find(int $id): ?array
    {
        $stmt = Connection::prepare('SELECT * FROM #__mail_templates WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(array $data, int $userId): int
    {
        $stmt = Connection::prepare('INSERT INTO #__mail_templates (owner_type, owner_key, template_key, name, language_tag, subject, body_doc_xml, html_cache, text_cache, is_enabled, is_core, created_at, updated_at, updated_by) VALUES (:owner_type, :owner_key, :template_key, :name, :language_tag, :subject, :body_doc_xml, :html_cache, :text_cache, :is_enabled, :is_core, :created_at, :updated_at, :updated_by)');
        $stmt->execute([
            ':owner_type' => $data['owner_type'],
            ':owner_key' => $data['owner_key'],
            ':template_key' => $data['template_key'],
            ':name' => $data['name'],
            ':language_tag' => $data['language_tag'],
            ':subject' => $data['subject'],
            ':body_doc_xml' => $data['body_doc_xml'],
            ':html_cache' => $data['html_cache'],
            ':text_cache' => $data['text_cache'],
            ':is_enabled' => $data['is_enabled'],
            ':is_core' => $data['is_core'],
            ':created_at' => date('Y-m-d H:i:s'),
            ':updated_at' => date('Y-m-d H:i:s'),
            ':updated_by' => $userId,
        ]);

        return (int)Connection::pdo()->lastInsertId();
    }

    public static function update(int $id, array $data, int $userId): void
    {
        $stmt = Connection::prepare('UPDATE #__mail_templates SET name = :name, language_tag = :language_tag, subject = :subject, body_doc_xml = :body_doc_xml, html_cache = :html_cache, text_cache = :text_cache, is_enabled = :is_enabled, is_core = :is_core, updated_at = :updated_at, updated_by = :updated_by WHERE id = :id');
        $stmt->execute([
            ':name' => $data['name'],
            ':language_tag' => $data['language_tag'],
            ':subject' => $data['subject'],
            ':body_doc_xml' => $data['body_doc_xml'],
            ':html_cache' => $data['html_cache'],
            ':text_cache' => $data['text_cache'],
            ':is_enabled' => $data['is_enabled'],
            ':is_core' => $data['is_core'],
            ':updated_at' => date('Y-m-d H:i:s'),
            ':updated_by' => $userId,
            ':id' => $id,
        ]);
    }

    public static function delete(int $id): bool
    {
        $stmt = Connection::prepare('DELETE FROM #__mail_templates WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    public static function existsKey(string $key): bool
    {
        $stmt = Connection::prepare('SELECT id FROM #__mail_templates WHERE template_key = :key LIMIT 1');
        $stmt->execute([':key' => $key]);
        return (bool)$stmt->fetch();
    }
}
