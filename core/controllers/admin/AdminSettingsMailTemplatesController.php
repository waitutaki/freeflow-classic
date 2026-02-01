<?php
namespace Core\Controllers\Admin;

use Core\Auth;
use Core\Controller;
use Core\Csrf;
use Core\Lang;
use Core\Freewrite;
use Core\MailService;
use Core\Response;
use Core\Session;
use Core\Template;
use Core\Models\MailTemplatesModel;

class AdminSettingsMailTemplatesController extends Controller
{
    public function create(): Response
    {
        Session::startAdmin();
        Auth::requirePermission('manage_settings_mail_templates');

        $errors = [];
        $values = [
            'name' => '',
            'template_key' => '',
            'language_tag' => 'en-GB',
            'subject' => '',
            'body_doc_xml' => '<freewrite version="1"></freewrite>',
            'is_enabled' => 1,
            'is_core' => 0,
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
                $errors['csrf'] = Lang::get('FFCMS_CSRF_INVALID');
            } else {
                $values = $this->collect($values);
                $errors = $this->validate($values, true);
                if (!$errors) {
                    $userId = (int)Session::get('user_id', 0);
                    $data = $this->buildData($values, $userId);
                    MailTemplatesModel::create($data, $userId);
                    return new Response('', 302, ['Location' => '/admin/settings?tab=mail&subtab=templates']);
                }
            }
        }

        $preview = $this->buildPreview($values['body_doc_xml']);

