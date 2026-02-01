<?php
namespace Core\Controllers\Admin;

use Core\Auth;
use Core\Controller;
use Core\Csrf;
use Core\DocumentService;
use Core\Lang;
use Core\Response;
use Core\Session;
use Core\ACL;
use Core\Logger;
use Core\Models\DocumentsModel;

class AdminDocumentsController extends Controller
{
    public function index(): Response
    {
        Session::startAdmin();
        $access = $this->checkListAccess();
        if ($access instanceof Response) {
            return $access;
        }

        $userId = (int)Session::get('user_id', 0);
        $documents = ACL::can('manage_documents') ? DocumentsModel::all() : DocumentsModel::forUser($userId);

        return $this->render('admin/documents', [
            'page_title' => Lang::get('FFCMS_DOCUMENTS'),
            'documents' => $documents,
        ], 'Zulu');
    }

    public function upload(): Response
    {
        Session::startAdmin();
        $access = $this->checkUploadAccess();
        if ($access instanceof Response) {
            return $access;
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $userId = (int)Session::get('user_id', 0);
        if (!empty($_FILES['document'])) {
            $result = DocumentService::upload($_FILES['document'], $userId);
            if ($result['ok']) {
                $result['owner_user_id'] = $userId;
                $result['uploaded_by'] = $userId;
                $docId = DocumentsModel::create($result);
                Logger::info('admin', 'Document uploaded', ['doc_id' => $docId, 'user_id' => $userId]);
            } else {
                Logger::error('error', 'Document upload failed', ['user_id' => $userId, 'reason' => $result['error'] ?? 'unknown']);
            }
        }

        return new Response('', 302, ['Location' => '/admin/documents']);
    }

    public function edit(\Core\Request $request): Response
    {
        Session::startAdmin();
        $id = (int)$request->param('id');
        $doc = DocumentsModel::find($id);
        if (!$doc) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        $access = $this->checkDocAccess($doc, 'manage_documents', 'rename_documents_own');
        if ($access instanceof Response) {
            return $access;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
                return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
            }
            $display = trim($_POST['display_name'] ?? '');
            $stored = trim($_POST['stored_name'] ?? $doc['stored_name']);
            $owner = (int)($doc['owner_user_id'] ?? 0);
            if (ACL::can('manage_documents')) {
                $owner = (int)($_POST['owner_user_id'] ?? $owner);
            }

            $renameResult = DocumentService::rename($doc, $stored);
            if (!$renameResult['ok']) {
                Logger::error('error', 'Document rename failed', ['doc_id' => $id, 'user_id' => (int)Session::get('user_id', 0)]);
                return new Response('Invalid file name', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
            }

            DocumentsModel::update($id, [
                'display_name' => $display !== '' ? $display : $doc['display_name'],
                'stored_name' => $renameResult['stored_name'],
                'storage_path' => $renameResult['storage_path'],
                'owner_user_id' => $owner,
                'modified_by' => (int)Session::get('user_id', 0),
            ]);
            Logger::info('admin', 'Document updated', ['doc_id' => $id, 'user_id' => (int)Session::get('user_id', 0)]);
            return new Response('', 302, ['Location' => '/admin/documents']);
        }

        return $this->render('admin/document_form', [
            'page_title' => Lang::get('FFCMS_DOCUMENTS'),
            'document' => $doc,
            'can_manage_all' => ACL::can('manage_documents'),
        ], 'Zulu');
    }

    public function delete(\Core\Request $request): Response
    {
        Session::startAdmin();
        $id = (int)$request->param('id');
        $doc = DocumentsModel::find($id);
        if (!$doc) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        $access = $this->checkDocAccess($doc, 'manage_documents', 'delete_documents_own');
        if ($access instanceof Response) {
            return $access;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
                return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
            }
            $path = __DIR__ . '/../../../storage/' . $doc['storage_path'];
            if (!is_file($path)) {
                Logger::error('error', 'Document delete failed', ['doc_id' => $id, 'user_id' => (int)Session::get('user_id', 0)]);
                return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
            }
            if (!unlink($path)) {
                Logger::error('error', 'Document delete failed', ['doc_id' => $id, 'user_id' => (int)Session::get('user_id', 0)]);
                return new Response('Delete failed', 500, ['Content-Type' => 'text/plain; charset=utf-8']);
            }
            DocumentsModel::delete($id);
            Logger::info('admin', 'Document deleted', ['doc_id' => $id, 'user_id' => (int)Session::get('user_id', 0)]);
            return new Response('', 302, ['Location' => '/admin/documents']);
        }

        return $this->render('admin/document_delete', [
            'page_title' => Lang::get('FFCMS_DOCUMENTS'),
            'document' => $doc,
        ], 'Zulu');
    }

