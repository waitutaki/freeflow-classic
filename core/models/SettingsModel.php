<?php
namespace Core\Models;

use Core\Settings;

class SettingsModel
{
    public static function saveGlobal(array $data, int $userId): array
    {
        $errors = [];

        $transport = $data['mail_transport'] ?? 'php_mail';
        if (!in_array($transport, ['php_mail', 'smtp'], true)) {
            $errors[] = 'mail_transport';
        }

        $fromEmail = trim($data['mail_from_email'] ?? '');
        $fromName = trim($data['mail_from_name'] ?? '');
        if ($fromEmail === '' || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'mail_from_email';
        }
        if ($fromName === '') {
            $errors[] = 'mail_from_name';
        }

        $replyTo = trim($data['mail_replyto_email'] ?? '');
        if ($replyTo !== '' && !filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'mail_replyto_email';
        }

        $smtpHost = trim($data['smtp_host'] ?? '');
        $smtpPort = (int)($data['smtp_port'] ?? 0);
        $smtpSecurity = $data['smtp_security'] ?? 'none';
        if (!in_array($smtpSecurity, ['none', 'tls', 'ssl'], true)) {
            $errors[] = 'smtp_security';
        }
        if ($transport === 'smtp') {
            if ($smtpHost === '') {
                $errors[] = 'smtp_host';
            }
            if ($smtpPort <= 0) {
                $errors[] = 'smtp_port';
            }
        }

        if ($errors) {
            return $errors;
        }

        Settings::set('global', 'mail_transport', $transport, 'string', $userId);
        Settings::set('global', 'mail_from_email', $fromEmail, 'string', $userId);
        Settings::set('global', 'mail_from_name', $fromName, 'string', $userId);
        Settings::set('global', 'mail_replyto_email', $replyTo, 'string', $userId);
        Settings::set('global', 'smtp_host', $smtpHost, 'string', $userId);
        Settings::set('global', 'smtp_port', $smtpPort, 'int', $userId);
        Settings::set('global', 'smtp_security', $smtpSecurity, 'string', $userId);

        $smtpUsername = trim($data['smtp_username'] ?? '');
        Settings::set('global', 'smtp_username', $smtpUsername, 'string', $userId);

        $smtpPassword = $data['smtp_password'] ?? '';
        if ($smtpPassword !== '') {
            Settings::set('global', 'smtp_password', $smtpPassword, 'string', $userId);
        }

        $forcePlain = isset($data['mail_force_plain_text']) ? 1 : 0;
        Settings::set('global', 'mail_force_plain_text', $forcePlain, 'bool', $userId);

        $mailLog = isset($data['mail_log_enabled']) ? 1 : 0;
        Settings::set('global', 'mail_log_enabled', $mailLog, 'bool', $userId);

        return [];
    }

    public static function saveSecurity(array $data, int $userId): array
    {
        $errors = [];
        $provider = $data['captcha_provider'] ?? 'none';
        if (!in_array($provider, ['none', 'hcaptcha', 'recaptcha'], true)) {
            $errors[] = 'captcha_provider';
        }

        Settings::set('security', 'captcha_provider', $provider, 'string', $userId);
        if ($provider === 'hcaptcha') {
            $siteKey = $data['hcaptcha_site_key'] ?? '';
            $secretKey = $data['hcaptcha_secret_key'] ?? '';
            if ($siteKey !== '') {
                Settings::set('security', 'hcaptcha_site_key', $siteKey, 'string', $userId);
            }
            if ($secretKey !== '') {
                Settings::set('security', 'hcaptcha_secret_key', $secretKey, 'string', $userId);
            }
        }
        if ($provider === 'recaptcha') {
            $siteKey = $data['recaptcha_site_key'] ?? '';
            $secretKey = $data['recaptcha_secret_key'] ?? '';
            if ($siteKey !== '') {
                Settings::set('security', 'recaptcha_site_key', $siteKey, 'string', $userId);
            }
            if ($secretKey !== '') {
                Settings::set('security', 'recaptcha_secret_key', $secretKey, 'string', $userId);
            }
        }

        $antiEnabled = isset($data['antispam_enabled']) ? 1 : 0;
        Settings::set('security', 'antispam_enabled', $antiEnabled, 'bool', $userId);
        $antiHoney = isset($data['antispam_honeypot']) ? 1 : 0;
        Settings::set('security', 'antispam_honeypot_default', $antiHoney, 'bool', $userId);
        $antiTime = isset($data['antispam_time']) ? 1 : 0;
        Settings::set('security', 'antispam_time_default', $antiTime, 'bool', $userId);
        $minSeconds = (int)($data['antispam_min_seconds'] ?? 3);
        if (!in_array($minSeconds, [1, 2, 3, 5, 10], true)) {
            $minSeconds = 3;
        }
        Settings::set('security', 'antispam_min_seconds', $minSeconds, 'int', $userId);

        $failEnabled = isset($data['failcount_enabled']) ? 1 : 0;
        Settings::set('security', 'failcount_enabled', $failEnabled, 'bool', $userId);
        $limit = (int)($data['failcount_limit'] ?? 3);
        if (!in_array($limit, [3, 5, 10], true)) {
            $limit = 3;
        }
        Settings::set('security', 'failcount_limit', $limit, 'int', $userId);
        $cooldownEnabled = isset($data['failcount_cooldown_enabled']) ? 1 : 0;
        Settings::set('security', 'failcount_cooldown_enabled', $cooldownEnabled, 'bool', $userId);
        $cooldown = (int)($data['failcount_cooldown_minutes'] ?? 5);
        if (!in_array($cooldown, [5, 15, 60, 240], true)) {
            $cooldown = 5;
        }
        Settings::set('security', 'failcount_cooldown_minutes', $cooldown, 'int', $userId);

        $forms = ['login', 'registration', 'password_reset_request', 'password_reset_confirm', 'email_verification_resend'];
        foreach ($forms as $form) {
            $captcha = isset($data['captcha_' . $form]) ? 1 : 0;
            $honeypot = isset($data['honeypot_' . $form]) ? 1 : 0;
            $time = isset($data['time_' . $form]) ? 1 : 0;
            Settings::set('security', 'captcha_' . $form, $captcha, 'bool', $userId);
            Settings::set('security', 'antispam_honeypot_' . $form, $honeypot, 'bool', $userId);
            Settings::set('security', 'antispam_time_' . $form, $time, 'bool', $userId);
        }

        return $errors;
    }
}
