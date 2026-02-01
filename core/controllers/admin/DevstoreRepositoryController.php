<?php
namespace Core\Controllers\Admin;

use Core\Auth;
use Core\Controller;
use Core\Lang;
use Core\Response;
use Core\Session;
use Core\Settings;
use Core\Models\DevstorePackagesModel;

class DevstoreRepositoryController extends Controller
{
    public function index(): Response
    {
        Session::startAdmin();
        Auth::requireSuper();
        if (!(bool)Settings::get('devstore', 'enabled', true)) {
            return new Response(Lang::get('DEVSTORE.DISABLED_MESSAGE'), 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $packages = DevstorePackagesModel::allPublished();

        return $this->render('admin/devstore_repository', [
            'page_title' => Lang::get('DEVSTORE.REPOSITORY_TITLE'),
            'packages' => $packages,
        ], 'Zulu');
    }
}
