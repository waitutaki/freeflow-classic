<?php
namespace Core;

class Slug
{
    public static function generate(string $value, int $maxLength = 160): string
    {
        $slug = strtolower(trim($value));
        $slug = preg_replace('/[^a-z0-9\\s-]/', '', $slug);
        $slug = preg_replace('/\\s+/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        if ($maxLength > 0) {
            $slug = substr($slug, 0, $maxLength);
            $slug = rtrim($slug, '-');
        }
        return $slug;
    }

    public static function isValid(string $slug, int $maxLength = 160): bool
    {
        if ($slug === '') {
            return false;
        }
        if (strlen($slug) > $maxLength) {
            return false;
        }
        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            return false;
        }
        return true;
    }
}
