<?php
namespace Core;

class Trust
{
    public static function publicKeyPath(): string
    {
        return __DIR__ . '/keys/freeflow_public.pem';
    }

    public static function fingerprint(): ?string
    {
        $path = self::publicKeyPath();
        if (!is_file($path) || !is_readable($path)) {
            return null;
        }
        $data = file_get_contents($path);
        if ($data === false) {
            return null;
        }
        $resource = openssl_pkey_get_public($data);
        if (!$resource) {
            return null;
        }
        $details = openssl_pkey_get_details($resource);
        if (!$details || !isset($details['key'])) {
            return null;
        }
        $pemKey = $details['key'];
        $derKey = base64_decode(preg_replace('/-----(BEGIN|END) PUBLIC KEY-----|\s/', '', $pemKey));
        return hash('sha256', $derKey);
    }

    public static function check(): array
    {
        $current = self::fingerprint();
        $stored = (string)Settings::get('freeflow_public_key_fingerprint', '', 'security');
        $missing = $current === null;
        $mismatch = !$missing && $stored !== '' && $current !== $stored;

        if ($missing || $mismatch) {
            Logger::error('error', 'Trusted key mismatch', ['stored' => $stored, 'current' => $current]);
            Logger::error('admin', 'Trusted key mismatch', ['stored' => $stored, 'current' => $current]);
        }

        return [
            'missing' => $missing,
            'mismatch' => $mismatch,
            'current' => $current,
            'stored' => $stored,
        ];
    }

    public static function requireTrusted(): ?Response
    {
        $status = self::check();
        if ($status['missing'] || $status['mismatch']) {
            $message = Lang::get('FFCMS_TRUST_KEY_ERROR');
            return new Response($message, 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        return null;
    }

    public static function bannerHtml(): string
    {
        if (!Auth::isSuper()) {
            return '';
        }
        $status = self::check();
        if (!$status['missing'] && !$status['mismatch']) {
            return '';
        }
        $text = Lang::get('FFCMS_TRUST_KEY_WARNING');
        return '<div class="alert alert-warning mb-0" role="alert">' . Template::escape($text) . '</div>';
    }
}
