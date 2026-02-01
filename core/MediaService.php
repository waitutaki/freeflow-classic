<?php
namespace Core;

class MediaService
{
    private const MAX_SIZE = 10485760;
    private const MAX_DIM = 4000;
    private const THUMB_SIZE = 320;
    private const ALLOWED = ['jpg', 'jpeg', 'png', 'webp'];

    public static function uploadImage(array $file, int $userId, string $bucket = 'users'): array
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['ok' => false, 'error' => 'Invalid upload.'];
        }

        if ((int)$file['size'] > self::MAX_SIZE) {
            return ['ok' => false, 'error' => 'File too large.'];
        }

        $info = @getimagesize($file['tmp_name']);
        if (!$info) {
            return ['ok' => false, 'error' => 'Invalid image.'];
        }

        [$width, $height, $type] = $info;
        if ($width > self::MAX_DIM || $height > self::MAX_DIM) {
            return ['ok' => false, 'error' => 'Image too large.'];
        }

        $ext = self::extensionFromType($type);
        if (!in_array($ext, self::ALLOWED, true)) {
            return ['ok' => false, 'error' => 'Invalid file type.'];
        }

        $tmpDir = __DIR__ . '/../storage/tmp';
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0775, true);
        }
        $tmpName = $tmpDir . '/' . bin2hex(random_bytes(16)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $tmpName)) {
            return ['ok' => false, 'error' => 'Upload failed.'];
        }

        $baseName = bin2hex(random_bytes(16));
        $fileName = $baseName . '.' . $ext;

        $destDir = self::bucketPath($userId, $bucket);
        if (!is_dir($destDir)) {
            mkdir($destDir, 0775, true);
        }
        $thumbDir = self::thumbPath($userId, $bucket);
        if (!is_dir($thumbDir)) {
            mkdir($thumbDir, 0775, true);
        }

        $finalPath = $destDir . '/' . $fileName;
        if (!rename($tmpName, $finalPath)) {
            @unlink($tmpName);
            return ['ok' => false, 'error' => 'Move failed.'];
        }

        $thumbName = $baseName . '.webp';
        $thumbPath = $thumbDir . '/' . $thumbName;
        $thumbOk = self::makeThumbnail($finalPath, $thumbPath, self::THUMB_SIZE);
        if (!$thumbOk) {
            return ['ok' => false, 'error' => 'Thumbnail failed.'];
        }

        $publicPath = self::publicPath($userId, $bucket, $fileName);
        $thumbPublic = self::publicThumbPath($userId, $bucket, $thumbName);

        return ['ok' => true, 'path' => $publicPath, 'thumb' => $thumbPublic];
    }

    public static function rotateImage(string $sourcePath, int $userId, string $bucket, int $angle): ?array
    {
        $abs = self::absFromPublic($sourcePath);
        if (!$abs) {
            return null;
        }

        $info = @getimagesize($abs);
        if (!$info) {
            return null;
        }

        $ext = self::extensionFromType($info[2]);
        if (!in_array($ext, self::ALLOWED, true)) {
            return null;
        }

        $image = self::createImage($abs, $ext);
        if (!$image) {
            return null;
        }

        $rotated = imagerotate($image, $angle, 0);
        imagedestroy($image);
        if (!$rotated) {
            return null;
        }

        $baseName = bin2hex(random_bytes(16));
        $fileName = $baseName . '.' . $ext;
        $destDir = self::bucketPath($userId, $bucket);
        if (!is_dir($destDir)) {
            mkdir($destDir, 0775, true);
        }
        $finalPath = $destDir . '/' . $fileName;

        $saved = self::saveImage($rotated, $finalPath, $ext);
        imagedestroy($rotated);
        if (!$saved) {
            return null;
        }

        $thumbDir = self::thumbPath($userId, $bucket);
        if (!is_dir($thumbDir)) {
            mkdir($thumbDir, 0775, true);
        }
        $thumbName = $baseName . '.webp';
        $thumbPath = $thumbDir . '/' . $thumbName;
        self::makeThumbnail($finalPath, $thumbPath, self::THUMB_SIZE);

        return [
            'path' => self::publicPath($userId, $bucket, $fileName),
            'thumb' => self::publicThumbPath($userId, $bucket, $thumbName),
        ];
    }

    public static function deleteImage(string $publicPath): bool
    {
        $abs = self::absFromPublic($publicPath);
        if (!$abs || !is_file($abs)) {
            return false;
        }

        $baseName = pathinfo($abs, PATHINFO_FILENAME);
        $thumbPath = dirname($abs) . '/thumbs/' . $baseName . '.webp';
        if (is_file($thumbPath)) {
            unlink($thumbPath);
        }

        return unlink($abs);
    }

    public static function listImages(int $userId, string $bucket): array
    {
        $dir = self::bucketPath($userId, $bucket);
        if (!is_dir($dir)) {
            return [];
        }
        $files = glob($dir . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: [];
        $items = [];
        foreach ($files as $file) {
            $name = basename($file);
            $base = pathinfo($name, PATHINFO_FILENAME);
            $items[] = [
                'path' => self::publicPath($userId, $bucket, $name),
                'thumb' => self::publicThumbPath($userId, $bucket, $base . '.webp'),
            ];
        }
        return $items;
    }

    private static function extensionFromType(int $type): string
    {
        return match ($type) {
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_WEBP => 'webp',
            default => '',
        };
    }

    private static function createImage(string $path, string $ext)
    {
        return match ($ext) {
            'jpg', 'jpeg' => imagecreatefromjpeg($path),
            'png' => imagecreatefrompng($path),
            'webp' => imagecreatefromwebp($path),
            default => null,
        };
    }

    private static function saveImage($image, string $path, string $ext): bool
    {
        return match ($ext) {
            'jpg', 'jpeg' => imagejpeg($image, $path, 90),
            'png' => imagepng($image, $path),
            'webp' => imagewebp($image, $path, 90),
            default => false,
        };
    }

    private static function makeThumbnail(string $source, string $dest, int $size): bool
    {
        $info = @getimagesize($source);
        if (!$info) {
            return false;
        }
        [$width, $height] = $info;
        $ext = self::extensionFromType($info[2]);
        $image = self::createImage($source, $ext);
        if (!$image) {
            return false;
        }

        $ratio = min($size / $width, $size / $height);
        $newW = (int)round($width * $ratio);
        $newH = (int)round($height * $ratio);
        $thumb = imagecreatetruecolor($newW, $newH);
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        imagecopyresampled($thumb, $image, 0, 0, 0, 0, $newW, $newH, $width, $height);
        imagedestroy($image);
        $ok = imagewebp($thumb, $dest, 85);
        imagedestroy($thumb);
        return $ok;
    }

    private static function bucketPath(int $userId, string $bucket): string
    {
        if ($bucket === 'system') {
            return __DIR__ . '/../storage/media/system';
        }
        if ($bucket === 'extension') {
            return __DIR__ . '/../storage/media/extension';
        }
        if ($bucket === 'themes') {
            return __DIR__ . '/../storage/media/themes';
        }
        return __DIR__ . '/../storage/media/users/' . $userId;
    }

    private static function thumbPath(int $userId, string $bucket): string
    {
        return self::bucketPath($userId, $bucket) . '/thumbs';
    }

    private static function publicPath(int $userId, string $bucket, string $file): string
    {
        if ($bucket === 'system') {
            return '/storage/media/system/' . $file;
        }
        if ($bucket === 'extension') {
            return '/storage/media/extension/' . $file;
        }
        if ($bucket === 'themes') {
            return '/storage/media/themes/' . $file;
        }
        return '/storage/media/users/' . $userId . '/' . $file;
    }

    private static function publicThumbPath(int $userId, string $bucket, string $file): string
    {
        if ($bucket === 'system') {
            return '/storage/media/system/thumbs/' . $file;
        }
        if ($bucket === 'extension') {
            return '/storage/media/extension/thumbs/' . $file;
        }
        if ($bucket === 'themes') {
            return '/storage/media/themes/thumbs/' . $file;
        }
        return '/storage/media/users/' . $userId . '/thumbs/' . $file;
    }

    private static function absFromPublic(string $public): ?string
    {
        $public = trim($public);
        if (!str_starts_with($public, '/storage/media/')) {
            return null;
        }
        $abs = __DIR__ . '/..' . $public;
        $real = realpath($abs);
        $base = realpath(__DIR__ . '/../storage/media');
        if ($real === false || $base === false || !str_starts_with($real, $base)) {
            return null;
        }
        return $real;
    }
}
