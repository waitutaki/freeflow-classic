<?php
namespace Core\Models;

use Core\Connection;

class ElementAssignmentsModel
{
    public static function all(string $context): array
    {
        $stmt = Connection::prepare('SELECT * FROM #__element_assignments WHERE context = :context ORDER BY target_type ASC, target_value ASC, id ASC');
        $stmt->execute([':context' => $context]);
        return $stmt->fetchAll() ?: [];
    }

    public static function find(int $id): ?array
    {
        $stmt = Connection::prepare('SELECT * FROM #__element_assignments WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = Connection::prepare('INSERT INTO #__element_assignments (context, target_type, target_value, is_enabled, created_at, created_by) VALUES (:context, :target_type, :target_value, :is_enabled, :created_at, :created_by)');
        $stmt->execute([
            ':context' => $data['context'],
            ':target_type' => $data['target_type'],
            ':target_value' => $data['target_value'],
            ':is_enabled' => $data['is_enabled'],
            ':created_at' => date('Y-m-d H:i:s'),
            ':created_by' => $data['created_by'],
        ]);
        return (int)Connection::pdo()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Connection::prepare('UPDATE #__element_assignments SET context = :context, target_type = :target_type, target_value = :target_value, is_enabled = :is_enabled WHERE id = :id');
        $stmt->execute([
            ':context' => $data['context'],
            ':target_type' => $data['target_type'],
            ':target_value' => $data['target_value'],
            ':is_enabled' => $data['is_enabled'],
            ':id' => $id,
        ]);
    }

    public static function delete(int $id): void
    {
        $stmt = Connection::prepare('DELETE FROM #__element_assignments WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public static function resolve(string $context, string $path): ?array
    {
        $stmt = Connection::prepare('SELECT * FROM #__element_assignments WHERE context = :context AND is_enabled = 1');
        $stmt->execute([':context' => $context]);
        $assignments = $stmt->fetchAll() ?: [];
        $global = null;
        $routeMatch = null;
        $prefixMatch = null;
        $prefixLen = -1;
        foreach ($assignments as $assignment) {
            $type = $assignment['target_type'] ?? '';
            $value = (string)($assignment['target_value'] ?? '');
            if ($type === 'global') {
                $global = $assignment;
                continue;
            }
            if ($type === 'route' && $value === $path) {
                $routeMatch = $assignment;
                continue;
            }
            if ($type === 'route_prefix' && $value !== '' && str_starts_with($path, $value)) {
                $len = strlen($value);
                if ($len > $prefixLen) {
                    $prefixLen = $len;
                    $prefixMatch = $assignment;
                }
            }
        }
        return $routeMatch ?: $prefixMatch ?: $global;
    }

    public static function hasGlobalAssignment(string $context): bool
    {
        $stmt = Connection::prepare('SELECT COUNT(*) FROM #__element_assignments WHERE context = :context AND target_type = "global"');
        $stmt->execute([':context' => $context]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
