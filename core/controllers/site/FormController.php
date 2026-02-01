<?php
namespace Core\Controllers\Site;

use Core\Controller;
use Core\Csrf;
use Core\Response;
use Core\Models\FormsModel;

class FormController extends Controller
{
    public function submit(): Response
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return new Response('Method not allowed', 405, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $formId = (int)($_POST['form_id'] ?? 0);
        $form = FormsModel::find($formId);
        if (!$form) {
            return new Response('Form not found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            return new Response('Invalid CSRF token', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        // Collect form data
        $submissionData = [];
        foreach ($_POST as $key => $value) {
            if ($key !== 'form_id' && $key !== 'csrf_token') {
                $submissionData[$key] = trim($value);
            }
        }

        // Convert to XML
        $xml = '<submission>';
        foreach ($submissionData as $key => $value) {
            $xml .= '<field name="' . htmlspecialchars($key) . '">' . htmlspecialchars($value) . '</field>';
        }
        $xml .= '</submission>';

        // Store submission
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        FormsModel::createSubmission($formId, $xml, $ip, $userAgent);

        // Redirect or show success message
        return new Response('Form submitted successfully', 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }
}