    public function download(\Core\Request $request): Response
    {
        Session::startAdmin();
        $id = (int)$request->param('id');
        $doc = DocumentsModel::find($id);
        if (!$doc) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        $access = $this->checkDocAccess($doc, 'manage_documents', 'download_documents_own');
        if ($access instanceof Response) {
            return $access;
        }

        $path = __DIR__ . '/../../../storage/' . $doc['storage_path'];
        if (!is_file($path)) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $content = file_get_contents($path);
        Logger::info('admin', 'Document downloaded', ['doc_id' => $id, 'user_id' => (int)Session::get('user_id', 0)]);
        return new Response($content, 200, [
            'Content-Type' => $doc['mime_type'],
            'Content-Disposition' => 'attachment; filename="' . $doc['stored_name'] . '"',
        ]);
    }

    public function picker(): Response
    {
        Session::startAdmin();
        $access = $this->checkPickerAccess();
        if ($access instanceof Response) {
            return $access;
        }

        $userId = (int)Session::get('user_id', 0);
        $docs = Auth::isSuper() ? DocumentsModel::all() : DocumentsModel::forUser($userId);

        return $this->render('admin/document_picker', [
            'page_title' => Lang::get('FFCMS_DOCUMENT_PICKER'),
            'documents' => $docs,
        ], 'Zulu');
    }

    public function insertLog(): Response
    {
        Session::startAdmin();
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        $docId = (int)($_POST['document_id'] ?? 0);
        if ($docId <= 0) {
            return new Response('ERROR', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $doc = DocumentsModel::find($docId);
        if (!$doc) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        $access = $this->checkDocAccess($doc, 'manage_documents', 'download_documents_own');
        if ($access instanceof Response) {
            return $access;
        }

        Logger::info('admin', 'Document inserted', [
            'doc_id' => $docId,
            'user_id' => (int)Session::get('user_id', 0),
        ]);
        return new Response('OK', 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    private function checkListAccess(): ?Response
    {
        if (ACL::can('manage_documents') || ACL::can('manage_documents_own')) {
            return null;
        }
        return new Response('Forbidden', 403, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    private function checkUploadAccess(): ?Response
    {
        if (ACL::can('manage_documents') || ACL::can('manage_documents_own') || ACL::can('upload_documents_own')) {
            return null;
        }
        return new Response('Forbidden', 403, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    private function checkPickerAccess(): ?Response
    {
        if (ACL::can('manage_documents') || ACL::can('manage_documents_own') || ACL::can('download_documents_own')) {
            return null;
        }
        return new Response('Forbidden', 403, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    private function checkDocAccess(array $doc, string $permAll, string $permOwn): ?Response
    {
        if (ACL::can($permAll)) {
            return null;
        }
        if (!ACL::can($permOwn) && !ACL::can('manage_documents_own')) {
            return new Response('Forbidden', 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        $ownerId = (int)($doc['owner_user_id'] ?? 0);
        $userId = (int)Session::get('user_id', 0);
        if ($ownerId !== $userId) {
            return new Response('Forbidden', 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        return null;
    }
}
