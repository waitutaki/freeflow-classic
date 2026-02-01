<?php
namespace Core\Models;

use Core\Connection;

class ElementTypesModel
{
    public static function allEnabled(): array
    {
        $stmt = Connection::prepare('SELECT * FROM #__element_types WHERE is_enabled = 1 ORDER BY name ASC');
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public static function find(int $id): ?array
    {
        $stmt = Connection::prepare('SELECT * FROM #__element_types WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
