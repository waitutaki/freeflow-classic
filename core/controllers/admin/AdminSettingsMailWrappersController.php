<?php
namespace Core\Controllers\Admin;

use Core\Auth;
use Core\Controller;
use Core\Csrf;
use Core\Freewrite;
use Core\Lang;
use Core\MailService;
use Core\Response;
use Core\Session;
use Core\Template;
use Core\Models\MailWrappersModel;

class AdminSettingsMailWrappersController extends Controller
{
    public function create(): Response
    {
        Session::startAdmin();
        Auth::requirePermission('manage_settings_mail_wrappers');

        $errors = [];
        $values = [
            'name' => '',
            'wrapper_key' => '',
            'wrapper_doc_xml' => '<freewrite version="1"></freewrite>',
            'is_default' => 0,
            'is_enabled' => 1,
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
                $errors['csrf'] = Lang::get('FFCMS_CSRF_INVALID');
            } else {
                $values = $this->collect($values);
                $errors = $this->validate($values, true);
                if (!$errors) {
                    $data = $this->buildData($values);
                    $id = MailWrappersModel::create($data, (int)Session::get('user_id', 0));
                    if ((int)$data['is_default'] === 1) {
                        MailWrappersModel::setDefault($id);
                    }
                    return new Response('', 302, ['Location' => '/admin/settings?tab=mail&subtab=wrappers']);
                }
            }
        }

        $preview = $this->buildPreview($values['wrapper_doc_xml'], $values['wrapper_key']);

