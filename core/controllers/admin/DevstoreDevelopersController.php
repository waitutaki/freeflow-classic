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
use Core\Models\DevstoreDevelopersModel;
use Core\Models\DevstoreAuditLogModel;

class DevstoreDevelopersController extends Controller
{
    public function index(): Response
    {
        Session::startAdmin();
        Auth::requireSuper();
        if (!(bool)Settings::get('devstore', 'enabled', true)) {
            return new Response(Lang::get('DEVSTORE.DISABLED_MESSAGE'), 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        DevstoreDevelopersModel::syncRoleDevelopers();

        return $this->render('admin/devstore_developers', [
            'page_title' => Lang::get('DEVSTORE.DEVELOPERS_TITLE'),
            'developers' => DevstoreDevelopersModel::all(),
        ], 'Zulu');
    }

    public function approve(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requireSuper();
        if (!(bool)Settings::get('devstore', 'enabled', true)) {
            return new Response(Lang::get('DEVSTORE.DISABLED_MESSAGE'), 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $id = (int)$request->param('id');
        DevstoreDevelopersModel::setStatus($id, 'approved', (int)Session::get('user_id', 0));
        DevstoreAuditLogModel::log('developer_approved', (int)Session::get('user_id', 0), 'Developer approved: ' . $id);
        Logger::info('admin', 'Devstore developer approved', ['developer_id' => $id]);

        return new Response('', 302, ['Location' => '/admin/devstore/developers']);
    }

    public function suspend(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requireSuper();
        if (!(bool)Settings::get('devstore', 'enabled', true)) {
            return new Response(Lang::get('DEVSTORE.DISABLED_MESSAGE'), 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $id = (int)$request->param('id');
        DevstoreDevelopersModel::setStatus($id, 'suspended', (int)Session::get('user_id', 0));
        DevstoreAuditLogModel::log('developer_suspended', (int)Session::get('user_id', 0), 'Developer suspended: ' . $id);
        Logger::info('admin', 'Devstore developer suspended', ['developer_id' => $id]);

        return new Response('', 302, ['Location' => '/admin/devstore/developers']);
    }

    public function reject(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requireSuper();
        if (!(bool)Settings::get('devstore', 'enabled', true)) {
            return new Response(Lang::get('DEVSTORE.DISABLED_MESSAGE'), 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $id = (int)$request->param('id');
        DevstoreDevelopersModel::setStatus($id, 'rejected', (int)Session::get('user_id', 0));
        DevstoreAuditLogModel::log('developer_rejected', (int)Session::get('user_id', 0), 'Developer rejected: ' . $id);
        Logger::info('admin', 'Devstore developer rejected', ['developer_id' => $id]);

        return new Response('', 302, ['Location' => '/admin/devstore/developers']);
    }
}