        return $this->render('admin/settings_mail_template_form', [
            'page_title' => Lang::get('FFCMS_MAIL_TEMPLATES'),
            'values' => $values,
            'errors' => $errors,
            'preview' => $preview,
            'is_edit' => false,
        ], 'Zulu');
    }

    public function edit(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requirePermission('manage_settings_mail_templates');

        $id = (int)$request->param('id');
        $template = MailTemplatesModel::find($id);
        if (!$template) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $docXml = (string)($template['body_doc_xml'] ?? '');
        if (trim($docXml) === '' && !empty($template['html_cache'])) {
            $docXml = '<freewrite version="1"><block type="richtext"><![CDATA[' . $template['html_cache'] . ']]></block></freewrite>';
        }

        $values = [
            'name' => $template['name'] ?? '',
            'template_key' => $template['template_key'] ?? '',
            'language_tag' => $template['language_tag'] ?? 'en-GB',
            'subject' => $template['subject'] ?? '',
            'body_doc_xml' => $this->normalizeDocXml($docXml),
            'is_enabled' => (int)($template['is_enabled'] ?? 0),
            'is_core' => (int)($template['is_core'] ?? 0),
        ];

        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
                $errors['csrf'] = Lang::get('FFCMS_CSRF_INVALID');
            } else {
                $values = $this->collect($values);
                $errors = $this->validate($values, false);
                if (!$errors) {
                    $userId = (int)Session::get('user_id', 0);
                    $data = $this->buildData($values, $userId);
                    MailTemplatesModel::update($id, $data, $userId);
                    return new Response('', 302, ['Location' => '/admin/settings?tab=mail&subtab=templates']);
                }
            }
        }

        $preview = $this->buildPreview($values['body_doc_xml']);

        return $this->render('admin/settings_mail_template_form', [
            'page_title' => Lang::get('FFCMS_MAIL_TEMPLATES'),
            'values' => $values,
            'errors' => $errors,
            'preview' => $preview,
            'is_edit' => true,
        ], 'Zulu');
    }

    public function delete(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requirePermission('manage_settings_mail_templates');

        $id = (int)$request->param('id');
        $template = MailTemplatesModel::find($id);
        if (!$template) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        if ((int)$template['is_core'] === 1) {
            return new Response('Forbidden', 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
                return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
            }
            MailTemplatesModel::delete($id);
            return new Response('', 302, ['Location' => '/admin/settings?tab=mail&subtab=templates']);
        }

        return $this->render('admin/settings_mail_template_delete', [
            'page_title' => Lang::get('FFCMS_MAIL_TEMPLATES'),
            'template' => $template,
        ], 'Zulu');
    }

    private function collect(array $values): array
    {
        $values['name'] = trim($_POST['name'] ?? '');
        $values['template_key'] = strtolower(trim($_POST['template_key'] ?? $values['template_key']));
        $values['language_tag'] = trim($_POST['language_tag'] ?? 'en-GB');
        $values['subject'] = trim($_POST['subject'] ?? '');
        $values['body_doc_xml'] = (string)($_POST['body_doc_xml'] ?? '');
        $values['is_enabled'] = isset($_POST['is_enabled']) ? 1 : 0;
        $values['is_core'] = isset($_POST['is_core']) ? 1 : 0;
        return $values;
    }

    private function validate(array $values, bool $isCreate): array
    {
        $errors = [];
        if ($values['name'] === '') {
            $errors['name'] = Lang::get('FFCMS_REQUIRED');
        }
        if ($isCreate) {
            if ($values['template_key'] === '' || !preg_match('/^[a-z0-9_]{1,80}$/', $values['template_key'])) {
                $errors['template_key'] = Lang::get('FFCMS_TEMPLATE_KEY_INVALID');
            } elseif (MailTemplatesModel::existsKey($values['template_key'])) {
                $errors['template_key'] = Lang::get('FFCMS_TEMPLATE_KEY_TAKEN');
            }
        }
        if ($values['subject'] === '') {
            $errors['subject'] = Lang::get('FFCMS_REQUIRED');
        }
        $sanitized = Freewrite::sanitizeXml($values['body_doc_xml'], true, false);
        if (!$sanitized['ok']) {
            $errors['body_doc_xml'] = Lang::get('FFCMS_CONTENT_INVALID');
        } else {
            $values['body_doc_xml'] = $sanitized['xml'];
            $rendered = Freewrite::render($values['body_doc_xml']);
            if ($values['is_enabled'] === 1 && (trim($rendered['html']) === '' || trim($rendered['text']) === '')) {
                $errors['body_doc_xml'] = Lang::get('FFCMS_MAIL_TEMPLATE_RENDER_REQUIRED');
            }
        }
        if ($values['is_core'] === 1 && !Auth::isSuper()) {
            $errors['is_core'] = Lang::get('FFCMS_FORBIDDEN');
        }
        return $errors;
    }

    private function buildData(array $values, int $userId): array
    {
        $sanitized = Freewrite::sanitizeXml($values['body_doc_xml'], true, false);
        $xml = $sanitized['ok'] ? $sanitized['xml'] : '<freewrite version="1"></freewrite>';
        $rendered = Freewrite::render($xml);
        return [
            'owner_type' => 'core',
            'owner_key' => 'core',
            'template_key' => $values['template_key'],
            'name' => $values['name'],
            'language_tag' => $values['language_tag'],
            'subject' => $values['subject'],
            'body_doc_xml' => $xml,
            'html_cache' => $rendered['html'],
            'text_cache' => $rendered['text'],
            'is_enabled' => $values['is_enabled'],
            'is_core' => $values['is_core'],
            'updated_by' => $userId,
        ];
    }

    private function buildPreview(string $xml): array
    {
        $sanitized = Freewrite::sanitizeXml($xml, true, false);
        if (!$sanitized['ok']) {
            return ['html' => '', 'text' => '', 'wrapped_html' => '', 'wrapped_text' => ''];
        }
        $rendered = Freewrite::render($sanitized['xml']);
        $wrapped = MailService::wrapContent($rendered['text'], $rendered['html']);

        return [
            'html' => $rendered['html'],
            'text' => $rendered['text'],
            'wrapped_html' => $wrapped['html'] ?? '',
            'wrapped_text' => $wrapped['text'] ?? '',
        ];
    }

    private function normalizeDocXml(string $xml): string
    {
        $xml = trim($xml);
        if ($xml === '') {
            return '<freewrite version="1"></freewrite>';
        }
        if (stripos($xml, '<freewrite') !== false) {
            return $xml;
        }
        $xml = preg_replace('/<\\/?document>/', '', $xml);
        return '<freewrite version="1">' . $xml . '</freewrite>';
    }
}
