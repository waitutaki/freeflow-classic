<?php
namespace Core\Controllers\Site;

use Core\Controller;
use Core\ElementRenderer;
use Core\Lang;
use Core\MailService;
use Core\Response;
use Core\TokenService;
use Core\Theme;
use Core\Models\User;

class RegisterController extends Controller
{
    private array $reserved = ['admin', 'administrator', 'webmaster', 'superadmin'];

    public function register(): Response
    {
        $values = [
            'name_first' => '',
            'name_last' => '',
            'email' => '',
            'username' => '',
        ];
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!\Core\Csrf::validate($_POST['csrf_token'] ?? null)) {
                $errors['csrf'] = Lang::get('FFCMS_CSRF_INVALID');
            }
            $check = \Core\AntiSpam::validate('registration', $_POST);
            if (!$check['ok']) {
                $errors['spam'] = $check['error'];
            }
            $values['name_first'] = trim($_POST['name_first'] ?? '');
            $values['name_last'] = trim($_POST['name_last'] ?? '');
            $values['email'] = strtolower(trim($_POST['email'] ?? ''));
            $values['username'] = strtolower(trim($_POST['username'] ?? ''));
            $password = (string)($_POST['password'] ?? '');
            $passwordConfirm = (string)($_POST['password_confirm'] ?? '');

            if ($values['name_first'] === '') {
                $errors['name_first'] = Lang::get('FFCMS_REQUIRED');
            }
            if ($values['name_last'] === '') {
                $errors['name_last'] = Lang::get('FFCMS_REQUIRED');
            }
            if ($values['email'] === '' || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = Lang::get('FFCMS_INVALID_EMAIL');
            }

            if ($values['email'] !== '' && User::emailExists($values['email'])) {
                $errors['email'] = Lang::get('FFCMS_EMAIL_TAKEN');
            }

            $username = $values['username'];
            if ($username !== '') {
                if (!$this->isValidUsername($username)) {
                    $errors['username'] = Lang::get('FFCMS_USERNAME_INVALID');
                } elseif (User::usernameExists($username)) {
                    $errors['username'] = Lang::get('FFCMS_USERNAME_TAKEN');
                }
            } else {
                $username = $this->generateUsername($values['name_first'], $values['name_last'], $values['email']);
                if (User::usernameExists($username)) {
                    $username = $this->ensureUniqueUsername($username);
                }
            }

            if ($password === '' || !$this->isValidPassword($password, $username, $values['email'])) {
                $errors['password'] = Lang::get('FFCMS_PASSWORD_INVALID');
            }
            if ($passwordConfirm === '' || $passwordConfirm !== $password) {
                $errors['password_confirm'] = Lang::get('FFCMS_PASSWORD_CONFIRM_INVALID');
            }

            if (!$errors) {
                $userId = User::createPending([
                    'name_first' => $values['name_first'],
                    'name_last' => $values['name_last'],
                    'username' => $username,
                    'email' => $values['email'],
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'role_id' => 1,
                    'status' => 2,
                    'email_verified_at' => null,
                    'is_enabled' => 1,
                ]);

                $token = TokenService::create('register_verify', $userId, $values['email'], 24);
                $verifyLink = '/verify-email?token=' . urlencode($token);

                MailService::sendTemplate($values['email'], 'register_verify', [
                    'name_first' => $values['name_first'],
                    'name_last' => $values['name_last'],
                    'username' => $username,
                    'verify_link' => $verifyLink,
                ]);

                $themeKey = Theme::activeKey('site');
                $regions = ElementRenderer::renderRegions('site', $themeKey, '/register');
                return $this->render('site/register_success', [
                    'page_title' => Lang::get('FFCMS_REGISTER'),
                ], $themeKey, $regions);
            }

            $values['username'] = $username;
        }

        $data = [
            'page_title' => Lang::get('FFCMS_REGISTER'),
            'values' => $values,
            'errors' => $errors,
        ];

        $themeKey = Theme::activeKey('site');
        $regions = ElementRenderer::renderRegions('site', $themeKey, '/register');
        return $this->render('site/register', $data, $themeKey, $regions);
    }

    public function verifyEmail(): Response
    {
        $token = trim($_GET['token'] ?? '');
        $row = TokenService::validate($token, 'register_verify');
        if (!$row) {
            $themeKey = Theme::activeKey('site');
            $regions = ElementRenderer::renderRegions('site', $themeKey, '/verify-email');
            return $this->render('site/verify_failed', [
                'page_title' => Lang::get('FFCMS_VERIFY_EMAIL'),
            ], $themeKey, $regions);
        }

        User::activate((int)$row['user_id']);
        TokenService::consume((int)$row['id']);

        $userId = (int)$row['user_id'];
        $stmt = \Core\Connection::prepare('SELECT username, email FROM #__users WHERE id = :id');
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();

        if ($user) {
            MailService::sendTemplate((string)$user['email'], 'welcome', [
                'username' => $user['username'],
                'login_link' => '/login',
            ]);
        }

        $themeKey = Theme::activeKey('site');
        $regions = ElementRenderer::renderRegions('site', $themeKey, '/verify-email');
        return $this->render('site/verify_success', [
            'page_title' => Lang::get('FFCMS_VERIFY_EMAIL'),
        ], $themeKey, $regions);
    }

    public function checkUsername(): Response
    {
        $username = strtolower(trim($_GET['u'] ?? ''));
        if ($username === '' || !$this->isValidUsername($username)) {
            return new Response('INVALID', 200, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        if (User::usernameExists($username)) {
            return new Response('TAKEN', 200, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        return new Response('AVAILABLE', 200, ['Content-Type' => 'text/plain; charset=utf-8']);
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

    private function generateUsername(string $first, string $last, string $email): string
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

        return $this->ensureUniqueUsername($username);
    }

    private function ensureUniqueUsername(string $username): string
    {
        if (!User::usernameExists($username)) {
            return $username;
        }
        $counter = 2;
        $candidate = $username . '-' . $counter;
        while (User::usernameExists($candidate)) {
            $counter++;
            $candidate = $username . '-' . $counter;
        }
        return $candidate;
    }

    private function isValidPassword(string $password, string $username, string $email): bool
    {
        if (strlen($password) < 8) {
            return false;
        }
        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }
        if (!preg_match('/[0-9]/', $password)) {
            return false;
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            return false;
        }

        $emailLocal = strtolower((string)strstr($email, '@', true));
        $lower = strtolower($password);
        if ($username !== '' && str_contains($lower, $username)) {
            return false;
        }
        if ($emailLocal !== '' && str_contains($lower, $emailLocal)) {
            return false;
        }
        foreach ($this->reserved as $term) {
            if (str_contains($lower, $term)) {
                return false;
            }
        }
        return true;
    }
}
