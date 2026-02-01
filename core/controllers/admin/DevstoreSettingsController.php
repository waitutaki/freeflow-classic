<?php
namespace Core\Controllers\Admin;

use Core\Auth;
use Core\Controller;
use Core\Csrf;
use Core\Lang;
use Core\Logger;
use Core\Response;
use Core\Session;
use Core\Settings;
use Core\Models\DevstoreAuditLogModel;

class DevstoreSettingsController extends Controller
{
    public function index(): Response
    {
        Session::startAdmin();
        Auth::requireSuper();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
                return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
            }

            $enabled = !empty($_POST['devstore_enabled']);
            Settings::set('devstore', 'enabled', $enabled ? '1' : '0', 'bool', (int)Session::get('user_id', 0));
            DevstoreAuditLogModel::log('settings_updated', (int)Session::get('user_id', 0), 'Devstore enabled=' . ($enabled ? '1' : '0'));
            Logger::info('admin', 'Devstore settings saved', ['enabled' => $enabled ? 1 : 0]);
            return new Response('', 302, ['Location' => '/admin/devstore/settings']);
        }

        return $this->render('admin/devstore_settings', [
            'page_title' => Lang::get('DEVSTORE.SETTINGS_TITLE'),
            'enabled' => (bool)Settings::get('devstore', 'enabled', true),
        ], 'Zulu');
    }
}
