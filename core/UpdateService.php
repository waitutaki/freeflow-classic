<?php
namespace Core;

use Core\Models\UpdateChecksModel;
use Core\Models\extensionModel;
use Core\Models\ThemesModel;
use Core\Logger;

class UpdateService
{
    public static function checkAll(int $userId): void
    {
        $components = [];
        $components[] = ['type' => 'system', 'key' => 'system', 'version' => (string)Settings::get('system', 'version', '1.0')];
        foreach (extensionModel::all() as $ext) {
            $components[] = ['type' => 'extension', 'key' => $ext['ext_key'], 'version' => (string)($ext['version'] ?? '')];
        }
        foreach (ThemesModel::all() as $theme) {
            $components[] = ['type' => 'theme', 'key' => $theme['theme_key'], 'version' => (string)($theme['version'] ?? '')];
        }

        foreach ($components as $component) {
            $result = self::checkComponent($component['type'], $component['key'], $component['version']);
            UpdateChecksModel::upsert([
                'component_type' => $component['type'],
                'component_key' => $component['key'],
                'installed_version' => $component['version'],
                'available_version' => $result['available_version'],
                'status' => $result['status'],
                'checked_at' => date('Y-m-d H:i:s'),
                'last_error' => $result['last_error'],
                'raw_xml' => $result['raw_xml'],
            ]);
            Logger::info('updates', 'Update check', [
                'type' => $component['type'],
                'key' => $component['key'],
                'status' => $result['status'],
            ]);
        }
    }

    public static function runUpdate(string $type, string $key, int $userId, bool $override = false): array
    {
        $check = UpdateChecksModel::find($type, $key);
        if (!$check || $check['status'] !== 'update_available') {
            return ['ok' => false, 'error' => 'No update available.'];
        }

        $feed = (string)($check['raw_xml'] ?? '');
        if ($feed === '') {
            return ['ok' => false, 'error' => 'Feed missing.'];
        }
        $parsed = self::parseFeed($feed);
        if (!$parsed['ok']) {
            return ['ok' => false, 'error' => 'Invalid feed.'];
        }

        $zipUrl = $parsed['zip'];
        $checksum = $parsed['checksum'];
        if ($zipUrl === '' || $checksum === '') {
            return ['ok' => false, 'error' => 'Feed missing fields.'];
        }

        $stage = self::createStageDir();
        if ($stage === '') {
            return ['ok' => false, 'error' => 'Unable to stage update.'];
        }

        $zipPath = $stage . '/package.zip';
        if (!self::download($zipUrl, $zipPath)) {
            self::cleanup($stage);
            return ['ok' => false, 'error' => 'Download failed.'];
        }

        $hash = hash_file('sha256', $zipPath) ?: '';
        if (!hash_equals(strtolower($checksum), strtolower($hash))) {
            self::cleanup($stage);
            return ['ok' => false, 'error' => 'Checksum failed.'];
        }

        $extractDir = $stage . '/extract';
        if (!self::safeExtract($zipPath, $extractDir)) {
            self::cleanup($stage);
            return ['ok' => false, 'error' => 'Extract failed.'];
        }

        $manifestPath = $extractDir . '/manifest.xml';
        if (!is_file($manifestPath)) {
            self::cleanup($stage);
            return ['ok' => false, 'error' => 'manifest.xml missing.'];
        }

        $manifest = self::loadManifest($manifestPath);
        if (!$manifest || $manifest['package_type'] !== $type || $manifest['key'] !== $key) {
            self::cleanup($stage);
            return ['ok' => false, 'error' => 'Manifest mismatch.'];
        }

        if (!self::validateTargets($manifest['files'], $type, $key)) {
            self::cleanup($stage);
            return ['ok' => false, 'error' => 'Invalid target paths.'];
        }

        if ($type !== 'system') {
            $scan = self::scanPhpFiles($extractDir);
            if (!empty($scan['violations']) && !$override) {
                self::cleanup($stage);
                return ['ok' => false, 'error' => 'Safety checks failed.'];
            }
        }

        if (!self::deployFiles($extractDir, $manifest['files'])) {
            self::cleanup($stage);
            return ['ok' => false, 'error' => 'Deploy failed.'];
        }

        if (!self::runUpdateSql($extractDir, $manifest, $type, $key)) {
            self::cleanup($stage);
            return ['ok' => false, 'error' => 'SQL update failed.'];
        }

        self::updateVersion($type, $key, $manifest['version']);
        Logger::info('updates', 'Update applied', ['type' => $type, 'key' => $key, 'version' => $manifest['version']]);
        self::cleanup($stage);
        return ['ok' => true];
    }

