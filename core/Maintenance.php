<?php
namespace Core;

class Maintenance
{
    public static function cleanupTemp(int $hours = 24): array
    {
        $path = __DIR__ . '/../storage/tmp';
        return self::cleanupByAge($path, $hours, ['installer', 'devstore']);
    }

    public static function cleanupUpdates(int $hours = 24): array
    {
        $path = __DIR__ . '/../storage/updates';
        return self::cleanupByAge($path, $hours, [], 'active.lock');
    }

    public static function truncateLogs(): array
    {
        $logs = Logger::files();
        $cleared = [];
        foreach ($logs as $channel => $file) {
            $path = __DIR__ . '/../storage/logs/' . $file;
            if (is_file($path)) {
                file_put_contents($path, '');
                $cleared[] = $file;
            }
        }
        return $cleared;
    }

    public static function cleanupOrphanThumbnails(): array
    {
        $bases = [
            __DIR__ . '/../storage/media/users',
            __DIR__ . '/../storage/media/system',
            __DIR__ . '/../storage/media/extension',
            __DIR__ . '/../storage/media/themes',
        ];

        $removed = 0;
        foreach ($bases as $base) {
            if (!is_dir($base)) {
                continue;
            }
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if (!$file->isDir() || $file->getFilename() !== 'thumbs') {
                    continue;
                }
                $thumbDir = $file->getPathname();
                $parent = dirname($thumbDir);
                foreach (glob($thumbDir . '/*.*') ?: [] as $thumb) {
                    $baseName = pathinfo($thumb, PATHINFO_FILENAME);
                    $found = false;
                    foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
                        if (is_file($parent . '/' . $baseName . '.' . $ext)) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        @unlink($thumb);
                        $removed++;
                    }
                }
            }
        }

        return ['removed' => $removed];
    }

    public static function scanOrphanMediaReferences(): array
    {
        $referenced = self::collectReferencedMedia();
        $storagePaths = self::collectStorageMedia();
        $orphans = array_diff($storagePaths, $referenced);

        return [
            'total' => count($storagePaths),
            'orphans' => count($orphans),
        ];
    }

    private static function collectReferencedMedia(): array
    {
        $paths = [];

        $stmt = Connection::prepare('SELECT avatar_path, cover_path FROM #__user_profiles');
        $stmt->execute();
        foreach ($stmt->fetchAll() as $row) {
            foreach (['avatar_path', 'cover_path'] as $field) {
                if (!empty($row[$field])) {
                    $paths[] = $row[$field];
                }
            }
        }

        $tables = [
            ['#__pages', 'featured_image_path', 'content_xml'],
            ['#__posts', 'featured_image_path', 'content_xml'],
        ];

        foreach ($tables as $table) {
            $stmt = Connection::prepare('SELECT ' . implode(', ', $table) . ' FROM ' . $table[0]);
            $stmt->execute();
            foreach ($stmt->fetchAll() as $row) {
                foreach ($row as $value) {
                    if (!empty($value) && is_string($value)) {
                        $paths = array_merge($paths, self::extractMediaPaths($value));
                    }
                }
            }
        }

        $paths = array_values(array_unique(array_filter($paths)));
        return $paths;
    }

    private static function extractMediaPaths(string $text): array
    {
        $matches = [];
        preg_match_all('/media\/[a-z0-9_\/-]+\.[a-z0-9]+/i', $text, $matches);
        return $matches[0] ?? [];
    }

    private static function collectStorageMedia(): array
    {
        $base = __DIR__ . '/../storage/media';
        if (!is_dir($base)) {
            return [];
        }
        $paths = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                continue;
            }
            $relative = str_replace($base . '/', 'media/', $file->getPathname());
            if (str_contains($relative, '/thumbs/')) {
                continue;
            }
            $paths[] = $relative;
        }
        return array_values(array_unique($paths));
    }

    private static function cleanupByAge(string $path, int $hours, array $skipDirs = [], string $activeMarker = ''): array
    {
        if (!is_dir($path)) {
            return ['removed' => 0, 'bytes' => 0];
        }

        $cutoff = time() - ($hours * 3600);
        $removed = 0;
        $bytes = 0;

        $items = scandir($path) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            if (in_array($item, $skipDirs, true)) {
                continue;
            }
            $full = $path . '/' . $item;
            if (is_dir($full)) {
                if ($activeMarker !== '' && is_file($full . '/' . $activeMarker)) {
                    continue;
                }
                if (filemtime($full) >= $cutoff) {
                    continue;
                }
                $size = self::dirSize($full);
                self::removeDir($full);
                $removed++;
                $bytes += $size;
            } elseif (is_file($full)) {
                if (filemtime($full) >= $cutoff) {
                    continue;
                }
                $size = filesize($full) ?: 0;
                if (@unlink($full)) {
                    $removed++;
                    $bytes += $size;
                }
            }
        }

        return ['removed' => $removed, 'bytes' => $bytes];
    }

    private static function removeDir(string $dir): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                @rmdir($file->getPathname());
            } else {
                @unlink($file->getPathname());
            }
        }
        @rmdir($dir);
    }

    private static function dirSize(string $dir): int
    {
        $size = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }
        return $size;
    }
}
