<?php
namespace Core\Controllers\Admin;

use Core\Auth;
use Core\Controller;
use Core\Csrf;
use Core\DocsBuilder;
use Core\Lang;
use Core\Logger;
use Core\Response;
use Core\Session;

class AdminDocsController extends Controller
{
    public function index(): Response
    {
        Session::startAdmin();
        Auth::requireSuper();

        $sections = [
            'user' => DocsBuilder::sectionInfo('user'),
            'developer' => DocsBuilder::sectionInfo('developer'),
            'sysadmin' => DocsBuilder::sectionInfo('sysadmin'),
        ];

        return $this->render('admin/docs', [
            'page_title' => Lang::get('FFCMS_DOCS_BUILDER'),
            'sections' => $sections,
        ], 'Zulu');
    }

    public function build(): Response
    {
        Session::startAdmin();
        Auth::requireSuper();

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $results = DocsBuilder::buildAll((int)Session::get('user_id', 0));
        $failed = [];
        foreach ($results as $section => $result) {
            if (($result['status'] ?? '') !== 'success') {
                $failed[] = $section;
            }
        }
        if ($failed) {
            Logger::error('error', 'Docs build failed', ['sections' => implode(',', $failed)]);
        }
        Logger::info('admin', 'Docs build complete', ['sections' => implode(',', array_keys($results))]);

        return new Response('', 302, ['Location' => '/admin/docs']);
    }
}
