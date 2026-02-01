<?php
namespace Core\Controllers\Admin;

use Core\Auth;
use Core\Controller;
use Core\Csrf;
use Core\Lang;
use Core\Logger;
use Core\Response;
use Core\Session;
use Core\Slug;
use Core\Models\CategoriesModel;

class AdminCategoriesController extends Controller
{
    private array $reserved = [
        'admin',
        'install',
        'page',
        'blog',
        'storage',
        'core',
        'extension',
        'themes',
        'api',
    ];

    public function index(): Response
    {
        Session::startAdmin();
        Auth::requirePermission('manage_content');

        return $this->render('admin/categories', [
            'page_title' => Lang::get('FFCMS_CATEGORIES'),
            'categories' => CategoriesModel::all(),
        ], 'Zulu');
    }

    public function create(): Response
    {
        Session::startAdmin();
        Auth::requirePermission('manage_content');

        $values = [
            'title' => '',
            'slug' => '',
            'parent_id' => 0,
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
                    $values['created_by'] = (int)Session::get('user_id', 0);
                    $values['modified_by'] = (int)Session::get('user_id', 0);
                    $categoryId = CategoriesModel::create($values);
                    Logger::info('admin', 'Category created', ['category_id' => $categoryId, 'user_id' => $values['created_by']]);
                    return new Response('', 302, ['Location' => '/admin/content/categories']);
                }
            }
        }

        return $this->render('admin/category_form', [
            'page_title' => Lang::get('FFCMS_CATEGORIES'),
            'values' => $values,
            'errors' => $errors,
            'categories' => CategoriesModel::listAll(),
            'is_edit' => false,
        ], 'Zulu');
    }

    public function edit(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requirePermission('manage_content');

        $id = (int)$request->param('id');
        $category = CategoriesModel::find($id);
        if (!$category) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $values = [
            'title' => $category['title'] ?? '',
            'slug' => $category['slug'] ?? '',
            'parent_id' => (int)($category['parent_id'] ?? 0),
            'description' => $category['description'] ?? '',
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
                    CategoriesModel::update($id, $values);
                    Logger::info('admin', 'Category updated', ['category_id' => $id, 'user_id' => $values['modified_by']]);
                    return new Response('', 302, ['Location' => '/admin/content/categories']);
                }
            }
        }

        return $this->render('admin/category_form', [
            'page_title' => Lang::get('FFCMS_CATEGORIES'),
            'values' => $values,
            'errors' => $errors,
            'categories' => CategoriesModel::listAll(),
            'is_edit' => true,
        ], 'Zulu');
    }

    public function delete(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requirePermission('manage_content');

        $id = (int)$request->param('id');
        $category = CategoriesModel::find($id);
        if (!$category) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
                return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
            }
            CategoriesModel::delete($id);
            Logger::info('admin', 'Category deleted', ['category_id' => $id, 'user_id' => (int)Session::get('user_id', 0)]);
            return new Response('', 302, ['Location' => '/admin/content/categories']);
        }

        return $this->render('admin/category_delete', [
            'page_title' => Lang::get('FFCMS_CATEGORIES'),
            'category' => $category,
        ], 'Zulu');
    }

    private function collect(array $values): array
    {
        $values['title'] = trim($_POST['title'] ?? '');
        $values['slug'] = strtolower(trim($_POST['slug'] ?? ''));
        if ($values['slug'] === '') {
            $values['slug'] = Slug::generate($values['title']);
        }
        $values['parent_id'] = (int)($_POST['parent_id'] ?? 0);
        $values['description'] = trim($_POST['description'] ?? '');
        return $values;
    }

    private function validate(array $values, int $id): array
    {
        $errors = [];
        if ($values['title'] === '') {
            $errors['title'] = Lang::get('FFCMS_REQUIRED');
        }
        if (!Slug::isValid($values['slug'])) {
            $errors['slug'] = Lang::get('FFCMS_SLUG_INVALID');
        }
        if (in_array($values['slug'], $this->reserved, true)) {
            $errors['slug'] = Lang::get('FFCMS_SLUG_RESERVED');
        }
        if (CategoriesModel::slugExists($values['slug'], $id)) {
            $errors['slug'] = Lang::get('FFCMS_SLUG_TAKEN');
        }
        if ($values['parent_id'] === $id && $id > 0) {
            $errors['parent_id'] = Lang::get('FFCMS_CATEGORY_CYCLE');
        }
        if ($id > 0 && $values['parent_id'] > 0 && CategoriesModel::isDescendant($values['parent_id'], $id)) {
            $errors['parent_id'] = Lang::get('FFCMS_CATEGORY_CYCLE');
        }
        return $errors;
    }
}
