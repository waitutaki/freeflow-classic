<?php
namespace Core\Controllers\Admin;

use Core\Auth;
use Core\Controller;
use Core\Lang;
use Core\Response;
use Core\Session;

class AuthController extends Controller
{
    public function login(): Response
    {
        Session::startAdmin();

        if (Auth::isAuthenticated()) {
            return new Response('', 302, ['Location' => '/admin/']);
        }

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
                    if (Auth::attempt($identifier, $password, 'admin')) {
                        return new Response('', 302, ['Location' => '/admin/']);
                    }
                    $error = Lang::get('FFCMS_LOGIN_FAILED');
                }
            }
        }

        $data = [
            'page_title' => Lang::get('FFCMS_ADMIN_LOGIN'),
            'error' => $error,
        ];

        return $this->render('admin/login', $data, 'Zulu');
    }

    public function logout(): Response
    {
        Auth::logout('admin');
        return new Response('', 302, ['Location' => '/admin/login']);
    }
}