        return $this->render('admin/settings_mail_wrapper_form', [
            'page_title' => Lang::get('FFCMS_MAIL_WRAPPERS'),
            'values' => $values,
            'errors' => $errors,
            'preview' => $preview,
            'is_edit' => false,
        ], 'Zulu');
    }

    public function edit(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requirePermission('manage_settings_mail_wrappers');

        $id = (int)$request->param('id');
        $wrapper = MailWrappersModel::find($id);
        if (!$wrapper) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $docXml = (string)($wrapper['wrapper_doc_xml'] ?? '');
        if (trim($docXml) === '' && !empty($wrapper['html_cache'])) {
            $docXml = '<freewrite version="1"><block type="richtext"><![CDATA[' . $wrapper['html_cache'] . ']]></block></freewrite>';
        }

        $values = [
            'name' => $wrapper['name'] ?? '',
            'wrapper_key' => $wrapper['wrapper_key'] ?? '',
            'wrapper_doc_xml' => $this->normalizeDocXml($docXml),
            'is_default' => (int)($wrapper['is_default'] ?? 0),
            'is_enabled' => (int)($wrapper['is_enabled'] ?? 0),
        ];

        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
                $errors['csrf'] = Lang::get('FFCMS_CSRF_INVALID');
            } else {
                $values = $this->collect($values);
                $errors = $this->validate($values, false, $id);
                if (!$errors) {
                    $data = $this->buildData($values);
                    MailWrappersModel::update($id, $data);
                    if ((int)$data['is_default'] === 1) {
                        MailWrappersModel::setDefault($id);
                    }
                    return new Response('', 302, ['Location' => '/admin/settings?tab=mail&subtab=wrappers']);
                }
            }
        }

        $preview = $this->buildPreview($values['wrapper_doc_xml'], $values['wrapper_key']);

        return $this->render('admin/settings_mail_wrapper_form', [
            'page_title' => Lang::get('FFCMS_MAIL_WRAPPERS'),
            'values' => $values,
            'errors' => $errors,
            'preview' => $preview,
            'is_edit' => true,
        ], 'Zulu');
    }

    public function delete(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requirePermission('manage_settings_mail_wrappers');

        $id = (int)$request->param('id');
        $wrapper = MailWrappersModel::find($id);
        if (!$wrapper) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        if (!empty($wrapper['is_default'])) {
            return new Response('Forbidden', 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
                return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
            }
            MailWrappersModel::delete($id);
            return new Response('', 302, ['Location' => '/admin/settings?tab=mail&subtab=wrappers']);
        }

        return $this->render('admin/settings_mail_wrapper_delete', [
            'page_title' => Lang::get('FFCMS_MAIL_WRAPPERS'),
            'wrapper' => $wrapper,
        ], 'Zulu');
    }

    private function collect(array $values): array
    {
        $values['name'] = trim($_POST['name'] ?? '');
        $values['wrapper_key'] = strtolower(trim($_POST['wrapper_key'] ?? $values['wrapper_key']));
        $values['wrapper_doc_xml'] = (string)($_POST['wrapper_doc_xml'] ?? '');
        $values['is_default'] = isset($_POST['is_default']) ? 1 : 0;
        $values['is_enabled'] = isset($_POST['is_enabled']) ? 1 : 0;
        return $values;
    }

    private function validate(array $values, bool $isCreate, int $id = 0): array
    {
        $errors = [];
        if ($values['name'] === '') {
            $errors['name'] = Lang::get('FFCMS_REQUIRED');
        }
        if ($isCreate) {
            if ($values['wrapper_key'] === '' || !preg_match('/^[a-z0-9_]{1,80}$/', $values['wrapper_key'])) {
                $errors['wrapper_key'] = Lang::get('FFCMS_TEMPLATE_KEY_INVALID');
            } elseif (MailWrappersModel::existsKey($values['wrapper_key'])) {
                $errors['wrapper_key'] = Lang::get('FFCMS_TEMPLATE_KEY_TAKEN');
            }
        }

        $sanitized = Freewrite::sanitizeXml($values['wrapper_doc_xml'], true, false);
        if (!$sanitized['ok']) {
            $errors['wrapper_doc_xml'] = Lang::get('FFCMS_CONTENT_INVALID');
        } else {
            $values['wrapper_doc_xml'] = $sanitized['xml'];
            $rendered = Freewrite::render($values['wrapper_doc_xml']);
            $htmlCount = substr_count($rendered['html'], '[mailcontent]');
            $textCount = substr_count($rendered['text'], '[mailcontent]');
            if ($htmlCount !== 1 || $textCount !== 1) {
                $errors['wrapper_doc_xml'] = Lang::get('FFCMS_MAIL_CONTENT_REQUIRED');
            }
        }

        if ($values['is_default'] === 0 && !MailWrappersModel::hasDefault($id)) {
            $errors['is_default'] = Lang::get('FFCMS_MAIL_WRAPPER_DEFAULT_REQUIRED');
        }

        return $errors;
    }

    private function buildData(array $values): array
    {
        $sanitized = Freewrite::sanitizeXml($values['wrapper_doc_xml'], true, false);
        $xml = $sanitized['ok'] ? $sanitized['xml'] : '<freewrite version="1"></freewrite>';
        $rendered = Freewrite::render($xml);

        return [
            'wrapper_key' => $values['wrapper_key'],
            'name' => $values['name'],
            'wrapper_doc_xml' => $xml,
            'html_cache' => $rendered['html'],
            'text_cache' => $rendered['text'],
            'is_default' => $values['is_default'],
            'is_enabled' => $values['is_enabled'],
        ];
    }

    private function buildPreview(string $xml, string $wrapperKey): array
    {
        $sanitized = Freewrite::sanitizeXml($xml, true, false);
        if (!$sanitized['ok']) {
            return ['html' => '', 'text' => '', 'wrapped_html' => '', 'wrapped_text' => ''];
        }
        $rendered = Freewrite::render($sanitized['xml']);
        $sample = Lang::get('FFCMS_MAIL_SAMPLE_CONTENT');
        $wrapped = MailService::wrapWithWrapperXml($sanitized['xml'], $wrapperKey !== '' ? $wrapperKey : 'preview', $sample, '<p>' . Template::escape($sample) . '</p>');

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
