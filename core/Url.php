<?php
namespace Core;

class Url
{
    public static function isSafe(?string $url): bool
    {
        if ($url === null) {
            return false;
        }
        $url = trim($url);
        if ($url === '') {
            return false;
        }
        if (str_starts_with($url, '/')) {
            return true;
        }
        return (bool)preg_match('#^https?://#i', $url);
    }
}
