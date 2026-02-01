<?php
namespace Core\Controllers\Admin;

use Core\Auth;
use Core\Controller;
use Core\Lang;
use Core\Response;
use Core\Session;
use Core\Settings;
use Core\Models\DevstoreAuditLogModel;
use Core\Models\DevstoreDevelopersModel;
use Core\Models\DevstorePackagesModel;

class DevstoreDashboardController extends Controller
{
    public function index(): Response
    {
        Session::startAdmin();
        Auth::requireSuper();
        if (!(bool)Settings::get('devstore', 'enabled', true)) {
            return new Response(Lang::get('DEVSTORE.DISABLED_MESSAGE'), 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $developers = DevstoreDevelopersModel::all();
        $packages = DevstorePackagesModel::all();
        $published = 0;
        foreach ($packages as $package) {
            if (($package['status'] ?? '') === 'published') {
                $published++;
            }
        }

        $data = [
            'page_title' => Lang::get('DEVSTORE.DASHBOARD_TITLE'),
            'developer_count' => count($developers),
            'package_count' => count($packages),
            'audit_log' => DevstoreAuditLogModel::recent(25),
            'published_count' => $published,
        ];

        return $this->render('admin/devstore_dashboard', $data, 'Zulu');
    }
}
