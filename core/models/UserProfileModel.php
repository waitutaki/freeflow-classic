<?php
namespace Core\Models;

use Core\Connection;

class UserProfileModel
{
    public static function findByUserId(int $userId): ?array
    {
        $stmt = Connection::prepare('SELECT u.id, u.username, u.email, u.name_first, u.name_last, u.role_id, u.status, u.is_enabled, u.created_at, p.avatar_path, p.cover_path, r.title AS role_title FROM #__users u LEFT JOIN #__user_profiles p ON p.user_id = u.id LEFT JOIN #__roles r ON r.id = u.role_id WHERE u.id = :id LIMIT 1');
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
