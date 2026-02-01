<?php
namespace Core\Models;

use Core\Connection;

class ElementsModel
{
    public static function allByAssignment(int $assignmentId): array
    {
        $stmt = Connection::prepare('SELECT e.*, t.type_key, t.name AS type_name, p.position AS position_name FROM #__elements e LEFT JOIN #__element_types t ON t.id = e.element_type_id LEFT JOIN #__element_positions p ON p.id = e.template_position_id WHERE e.assignment_id = :assignment_id ORDER BY e.template_position_id ASC, e.ordering ASC, e.id ASC');
        $stmt->execute([':assignment_id' => $assignmentId]);
        return $stmt->fetchAll() ?: [];
    }

    public static function find(int $id): ?array
    {
        $stmt = Connection::prepare('SELECT * FROM #__elements WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = Connection::prepare('INSERT INTO #__elements (element_type_id, title, is_published, ordering, template_position_id, assignment_id, config_xml, created_at, created_by, modified_at, modified_by) VALUES (:element_type_id, :title, :is_published, :ordering, :template_position_id, :assignment_id, :config_xml, :created_at, :created_by, :modified_at, :modified_by)');
        $stmt->execute([
            ':element_type_id' => $data['element_type_id'],
            ':title' => $data['title'],
            ':is_published' => $data['is_published'],
            ':ordering' => $data['ordering'],
            ':template_position_id' => $data['template_position_id'],
            ':assignment_id' => $data['assignment_id'],
            ':config_xml' => $data['config_xml'],
            ':created_at' => date('Y-m-d H:i:s'),
            ':created_by' => $data['created_by'],
            ':modified_at' => date('Y-m-d H:i:s'),
            ':modified_by' => $data['modified_by'],
        ]);
        return (int)Connection::pdo()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Connection::prepare('UPDATE #__elements SET element_type_id = :element_type_id, title = :title, is_published = :is_published, ordering = :ordering, template_position_id = :template_position_id, assignment_id = :assignment_id, config_xml = :config_xml, modified_at = :modified_at, modified_by = :modified_by WHERE id = :id');
        $stmt->execute([
            ':element_type_id' => $data['element_type_id'],
            ':title' => $data['title'],
            ':is_published' => $data['is_published'],
            ':ordering' => $data['ordering'],
            ':template_position_id' => $data['template_position_id'],
            ':assignment_id' => $data['assignment_id'],
            ':config_xml' => $data['config_xml'],
            ':modified_at' => date('Y-m-d H:i:s'),
            ':modified_by' => $data['modified_by'],
            ':id' => $id,
        ]);
    }

    public static function delete(int $id): void
    {
        $stmt = Connection::prepare('DELETE FROM #__elements WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public static function setPublished(int $id, int $isPublished): void
    {
        $stmt = Connection::prepare('UPDATE #__elements SET is_published = :state WHERE id = :id');
        $stmt->execute([':state' => $isPublished, ':id' => $id]);
    }

    public static function updateOrdering(array $items): void
    {
        $stmt = Connection::prepare('UPDATE #__elements SET ordering = :ordering, template_position_id = :template_position_id WHERE id = :id');
        foreach ($items as $item) {
            $stmt->execute([
                ':ordering' => $item['ordering'],
                ':template_position_id' => $item['template_position_id'],
                ':id' => $item['id'],
            ]);
        }
    }
}
