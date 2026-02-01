<?php
namespace Core\Models;

use Core\Connection;

class UpdateChecksModel
{
    public static function all(): array
    {
        $stmt = Connection::prepare('SELECT * FROM #__update_checks ORDER BY component_type, component_key');
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public static function find(string $type, string $key): ?array
    {
        $stmt = Connection::prepare('SELECT * FROM #__update_checks WHERE component_type = :type AND component_key = :key LIMIT 1');
        $stmt->execute([':type' => $type, ':key' => $key]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function upsert(array $data): void
    {
        $stmt = Connection::prepare('INSERT INTO #__update_checks (component_type, component_key, installed_version, available_version, status, checked_at, last_error, raw_xml) VALUES (:component_type, :component_key, :installed_version, :available_version, :status, :checked_at, :last_error, :raw_xml) ON DUPLICATE KEY UPDATE installed_version = VALUES(installed_version), available_version = VALUES(available_version), status = VALUES(status), checked_at = VALUES(checked_at), last_error = VALUES(last_error), raw_xml = VALUES(raw_xml)');
        $stmt->execute([
            ':component_type' => $data['component_type'],
            ':component_key' => $data['component_key'],
            ':installed_version' => $data['installed_version'],
            ':available_version' => $data['available_version'],
            ':status' => $data['status'],
            ':checked_at' => $data['checked_at'],
            ':last_error' => $data['last_error'],
            ':raw_xml' => $data['raw_xml'],
        ]);
    }
}
