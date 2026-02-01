<?php
namespace Core\Models;

use Core\Connection;

class DevstoreAuditLogModel
{
    public static function log(string $action, ?int $actorId, string $detail): void
    {
        $stmt = Connection::prepare('INSERT INTO #__ext_devstore_audit_log (action, actor_user_id, detail_text, created_at) VALUES (:action, :actor, :detail, NOW())');
        $stmt->execute([
            ':action' => $action,
            ':actor' => $actorId,
            ':detail' => $detail,
        ]);
    }

    public static function recent(int $limit = 50): array
    {
        $limit = max(1, min($limit, 200));
        $stmt = Connection::prepare('SELECT * FROM #__ext_devstore_audit_log ORDER BY id DESC LIMIT ' . $limit);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }
}
