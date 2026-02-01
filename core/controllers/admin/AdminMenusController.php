<?php
namespace Core\Controllers\Admin;

use Core\Auth;
use Core\Controller;
use Core\Csrf;
use Core\Lang;
use Core\Logger;
use Core\Response;
use Core\Session;
use Core\Models\MenusModel;

class AdminMenusController extends Controller
{
    public function index(): Response
    {
        Session::startAdmin();
        Auth::requirePermission('manage_menus');

        return $this->render('admin/menus', [
            'page_title' => Lang::get('FFCMS_MENUS'),
            'menus' => MenusModel::all(),
        ], 'Zulu');
    }

    public function create(): Response
    {
        Session::startAdmin();
        Auth::requirePermission('manage_menus');

        $values = [
            'title' => '',
            'key' => '',
            'description' => '',
        ];
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
                $errors['csrf'] = Lang::get('FFCMS_CSRF_INVALID');
            } else {
                $values = $this->collect($values);
                $errors = $this->validate($values, 0);
                if (!$errors) {
                    $userId = (int)Session::get('user_id', 0);
                    $values['created_by'] = $userId;
                    $values['modified_by'] = $userId;
                    $menuId = MenusModel::create($values);
                    Logger::info('admin', 'Menu created', ['menu_id' => $menuId, 'user_id' => $userId]);
                    return new Response('', 302, ['Location' => '/admin/menus']);
                }
            }
        }

        return $this->render('admin/menu_form', [
            'page_title' => Lang::get('FFCMS_MENUS'),
            'values' => $values,
            'errors' => $errors,
            'is_edit' => false,
        ], 'Zulu');
    }

    public function edit(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requirePermission('manage_menus');

        $id = (int)$request->param('id');
        $menu = MenusModel::find($id);
        if (!$menu) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $values = [
            'title' => $menu['title'] ?? '',
            'key' => $menu['key'] ?? '',
            'description' => $menu['description'] ?? '',
        ];
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
                $errors['csrf'] = Lang::get('FFCMS_CSRF_INVALID');
            } else {
                $values = $this->collect($values);
                $errors = $this->validate($values, $id);
                if (!$errors) {
                    $values['modified_by'] = (int)Session::get('user_id', 0);
                    MenusModel::update($id, $values);
                    Logger::info('admin', 'Menu updated', ['menu_id' => $id, 'user_id' => $values['modified_by']]);
                    return new Response('', 302, ['Location' => '/admin/menus']);
                }
            }
        }

        return $this->render('admin/menu_form', [
            'page_title' => Lang::get('FFCMS_MENUS'),
            'values' => $values,
            'errors' => $errors,
            'is_edit' => true,
        ], 'Zulu');
    }

    public function delete(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requirePermission('manage_menus');

        $id = (int)$request->param('id');
        $menu = MenusModel::find($id);
        if (!$menu) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
                return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
            }
            MenusModel::delete($id);
            Logger::info('admin', 'Menu deleted', ['menu_id' => $id, 'user_id' => (int)Session::get('user_id', 0)]);
            return new Response('', 302, ['Location' => '/admin/menus']);
        }

        return $this->render('admin/menu_delete', [
            'page_title' => Lang::get('FFCMS_MENUS'),
            'menu' => $menu,
        ], 'Zulu');
    }

    private function collect(array $values): array
    {
        $values['title'] = trim($_POST['title'] ?? '');
        $values['key'] = strtolower(trim($_POST['key'] ?? ''));
        $values['description'] = trim($_POST['description'] ?? '');
        return $values;
    }

    private function validate(array $values, int $id): array
    {
        $errors = [];
        if ($values['title'] === '') {
            $errors['title'] = Lang::get('FFCMS_REQUIRED');
        }
        if ($values['key'] === '' || !preg_match('/^[a-z0-9_-]+$/', $values['key'])) {
            $errors['key'] = Lang::get('FFCMS_MENU_KEY_INVALID');
        }
        $existing = MenusModel::findByKey($values['key']);
        if ($existing && (int)$existing['id'] !== $id) {
            $errors['key'] = Lang::get('FFCMS_MENU_KEY_TAKEN');
        }
        return $errors;
    }
}
