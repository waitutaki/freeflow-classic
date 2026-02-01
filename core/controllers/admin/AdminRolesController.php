<?php
namespace Core\Controllers\Admin;

use Core\Auth;
use Core\Controller;
use Core\Csrf;
use Core\Lang;
use Core\Response;
use Core\Session;
use Core\ACL;
use Core\Models\RolesModel;
use Core\Models\PermissionsModel;

class AdminRolesController extends Controller
{
    private array $superOnly = [
        'manage_permissions',
        'manage_updates_core',
        'manage_updates_extension',
        'manage_updates_themes',
        'manage_docs_build',
        'export_database',
        'import_database',
        'manage_extension',
        'install_extension',
        'run_maintenance',
        'use_block_html',
        'manage_themes',
    ];

    public function index(): Response
    {
        Session::startAdmin();
        Auth::requireSuper();

        $tab = $_GET['tab'] ?? 'roles';
        $tab = $tab === 'permissions' ? 'permissions' : 'roles';

        $roles = RolesModel::all();
        $perms = PermissionsModel::all();
        $matrix = [];
        foreach ($roles as $role) {
            $matrix[(int)$role['id']] = PermissionsModel::rolePermissions((int)$role['id']);
        }

        return $this->render('admin/roles', [
            'page_title' => Lang::get('FFCMS_ROLES'),
            'tab' => $tab,
            'roles' => $roles,
            'permissions' => $perms,
            'matrix' => $matrix,
            'super_only' => $this->superOnly,
        ], 'Zulu');
    }

    public function create(): Response
    {
        Session::startAdmin();
        Auth::requireSuper();

        $values = ['title' => '', 'parent_role_id' => 0];
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
                $errors['csrf'] = Lang::get('FFCMS_CSRF_INVALID');
            } else {
                $values['title'] = trim($_POST['title'] ?? '');
                $values['parent_role_id'] = (int)($_POST['parent_role_id'] ?? 0);
                if ($values['title'] === '') {
                    $errors['title'] = Lang::get('FFCMS_REQUIRED');
                }
                if ($values['parent_role_id'] < 0) {
                    $values['parent_role_id'] = 0;
                }
                if (!$errors) {
                    $id = RolesModel::nextCustomId($values['parent_role_id']);
                    RolesModel::create([
                        'id' => $id,
                        'title' => $values['title'],
                        'is_system' => 0,
                        'parent_role_id' => $values['parent_role_id'] ?: null,
                    ]);
                    ACL::clearCache();
                    return new Response('', 302, ['Location' => '/admin/roles']);
                }
            }
        }

        return $this->render('admin/role_form', [
            'page_title' => Lang::get('FFCMS_ROLES'),
            'values' => $values,
            'errors' => $errors,
            'roles' => RolesModel::all(),
            'is_edit' => false,
        ], 'Zulu');
    }

    public function edit(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requireSuper();

        $id = (int)$request->param('id');
        $role = RolesModel::find($id);
        if (!$role) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $values = [
            'title' => $role['title'] ?? '',
            'parent_role_id' => (int)($role['parent_role_id'] ?? 0),
        ];
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
                $errors['csrf'] = Lang::get('FFCMS_CSRF_INVALID');
            } else {
                $values['title'] = trim($_POST['title'] ?? '');
                $values['parent_role_id'] = (int)($_POST['parent_role_id'] ?? 0);
                if ($values['title'] === '') {
                    $errors['title'] = Lang::get('FFCMS_REQUIRED');
                }
                if ((int)($role['is_system'] ?? 0) === 1) {
                    $values['parent_role_id'] = 0;
                }
                if (RolesModel::hasCycle($id, $values['parent_role_id'] ?: null)) {
                    $errors['parent_role_id'] = Lang::get('FFCMS_ROLE_CYCLE');
                }
                if (!$errors) {
                    RolesModel::update($id, [
                        'title' => $values['title'],
                        'parent_role_id' => $values['parent_role_id'] ?: null,
                    ]);
                    ACL::clearCache();
                    return new Response('', 302, ['Location' => '/admin/roles']);
                }
            }
        }

        return $this->render('admin/role_form', [
            'page_title' => Lang::get('FFCMS_ROLES'),
            'values' => $values,
            'errors' => $errors,
            'roles' => RolesModel::all(),
            'is_edit' => true,
            'role' => $role,
        ], 'Zulu');
    }

    public function delete(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requireSuper();

        $id = (int)$request->param('id');
        $role = RolesModel::find($id);
        if (!$role) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        if ((int)$role['is_system'] === 1) {
            return new Response('Forbidden', 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        if (RolesModel::hasChildren($id) || RolesModel::inUse($id)) {
            return new Response('Forbidden', 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
                return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
            }
            RolesModel::delete($id);
            ACL::clearCache();
            return new Response('', 302, ['Location' => '/admin/roles']);
        }

        return $this->render('admin/role_delete', [
            'page_title' => Lang::get('FFCMS_ROLES'),
            'role' => $role,
        ], 'Zulu');
    }

    public function toggle(): Response
    {
        Session::startAdmin();
        Auth::requireSuper();

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            return new Response('ERROR', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $roleId = (int)($_POST['role_id'] ?? 0);
        $permKey = trim($_POST['perm_key'] ?? '');
        $state = (int)($_POST['state'] ?? 0);
        if ($roleId <= 0 || $permKey === '') {
            return new Response('ERROR', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        if (in_array($permKey, $this->superOnly, true) && $roleId !== 998) {
            return new Response('DENY', 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $permId = PermissionsModel::permissionId($permKey);
        if ($permId === null) {
            return new Response('ERROR', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        PermissionsModel::setRolePermission($roleId, $permId, $state === 1);
        ACL::clearCache();
        return new Response('OK', 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }
}
