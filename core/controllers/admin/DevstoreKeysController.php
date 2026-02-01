<?php
namespace Core\Controllers\Admin;

use Core\Auth;
use Core\Controller;
use Core\Csrf;
use Core\Lang;
use Core\Logger;
use Core\Response;
use Core\Session;
use Core\Settings;

class DevstoreKeysController extends Controller
{
    public function index(): Response
    {
        Session::startAdmin();
        Auth::requireSuper();

        if (!Csrf::validate($_POST['csrf_token'] ?? null) && $_SERVER['REQUEST_METHOD'] === 'POST') {
            return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $message = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['generate_keys'])) {
            $result = $this->generateKeys((int)Session::get('user_id', 0));
            if ($result['ok']) {
                $message = Lang::get('DEVSTORE.KEYS_GENERATED');
            } else {
                $message = $result['error'];
            }
        }

        $status = $this->keyStatus();

        return $this->render('admin/devstore_keys', [
            'page_title' => Lang::get('DEVSTORE.KEYS_TITLE'),
            'status' => $status,
            'message' => $message,
        ], 'Zulu');
    }

    private function keyStatus(): array
    {
        $privatePath = __DIR__ . '/../../keys/freeflow_private.pem';
        $publicPath = __DIR__ . '/../../keys/freeflow_public.pem';

        $privateExists = is_file($privatePath) && is_readable($privatePath);
        $publicExists = is_file($publicPath) && is_readable($publicPath);
        
        $fingerprint = '';
        if ($publicExists) {
            $publicKey = (string)file_get_contents($publicPath);
            $resource = openssl_pkey_get_public($publicKey);
            if ($resource) {
                $details = openssl_pkey_get_details($resource);
                if ($details && isset($details['key'])) {
                    $pemKey = $details['key'];
                    $derKey = base64_decode(preg_replace('/-----(BEGIN|END) PUBLIC KEY-----|\s/', '', $pemKey));
                    $fingerprint = hash('sha256', $derKey);
                }
            }
        }
        
        $trusted = (string)Settings::get('freeflow_public_key_fingerprint', '', 'security');

        return [
            'private_exists' => $privateExists,
            'public_exists' => $publicExists,
            'fingerprint' => $fingerprint,
            'trusted_fingerprint' => $trusted,
            'fingerprint_match' => $fingerprint !== '' && $trusted !== '' && $fingerprint === $trusted,
        ];
    }

    private function generateKeys(int $userId): array
    {
        $privatePath = __DIR__ . '/../../keys/freeflow_private.pem';
        $publicPath = __DIR__ . '/../../keys/freeflow_public.pem';

        $config = [
            'private_key_bits' => 4096,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];
        $resource = openssl_pkey_new($config);
        if (!$resource) {
            return ['ok' => false, 'error' => Lang::get('DEVSTORE.KEYS_GENERATE_FAILED')];
        }

        $privateKey = '';
        if (!openssl_pkey_export($resource, $privateKey)) {
            return ['ok' => false, 'error' => Lang::get('DEVSTORE.KEYS_GENERATE_FAILED')];
        }
        $details = openssl_pkey_get_details($resource);
        if (!$details || empty($details['key'])) {
            return ['ok' => false, 'error' => Lang::get('DEVSTORE.KEYS_GENERATE_FAILED')];
        }

        $publicKey = $details['key'];
        if (file_put_contents($privatePath, $privateKey) === false) {
            return ['ok' => false, 'error' => Lang::get('DEVSTORE.KEYS_SAVE_FAILED')];
        }
        if (file_put_contents($publicPath, $publicKey) === false) {
            return ['ok' => false, 'error' => Lang::get('DEVSTORE.KEYS_SAVE_FAILED')];
        }
        @chmod($privatePath, 0600);
        @chmod($publicPath, 0644);

        $derKey = base64_decode(preg_replace('/-----(BEGIN|END) PUBLIC KEY-----|\s/', '', $publicKey));
        $fingerprint = hash('sha256', $derKey);
        Settings::set('freeflow_public_key_fingerprint', $fingerprint, 'string', false, 'security');
        Logger::warning('admin', 'Devstore keys regenerated', ['fingerprint' => $fingerprint]);

        return ['ok' => true];
    }
}
