<?php
namespace Core\Controllers\Admin;

use Core\Auth;
use Core\Controller;
use Core\Csrf;
use Core\Freewrite;
use Core\Lang;
use Core\Logger;
use Core\Response;
use Core\Session;
use Core\Slug;
use Core\ACL;
use Core\Models\PagesModel;

class AdminPagesController extends Controller
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
        $canManageAll = ACL::can('manage_content');
        $canManageOwn = ACL::can('manage_content_own');
        if (!$canManageAll && !$canManageOwn) {
            Auth::requirePermission('manage_content');
        }

        $userId = (int)Session::get('user_id', 0);
        $pages = $canManageAll ? PagesModel::all() : PagesModel::allForUser($userId);
        $canPublish = ACL::can('publish_content');
        $canDelete = $canManageAll || $canManageOwn;

        return $this->render('admin/pages', [
            'page_title' => Lang::get('FFCMS_PAGES'),
            'pages' => $pages,
            'can_publish' => $canPublish,
            'can_delete' => $canDelete,
            'can_manage_all' => $canManageAll,
            'current_user_id' => $userId,
        ], 'Zulu');
    }

    public function create(): Response
    {
        Session::startAdmin();
        $canManageAll = ACL::can('manage_content');
        $canManageOwn = ACL::can('manage_content_own');
        if (!$canManageAll && !$canManageOwn) {
            Auth::requirePermission('manage_content');
        }

        $values = [
            'title' => '',
            'slug' => '',
            'content_xml' => '',
            'excerpt' => '',
            'status' => 'draft',
            'featured_image_path' => '',
        ];
        $errors = [];
        $canPublish = \Core\ACL::can('publish_content');
        $canHtml = \Core\ACL::can('use_block_html');
        $canEmbed = \Core\ACL::can('use_block_embed');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
                $errors['csrf'] = Lang::get('FFCMS_CSRF_INVALID');
            } else {
                if (defined('APP_DEBUG') && APP_DEBUG === true) {
                    error_log('[FORMFIX] ' . __METHOD__ . ' POST keys=' . implode(',', array_keys($_POST)));
                }
                $values = $this->collect($values);
                $values['status'] = $canPublish ? $values['status'] : 'draft';
                $errors = $this->validate($values, 0);
                $sanitized = Freewrite::sanitizeXml($values['content_xml'], $canHtml, $canEmbed);
                if (!$sanitized['ok']) {
                    $errors['content_xml'] = Lang::get('FFCMS_CONTENT_INVALID');
                    Logger::error('error', 'Page content invalid', ['user_id' => (int)Session::get('user_id', 0)]);
                } else {
                    $values['content_xml'] = $sanitized['xml'];
                }

                if (!$errors) {
                    $publishedAt = null;
                    if ($values['status'] === 'published') {
                        $publishedAt = date('Y-m-d H:i:s');
                    }
                    $values['published_at'] = $publishedAt;
                    $values['created_by'] = (int)Session::get('user_id', 0);
                    $values['modified_by'] = (int)Session::get('user_id', 0);
                    $pageId = PagesModel::create($values);
                    Logger::info('admin', 'Page created', ['page_id' => $pageId, 'user_id' => $values['created_by']]);
                    if ($values['status'] === 'published') {
                        Logger::info('admin', 'Page published', ['page_id' => $pageId, 'user_id' => $values['created_by']]);
                    }
                    if ($values['featured_image_path'] !== '') {
                        Logger::info('admin', 'Page featured image set', ['page_id' => $pageId, 'user_id' => $values['created_by']]);
                    }
                    return new Response('', 302, ['Location' => '/admin/content/pages']);
                }
            }
        }

        return $this->render('admin/page_form', [
            'page_title' => Lang::get('FFCMS_PAGES'),
            'values' => $values,
            'errors' => $errors,
            'is_edit' => false,
            'can_publish' => $canPublish,
            'can_html' => $canHtml,
            'can_embed' => $canEmbed,
        ], 'Zulu');
    }

    public function edit(\Core\Request $request): Response
    {
        Session::startAdmin();
        $canManageAll = ACL::can('manage_content');
        $canManageOwn = ACL::can('manage_content_own');
        if (!$canManageAll && !$canManageOwn) {
            Auth::requirePermission('manage_content');
        }

        $id = (int)$request->param('id');
        $page = PagesModel::find($id);
        if (!$page) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        $userId = (int)Session::get('user_id', 0);
        if (!$canManageAll && (int)($page['created_by'] ?? 0) !== $userId) {
            return new Response('Forbidden', 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $values = [
            'title' => $page['title'] ?? '',
            'slug' => $page['slug'] ?? '',
            'content_xml' => $page['content_xml'] ?? '',
            'excerpt' => $page['excerpt'] ?? '',
            'status' => $page['status'] ?? 'draft',
            'featured_image_path' => $page['featured_image_path'] ?? '',
        ];
        $errors = [];
        $canPublish = \Core\ACL::can('publish_content');
        $canHtml = \Core\ACL::can('use_block_html');
        $canEmbed = \Core\ACL::can('use_block_embed');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
                $errors['csrf'] = Lang::get('FFCMS_CSRF_INVALID');
            } else {
                $oldSlug = $values['slug'];
                $oldStatus = $values['status'];
                $oldFeatured = $values['featured_image_path'];
                $values = $this->collect($values);
                if (!$canPublish) {
                    $values['status'] = $oldStatus;
                }
                $errors = $this->validate($values, $id);
                $sanitized = Freewrite::sanitizeXml($values['content_xml'], $canHtml, $canEmbed);
                if (!$sanitized['ok']) {
                    $errors['content_xml'] = Lang::get('FFCMS_CONTENT_INVALID');
                    Logger::error('error', 'Page content invalid', ['page_id' => $id, 'user_id' => (int)Session::get('user_id', 0)]);
                } else {
                    $values['content_xml'] = $sanitized['xml'];
                }

                if (!$errors) {
                    $publishedAt = $page['published_at'] ?? null;
                    if ($values['status'] === 'published' && !$publishedAt) {
                        $publishedAt = date('Y-m-d H:i:s');
                    }
                    $values['published_at'] = $publishedAt;
                    $values['modified_by'] = (int)Session::get('user_id', 0);
                    PagesModel::update($id, $values);
                    Logger::info('admin', 'Page updated', ['page_id' => $id, 'user_id' => $values['modified_by']]);
                    if ($oldSlug !== $values['slug']) {
                        Logger::info('admin', 'Page slug changed', ['page_id' => $id, 'user_id' => $values['modified_by']]);
                    }
                    if ($oldStatus !== $values['status']) {
                        Logger::info('admin', 'Page status changed', ['page_id' => $id, 'user_id' => $values['modified_by']]);
                    }
                    if ($oldFeatured !== $values['featured_image_path']) {
                        Logger::info('admin', 'Page featured image changed', ['page_id' => $id, 'user_id' => $values['modified_by']]);
                    }
                    return new Response('', 302, ['Location' => '/admin/content/pages']);
                }
            }
        }

        return $this->render('admin/page_form', [
            'page_title' => Lang::get('FFCMS_PAGES'),
            'values' => $values,
            'errors' => $errors,
            'is_edit' => true,
            'can_publish' => $canPublish,
            'can_html' => $canHtml,
            'can_embed' => $canEmbed,
        ], 'Zulu');
    }

    private function collect(array $values): array
    {
        $values['title'] = trim($_POST['title'] ?? '');
        $values['slug'] = strtolower(trim($_POST['slug'] ?? ''));
        if ($values['slug'] === '') {
            $values['slug'] = Slug::generate($values['title']);
        }
        $values['content_xml'] = trim($_POST['content_xml'] ?? '');
        $values['excerpt'] = trim($_POST['excerpt'] ?? '');
        $values['status'] = trim($_POST['status'] ?? $values['status']);
        $values['featured_image_path'] = trim($_POST['featured_image_path'] ?? '');
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
        if (PagesModel::slugExists($values['slug'], $id)) {
            $errors['slug'] = Lang::get('FFCMS_SLUG_TAKEN');
        }
        if ($values['content_xml'] === '') {
            $errors['content_xml'] = Lang::get('FFCMS_REQUIRED');
        }
        return $errors;
    }

    public function delete(\Core\Request $request): Response
    {
        Session::startAdmin();
        $canManageAll = ACL::can('manage_content');
        $canManageOwn = ACL::can('manage_content_own');
        if (!$canManageAll && !$canManageOwn) {
            Auth::requirePermission('manage_content');
        }

        $id = (int)$request->param('id');
        $page = PagesModel::find($id);
        if (!$page) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $userId = (int)Session::get('user_id', 0);
        if (!$canManageAll && (int)($page['created_by'] ?? 0) !== $userId) {
            return new Response('Forbidden', 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
                return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
            }
            PagesModel::delete($id);
            Logger::info('admin', 'Page deleted', ['page_id' => $id, 'user_id' => $userId]);
            return new Response('', 302, ['Location' => '/admin/content/pages']);
        }

        return $this->render('admin/page_delete', [
            'page_title' => Lang::get('FFCMS_PAGES'),
            'page' => $page,
        ], 'Zulu');
    }

    public function togglePublish(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requirePermission('publish_content');

        $id = (int)$request->param('id');
        $page = PagesModel::find($id);
        if (!$page) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $canManageAll = ACL::can('manage_content');
        $canManageOwn = ACL::can('manage_content_own');
        $userId = (int)Session::get('user_id', 0);
        if (!$canManageAll && (!$canManageOwn || (int)($page['created_by'] ?? 0) !== $userId)) {
            return new Response('Forbidden', 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $newStatus = ($page['status'] ?? 'draft') === 'published' ? 'draft' : 'published';
        $publishedAt = $newStatus === 'published' ? date('Y-m-d H:i:s') : null;
        PagesModel::updateStatus($id, $newStatus, $publishedAt, $userId);
        Logger::info('admin', 'Page publish toggled', ['page_id' => $id, 'status' => $newStatus, 'user_id' => $userId]);

        return new Response('', 302, ['Location' => '/admin/content/pages']);
    }
}
