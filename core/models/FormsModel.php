<?php
namespace Core\Models;

use Core\Connection;

class FormsModel
{
    public static function all(): array
    {
        $stmt = Connection::prepare('SELECT f.*, u.username AS created_by_name, m.username AS modified_by_name FROM #__forms f LEFT JOIN #__users u ON u.id = f.created_by LEFT JOIN #__users m ON m.id = f.modified_by ORDER BY f.modified_at DESC, f.id DESC');
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public static function find(int $id): ?array
    {
        $stmt = Connection::prepare('SELECT * FROM #__forms WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $stmt = Connection::prepare('SELECT * FROM #__forms WHERE slug = :slug LIMIT 1');
        $stmt->execute([':slug' => $slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function slugExists(string $slug, int $excludeId = 0): bool
    {
        $sql = 'SELECT id FROM #__forms WHERE slug = :slug';
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
        $stmt = Connection::prepare('INSERT INTO #__forms (title, slug, form_xml, created_by, created_at, modified_by, modified_at) VALUES (:title, :slug, :form_xml, :created_by, :created_at, :modified_by, :modified_at)');
        $stmt->execute([
            ':title' => $data['title'],
            ':slug' => $data['slug'],
            ':form_xml' => $data['form_xml'],
            ':created_by' => $data['created_by'],
            ':created_at' => date('Y-m-d H:i:s'),
            ':modified_by' => $data['modified_by'],
            ':modified_at' => date('Y-m-d H:i:s'),
        ]);
        return (int)Connection::pdo()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Connection::prepare('UPDATE #__forms SET title = :title, slug = :slug, form_xml = :form_xml, modified_by = :modified_by, modified_at = :modified_at WHERE id = :id');
        $stmt->execute([
            ':title' => $data['title'],
            ':slug' => $data['slug'],
            ':form_xml' => $data['form_xml'],
            ':modified_by' => $data['modified_by'],
            ':modified_at' => date('Y-m-d H:i:s'),
            ':id' => $id,
        ]);
    }

    public static function delete(int $id): void
    {
        $stmt = Connection::prepare('DELETE FROM #__forms WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public static function createSubmission(int $formId, string $submissionXml, string $ip, ?string $userAgent): int
    {
        $stmt = Connection::prepare('INSERT INTO #__form_submissions (form_id, submission_xml, ip_address, user_agent, submitted_at) VALUES (:form_id, :submission_xml, :ip_address, :user_agent, :submitted_at)');
        $stmt->execute([
            ':form_id' => $formId,
            ':submission_xml' => $submissionXml,
            ':ip_address' => $ip,
            ':user_agent' => $userAgent,
            ':submitted_at' => date('Y-m-d H:i:s'),
        ]);
        return (int)Connection::pdo()->lastInsertId();
    }

    public static function getSubmissionsForForm(int $formId): array
    {
        $stmt = Connection::prepare('SELECT * FROM #__form_submissions WHERE form_id = :form_id ORDER BY submitted_at DESC');
        $stmt->execute([':form_id' => $formId]);
        return $stmt->fetchAll() ?: [];
    }
}