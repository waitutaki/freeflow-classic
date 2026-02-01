<?php
namespace Core;

use Core\Models\ApiRoutesModel;
use Core\Models\extensionModel;
use Core\Models\ThemesModel;

class PackageInstaller
{
    public static function installFromUpload(array $file, string $type, int $userId, bool $override): array
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['ok' => false, 'error' => 'Invalid upload.'];
        }
        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        if ($ext !== 'zip') {
            return ['ok' => false, 'error' => 'Invalid package.'];
        }

        $stage = self::createStageDir();
        if ($stage === '') {
            return ['ok' => false, 'error' => 'Unable to stage package.'];
        }

        $zipPath = $stage . '/package.zip';
        if (!move_uploaded_file($file['tmp_name'], $zipPath)) {
            self::cleanup($stage);
            return ['ok' => false, 'error' => 'Upload failed.'];
        }

        return self::installFromStagedZip($zipPath, $stage, $type, $userId, $override);
    }

    public static function installFromPath(string $zipPath, string $type, int $userId, bool $override): array
    {
        if (!is_file($zipPath)) {
            return ['ok' => false, 'error' => 'Package file not found.'];
        }

        $stage = self::createStageDir();
        if ($stage === '') {
            return ['ok' => false, 'error' => 'Unable to stage package.'];
        }

        $stagedZip = $stage . '/package.zip';
        if (!copy($zipPath, $stagedZip)) {
            self::cleanup($stage);
            return ['ok' => false, 'error' => 'Copy failed.'];
        }

        return self::installFromStagedZip($stagedZip, $stage, $type, $userId, $override);
    }

    private static function installFromStagedZip(string $zipPath, string $stage, string $type, int $userId, bool $override): array
    {
        Logger::info('install', 'Starting installation from staged ZIP', [
            'type' => $type,
            'zip_path' => $zipPath,
            'stage_dir' => $stage,
            'user_id' => $userId,
            'override' => $override,
        ]);

        $extractDir = $stage . '/extract';
        Logger::info('install', 'Extracting package to directory', [
            'extract_dir' => $extractDir,
        ]);

        if (!self::safeExtract($zipPath, $extractDir)) {
            Logger::error('install', 'Extraction failed', [
                'zip_path' => $zipPath,
                'extract_dir' => $extractDir,
            ]);
            self::cleanup($stage);
            return ['ok' => false, 'error' => 'Invalid archive.'];
        }

        Logger::info('install', 'Package extracted successfully', [
            'extract_dir' => $extractDir,
        ]);

        $manifestPath = $extractDir . '/manifest.xml';
        Logger::info('install', 'Checking for manifest file', [
            'manifest_path' => $manifestPath,
        ]);

        if (!is_file($manifestPath)) {
            Logger::error('install', 'Manifest file missing', [
                'manifest_path' => $manifestPath,
            ]);
            self::cleanup($stage);
            return ['ok' => false, 'error' => 'manifest.xml missing.'];
        }

        Logger::info('install', 'Loading manifest file', [
            'manifest_path' => $manifestPath,
        ]);

        $manifest = self::loadManifest($manifestPath);
        if (!$manifest) {
            Logger::error('install', 'Manifest file invalid', [
                'manifest_path' => $manifestPath,
            ]);
            self::cleanup($stage);
            return ['ok' => false, 'error' => 'manifest.xml invalid.'];
        }

        Logger::info('install', 'Manifest loaded successfully', [
            'package_type' => $manifest['package_type'],
            'key' => $manifest['key'],
            'version' => $manifest['version'],
        ]);

        if ($manifest['package_type'] !== $type) {
            Logger::error('install', 'Package type mismatch', [
                'expected_type' => $type,
                'actual_type' => $manifest['package_type'],
            ]);
            self::cleanup($stage);
            return ['ok' => false, 'error' => 'Package type mismatch.'];
        }

        // Skip signature verification if no signature is present (for backward compatibility)
        if (($manifest['signature'] ?? '') !== '') {
            Logger::info('install', 'Signature present, starting verification', [
                'key' => $manifest['key'],
            ]);

            // Get the SHA256 hash from the manifest (this should be the hash of the ZIP file)
            $packageHash = $manifest['sha256'] ?? '';
            if ($packageHash === '') {
                Logger::error('install', 'Package signature present but no SHA256 hash in manifest', [
                    'type' => $type,
                    'key' => $manifest['key'],
                ]);
                self::cleanup($stage);
                return ['ok' => false, 'error' => 'Package hash missing for signature verification.'];
            }
            
            if (!self::verifySignature($manifestPath, $manifest['signature'] ?? '', $manifest['signature_algo'] ?? '')) {
                Logger::error('install', 'Package signature invalid', [
                    'type' => $type,
                    'key' => $manifest['key'],
                ]);
                self::cleanup($stage);
                return ['ok' => false, 'error' => 'Signature verification failed.'];
            }

            Logger::info('install', 'Signature verification passed', [
                'key' => $manifest['key'],
            ]);
        } else {
            Logger::warning('install', 'Package has no signature - proceeding without verification', [
                'type' => $type,
                'key' => $manifest['key'],
            ]);
        }

        $key = $manifest['key'];
        Logger::info('install', 'Validating target paths', [
            'key' => $key,
            'file_count' => count($manifest['files']),
        ]);

        $targetsOk = self::validateTargets($manifest['files'], $type, $key);
        if (!$targetsOk) {
            Logger::error('install', 'Invalid target paths', [
                'key' => $key,
            ]);
            self::cleanup($stage);
            return ['ok' => false, 'error' => 'Invalid target paths.'];
        }

        Logger::info('install', 'Target paths validated successfully', [
            'key' => $key,
        ]);

        Logger::info('install', 'Scanning PHP files for safety checks', [
            'extract_dir' => $extractDir,
        ]);

        $scan = self::scanPhpFiles($extractDir);
        if ($scan['violations'] && !$override) {
            Logger::error('install', 'Safety checks failed', [
                'violations' => $scan['violations'],
                'extract_dir' => $extractDir,
            ]);
            self::cleanup($stage);
            return ['ok' => false, 'error' => 'Safety checks failed.'];
        }

        Logger::info('install', 'Safety checks passed', [
            'extract_dir' => $extractDir,
        ]);

        Logger::info('install', 'Validating SQL files', [
            'extract_dir' => $extractDir,
        ]);

        if (!self::validateSqlFiles($extractDir, $manifest, $type, $key)) {
            Logger::error('install', 'Invalid SQL script', [
                'extract_dir' => $extractDir,
            ]);
            self::cleanup($stage);
            return ['ok' => false, 'error' => 'Invalid SQL script.'];
        }

        Logger::info('install', 'SQL files validated successfully', [
            'extract_dir' => $extractDir,
        ]);

        Logger::info('install', 'Deploying files', [
            'extract_dir' => $extractDir,
            'file_count' => count($manifest['files']),
        ]);

        if (!self::deployFiles($extractDir, $manifest['files'])) {
            Logger::error('install', 'File deployment failed', [
                'extract_dir' => $extractDir,
            ]);
            self::cleanup($stage);
            return ['ok' => false, 'error' => 'Deploy failed.'];
        }

        Logger::info('install', 'Files deployed successfully', [
            'extract_dir' => $extractDir,
        ]);

        Logger::info('install', 'Running install SQL', [
            'extract_dir' => $extractDir,
        ]);

        if (!self::runInstallSql($extractDir, $manifest, $type, $key)) {
            Logger::error('install', 'SQL install failed', [
                'extract_dir' => $extractDir,
            ]);
            self::cleanup($stage);
            return ['ok' => false, 'error' => 'SQL install failed.'];
        }

        Logger::info('install', 'SQL install completed successfully', [
            'extract_dir' => $extractDir,
        ]);

        Logger::info('install', 'Storing uninstall SQL', [
            'extract_dir' => $extractDir,
        ]);

        self::storeUninstallSql($extractDir, $manifest, $type, $key);

        Logger::info('install', 'Uninstall SQL stored successfully', [
            'extract_dir' => $extractDir,
        ]);

        if ($type === 'extension') {
            Logger::info('install', 'Registering extension', [
                'key' => $key,
            ]);

            extensionModel::upsert([
                'ext_key' => $key,
                'type' => $manifest['type'],
                'name' => $manifest['name'],
                'version' => $manifest['version'],
                'is_active' => 0,
                'is_core' => 0,
                'author' => $manifest['author'],
                'description' => $manifest['description'],
                'admin_menu_xml' => $manifest['admin_menu_xml'] ?? null,
                'installed_at' => date('Y-m-d H:i:s'),
                'installed_by' => $userId,
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_by' => $userId,
            ]);

            Logger::info('install', 'Registering API routes', [
                'key' => $key,
                'route_count' => count($manifest['api_routes']),
            ]);

            self::registerApiRoutes($manifest['api_routes'], $key, $userId);

            Logger::info('install', 'Registering admin menu', [
                'key' => $key,
                'menu_item_count' => count($manifest['admin_menu']),
            ]);

            self::registerAdminMenu($manifest['admin_menu'], $key);
        } else {
            Logger::info('install', 'Registering theme', [
                'key' => $key,
            ]);

            ThemesModel::upsert([
                'theme_key' => $key,
                'name' => $manifest['name'],
                'context' => $manifest['context'],
                'is_enabled' => 1,
                'is_active' => 0,
                'is_core' => 0,
                'version' => $manifest['version'],
                'author' => $manifest['author'],
                'description' => $manifest['description'],
                'installed_at' => date('Y-m-d H:i:s'),
                'installed_by' => $userId,
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_by' => $userId,
            ]);

            Logger::info('install', 'Syncing theme positions', [
                'key' => $key,
                'context' => $manifest['context'],
            ]);

            Theme::syncPositions($key, $manifest['context']);
        }

        Logger::info('install', 'Cleaning up staging directory', [
            'stage_dir' => $stage,
        ]);

        self::cleanup($stage);

        Logger::info('install', 'Installation completed successfully', [
            'type' => $type,
            'key' => $key,
        ]);

        return ['ok' => true, 'key' => $key];
    }

    public static function uninstallExtension(string $extKey, int $userId): array
    {
        $extKey = strtolower($extKey);
        $ext = extensionModel::findByKey($extKey);
        if (!$ext) {
            return ['ok' => false, 'error' => 'Extension not found.'];
        }
        if (!empty($ext['is_core'])) {
            return ['ok' => false, 'error' => 'Core extension cannot be removed.'];
        }

        self::removeExtensionFiles($extKey);
        self::runUninstallSql($extKey, 'extension');
        ApiRoutesModel::deleteForExtension($extKey);
        self::removeAdminMenu($extKey);
        extensionModel::delete($extKey);

        Logger::info('admin', 'Extension uninstalled', ['ext_key' => $extKey, 'user_id' => $userId]);
        return ['ok' => true];
    }

    public static function uninstallTheme(array $theme, int $userId): array
    {
        if (!empty($theme['is_core'])) {
            return ['ok' => false, 'error' => 'Core themes cannot be removed.'];
        }

        $themeKey = strtolower((string)($theme['theme_key'] ?? ''));
        if ($themeKey === '') {
            return ['ok' => false, 'error' => 'Theme key missing.'];
        }

        self::removeThemeFiles($themeKey);
        self::runUninstallSql($themeKey, 'theme');
        self::deleteThemeRow((int)$theme['id']);

        Logger::info('admin', 'Theme uninstalled', ['theme_key' => $themeKey, 'user_id' => $userId]);
        return ['ok' => true];
    }

    /**
     * Create staging directory for package installation/updates
     *
     * Per specification: All packages must be staged to storage/tmp
     * Using storage/updates for consistency with UpdateService
     *
     * @return string Path to staging directory or empty string on failure
     */
    private static function createStageDir(): string
    {
        $base = __DIR__ . '/../storage/updates';
        if (!is_dir($base) && !mkdir($base, 0775, true)) {
            return '';
        }
        $dir = $base . '/' . date('YmdHis') . '_' . bin2hex(random_bytes(4));
        if (!mkdir($dir, 0775, true)) {
            return '';
        }
        return $dir;
    }

    private static function safeExtract(string $zipPath, string $dest): bool
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return false;
        }
        if (!mkdir($dest, 0775, true) && !is_dir($dest)) {
            $zip->close();
            return false;
        }
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if (!$stat || !isset($stat['name'])) {
                continue;
            }
            $entry = $stat['name'];
            if (str_contains($entry, '..') || str_starts_with($entry, '/')) {
                $zip->close();
                return false;
            }
        }
        $zip->extractTo($dest);
        $zip->close();
        return true;
    }

    private static function loadManifest(string $path): ?array
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

        $rootName = $xml->getName();
        $packageType = $rootName === 'extension' ? 'extension' : ($rootName === 'theme' ? 'theme' : '');
        if ($packageType === '') {
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

        $apiRoutes = [];
        if (isset($xml->apiRoutes->route)) {
            foreach ($xml->apiRoutes->route as $route) {
                $apiRoutes[] = [
                    'route_path' => trim((string)($route['path'] ?? '')),
                    'http_method' => strtoupper(trim((string)($route['method'] ?? 'GET'))),
                    'controller' => trim((string)($route['controller'] ?? '')),
                    'action' => trim((string)($route['action'] ?? '')),
                    'is_public' => (int)($route['public'] ?? 0),
                    'required_permission' => trim((string)($route['permission'] ?? '')),
                ];
            }
        }

        $adminMenu = [];
        if (isset($xml->adminMenu->item)) {
            foreach ($xml->adminMenu->item as $item) {
                $adminMenu[] = [
                    'item_key' => trim((string)($item['key'] ?? '')),
                    'parent_key' => trim((string)($item['parent'] ?? '')),
                    'label_key' => trim((string)($item['label_key'] ?? '')),
                    'icon' => trim((string)($item['icon'] ?? '')),
                    'route' => trim((string)($item['route'] ?? '')),
                    'sort_order' => (int)($item['sort'] ?? 0),
                    'perm_key' => trim((string)($item['perm_key'] ?? '')),
                ];
            }
        }

        $signatureNode = $xml->signature ?? null;
        $signature = $signatureNode ? trim((string)$signatureNode) : '';
        $signatureAlgo = $signatureNode ? trim((string)($signatureNode['algorithm'] ?? '')) : '';

        $context = trim((string)($xml->context ?? 'site'));
        if ($packageType === 'theme' && !in_array($context, ['site', 'admin'], true)) {
            return null;
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
            'type' => trim((string)($xml->type ?? 'component')),
            'name' => $name,
            'key' => $key,
            'version' => $version,
            'author' => trim((string)($xml->author ?? '')),
            'description' => trim((string)($xml->description ?? '')),
            'context' => $context,
            'files' => $files,
            'api_routes' => $apiRoutes,
            'admin_menu' => $adminMenu,
            'admin_menu_xml' => $xml->adminMenu ? $xml->adminMenu->asXML() : null,
            'install_sql' => $installSql,
            'uninstall_sql' => $uninstallSql,
            'signature' => $signature,
            'signature_algo' => $signatureAlgo,
        ];
    }

    private static function verifySignature(string $manifestPath, string $signature, string $algo): bool
    {
        if ($signature === '') {
            return false;
        }
        
        // Load the manifest to extract key information for payload verification
        $manifest = self::loadManifest($manifestPath);
        if (!$manifest) {
            return false;
        }
        
        // Build the payload string that should have been signed
        // This matches the format used by DevstoreSigningService: type|key|version|sha256
        // The sha256 should be the hash of the ZIP file, not the manifest
        $payload = $manifest['package_type'] . '|' . $manifest['key'] . '|' . $manifest['version'] . '|' . $manifest['sha256'];
        
        $publicKey = file_get_contents(__DIR__ . '/keys/freeflow_public.pem');
        if ($publicKey === false) {
            return false;
        }

        $sig = base64_decode($signature, true);
        if ($sig === false) {
            return false;
        }
        $algo = strtolower($algo) === 'sha256' ? OPENSSL_ALGO_SHA256 : OPENSSL_ALGO_SHA256;
        return openssl_verify($payload, $sig, $publicKey, $algo) === 1;
    }

    /**
     * Calculate SHA256 hash of the package file (excluding signature)
     * This is used to verify the package integrity
     */
    private static function calculatePackageHash(string $manifestPath): string
    {
        // For now, we'll hash the manifest content (excluding signature node)
        // In a full implementation, this would hash the entire package ZIP file
        $content = file_get_contents($manifestPath);
        if ($content === false) {
            return '';
        }
        
        // Remove signature node for hashing
        $content = preg_replace('/<signature[^>]*>.*?<\/signature>/s', '', $content);
        return hash('sha256', $content);
    }

    private static function validateTargets(array $files, string $type, string $key): bool
    {
        if (!$files) {
            return false;
        }
        foreach ($files as $file) {
            $dest = $file['destination'] ?? '';
            if ($dest === '' || str_contains($dest, '..') || !str_starts_with($dest, '/')) {
                return false;
            }
            if ($type === 'extension') {
                if (!str_starts_with($dest, '/extension/' . $key) && !str_starts_with($dest, '/storage/media/extension/' . $key)) {
                    return false;
                }
            } else {
                if (!str_starts_with($dest, '/themes/' . $key) && !str_starts_with($dest, '/storage/media/themes/' . $key)) {
                    return false;
                }
            }
        }
        return true;
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
            if (preg_match('/\\bnew\\s+PDO\\b/i', $content) || preg_match('/\\bmysqli_\\w+\\b/i', $content)) {
                $violations[] = 'db_connection';
            }
            if (preg_match('/\\brole_id\\b\\s*==\\s*\\d+/i', $content)) {
                $violations[] = 'role_check';
            }
            if (preg_match('/#_+users|#_+roles|#_+permissions/i', $content)) {
                $violations[] = 'core_table';
            }
        }
        return ['violations' => array_values(array_unique($violations))];
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
        $lines = preg_split('/;\\s*\\n/', $sql) ?: [];
        foreach ($lines as $line) {
            if (preg_match('/\\b(INSERT|UPDATE|DELETE|CREATE|ALTER|DROP|RENAME|TRUNCATE)\\b/i', $line)) {
                if (str_contains($line, '#__') && !str_contains($line, $prefix)) {
                    return false;
                }
            }
        }
        return true;
    }

    private static function deployFiles(string $root, array $files): bool
    {
        $base = dirname(__DIR__);
        foreach ($files as $file) {
            $source = $root . '/' . $file['source'];
            $dest = $base . $file['destination'];
            if (!file_exists($source)) {
                return false;
            }
            if (is_dir($source)) {
                self::copyDir($source, $dest);
            } else {
                $dir = dirname($dest);
                if (!is_dir($dir)) {
                    mkdir($dir, 0775, true);
                }
                if (!copy($source, $dest)) {
                    return false;
                }
            }
        }
        return true;
    }

    private static function runInstallSql(string $root, array $manifest, string $type, string $key): bool
    {
        $path = $manifest['install_sql'] ?? '';
        if ($path === '') {
            return true;
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
        foreach (self::splitSql($sql) as $statement) {
            $trim = trim($statement);
            if ($trim === '') {
                continue;
            }
            Connection::query($trim);
        }
        return true;
    }

    private static function runUninstallSql(string $key, string $type): void
    {
        $path = __DIR__ . '/../storage/tmp/' . $type . '_' . $key . '_uninstall.sql';
        if (!is_file($path)) {
            return;
        }
        $sql = file_get_contents($path);
        if ($sql === false || !self::sqlAllowsNamespace($sql, $type, $key)) {
            return;
        }
        foreach (self::splitSql($sql) as $statement) {
            $trim = trim($statement);
            if ($trim === '') {
                continue;
            }
            Connection::query($trim);
        }
    }

    private static function splitSql(string $sql): array
    {
        $lines = preg_split('/;\\s*\\n/', $sql) ?: [];
        return array_filter($lines, fn($line) => trim($line) !== '');
    }

    private static function storeUninstallSql(string $root, array $manifest, string $type, string $key): void
    {
        $path = $manifest['uninstall_sql'] ?? '';
        if ($path === '') {
            return;
        }
        $full = $root . '/' . $path;
        if (!is_file($full)) {
            return;
        }
        $dir = __DIR__ . '/../storage/tmp';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $target = $dir . '/' . $type . '_' . $key . '_uninstall.sql';
        copy($full, $target);
    }

    private static function registerApiRoutes(array $routes, string $extKey, int $userId): void
    {
        ApiRoutesModel::deleteForExtension($extKey);
        foreach ($routes as $route) {
            if ($route['route_path'] === '' || !preg_match('/^[a-z0-9_\\/-]+$/i', $route['route_path'])) {
                continue;
            }
            if (!in_array($route['http_method'], ['GET', 'POST'], true)) {
                continue;
            }
            if ($route['controller'] === '' || $route['action'] === '') {
                continue;
            }
            ApiRoutesModel::insert([
                'ext_key' => $extKey,
                'route_path' => $route['route_path'],
                'http_method' => $route['http_method'],
                'controller' => $route['controller'],
                'action' => $route['action'],
                'is_public' => $route['is_public'],
                'required_permission' => $route['required_permission'],
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => $userId,
            ]);
        }
    }

    private static function registerAdminMenu(array $items, string $extKey): void
    {
        if (!$items) {
            return;
        }
        self::removeAdminMenu($extKey);
        $map = [];
        foreach ($items as $item) {
            if ($item['item_key'] === '' || $item['label_key'] === '' || $item['route'] === '') {
                continue;
            }
            $map[$item['item_key']] = $item;
        }
        foreach ($map as $key => $item) {
            if ($item['parent_key'] !== '') {
                continue;
            }
            self::insertMenuEntry($extKey, $item, null);
        }
        $pending = true;
        while ($pending) {
            $pending = false;
            foreach ($map as $key => $item) {
                if ($item['parent_key'] === '') {
                    continue;
                }
                $parentId = self::findMenuId($extKey, $item['parent_key']);
                if ($parentId) {
                    self::insertMenuEntry($extKey, $item, $parentId);
                } else {
                    $pending = true;
                }
            }
            $map = array_filter($map, fn($item) => self::findMenuId($extKey, $item['item_key']) === null);
        }
    }

    private static function insertMenuEntry(string $extKey, array $item, ?int $parentId): void
    {
        $stmt = Connection::prepare('INSERT IGNORE INTO #__admin_menu_entries (owner_type, owner_key, item_key, parent_id, label_key, icon, route, sort_order, perm_key, is_enabled, created_at, updated_at) VALUES (:owner_type, :owner_key, :item_key, :parent_id, :label_key, :icon, :route, :sort_order, :perm_key, 1, NOW(), NOW())');
        $stmt->execute([
            ':owner_type' => 'ext',
            ':owner_key' => $extKey,
            ':item_key' => $item['item_key'],
            ':parent_id' => $parentId,
            ':label_key' => $item['label_key'],
            ':icon' => $item['icon'],
            ':route' => $item['route'],
            ':sort_order' => (int)($item['sort_order'] ?? 0),
            ':perm_key' => $item['perm_key'],
        ]);
    }

    private static function findMenuId(string $extKey, string $itemKey): ?int
    {
        $stmt = Connection::prepare('SELECT id FROM #__admin_menu_entries WHERE owner_type = \'ext\' AND owner_key = :owner_key AND item_key = :item_key LIMIT 1');
        $stmt->execute([':owner_key' => $extKey, ':item_key' => $itemKey]);
        $row = $stmt->fetch();
        return $row ? (int)$row['id'] : null;
    }

    private static function removeAdminMenu(string $extKey): void
    {
        $stmt = Connection::prepare('DELETE FROM #__admin_menu_entries WHERE owner_type = \'ext\' AND owner_key = :owner_key');
        $stmt->execute([':owner_key' => $extKey]);
    }

    private static function removeExtensionFiles(string $extKey): void
    {
        $root = dirname(__DIR__);
        self::removeDir($root . '/extension/' . $extKey);
        self::removeDir($root . '/storage/media/extension/' . $extKey);
    }

    private static function removeThemeFiles(string $themeKey): void
    {
        $root = dirname(__DIR__);
        self::removeDir($root . '/themes/' . $themeKey);
        self::removeDir($root . '/storage/media/themes/' . $themeKey);
    }

    private static function deleteThemeRow(int $id): void
    {
        $stmt = Connection::prepare('DELETE FROM #__themes WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    private static function copyDir(string $source, string $dest): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $item) {
            $target = $dest . '/' . $iterator->getSubPathName();
            if ($item->isDir()) {
                if (!is_dir($target)) {
                    mkdir($target, 0775, true);
                }
            } else {
                $dir = dirname($target);
                if (!is_dir($dir)) {
                    mkdir($dir, 0775, true);
                }
                copy($item->getPathname(), $target);
            }
        }
    }

    private static function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }
        rmdir($dir);
    }

    private static function cleanup(string $dir): void
    {
        self::removeDir($dir);
    }
}
