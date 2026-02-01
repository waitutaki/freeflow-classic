<?php
namespace Core\Controllers\Admin;

use Core\Auth;
use Core\Controller;
use Core\Csrf;
use Core\Lang;
use Core\Response;
use Core\Session;
use Core\Connection;
use Core\Models\AdminUsersModel;
use Core\Models\RolesModel;
use Core\Models\User;

class AdminUsersController extends Controller
{
    private array $reserved = ['admin', 'administrator', 'webmaster', 'superadmin'];

    public function index(): Response
    {
        Session::startAdmin();
        Auth::requirePermission('manage_users');

        return $this->render('admin/users', [
            'page_title' => Lang::get('FFCMS_USERS'),
            'users' => AdminUsersModel::all(),
        ], 'Zulu');
    }

    public function create(): Response
    {
        Session::startAdmin();
        Auth::requirePermission('manage_users');

        $values = [
            'name_first' => '',
            'name_last' => '',
            'username' => '',
            'email' => '',
            'role_id' => 100,
            'status' => 1,
            'is_enabled' => 1,
        ];
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
                $errors['csrf'] = Lang::get('FFCMS_CSRF_INVALID');
            } else {
                if (defined('APP_DEBUG') && APP_DEBUG === true) {
                    error_log('[FORMFIX] ' . __METHOD__ . ' POST keys=' . implode(',', array_keys($_POST)));
                }
                $values = $this->collect($values);
                if ($values['username'] === '') {
                    $values['username'] = $this->generateUsername($values['name_first'], $values['name_last'], $values['email'], 0);
                }
                $errors = $this->validate($values, true, 0);
                if (!$errors) {
                    $values['password_hash'] = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);
                    AdminUsersModel::create($values);
                    return new Response('', 302, ['Location' => '/admin/users']);
                }
            }
        }

        return $this->render('admin/user_form', [
            'page_title' => Lang::get('FFCMS_USERS'),
            'values' => $values,
            'errors' => $errors,
            'roles' => RolesModel::all(),
            'is_edit' => false,
        ], 'Zulu');
    }

    public function edit(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requirePermission('manage_users');

        $id = (int)$request->param('id');
        $user = AdminUsersModel::find($id);
        if (!$user) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $values = [
            'name_first' => $user['name_first'] ?? '',
            'name_last' => $user['name_last'] ?? '',
            'username' => $user['username'] ?? '',
            'email' => $user['email'] ?? '',
            'role_id' => (int)($user['role_id'] ?? 100),
            'status' => (int)($user['status'] ?? 1),
            'is_enabled' => (int)($user['is_enabled'] ?? 1),
        ];
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
                $errors['csrf'] = Lang::get('FFCMS_CSRF_INVALID');
            } else {
                $values = $this->collect($values);
                if ($values['username'] === '') {
                    $values['username'] = $this->generateUsername($values['name_first'], $values['name_last'], $values['email'], $id);
                }
                $errors = $this->validate($values, false, $id);
                if (!$errors) {
                    $password = $_POST['password'] ?? '';
                    $values['password_hash'] = $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : '';

                    AdminUsersModel::update($id, $values);
                    return new Response('', 302, ['Location' => '/admin/users']);
                }
            }
        }

        return $this->render('admin/user_form', [
            'page_title' => Lang::get('FFCMS_USERS'),
            'values' => $values,
            'errors' => $errors,
            'roles' => RolesModel::all(),
            'is_edit' => true,
        ], 'Zulu');
    }

    public function delete(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requirePermission('manage_users');

        $id = (int)$request->param('id');
        $user = AdminUsersModel::find($id);
        if (!$user) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
                return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
            }
            AdminUsersModel::delete($id);
            return new Response('', 302, ['Location' => '/admin/users']);
        }

        return $this->render('admin/user_delete', [
            'page_title' => Lang::get('FFCMS_USERS'),
            'user' => $user,
        ], 'Zulu');
    }

    private function collect(array $values): array
    {
        $values['name_first'] = trim($_POST['name_first'] ?? '');
        $values['name_last'] = trim($_POST['name_last'] ?? '');
        $values['username'] = strtolower(trim($_POST['username'] ?? $values['username']));
        $values['email'] = strtolower(trim($_POST['email'] ?? ''));
        $values['role_id'] = (int)($_POST['role_id'] ?? 100);
        $values['status'] = (int)($_POST['status'] ?? 1);
        $values['is_enabled'] = isset($_POST['is_enabled']) ? 1 : 0;
        return $values;
    }

    private function validate(array $values, bool $isCreate, int $userId): array
    {
        $errors = [];
        if ($values['name_first'] === '') {
            $errors['name_first'] = Lang::get('FFCMS_REQUIRED');
        }
        if ($values['name_last'] === '') {
            $errors['name_last'] = Lang::get('FFCMS_REQUIRED');
        }
        if ($values['username'] === '' || !$this->isValidUsername($values['username'])) {
            $errors['username'] = Lang::get('FFCMS_USERNAME_INVALID');
        } elseif ($this->usernameExists($values['username'], $userId)) {
            $errors['username'] = Lang::get('FFCMS_USERNAME_TAKEN');
        }
        if ($values['email'] === '' || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = Lang::get('FFCMS_INVALID_EMAIL');
        }
        if ($isCreate) {
            $password = $_POST['password'] ?? '';
            if ($password === '') {
                $errors['password'] = Lang::get('FFCMS_PASSWORD_INVALID');
            }
        }
        return $errors;
    }

    private function isValidUsername(string $username): bool
    {
        if (strlen($username) < 8) {
            return false;
        }
        if (!preg_match('/^[a-z][a-z0-9_-]*$/', $username)) {
            return false;
        }
        $normalized = str_replace(['_', '-'], '', $username);
        foreach ($this->reserved as $term) {
            if (str_contains($normalized, $term)) {
                return false;
            }
        }
        return true;
    }

    private function generateUsername(string $first, string $last, string $email, int $excludeId): string
    {
        $base = strtolower($first . $last);
        $base = preg_replace('/[^a-z0-9]/', '', $base);
        if ($base === '' || !preg_match('/^[a-z]/', $base)) {
            $base = 'u' . $base;
        }

        $normalized = str_replace(['_', '-'], '', $base);
        foreach ($this->reserved as $term) {
            if (str_contains($normalized, $term)) {
                $base = 'user';
                break;
            }
        }

        $emailLocal = strtolower((string)strstr($email, '@', true));
        $suffix = substr(sha1($first . $last . $emailLocal), 0, 4);
        $username = $base . '-' . $suffix;

        if (strlen($username) < 8) {
            $username .= substr(sha1($username), 0, 8 - strlen($username));
        }

        if (!$this->isValidUsername($username)) {
            $username = 'user-' . $suffix;
        }

        return $this->ensureUniqueUsername($username, $excludeId);
    }

    private function ensureUniqueUsername(string $username, int $excludeId): string
    {
        if (!$this->usernameExists($username, $excludeId)) {
            return $username;
        }
        $counter = 2;
        $candidate = $username . '-' . $counter;
        while ($this->usernameExists($candidate, $excludeId)) {
            $counter++;
            $candidate = $username . '-' . $counter;
        }
        return $candidate;
    }

    private function usernameExists(string $username, int $excludeId): bool
    {
        $stmt = Connection::prepare('SELECT id FROM #__users WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $username]);
        $row = $stmt->fetch();
        if (!$row) {
            return false;
        }
        $id = (int)($row['id'] ?? 0);
        if ($excludeId > 0 && $id === $excludeId) {
            return false;
        }
        return true;
    }
}