    /**
     * Run batch updates for extension and themes
     *
     * Per specification: Update All should NOT include core updates
     * Core updates require explicit admin action and password re-entry
     *
     * @param string $scope Update scope: 'all', 'extension', or 'themes'
     * @param int $userId ID of user initiating the update
     * @return array Array of update results
     */
    public static function runBatch(string $scope, int $userId): array
    {
        $scope = strtolower($scope);
        $checks = UpdateChecksModel::all();
        $queue = [];
        foreach ($checks as $check) {
            if (($check['status'] ?? '') !== 'update_available') {
                continue;
            }
            $queue[] = $check;
        }

        $ordered = [];
        // Per specification: Update All should NOT include core updates
        // Core updates require explicit admin action and password re-entry
        // if ($scope === 'system' || $scope === 'all') {
        //     $ordered = array_merge($ordered, self::filterQueue($queue, 'system'));
        // }
        if ($scope === 'extension' || $scope === 'all') {
            $ordered = array_merge($ordered, self::filterQueue($queue, 'extension'));
        }
        if ($scope === 'themes' || $scope === 'all') {
            $ordered = array_merge($ordered, self::filterQueue($queue, 'theme'));
        }

        $results = [];
        foreach ($ordered as $item) {
            $results[] = self::runUpdate($item['component_type'], $item['component_key'], $userId, false);
        }
        return $results;
    }

    private static function checkComponent(string $type, string $key, string $installedVersion): array
    {
        $endpoint = self::endpointFor($type, $key);
        if ($endpoint === '') {
            return ['status' => 'error', 'available_version' => null, 'last_error' => 'Invalid endpoint.', 'raw_xml' => null];
        }

        $xml = self::fetchFeed($endpoint);
        if ($xml === '') {
            return ['status' => 'error', 'available_version' => null, 'last_error' => 'Feed fetch failed.', 'raw_xml' => null];
        }

        // Note: Feed signature verification removed for DevStore packages

        $parsed = self::parseFeed($xml);
        if (!$parsed['ok']) {
            return ['status' => 'error', 'available_version' => null, 'last_error' => 'Feed parse failed.', 'raw_xml' => null];
        }

        $available = $parsed['version'];
        $status = 'unknown';
        if ($available !== '' && $installedVersion !== '') {
            $status = version_compare($available, $installedVersion, '>') ? 'update_available' : 'up_to_date';
        }

        return [
            'status' => $status,
            'available_version' => $available !== '' ? $available : null,
            'last_error' => null,
            'raw_xml' => $xml,
        ];
    }

    private static function endpointFor(string $type, string $key): string
    {
        if ($type === 'system') {
            return 'https://freeflowcms.online/api/main/feed';
        }
        if ($type === 'extension') {
            return 'https://freeflowcms.online/api/ext/devstore/feeds/extension/' . rawurlencode($key) . '/feed';
        }
        if ($type === 'theme') {
            return 'https://freeflowcms.online/api/ext/devstore/feeds/themes/' . rawurlencode($key) . '/feed';
        }
        return '';
    }

    private static function filterQueue(array $queue, string $type): array
    {
        return array_values(array_filter($queue, static fn ($item) => ($item['component_type'] ?? '') === $type));
    }

