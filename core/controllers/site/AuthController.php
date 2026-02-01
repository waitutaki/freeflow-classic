<?php
namespace Core\Controllers\Site;

use Core\Auth;
use Core\Controller;
use Core\ElementRenderer;
use Core\Lang;
use Core\Connection;
use Core\Response;
use Core\Session;
use Core\Theme;

class AuthController extends Controller
{
    public function login(): Response
    {
        Session::startSite();

        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!\Core\Csrf::validate($_POST['csrf_token'] ?? null)) {
                $error = Lang::get('FFCMS_CSRF_INVALID');
            } else {
                $check = \Core\AntiSpam::validate('login', $_POST);
                if (!$check['ok']) {
                    $error = $check['error'];
                } else {
                    $identifier = $_POST['identifier'] ?? '';
                    $password = $_POST['password'] ?? '';
                    if (Auth::attempt($identifier, $password, 'site')) {
                        return new Response('', 302, ['Location' => '/']);
                    }
                    $error = $this->loginError($identifier, $password);
                }
            }
        }

        $data = [
            'page_title' => Lang::get('FFCMS_LOGIN'),
            'error' => $error,
        ];

        $themeKey = Theme::activeKey('site');
        $regions = ElementRenderer::renderRegions('site', $themeKey, '/login');
        return $this->render('site/login', $data, $themeKey, $regions);
    }

    private function loginError(string $identifier, string $password): string
    {
        $identifier = trim($identifier);
        if ($identifier === '' || $password === '') {
            return Lang::get('FFCMS_LOGIN_FAILED');
        }
        $stmt = Connection::prepare('SELECT password_hash, is_enabled, status, email_verified_at FROM #__users WHERE username = :id OR email = :id LIMIT 1');
        $stmt->execute([':id' => $identifier]);
        $row = $stmt->fetch();
        if (!$row) {
            return Lang::get('FFCMS_LOGIN_FAILED');
        }
        if (!password_verify($password, $row['password_hash'])) {
            return Lang::get('FFCMS_LOGIN_FAILED');
        }
        if (!(int)$row['is_enabled']) {
            return Lang::get('FFCMS_LOGIN_DISABLED');
        }
        if ((int)$row['status'] !== 1) {
            return Lang::get('FFCMS_LOGIN_INACTIVE');
        }
        return Lang::get('FFCMS_LOGIN_FAILED');
    }

    public function logout(): Response
    {
        Auth::logout('site');
        return new Response('', 302, ['Location' => '/']);
    }
}
