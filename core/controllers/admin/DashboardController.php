<?php
namespace Core\Controllers\Admin;

use Core\Controller;
use Core\Lang;

class DashboardController extends Controller
{
    public function index(): \Core\Response
    {
        $data = [
            'page_title' => Lang::get('FFCMS_DASHBOARD'),
        ];

        return $this->render('admin/dashboard', $data, 'Zulu');
    }
}