    private static function fetchFeed(string $url): string
    {
        if (!str_starts_with($url, 'https://')) {
            Logger::warning('updates', 'Invalid feed URL scheme', ['url' => $url]);
            return '';
        }

        $ch = curl_init();
        if (!$ch) {
            Logger::error('updates', 'cURL init failed for feed fetch', ['url' => $url]);
            return '';
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($data === false) {
            Logger::warning('updates', 'Feed fetch failed', ['url' => $url, 'http_code' => $httpCode, 'error' => $error]);
            return '';
        }

        if ($httpCode !== 200) {
            Logger::warning('updates', 'Feed fetch HTTP error', ['url' => $url, 'http_code' => $httpCode]);
            return '';
        }

        return $data;
    }

    private static function verifyFeedSignature(string $xml): bool
    {
        $dom = new \DOMDocument();
        if (!$dom->loadXML($xml, LIBXML_NONET)) {
            return false;
        }
        $sigNodes = $dom->getElementsByTagName('signature');
        if ($sigNodes->length === 0) {
            return false;
        }
        $signature = trim($sigNodes->item(0)->nodeValue);
        $sigNodes->item(0)->parentNode->removeChild($sigNodes->item(0));
        $canonical = $dom->C14N();
        if ($canonical === false) {
            return false;
        }
        $publicKey = file_get_contents(Trust::publicKeyPath());
        if ($publicKey === false) {
            return false;
        }
        $sig = base64_decode($signature, true);
        if ($sig === false) {
            return false;
        }
        return openssl_verify($canonical, $sig, $publicKey, OPENSSL_ALGO_SHA256) === 1;
    }

    private static function parseFeed(string $xml): array
    {
        $prev = libxml_disable_entity_loader(true);
        $feed = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NONET);
        libxml_disable_entity_loader($prev);
        if ($feed === false) {
            return ['ok' => false];
        }

        $version = '';
        $zip = '';
        $checksum = '';

        if (isset($feed->version)) {
            $version = trim((string)$feed->version);
        }
        if (isset($feed->zip)) {
            $zip = trim((string)$feed->zip);
        }
        if (isset($feed->checksum)) {
            $checksum = trim((string)$feed->checksum);
        }

        if ($version === '' && isset($feed->package)) {
            $package = $feed->package[0];
            $version = trim((string)($package['version'] ?? ''));
            $zip = trim((string)($package->zip ?? ''));
            $checksum = trim((string)($package->checksum ?? ''));
        }

        return [
            'ok' => $version !== '' && $zip !== '' && $checksum !== '',
            'version' => $version,
            'zip' => $zip,
            'checksum' => $checksum,
        ];
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

    private static function downloadStream(string $url, string $dest): array
{
    if (!str_starts_with($url, 'https://')) {
        return ['ok' => false, 'error' => 'Invalid URL scheme'];
    }

    $ch = curl_init();
    if (!$ch) {
        return ['ok' => false, 'error' => 'cURL init failed'];
    }

    // Write binary-safe
    $fp = fopen($dest, 'wb');
    if (!$fp) {
        curl_close($ch);
        return ['ok' => false, 'error' => 'Failed to open destination file'];
    }

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    // Good citizen: set a UA (some servers behave oddly without one)
    curl_setopt($ch, CURLOPT_USERAGENT, 'FreeflowCMS-UpdateService/1.0');

    // SSL verification
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

    // Fail fast on common transfer errors
    curl_setopt($ch, CURLOPT_FAILONERROR, false);

    $result = curl_exec($ch);

    $httpCode     = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $finalUrl     = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $contentType  = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $curlErrNo    = curl_errno($ch);
    $curlError    = curl_error($ch);

    curl_close($ch);
    fclose($fp);

    // Prefer actual file size on disk (more reliable than CURLINFO_SIZE_DOWNLOAD)
    $bytesWritten = is_file($dest) ? (int) filesize($dest) : 0;

    if ($result === false) {
        if (is_file($dest)) {
            @unlink($dest);
        }

        
        $msg = $curlErrNo ? ("cURL error {$curlErrNo}: " . $curlError) : ('cURL error: ' . $curlError);
        return [
            
            'ok' => false,
            'error' => $msg,
            'http_status' => $httpCode,
            'final_url' => $finalUrl,
            'content_type' => $contentType,
            'bytes_written' => $bytesWritten,
        ];
    }

    if ($httpCode !== 200) {
        if (is_file($dest)) {
            @unlink($dest);
        }
        return [
            'ok' => false,
            'error' => 'HTTP ' . $httpCode,
            'http_status' => $httpCode,
            'final_url' => $finalUrl,
            'content_type' => $contentType,
            'bytes_written' => $bytesWritten,
        ];
    }

    // Sanity check: ensure we actually got a zip-ish response
    // (Some servers return an error payload with 200; this catches that.)
    $ct = strtolower($contentType);
    $looksZip = ($ct === '' // sometimes omitted
        || str_contains($ct, 'application/zip')
        || str_contains($ct, 'application/octet-stream'));

    if (!$looksZip || $bytesWritten < 1024) {
        if (is_file($dest)) {
            @unlink($dest);
        }
        return [
            'ok' => false,
            'error' => 'Unexpected download content (not a zip)',
            'http_status' => $httpCode,
            'final_url' => $finalUrl,
            'content_type' => $contentType,
            'bytes_written' => $bytesWritten,
        ];
    }

    return [
        'ok' => true,
        'http_status' => $httpCode,
        'bytes_written' => $bytesWritten,
        'final_url' => $finalUrl,
        'content_type' => $contentType,
    ];
}


    private static function download(string $url, string $dest): bool
    {
        $result = self::downloadStream($url, $dest);
        return $result['ok'];
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
        $packageType = $rootName === 'extension' ? 'extension' : ($rootName === 'theme' ? 'theme' : ($rootName === 'system' ? 'system' : ''));
        if ($packageType === '') {
            return null;
        }

        $key = strtolower(trim((string)($xml->key ?? '')));
        $version = trim((string)($xml->version ?? ''));
        if ($key === '' || $version === '') {
            return null;
        }
        if ($packageType !== 'system' && !preg_match('/^[a-z0-9_-]+$/', $key)) {
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

        $updateSql = '';
        if (isset($xml->update)) {
            $updateSql = trim((string)($xml->update['script'] ?? ''));
        }

        return [
            'package_type' => $packageType,
            'key' => $key,
            'version' => $version,
            'files' => $files,
            'update_sql' => $updateSql,
        ];
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
            } elseif ($type === 'theme') {
                if (!str_starts_with($dest, '/themes/' . $key) && !str_starts_with($dest, '/storage/media/themes/' . $key)) {
                    return false;
                }
            } else {
                if (!self::isCoreTarget($dest)) {
                    return false;
                }
            }
        }
        return true;
    }

