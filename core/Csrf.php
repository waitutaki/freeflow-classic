<?php
namespace Core;

class Csrf
{
    private const SESSION_KEY = 'csrf_token';

    public static function token(): string
    {
        if (!Session::has(self::SESSION_KEY)) {
            Session::set(self::SESSION_KEY, bin2hex(random_bytes(32)));
        }
        return (string)Session::get(self::SESSION_KEY, '');
    }

    public static function input(): string
    {
        $token = self::token();
        return '<input type="hidden" name="csrf_token" value="' . Template::escape($token) . '">';
    }

    public static function validate(?string $token): bool
    {
        $sessionToken = (string)Session::get(self::SESSION_KEY, '');
        if ($sessionToken === '' || $token === null) {
            return false;
        }
        return hash_equals($sessionToken, $token);
    }
}
