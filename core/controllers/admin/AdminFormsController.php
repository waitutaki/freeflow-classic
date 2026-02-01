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
use Core\ACL;
use Core\Models\FormsModel;

class AdminFormsController extends Controller
{
    public function index(): Response
    {
        Session::startAdmin();
        if (!ACL::can('manage_content')) {
            Auth::requirePermission('manage_content');
        }

        $forms = FormsModel::all();

        return $this->render('admin/forms', [
            'page_title' => 'Forms',
            'forms' => $forms,
        ], 'Zulu');
    }

    public function create(): Response
    {
        Session::startAdmin();
        if (!ACL::can('manage_content')) {
            Auth::requirePermission('manage_content');
        }

        $values = [
            'title' => '',
            'slug' => '',
            'form_xml' => '<form></form>',
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
                    $formId = FormsModel::create($values);
                    Logger::info('admin', 'Form created', ['form_id' => $formId, 'user_id' => $values['created_by']]);
                    return new Response('', 302, ['Location' => '/admin/forms']);
                }
            }
        }

        return $this->render('admin/form_form', [
            'page_title' => 'Create Form',
            'values' => $values,
            'errors' => $errors,
            'is_edit' => false,
        ], 'Zulu');
    }

    public function edit(\Core\Request $request): Response
    {
        Session::startAdmin();
        if (!ACL::can('manage_content')) {
            Auth::requirePermission('manage_content');
        }

        $id = (int)$request->param('id');
        $form = FormsModel::find($id);
        if (!$form) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $values = [
            'title' => $form['title'] ?? '',
            'slug' => $form['slug'] ?? '',
            'form_xml' => $form['form_xml'] ?? '',
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
                    FormsModel::update($id, $values);
                    Logger::info('admin', 'Form updated', ['form_id' => $id, 'user_id' => $values['modified_by']]);
                    return new Response('', 302, ['Location' => '/admin/forms']);
                }
            }
        }

        return $this->render('admin/form_form', [
            'page_title' => 'Edit Form',
            'values' => $values,
            'errors' => $errors,
            'is_edit' => true,
        ], 'Zulu');
    }

    public function delete(\Core\Request $request): Response
    {
        Session::startAdmin();
        if (!ACL::can('manage_content')) {
            Auth::requirePermission('manage_content');
        }

        $id = (int)$request->param('id');
        $form = FormsModel::find($id);
        if (!$form) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
                return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
            }
            FormsModel::delete($id);
            Logger::info('admin', 'Form deleted', ['form_id' => $id, 'user_id' => (int)Session::get('user_id', 0)]);
            return new Response('', 302, ['Location' => '/admin/forms']);
        }

        return $this->render('admin/form_delete', [
            'page_title' => 'Delete Form',
            'form' => $form,
        ], 'Zulu');
    }

    public function submissions(\Core\Request $request): Response
    {
        Session::startAdmin();
        if (!ACL::can('manage_content')) {
            Auth::requirePermission('manage_content');
        }

        $id = (int)$request->param('id');
        $form = FormsModel::find($id);
        if (!$form) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $submissions = FormsModel::getSubmissionsForForm($id);

        return $this->render('admin/form_submissions', [
            'page_title' => 'Form Submissions',
            'form' => $form,
            'submissions' => $submissions,
        ], 'Zulu');
    }

    private function collect(array $values): array
    {
        $values['title'] = trim($_POST['title'] ?? '');
        $values['slug'] = strtolower(trim($_POST['slug'] ?? ''));
        if ($values['slug'] === '') {
            $values['slug'] = Slug::generate($values['title']);
        }
        $values['form_xml'] = trim($_POST['form_xml'] ?? '');
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
        if (FormsModel::slugExists($values['slug'], $id)) {
            $errors['slug'] = Lang::get('FFCMS_SLUG_TAKEN');
        }
        if ($values['form_xml'] === '') {
            $errors['form_xml'] = Lang::get('FFCMS_REQUIRED');
        }
        return $errors;
    }
}