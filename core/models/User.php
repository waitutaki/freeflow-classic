<?php
namespace Core\Models;

use Core\Connection;

class User
{
    public static function usernameExists(string $username): bool
    {
        $stmt = Connection::prepare('SELECT id FROM #__users WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $username]);
        return (bool)$stmt->fetch();
    }

    public static function emailExists(string $email): bool
    {
        $stmt = Connection::prepare('SELECT id FROM #__users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        return (bool)$stmt->fetch();
    }

    public static function createPending(array $data): int
    {
        $stmt = Connection::prepare('INSERT INTO #__users (name_first, name_last, username, email, password_hash, role_id, status, email_verified_at, is_enabled, created_at, updated_at) VALUES (:name_first, :name_last, :username, :email, :password_hash, :role_id, :status, :email_verified_at, :is_enabled, :created_at, :updated_at)');
        $stmt->execute([
            ':name_first' => $data['name_first'],
            ':name_last' => $data['name_last'],
            ':username' => $data['username'],
            ':email' => $data['email'],
            ':password_hash' => $data['password_hash'],
            ':role_id' => $data['role_id'],
            ':status' => $data['status'],
            ':email_verified_at' => $data['email_verified_at'],
            ':is_enabled' => $data['is_enabled'],
            ':created_at' => date('Y-m-d H:i:s'),
            ':updated_at' => date('Y-m-d H:i:s'),
        ]);

        $userId = (int)Connection::pdo()->lastInsertId();
        $profileStmt = Connection::prepare('INSERT INTO #__user_profiles (user_id, created_at, updated_at) VALUES (:user_id, :created_at, :updated_at)');
        $profileStmt->execute([
            ':user_id' => $userId,
            ':created_at' => date('Y-m-d H:i:s'),
            ':updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $userId;
    }

    public static function activate(int $userId): void
    {
        $stmt = Connection::prepare('UPDATE #__users SET status = 1, email_verified_at = :verified_at, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            ':verified_at' => date('Y-m-d H:i:s'),
            ':updated_at' => date('Y-m-d H:i:s'),
            ':id' => $userId,
        ]);
    }
}
