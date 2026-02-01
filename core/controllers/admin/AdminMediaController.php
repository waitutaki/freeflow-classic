<?php
namespace Core\Controllers\Admin;

use Core\Auth;
use Core\Controller;
use Core\Csrf;
use Core\Lang;
use Core\Response;
use Core\Session;
use Core\MediaService;
use Core\Template;
use Core\ACL;

class AdminMediaController extends Controller
{
    public function picker(): Response
    {
        Session::startAdmin();
        Auth::requireAdmin();
        if (!ACL::can('manage_media_admin') && !ACL::can('manage_media_own')) {
            return new Response('Forbidden', 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $userId = (int)Session::get('user_id', 0);
        $isSuper = Auth::isSuper();
        $bucket = $_GET['bucket'] ?? 'users';
        $bucket = $this->sanitizeBucket($bucket, $isSuper);

        $message = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
                $message = Lang::get('FFCMS_CSRF_INVALID');
            } elseif (!empty($_FILES['image'])) {
                $result = MediaService::uploadImage($_FILES['image'], $userId, $bucket);
                if (!$result['ok']) {
                    $message = $result['error'];
                }
            }
        }

        $items = MediaService::listImages($userId, $bucket);
        return $this->render('admin/media_picker', [
            'page_title' => Lang::get('FFCMS_MEDIA_PICKER'),
            'items' => $items,
            'message' => $message,
            'bucket' => $bucket,
            'is_super' => $isSuper,
        ], '');
    }

    public function index(): Response
    {
        Session::startAdmin();
        Auth::requireAdmin();
        $canManageAll = ACL::can('manage_media_admin');
        $canManageOwn = ACL::can('manage_media_own');
        if (!$canManageAll && !$canManageOwn) {
            return new Response('Forbidden', 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $bucket = $_GET['bucket'] ?? 'users';
        $bucket = $this->sanitizeBucket($bucket, $canManageAll);
        $userId = (int)Session::get('user_id', 0);
        $items = MediaService::listImages($userId, $bucket);

        return $this->render('admin/media_manager', [
            'page_title' => Lang::get('FFCMS_MEDIA_MANAGER'),
            'items' => $items,
            'bucket' => $bucket,
            'is_super' => $canManageAll,
        ], 'Zulu');
    }

    public function upload(): Response
    {
        Session::startAdmin();
        Auth::requireAdmin();
        $canManageAll = ACL::can('manage_media_admin');
        $canManageOwn = ACL::can('manage_media_own');
        if (!$canManageAll && !$canManageOwn) {
            return new Response('Forbidden', 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $bucket = $_POST['bucket'] ?? 'users';
        $bucket = $this->sanitizeBucket($bucket, $canManageAll);
        $userId = (int)Session::get('user_id', 0);

        if (!empty($_FILES['image'])) {
            MediaService::uploadImage($_FILES['image'], $userId, $bucket);
        }

        return new Response('', 302, ['Location' => '/admin/media?bucket=' . $bucket]);
    }

    public function delete(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requireAdmin();
        $canManageAll = ACL::can('manage_media_admin');
        $canManageOwn = ACL::can('manage_media_own');
        if (!$canManageAll && !$canManageOwn) {
            return new Response('Forbidden', 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $path = $_GET['path'] ?? '';
        if (!$canManageAll && !$this->isOwnMediaPath($path, (int)Session::get('user_id', 0))) {
            return new Response('Forbidden', 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
                return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
            }
            MediaService::deleteImage($path);
            return new Response('', 302, ['Location' => '/admin/media']);
        }

        return $this->render('admin/media_delete', [
            'page_title' => Lang::get('FFCMS_MEDIA_MANAGER'),
            'path' => $path,
        ], 'Zulu');
    }

    public function rotate(): Response
    {
        Session::startAdmin();
        Auth::requireAdmin();
        $canManageAll = ACL::can('manage_media_admin');
        $canManageOwn = ACL::can('manage_media_own');
        if (!$canManageAll && !$canManageOwn) {
            return new Response('Forbidden', 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $path = $_POST['path'] ?? '';
        if (!$canManageAll && !$this->isOwnMediaPath($path, (int)Session::get('user_id', 0))) {
            return new Response('Forbidden', 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        $bucket = $_POST['bucket'] ?? 'users';
        $bucket = $this->sanitizeBucket($bucket, $canManageAll);
        $userId = (int)Session::get('user_id', 0);
        MediaService::rotateImage($path, $userId, $bucket, 90);

        return new Response('', 302, ['Location' => '/admin/media?bucket=' . $bucket]);
    }

    private function sanitizeBucket(string $bucket, bool $isSuper): string
    {
        $allowed = $isSuper ? ['system', 'users', 'extension', 'themes'] : ['users'];
        return in_array($bucket, $allowed, true) ? $bucket : 'users';
    }

    private function isOwnMediaPath(string $path, int $userId): bool
    {
        $prefix = '/storage/media/users/' . $userId . '/';
        return str_starts_with($path, $prefix);
    }
}
