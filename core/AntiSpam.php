<?php
namespace Core;

class AntiSpam
{
    private const FORM_TYPES = [
        'login',
        'registration',
        'password_reset_request',
        'password_reset_confirm',
        'email_verification_resend',
    ];

    public static function renderFields(string $formType): string
    {
        if (!in_array($formType, self::FORM_TYPES, true)) {
            return '';
        }

        if (Auth::isAuthenticated()) {
            return '';
        }

        $html = '';
        if (self::isHoneypotEnabled($formType)) {
            $html .= '<div class="ff-honeypot" aria-hidden="true">';
            $html .= '<label for="ff_hp_' . Template::escape($formType) . '">Leave blank</label>';
            $html .= '<input type="text" id="ff_hp_' . Template::escape($formType) . '" name="ff_hp" tabindex="-1" autocomplete="off">';
            $html .= '</div>';
        }

        if (self::isTimeEnabled($formType)) {
            Session::set(self::timeKey($formType), time());
        }

        if (self::isCaptchaEnabled($formType) && self::provider() !== 'none') {
            $html .= '<div class="mb-3">';
            $html .= '<label class="form-label" for="ff_captcha">' . Lang::get('FFCMS_CAPTCHA') . '</label>';
            $html .= '<input class="form-control" type="text" id="ff_captcha" name="ff_captcha">';
            $html .= '<div class="form-text">' . Lang::get('FFCMS_CAPTCHA_HELP') . '</div>';
            $html .= '</div>';
        }

        return $html;
    }

    public static function validate(string $formType, array $post): array
    {
        if (!in_array($formType, self::FORM_TYPES, true)) {
            return ['ok' => true, 'error' => ''];
        }

        if (Auth::isAuthenticated()) {
            return ['ok' => true, 'error' => ''];
        }

        if (self::isBlocked($formType)) {
            return ['ok' => false, 'error' => Lang::get('FFCMS_FORM_BLOCKED')];
        }

        if (self::isHoneypotEnabled($formType)) {
            $hp = trim((string)($post['ff_hp'] ?? ''));
            if ($hp !== '') {
                self::recordFailure($formType);
                return ['ok' => false, 'error' => Lang::get('FFCMS_SPAM_DETECTED')];
            }
        }

        if (self::isTimeEnabled($formType)) {
            $start = (int)Session::get(self::timeKey($formType), 0);
            $minSeconds = (int)Settings::get('antispam_min_seconds', 3, 'security');
            if ($start > 0 && (time() - $start) < $minSeconds) {
                self::recordFailure($formType);
                return ['ok' => false, 'error' => Lang::get('FFCMS_FORM_TOO_FAST')];
            }
        }

        if (self::isCaptchaEnabled($formType) && self::provider() !== 'none') {
            $token = trim((string)($post['ff_captcha'] ?? ''));
            if ($token === '' || !self::verifyCaptcha($token)) {
                self::recordFailure($formType);
                return ['ok' => false, 'error' => Lang::get('FFCMS_CAPTCHA_FAILED')];
            }
        }

        return ['ok' => true, 'error' => ''];
    }

    private static function isHoneypotEnabled(string $formType): bool
    {
        if (!(bool)Settings::get('antispam_enabled', true, 'security')) {
            return false;
        }
        return (bool)Settings::get('antispam_honeypot_' . $formType, true, 'security');
    }

    private static function isTimeEnabled(string $formType): bool
    {
        if (!(bool)Settings::get('antispam_enabled', true, 'security')) {
            return false;
        }
        return (bool)Settings::get('antispam_time_' . $formType, true, 'security');
    }

    private static function isCaptchaEnabled(string $formType): bool
    {
        return (bool)Settings::get('captcha_' . $formType, false, 'security');
    }

    private static function provider(): string
    {
        $provider = (string)Settings::get('captcha_provider', 'none', 'security');
        return in_array($provider, ['none', 'hcaptcha', 'recaptcha'], true) ? $provider : 'none';
    }

    private static function verifyCaptcha(string $token): bool
    {
        $provider = self::provider();
        if ($provider === 'none') {
            return true;
        }

        $secretKey = '';
        $endpoint = '';
        if ($provider === 'hcaptcha') {
            $secretKey = (string)Settings::get('hcaptcha_secret_key', '', 'security');
            $endpoint = 'https://hcaptcha.com/siteverify';
        } elseif ($provider === 'recaptcha') {
            $secretKey = (string)Settings::get('recaptcha_secret_key', '', 'security');
            $endpoint = 'https://www.google.com/recaptcha/api/siteverify';
        }

        if ($secretKey === '' || $endpoint === '') {
            return false;
        }

        $postData = http_build_query([
            'secret' => $secretKey,
            'response' => $token,
        ]);

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        $result = curl_exec($ch);
        curl_close($ch);

        if ($result === false) {
            Logger::error('error', 'Captcha verification failed', []);
            return false;
        }

        $data = json_decode($result, true);
        if (!is_array($data)) {
            Logger::error('error', 'Captcha verification invalid response', []);
            return false;
        }

        return !empty($data['success']);
    }

    private static function recordFailure(string $formType): void
    {
        if (!(bool)Settings::get('failcount_enabled', false, 'security')) {
            return;
        }

        $countKey = self::failKey($formType);
        $count = (int)Session::get($countKey, 0);
        $count++;
        Session::set($countKey, $count);

        $limit = (int)Settings::get('failcount_limit', 3, 'security');
        if ($count >= $limit) {
            $cooldownEnabled = (bool)Settings::get('failcount_cooldown_enabled', false, 'security');
            if ($cooldownEnabled) {
                $minutes = (int)Settings::get('failcount_cooldown_minutes', 5, 'security');
                Session::set(self::blockKey($formType), time() + ($minutes * 60));
            } else {
                Session::set(self::blockKey($formType), PHP_INT_MAX);
            }
        }
    }

    private static function isBlocked(string $formType): bool
    {
        $until = (int)Session::get(self::blockKey($formType), 0);
        if ($until === 0) {
            return false;
        }
        if ($until === PHP_INT_MAX) {
            return true;
        }
        if ($until < time()) {
            Session::remove(self::blockKey($formType));
            Session::remove(self::failKey($formType));
            return false;
        }
        return true;
    }

    private static function timeKey(string $formType): string
    {
        return 'ff_time_' . $formType;
    }

    private static function failKey(string $formType): string
    {
        return 'ff_fail_' . $formType;
    }

    private static function blockKey(string $formType): string
    {
        return 'ff_block_' . $formType;
    }
}
