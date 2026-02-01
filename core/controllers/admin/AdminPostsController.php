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
use Core\Models\PostsModel;
use Core\Models\CategoriesModel;

class AdminPostsController extends Controller
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
        $posts = $canManageAll ? PostsModel::all() : PostsModel::allForUser($userId);
        $canPublish = ACL::can('publish_content');
        $canDelete = $canManageAll || $canManageOwn;

        return $this->render('admin/posts', [
            'page_title' => Lang::get('FFCMS_POSTS'),
            'posts' => $posts,
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
            'category_id' => 0,
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
                    Logger::error('error', 'Post content invalid', ['user_id' => (int)Session::get('user_id', 0)]);
                } else {
                    $values['content_xml'] = $sanitized['xml'];
                }

                if (!$errors) {
                    $publishedAt = null;
                    if ($values['status'] === 'published') {
                        $publishedAt = date('Y-m-d H:i:s');
                    }
                    $values['published_at'] = $publishedAt;
                    $values['author_id'] = (int)Session::get('user_id', 0);
                    $values['modified_by'] = (int)Session::get('user_id', 0);
                    $postId = PostsModel::create($values);
                    Logger::info('admin', 'Post created', ['post_id' => $postId, 'user_id' => $values['author_id']]);
                    if ($values['status'] === 'published') {
                        Logger::info('admin', 'Post published', ['post_id' => $postId, 'user_id' => $values['author_id']]);
                    }
                    if ($values['featured_image_path'] !== '') {
                        Logger::info('admin', 'Post featured image set', ['post_id' => $postId, 'user_id' => $values['author_id']]);
                    }
                    return new Response('', 302, ['Location' => '/admin/content/posts']);
                }
            }
        }

        return $this->render('admin/post_form', [
            'page_title' => Lang::get('FFCMS_POSTS'),
            'values' => $values,
            'errors' => $errors,
            'categories' => CategoriesModel::all(),
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
        $post = PostsModel::find($id);
        if (!$post) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        $userId = (int)Session::get('user_id', 0);
        if (!$canManageAll && (int)($post['author_id'] ?? 0) !== $userId) {
            return new Response('Forbidden', 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $values = [
            'title' => $post['title'] ?? '',
            'slug' => $post['slug'] ?? '',
            'content_xml' => $post['content_xml'] ?? '',
            'excerpt' => $post['excerpt'] ?? '',
            'status' => $post['status'] ?? 'draft',
            'featured_image_path' => $post['featured_image_path'] ?? '',
            'category_id' => (int)($post['category_id'] ?? 0),
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
                    Logger::error('error', 'Post content invalid', ['post_id' => $id, 'user_id' => (int)Session::get('user_id', 0)]);
                } else {
                    $values['content_xml'] = $sanitized['xml'];
                }

                if (!$errors) {
                    $publishedAt = $post['published_at'] ?? null;
                    if ($values['status'] === 'published' && !$publishedAt) {
                        $publishedAt = date('Y-m-d H:i:s');
                    }
                    $values['published_at'] = $publishedAt;
                    $values['modified_by'] = (int)Session::get('user_id', 0);
                    PostsModel::update($id, $values);
                    Logger::info('admin', 'Post updated', ['post_id' => $id, 'user_id' => $values['modified_by']]);
                    if ($oldSlug !== $values['slug']) {
                        Logger::info('admin', 'Post slug changed', ['post_id' => $id, 'user_id' => $values['modified_by']]);
                    }
                    if ($oldStatus !== $values['status']) {
                        Logger::info('admin', 'Post status changed', ['post_id' => $id, 'user_id' => $values['modified_by']]);
                    }
                    if ($oldFeatured !== $values['featured_image_path']) {
                        Logger::info('admin', 'Post featured image changed', ['post_id' => $id, 'user_id' => $values['modified_by']]);
                    }
                    return new Response('', 302, ['Location' => '/admin/content/posts']);
                }
            }
        }

        return $this->render('admin/post_form', [
            'page_title' => Lang::get('FFCMS_POSTS'),
            'values' => $values,
            'errors' => $errors,
            'categories' => CategoriesModel::all(),
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
        $values['category_id'] = (int)($_POST['category_id'] ?? 0);
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
        if (PostsModel::slugExists($values['slug'], $id)) {
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
        $post = PostsModel::find($id);
        if (!$post) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $userId = (int)Session::get('user_id', 0);
        if (!$canManageAll && (int)($post['author_id'] ?? 0) !== $userId) {
            return new Response('Forbidden', 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
                return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
            }
            PostsModel::delete($id);
            Logger::info('admin', 'Post deleted', ['post_id' => $id, 'user_id' => $userId]);
            return new Response('', 302, ['Location' => '/admin/content/posts']);
        }

        return $this->render('admin/post_delete', [
            'page_title' => Lang::get('FFCMS_POSTS'),
            'post' => $post,
        ], 'Zulu');
    }

    public function togglePublish(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requirePermission('publish_content');

        $id = (int)$request->param('id');
        $post = PostsModel::find($id);
        if (!$post) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $canManageAll = ACL::can('manage_content');
        $canManageOwn = ACL::can('manage_content_own');
        $userId = (int)Session::get('user_id', 0);
        if (!$canManageAll && (!$canManageOwn || (int)($post['author_id'] ?? 0) !== $userId)) {
            return new Response('Forbidden', 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $newStatus = ($post['status'] ?? 'draft') === 'published' ? 'draft' : 'published';
        $publishedAt = $newStatus === 'published' ? date('Y-m-d H:i:s') : null;
        PostsModel::updateStatus($id, $newStatus, $publishedAt, $userId);
        Logger::info('admin', 'Post publish toggled', ['post_id' => $id, 'status' => $newStatus, 'user_id' => $userId]);

        return new Response('', 302, ['Location' => '/admin/content/posts']);
    }
}
