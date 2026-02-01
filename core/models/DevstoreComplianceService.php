<?php
namespace Core\Models;

class DevstoreComplianceService
{
    public static function inspectPackage(string $extractDir, array $manifest): array
    {
        $violations = [];

        if (!isset($manifest['files']) || !$manifest['files']) {
            $violations[] = 'missing_files';
        } else {
            if (!self::validateTargets($manifest['files'], $manifest['package_type'], $manifest['key'])) {
                $violations[] = 'invalid_targets';
            }
            if (!self::validateSources($extractDir, $manifest['files'])) {
                $violations[] = 'missing_files';
            }
        }

        if (!self::hasPreviewImage($extractDir)) {
            $violations[] = 'missing_preview';
        }

        $scan = self::scanPhpFiles($extractDir);
        if ($scan) {
            $violations = array_merge($violations, $scan);
        }

        if (!self::validateSqlFiles($extractDir, $manifest, $manifest['package_type'], $manifest['key'])) {
            $violations[] = 'invalid_sql';
        }

        $violations = array_values(array_unique($violations));
        $report = self::buildReport($violations);
        return [
            'ok' => $violations === [],
            'violations' => $violations,
            'report' => $report,
        ];
    }

    public static function loadManifest(string $path): ?array
    {
        $content = file_get_contents($path);
        if ($content === false) {
            return null;
        }
        $content = ltrim($content, "\xEF\xBB\xBF"); // remove UTF-8 BOM
        $prev = libxml_disable_entity_loader(true);
        $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NONET);
        libxml_disable_entity_loader($prev);
        if ($xml === false) {
            return null;
        }

        $packageType = strtolower(trim((string)($xml->type ?? '')));
        if (!in_array($packageType, ['extension', 'theme'], true)) {
            return null;
        }

        $key = strtolower(trim((string)($xml->key ?? '')));
        $name = trim((string)($xml->name ?? ''));
        $version = trim((string)($xml->version ?? ''));
        if ($key === '' || $name === '' || $version === '') {
            return null;
        }
        if (!preg_match('/^[a-z0-9_-]+$/', $key)) {
            return null;
        }

        $files = [];
        if (isset($xml->files->file)) {
            foreach ($xml->files->file as $file) {
                $source = trim((string)($file['src'] ?? ''));
                $dest = trim((string)($file['dest'] ?? ''));
                if ($source !== '' && $dest !== '') {
                    $files[] = ['source' => $source, 'destination' => $dest];
                }
            }
        }

        $installSql = '';
        if (isset($xml->script)) {
            $installSql = trim((string)($xml->script['file'] ?? ''));
        }

        $uninstallSql = '';
        if (isset($xml->uninstall)) {
            $uninstallSql = trim((string)($xml->uninstall['script'] ?? ''));
        }

        return [
            'package_type' => $packageType,
            'name' => $name,
            'key' => $key,
            'version' => $version,
            'author' => trim((string)($xml->author ?? '')),
            'description' => trim((string)($xml->description ?? '')),
            'files' => $files,
            'install_sql' => $installSql,
            'uninstall_sql' => $uninstallSql,
        ];
    }

    private static function validateTargets(array $files, string $type, string $key): bool
    {
        foreach ($files as $file) {
            $dest = $file['destination'] ?? '';
            if ($dest === '' || str_contains($dest, '..')) {
                return false;
            }
            // Allow both absolute and relative paths
            $normalizedDest = str_starts_with($dest, '/') ? $dest : '/' . $dest;
            if ($type === 'extension') {
                if (!str_starts_with($normalizedDest, '/extension/' . $key) && !str_starts_with($normalizedDest, '/storage/media/extension/' . $key)) {
                    return false;
                }
            } else {
                if (!str_starts_with($normalizedDest, '/themes/' . $key) && !str_starts_with($normalizedDest, '/storage/media/themes/' . $key)) {
                    return false;
                }
            }
        }
        return true;
    }

    private static function validateSources(string $extractDir, array $files): bool
    {
        foreach ($files as $file) {
            $source = $file['source'] ?? '';
            if ($source === '' || !file_exists($extractDir . '/' . $source)) {
                return false;
            }
        }
        return true;
    }

    private static function hasPreviewImage(string $extractDir): bool
    {
        return is_file($extractDir . '/preview.png') || is_file($extractDir . '/preview.jpg');
    }

    private static function scanPhpFiles(string $root): array
    {
        $violations = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if (strtolower($file->getExtension()) !== 'php') {
                continue;
            }
            $content = file_get_contents($file->getPathname());
            if ($content === false) {
                continue;
            }
            if (preg_match('/\bnew\s+PDO\b/i', $content) || preg_match('/\bmysqli_\w+\b/i', $content)) {
                $violations[] = 'db_connection';
            }
            if (preg_match('/\brole_id\b\s*==\s*\d+/i', $content)) {
                $violations[] = 'role_check';
            }
            // Note: Reading core tables is allowed for extension
        }
        return array_values(array_unique($violations));
    }

    private static function validateSqlFiles(string $root, array $manifest, string $type, string $key): bool
    {
        foreach (['install_sql', 'uninstall_sql'] as $field) {
            $path = $manifest[$field] ?? '';
            if ($path === '') {
                continue;
            }
            $full = $root . '/' . $path;
            if (!is_file($full)) {
                return false;
            }
            $sql = file_get_contents($full);
            if ($sql === false) {
                return false;
            }
            if (!self::sqlAllowsNamespace($sql, $type, $key)) {
                return false;
            }
        }
        return true;
    }

    private static function sqlAllowsNamespace(string $sql, string $type, string $key): bool
    {
        $prefix = $type === 'extension' ? '#__ext_' . $key . '_' : '#__theme_' . $key . '_';
        $lines = preg_split('/;\s*\n/', $sql) ?: [];
        foreach ($lines as $line) {
            if (preg_match('/\b(INSERT|UPDATE|DELETE|CREATE|ALTER|DROP|RENAME|TRUNCATE)\b/i', $line)) {
                if (str_contains($line, '#__') && !str_contains($line, $prefix)) {
                    return false;
                }
            }
        }
        return true;
    }

    private static function buildReport(array $violations): string
    {
        if (!$violations) {
            return 'No issues found.';
        }

        $lines = ["Compliance checks failed:"];
        foreach ($violations as $violation) {
            $desc = match ($violation) {
                'missing_files' => 'Required files section missing or files not found in package',
                'invalid_targets' => 'File destinations are invalid or outside allowed paths',
                'missing_preview' => 'Preview image (preview.png or preview.jpg) missing from package root',
                'db_connection' => 'Direct database connections found in PHP files',
                'role_check' => 'Hardcoded role checks found in PHP files',
                'invalid_sql' => 'SQL files missing or contain invalid table prefixes',
                default => $violation,
            };
            $lines[] = '- ' . $desc;
        }
        return implode("\n", $lines);
    }
}
