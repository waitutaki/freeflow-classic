<?php
namespace Core;

class Auth
{
    private static ?array $user = null;

    public static function attempt(string $identifier, string $password, string $context): bool
    {
        $identifier = trim($identifier);
        if ($identifier === '' || $password === '') {
            Logger::warning('auth', 'Login failed', ['context' => $context, 'reason' => 'empty']);
            Freegate::logAuthFailure($context, 'empty');
            return false;
        }

        $stmt = Connection::prepare('SELECT id, username, email, password_hash, role_id, is_enabled, status FROM #__users WHERE username = :id OR email = :id LIMIT 1');
        $stmt->execute([':id' => $identifier]);
        $row = $stmt->fetch();
        if (!$row || !(int)$row['is_enabled']) {
            Logger::warning('auth', 'Login failed', ['context' => $context, 'reason' => 'not_found']);
            Freegate::logAuthFailure($context, 'not_found');
            return false;
        }

        if ((int)$row['status'] !== 1) {
            Logger::warning('auth', 'Login failed', ['context' => $context, 'reason' => 'inactive']);
            Freegate::logAuthFailure($context, 'inactive');
            return false;
        }

        if (!password_verify($password, $row['password_hash'])) {
            Logger::warning('auth', 'Login failed', ['context' => $context, 'reason' => 'invalid_password']);
            Freegate::logAuthFailure($context, 'invalid_password');
            return false;
        }

        if ($context === 'admin' && (int)$row['role_id'] < 500) {
            Logger::warning('auth', 'Login failed', ['context' => $context, 'reason' => 'insufficient_role']);
            Freegate::logAuthFailure($context, 'insufficient_role');
            return false;
        }

        self::login((int)$row['id'], (int)$row['role_id'], $context);
        Logger::info('auth', 'Login successful', ['context' => $context, 'user_id' => $row['id']]);
        Freegate::logAuthSuccess((int)$row['id'], $context);
        return true;
    }

    public static function login(int $userId, int $roleId, string $context): void
    {
        if ($context === 'admin') {
            Session::startAdmin();
        } else {
            Session::startSite();
        }

        Session::set('user_id', $userId);
        Session::set('role_id', $roleId);
    }

    public static function logout(string $context): void
    {
        if ($context === 'admin') {
            Session::startAdmin();
        } else {
            Session::startSite();
        }

        $userId = (int)Session::get('user_id', 0);
        Session::destroy();
        Logger::info('auth', 'Logout', ['context' => $context, 'user_id' => $userId]);
    }

    public static function user(): ?array
    {
        if (self::$user !== null) {
            return self::$user;
        }

        $userId = (int)Session::get('user_id', 0);
        if ($userId <= 0) {
            return null;
        }

        $stmt = Connection::prepare('SELECT id, username, email, role_id, name_first, name_last FROM #__users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch();
        self::$user = $row ?: null;

        return self::$user;
    }

    public static function isAuthenticated(): bool
    {
        return Session::get('user_id') !== null;
    }

    public static function isSuper(): bool
    {
        $userId = (int)Session::get('user_id', 0);
        if ($userId <= 0) {
            return false;
        }
        return in_array(998, ACL::userRoles($userId), true);
    }

    public static function requireAdmin(): void
    {
        if (!self::isAuthenticated()) {
            header('Location: /admin/login');
            exit;
        }
    }

    public static function requireSuper(): void
    {
        if (!self::isAuthenticated() || !self::isSuper()) {
            header('Location: /admin/login');
            exit;
        }
    }

    public static function requirePermission(string $permKey): void
    {
        if (!self::isAuthenticated()) {
            header('Location: /admin/login');
            exit;
        }

        $userId = (int)Session::get('user_id', 0);
        if (!ACL::can($permKey, $userId)) {
            header('Location: /admin/login');
            exit;
        }
    }
}
