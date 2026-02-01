<?php
namespace Core;

class DocumentService
{
    private const MAX_SIZE = 10485760;
    private const DENY = ['php','phtml','phar','js','mjs','exe','dll','bat','cmd','sh','cgi','pl','py','rb'];
    private const ALLOW = ['pdf','txt','rtf','doc','docx','xls','xlsx','ppt','pptx','csv','odt','ods','odp','zip'];
    private const MIME_MAP = [
        'pdf' => ['application/pdf'],
        'txt' => ['text/plain'],
        'rtf' => ['application/rtf', 'text/rtf'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'xls' => ['application/vnd.ms-excel'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'ppt' => ['application/vnd.ms-powerpoint'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
        'csv' => ['text/csv', 'application/csv', 'application/vnd.ms-excel'],
        'odt' => ['application/vnd.oasis.opendocument.text'],
        'ods' => ['application/vnd.oasis.opendocument.spreadsheet'],
        'odp' => ['application/vnd.oasis.opendocument.presentation'],
        'zip' => ['application/zip', 'application/x-zip-compressed'],
    ];

    public static function upload(array $file, int $userId): array
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['ok' => false, 'error' => 'Invalid upload.'];
        }
        if ((int)$file['size'] > self::MAX_SIZE) {
            return ['ok' => false, 'error' => 'File too large.'];
        }

        $original = $file['name'] ?? '';
        if (self::containsDeniedExtension($original)) {
            return ['ok' => false, 'error' => 'Invalid file type.'];
        }
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        if ($ext === '' || in_array($ext, self::DENY, true)) {
            return ['ok' => false, 'error' => 'Invalid file type.'];
        }
        if (!in_array($ext, self::ALLOW, true)) {
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

        $safeName = self::sanitizeFileName(pathinfo($original, PATHINFO_FILENAME)) . '.' . $ext;
        $destDir = __DIR__ . '/../storage/media/users/' . $userId . '/files';
        if (!is_dir($destDir)) {
            mkdir($destDir, 0775, true);
        }
        $finalName = self::uniqueName($destDir, $safeName);
        $finalPath = $destDir . '/' . $finalName;
        if (!rename($tmpName, $finalPath)) {
            @unlink($tmpName);
            return ['ok' => false, 'error' => 'Move failed.'];
        }

        $mime = mime_content_type($finalPath) ?: 'application/octet-stream';
        if (!self::mimeMatchesExtension($mime, $ext)) {
            @unlink($finalPath);
            return ['ok' => false, 'error' => 'Invalid file type.'];
        }
        return [
            'ok' => true,
            'stored_name' => $finalName,
            'original_name' => $original,
            'display_name' => pathinfo($original, PATHINFO_FILENAME),
            'mime_type' => $mime,
            'file_ext' => $ext,
            'file_size' => (int)$file['size'],
            'storage_path' => 'media/users/' . $userId . '/files/' . $finalName,
        ];
    }

    public static function sanitizeFileName(string $name): string
    {
        $name = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $name);
        $name = trim($name, '-');
        return $name === '' ? 'file' : strtolower($name);
    }

    public static function sanitizeStoredName(string $name, string $ext): string
    {
        $name = trim($name);
        $base = pathinfo($name, PATHINFO_FILENAME);
        $base = self::sanitizeFileName($base);
        return $base . '.' . $ext;
    }

    public static function containsDeniedExtension(string $name): bool
    {
        $parts = explode('.', strtolower($name));
        foreach ($parts as $part) {
            if (in_array($part, self::DENY, true)) {
                return true;
            }
        }
        return false;
    }

    public static function mimeMatchesExtension(string $mime, string $ext): bool
    {
        $mime = strtolower($mime);
        $allowed = self::MIME_MAP[$ext] ?? [];
        foreach ($allowed as $allowedMime) {
            if ($mime === strtolower($allowedMime)) {
                return true;
            }
        }
        return false;
    }

    public static function fileExists(array $doc): bool
    {
        $path = $doc['storage_path'] ?? '';
        if (!self::isSafeStoragePath($path)) {
            return false;
        }
        $full = __DIR__ . '/../storage/' . ltrim($path, '/');
        return is_file($full);
    }

    public static function rename(array $doc, string $newName): array
    {
        $ext = strtolower((string)($doc['file_ext'] ?? ''));
        if ($ext === '' || !in_array($ext, self::ALLOW, true)) {
            return ['ok' => false, 'error' => 'Invalid file type.'];
        }
        if (self::containsDeniedExtension($newName)) {
            return ['ok' => false, 'error' => 'Invalid file type.'];
        }

        $safeName = self::sanitizeStoredName($newName, $ext);
        $current = (string)($doc['stored_name'] ?? '');
        if ($safeName === $current) {
            return ['ok' => true, 'stored_name' => $current, 'storage_path' => $doc['storage_path'] ?? ''];
        }

        $storagePath = (string)($doc['storage_path'] ?? '');
        if (!self::isSafeStoragePath($storagePath)) {
            return ['ok' => false, 'error' => 'Invalid storage path.'];
        }
        $dir = dirname($storagePath);
        $fullDir = __DIR__ . '/../storage/' . ltrim($dir, '/');
        if (!is_dir($fullDir)) {
            return ['ok' => false, 'error' => 'Storage missing.'];
        }
        $targetName = self::uniqueName($fullDir, $safeName);
        $oldFull = __DIR__ . '/../storage/' . ltrim($storagePath, '/');
        $newFull = $fullDir . '/' . $targetName;
        if (!is_file($oldFull)) {
            return ['ok' => false, 'error' => 'File missing.'];
        }
        if (!rename($oldFull, $newFull)) {
            return ['ok' => false, 'error' => 'Rename failed.'];
        }

        return [
            'ok' => true,
            'stored_name' => $targetName,
            'storage_path' => trim($dir, '/') . '/' . $targetName,
        ];
    }

    private static function uniqueName(string $dir, string $name): string
    {
        $path = $dir . '/' . $name;
        if (!file_exists($path)) {
            return $name;
        }
        $base = pathinfo($name, PATHINFO_FILENAME);
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $i = 2;
        while (file_exists($dir . '/' . $base . '-' . $i . '.' . $ext)) {
            $i++;
        }
        return $base . '-' . $i . '.' . $ext;
    }

    private static function isSafeStoragePath(string $path): bool
    {
        if ($path === '' || str_contains($path, '..')) {
            return false;
        }
        return str_starts_with($path, 'media/users/');
    }
}
