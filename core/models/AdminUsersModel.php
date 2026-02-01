<?php
namespace Core\Models;

use Core\Connection;

class AdminUsersModel
{
    public static function all(): array
    {
        $stmt = Connection::prepare('SELECT u.id, u.username, u.email, u.role_id, u.is_enabled, u.status, u.created_at, r.title AS role_title FROM #__users u LEFT JOIN #__roles r ON r.id = u.role_id ORDER BY u.id ASC');
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public static function find(int $id): ?array
    {
        $stmt = Connection::prepare('SELECT * FROM #__users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = Connection::prepare('INSERT INTO #__users (name_first, name_last, username, email, password_hash, role_id, status, is_enabled, created_at, updated_at) VALUES (:name_first, :name_last, :username, :email, :password_hash, :role_id, :status, :is_enabled, :created_at, :updated_at)');
        $stmt->execute([
            ':name_first' => $data['name_first'],
            ':name_last' => $data['name_last'],
            ':username' => $data['username'],
            ':email' => $data['email'],
            ':password_hash' => $data['password_hash'],
            ':role_id' => $data['role_id'],
            ':status' => $data['status'],
            ':is_enabled' => $data['is_enabled'],
            ':created_at' => date('Y-m-d H:i:s'),
            ':updated_at' => date('Y-m-d H:i:s'),
        ]);
        $id = (int)Connection::pdo()->lastInsertId();
        $profileStmt = Connection::prepare('INSERT INTO #__user_profiles (user_id, created_at, updated_at) VALUES (:user_id, :created_at, :updated_at)');
        $profileStmt->execute([
            ':user_id' => $id,
            ':created_at' => date('Y-m-d H:i:s'),
            ':updated_at' => date('Y-m-d H:i:s'),
        ]);
        return $id;
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Connection::prepare('UPDATE #__users SET name_first = :name_first, name_last = :name_last, email = :email, role_id = :role_id, status = :status, is_enabled = :is_enabled, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            ':name_first' => $data['name_first'],
            ':name_last' => $data['name_last'],
            ':email' => $data['email'],
            ':role_id' => $data['role_id'],
            ':status' => $data['status'],
            ':is_enabled' => $data['is_enabled'],
            ':updated_at' => date('Y-m-d H:i:s'),
            ':id' => $id,
        ]);
        if (!empty($data['password_hash'])) {
            $stmt = Connection::prepare('UPDATE #__users SET password_hash = :password_hash WHERE id = :id');
            $stmt->execute([':password_hash' => $data['password_hash'], ':id' => $id]);
        }
    }

    public static function delete(int $id): void
    {
        $stmt = Connection::prepare('DELETE FROM #__users WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $stmt = Connection::prepare('DELETE FROM #__user_profiles WHERE user_id = :id');
        $stmt->execute([':id' => $id]);
        $stmt = Connection::prepare('DELETE FROM #__user_roles WHERE user_id = :id');
        $stmt->execute([':id' => $id]);
    }
}
