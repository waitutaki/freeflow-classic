<?php
namespace Core\Models;

use Core\Connection;

class DevstoreDevelopersModel
{
    public static function all(): array
    {
        $stmt = Connection::prepare('SELECT d.id, d.user_id, COALESCE(d.status, \'pending\') AS status, COALESCE(d.updated_at, u.updated_at) AS updated_at, u.username, u.email FROM #__users u LEFT JOIN #__ext_devstore_developers d ON d.user_id = u.id WHERE u.role_id = 200 ORDER BY u.created_at DESC');
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public static function syncRoleDevelopers(): void
    {
        $stmt = Connection::prepare('INSERT IGNORE INTO #__ext_devstore_developers (user_id, status, created_at, updated_at) SELECT u.id, \'pending\', NOW(), NOW() FROM #__users u WHERE u.role_id = 200');
        $stmt->execute();
    }

    public static function findByUserId(int $userId): ?array
    {
        $stmt = Connection::prepare('SELECT * FROM #__ext_devstore_developers WHERE user_id = :user_id LIMIT 1');
        $stmt->execute([':user_id' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function createRequest(int $userId): void
    {
        $stmt = Connection::prepare('INSERT IGNORE INTO #__ext_devstore_developers (user_id, status, created_at, updated_at) VALUES (:user_id, :status, NOW(), NOW())');
        $stmt->execute([
            ':user_id' => $userId,
            ':status' => 'pending',
        ]);
    }

    public static function setStatus(int $id, string $status, int $actorId): void
    {
        $allowed = ['pending', 'approved', 'suspended', 'rejected'];
        if (!in_array($status, $allowed, true)) {
            return;
        }

        $stmt = Connection::prepare('UPDATE #__ext_devstore_developers SET status = :status, updated_at = NOW(), approved_at = IF(:status = \'approved\', NOW(), approved_at), approved_by = IF(:status = \'approved\', :actor_id, approved_by) WHERE id = :id');
        $stmt->execute([
            ':status' => $status,
            ':actor_id' => $actorId,
            ':id' => $id,
        ]);
    }

    public static function isApprovedUser(int $userId): bool
    {
        $stmt = Connection::prepare('SELECT status FROM #__ext_devstore_developers WHERE user_id = :user_id LIMIT 1');
        $stmt->execute([':user_id' => $userId]);
        $row = $stmt->fetch();
        return $row && $row['status'] === 'approved';
    }
}
