<?php
namespace Core\Models;

use Core\Connection;

class DevstoreComplianceReportsModel
{
    public static function create(int $packageId, bool $passed, string $report): void
    {
        $stmt = Connection::prepare('INSERT INTO #__ext_devstore_compliance_reports (package_id, is_passed, report_text, created_at) VALUES (:package_id, :is_passed, :report_text, NOW())');
        $stmt->execute([
            ':package_id' => $packageId,
            ':is_passed' => $passed ? 1 : 0,
            ':report_text' => $report,
        ]);
    }

    public static function latestForPackage(int $packageId): ?array
    {
        $stmt = Connection::prepare('SELECT * FROM #__ext_devstore_compliance_reports WHERE package_id = :package_id ORDER BY id DESC LIMIT 1');
        $stmt->execute([':package_id' => $packageId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function deleteForPackage(int $packageId): void
    {
        $stmt = Connection::prepare('DELETE FROM #__ext_devstore_compliance_reports WHERE package_id = :package_id');
        $stmt->execute([':package_id' => $packageId]);
    }
}