    private static function isCoreTarget(string $dest): bool
    {
        $allowed = ['/core/', '/admin/', '/install/', '/themes/', '/extension/', '/index.php'];
        foreach ($allowed as $prefix) {
            if (str_starts_with($dest, $prefix)) {
                return true;
            }
        }
        return false;
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

    private static function runUpdateSql(string $root, array $manifest, string $type, string $key): bool
    {
        $path = $manifest['update_sql'] ?? '';
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
        if ($type !== 'system' && !self::sqlAllowsNamespace($sql, $type, $key)) {
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

    private static function splitSql(string $sql): array
    {
        $lines = preg_split('/;\s*\n/', $sql) ?: [];
        return array_filter($lines, fn($line) => trim($line) !== '');
    }

    private static function updateVersion(string $type, string $key, string $version): void
    {
        if ($type === 'system') {
            Settings::set('system', 'version', $version, 'string', null);
            return;
        }
        if ($type === 'extension') {
            $stmt = Connection::prepare('UPDATE #__extension SET version = :version, updated_at = NOW() WHERE ext_key = :key');
            $stmt->execute([':version' => $version, ':key' => $key]);
            return;
        }
        if ($type === 'theme') {
            $stmt = Connection::prepare('UPDATE #__themes SET version = :version, updated_at = NOW() WHERE theme_key = :key');
            $stmt->execute([':version' => $version, ':key' => $key]);
        }
    }

    private static function cleanup(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
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

    /**
     * Fetch available packages from repository API for Install from Web tab
     * This method implements the specification requirement for API-only communication
     */
    public static function fetchAvailablePackages(): array
    {
        // Fetch extension feed from DevStore API
        $extensionFeed = self::fetchFeed('https://freeflowcms.online/api/ext/devstore/feeds/extension/index');
        if ($extensionFeed === '') {
            Logger::warning('updates', 'extension feed fetch failed');
            $extensionFeed = '';
        }
        
        // Fetch themes feed from DevStore API
        $themesFeed = self::fetchFeed('https://freeflowcms.online/api/ext/devstore/feeds/themes/index');
        if ($themesFeed === '') {
            Logger::warning('updates', 'Themes feed fetch failed');
            $themesFeed = '';
        }
        
        $packages = [];
        
        // Parse extension feed
        if ($extensionFeed !== '') {
            $extension = self::parsePackagesFeed($extensionFeed, 'extension');
            $packages = array_merge($packages, $extension);
        }
        
        // Parse themes feed
        if ($themesFeed !== '') {
            $themes = self::parsePackagesFeed($themesFeed, 'theme');
            $packages = array_merge($packages, $themes);
        }
        
        return $packages;
    }
    
    /**
     * Resolve package descriptor from repository
     */
    public static function resolvePackage(string $type, string $key, string $version = ''): array
    {
        $packages = self::fetchAvailablePackages();
        $filtered = array_filter($packages, static fn($p) => $p['package_type'] === $type && $p['package_key'] === $key);
        if (empty($filtered)) {
            return ['ok' => false, 'error' => 'Package not found in repository'];
        }

        // Select version
        $selected = null;
        if ($version !== '') {
            foreach ($filtered as $pkg) {
                if ($pkg['version'] === $version) {
                    $selected = $pkg;
                    break;
                }
            }
            if (!$selected) {
                return ['ok' => false, 'error' => 'Requested version not available'];
            }
        } else {
            // Select best (latest) version
            usort($filtered, static fn($a, $b) => version_compare($b['version'], $a['version']));
            $selected = $filtered[0];
        }

        // Use verbatim values from feed
        $downloadUrl = $selected['download_url'];
        $filename = $selected['filename'];
        $sha256 = $selected['sha256'];

        if ($downloadUrl === '') {
            return ['ok' => false, 'error' => 'No download_url in feed for ' . $type . '/' . $key . '/' . $selected['version']];
        }

        return [
            'ok' => true,
            'resolved' => [
                'package_type' => $type,
                'package_key' => $key,
                'version' => $selected['version'],
                'download_url' => $downloadUrl,
                'filename' => $filename,
                'sha256' => $sha256,
            ]
        ];
    }

    /**
     * Download package from repository API
     */
    public static function downloadPackage(string $type, string $key, string $version): array
    {
        Logger::info('updates', 'Starting package download', [
            'type' => $type,
            'key' => $key,
            'version' => $version,
        ]);

        $resolved = self::resolvePackage($type, $key, $version);
        if (!$resolved['ok']) {
            Logger::error('updates', 'Package resolution failed', [
                'type' => $type,
                'key' => $key,
                'version' => $version,
                'error' => $resolved['error'],
            ]);
            return ['ok' => false, 'error' => $resolved['error']];
        }
        $desc = $resolved['resolved'];

        Logger::info('updates', 'Package resolved successfully', [
            'type' => $type,
            'key' => $key,
            'version' => $desc['version'],
            'download_url' => $desc['download_url'],
            'filename' => $desc['filename'],
            'sha256' => $desc['sha256'],
        ]);

        // Additional logging to validate the download URL
        Logger::info('updates', 'Validating download URL', [
            'download_url' => $desc['download_url'],
            'filename' => $desc['filename'],
        ]);

        // Download the package
        $stage = self::createStageDir();
        if ($stage === '') {
            Logger::error('updates', 'Failed to create staging directory', [
                'type' => $type,
                'key' => $key,
                'version' => $desc['version'],
            ]);
            return ['ok' => false, 'error' => 'Unable to create staging directory'];
        }

        Logger::info('updates', 'Staging directory created', [
            'type' => $type,
            'key' => $key,
            'version' => $desc['version'],
            'stage_dir' => $stage,
        ]);

        $zipPath = $stage . '/package.zip';
        Logger::info('updates', 'Starting download stream', [
            'type' => $type,
            'key' => $key,
            'version' => $desc['version'],
            'download_url' => $desc['download_url'],
            'dest_path' => $zipPath,
        ]);

        $downloadResult = self::downloadStream($desc['download_url'], $zipPath);

        if (!$downloadResult['ok']) {
            Logger::error('updates', 'Download failed', [
                'type' => $type,
                'key' => $key,
                'version' => $desc['version'] ?? $version,
                'download_url' => $desc['download_url'] ?? '',
                'error' => $downloadResult['error'] ?? '',
                'http_status' => $downloadResult['http_status'] ?? null,
                'final_url' => $downloadResult['final_url'] ?? '',
                'content_type' => $downloadResult['content_type'] ?? '',
                'bytes_written' => $downloadResult['bytes_written'] ?? 0,
            ]);

            // Additional logging to capture the exact URL and response details
            Logger::error('updates', 'Download URL details', [
                'download_url' => $desc['download_url'],
                'final_url' => $downloadResult['final_url'] ?? '',
                'http_status' => $downloadResult['http_status'] ?? null,
            ]);

            self::cleanup($stage);
            return ['ok' => false, 'error' => 'Download failed: ' . ($downloadResult['error'] ?? 'unknown')];
        }

        Logger::info('updates', 'Download completed successfully', [
            'type' => $type,
            'key' => $key,
            'version' => $desc['version'],
            'http_status' => $downloadResult['http_status'],
            'bytes_written' => $downloadResult['bytes_written'],
            'final_url' => $downloadResult['final_url'],
            'content_type' => $downloadResult['content_type'],
        ]);

        // Verify SHA256 IF provided
        $sha256Result = 'not checked';
        if ($desc['sha256'] !== '') {
            Logger::info('updates', 'Starting SHA256 verification', [
                'type' => $type,
                'key' => $key,
                'version' => $desc['version'],
                'expected_hash' => $desc['sha256'],
            ]);

            $hash = hash_file('sha256', $zipPath) ?: '';
            if (!hash_equals(strtolower($desc['sha256']), strtolower($hash))) {
                Logger::error('updates', 'SHA256 verification failed', [
                    'type' => $type,
                    'key' => $key,
                    'version' => $desc['version'],
                    'expected_hash' => $desc['sha256'],
                    'actual_hash' => $hash,
                ]);
                self::cleanup($stage);
                return ['ok' => false, 'error' => 'SHA256 verification failed'];
            }
            $sha256Result = 'verified';

            Logger::info('updates', 'SHA256 verification passed', [
                'type' => $type,
                'key' => $key,
                'version' => $desc['version'],
                'hash' => $hash,
            ]);
        } else {
            Logger::warning('updates', 'No SHA256 hash provided, skipping verification', [
                'type' => $type,
                'key' => $key,
                'version' => $desc['version'],
            ]);
        }

        Logger::info('updates', 'Package download completed successfully', [
            'type' => $type,
            'key' => $key,
            'version' => $desc['version'],
            'path' => $zipPath,
            'sha256_result' => $sha256Result,
        ]);

        return [
            'ok' => true,
            'path' => $zipPath,
            'resolved' => $desc,
            'http_status' => $downloadResult['http_status'],
            'bytes_written' => $downloadResult['bytes_written'],
            'final_url' => $downloadResult['final_url'],
            'sha256_result' => $sha256Result,
        ];
    }
    
    /**
     * Parse packages feed (different format from single package feed)
     */
    private static function parsePackagesFeed(string $xml, string $type): array
    {
        $prev = libxml_disable_entity_loader(true);
        $feed = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NONET);
        libxml_disable_entity_loader($prev);
        
        if ($feed === false) {
            return [];
        }
        
        $packages = [];
        
        // Parse package entries
        if (isset($feed->package)) {
            foreach ($feed->package as $package) {
                $key = trim((string)($package->key ?? ''));
                $name = trim((string)($package->name ?? ''));
                $version = trim((string)($package->version ?? ''));
                $author = trim((string)($package->author ?? ''));
                $description = trim((string)($package->description ?? ''));
                $sha256 = trim((string)($package->sha256 ?? ''));
                $previewUrl = trim((string)($package->preview_url ?? ''));
                $downloadUrl = trim((string)($package->download_url ?? ''));
                $filename = trim((string)($package->filename ?? ''));

                // If name is still empty, try to get it from the package key
                if ($name === '') {
                    $name = $key;
                }

                // If author is still empty, set a default value
                if ($author === '') {
                    $author = 'Unknown Author';
                }

                // Debug: Log the parsed values
                Logger::info('updates', 'Parsed package', [
                    'key' => $key,
                    'name' => $name,
                    'version' => $version,
                    'author' => $author,
                    'description' => $description,
                    'sha256' => $sha256,
                    'download_url' => $downloadUrl,
                    'filename' => $filename,
                ]);

                if ($key !== '' && $version !== '' && $downloadUrl !== '' && $filename !== '' && $sha256 !== '') {
                    $packages[] = [
                        'package_type' => $type,
                        'package_key' => $key,
                        'name' => $name,
                        'version' => $version,
                        'author' => $author,
                        'description' => $description,
                        'sha256' => $sha256,
                        'download_url' => $downloadUrl,
                        'filename' => $filename,
                        'preview_url' => $previewUrl,
                    ];
                }
            }
        }
        
        return $packages;
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
}
