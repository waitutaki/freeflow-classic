<?php
namespace Core\Controllers\Admin;

use Core\Auth;
use Core\Controller;
use Core\Csrf;
use Core\Lang;
use Core\Logger;
use Core\Response;
use Core\Session;
use Core\Settings;
use Core\Models\DevstorePackagesModel;
use Core\Models\DevstoreComplianceReportsModel;
use Core\Models\DevstoreSigningService;
use Core\Models\DevstoreAuditLogModel;
use Core\Models\FreegateModel;

class DevstorePackagesController extends Controller
{
    public function index(): Response
    {
        Session::startAdmin();
        Auth::requireSuper();
        if (!(bool)Settings::get('devstore', 'enabled', true)) {
            return new Response(Lang::get('DEVSTORE.DISABLED_MESSAGE'), 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $subtab = $_GET['subtab'] ?? 'list';
        $validSubtabs = ['list', 'upload'];
        $subtab = in_array($subtab, $validSubtabs, true) ? $subtab : 'list';

        $data = [
            'page_title' => Lang::get('DEVSTORE.PACKAGES_TITLE'),
            'subtab' => $subtab,
        ];

        if ($subtab === 'list') {
            $data['packages'] = DevstorePackagesModel::all();
        }

        return $this->render('admin/devstore_packages', $data, 'Zulu');
    }

    public function view(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requireSuper();
        if (!(bool)Settings::get('devstore', 'enabled', true)) {
            return new Response(Lang::get('DEVSTORE.DISABLED_MESSAGE'), 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $id = (int)$request->param('id');
        $package = DevstorePackagesModel::find($id);
        if (!$package) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        if (!in_array($package['status'] ?? '', ['approved', 'published'], true)) {
            return new Response(Lang::get('DEVSTORE.ERROR_NOT_APPROVED'), 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        $report = DevstoreComplianceReportsModel::latestForPackage($id);

        return $this->render('admin/devstore_package_view', [
            'page_title' => Lang::get('DEVSTORE.PACKAGE_VIEW_TITLE'),
            'package' => $package,
            'report' => $report,
        ], 'Zulu');
    }

    public function publish(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requireSuper();
        if (!(bool)Settings::get('devstore', 'enabled', true)) {
            return new Response(Lang::get('DEVSTORE.DISABLED_MESSAGE'), 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $id = (int)$request->param('id');
        $package = DevstorePackagesModel::find($id);
        if (!$package) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $archiveName = $package['archive_name'] ?? '';
        $source = __DIR__ . '/../../../storage/devstore/uploads/' . $archiveName;
        if (!is_file($source)) {
            return new Response('Package missing', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $signature = DevstoreSigningService::signPayload($package['package_type'], $package['package_key'], $package['version'], $package['sha256'] ?? '');
        if ($signature === null) {
            return new Response('Signing failed', 500, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $repoPath = $this->publishToRepository($package, $source, $signature);
        if ($repoPath === '') {
            return new Response('Publish failed', 500, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        DevstorePackagesModel::setPublished($id, $repoPath, (int)Session::get('user_id', 0));
        DevstoreAuditLogModel::log('package_published', (int)Session::get('user_id', 0), 'Package published: ' . $id);
        Logger::info('admin', 'Devstore package published', ['package_id' => $id]);
        FreegateModel::addTimeline([
            'event_type' => 'devstore',
            'title' => 'FFCMS.FREEGATE_TIMELINE_PACKAGE_PUBLISHED',
            'details' => 'Package ' . $id,
            'severity' => 'low',
            'correlation_id' => null,
            'actor_user_id' => (int)Session::get('user_id', 0),
        ]);

        return new Response('', 302, ['Location' => '/admin/devstore/packages']);
    }

    public function unpublish(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requireSuper();
        if (!(bool)Settings::get('devstore', 'enabled', true)) {
            return new Response(Lang::get('DEVSTORE.DISABLED_MESSAGE'), 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $id = (int)$request->param('id');
        $package = DevstorePackagesModel::find($id);
        if ($package) {
            DevstorePackagesModel::setUnpublished($id);
            DevstoreAuditLogModel::log('package_unpublished', (int)Session::get('user_id', 0), 'Package unpublished: ' . $id);
            Logger::info('admin', 'Devstore package unpublished', ['package_id' => $id]);
            FreegateModel::addTimeline([
                'event_type' => 'devstore',
                'title' => 'FFCMS.FREEGATE_TIMELINE_PACKAGE_UNPUBLISHED',
                'details' => 'Package ' . $id,
                'severity' => 'medium',
                'correlation_id' => null,
                'actor_user_id' => (int)Session::get('user_id', 0),
            ]);
        }

        return new Response('', 302, ['Location' => '/admin/devstore/packages']);
    }

    public function delete(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requireSuper();
        if (!(bool)Settings::get('devstore', 'enabled', true)) {
            return new Response(Lang::get('DEVSTORE.DISABLED_MESSAGE'), 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $id = (int)$request->param('id');
        $package = DevstorePackagesModel::find($id);
        if (!$package) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        // Delete files
        $this->deletePackageFiles($package);

        // Delete from database
        DevstoreComplianceReportsModel::deleteForPackage($id);
        DevstorePackagesModel::delete($id);

        DevstoreAuditLogModel::log('package_deleted', (int)Session::get('user_id', 0), 'Package deleted: ' . $id);
        Logger::info('admin', 'Devstore package deleted', ['package_id' => $id]);
        FreegateModel::addTimeline([
            'event_type' => 'devstore',
            'title' => 'FFCMS.FREEGATE_TIMELINE_PACKAGE_DELETED',
            'details' => 'Package ' . $id,
            'severity' => 'high',
            'correlation_id' => null,
            'actor_user_id' => (int)Session::get('user_id', 0),
        ]);

        return new Response('', 302, ['Location' => '/admin/devstore/packages']);
    }

    public function upload(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requireSuper();
        if (!(bool)Settings::get('devstore', 'enabled', true)) {
            return new Response(Lang::get('DEVSTORE.DISABLED_MESSAGE'), 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $result = $this->handleSuperUpload();
        if (!$result['ok']) {
            return $this->render('admin/devstore_packages', [
                'page_title' => Lang::get('DEVSTORE.PACKAGES_TITLE'),
                'subtab' => 'upload',
                'error' => $result['error'],
            ], 'Zulu');
        }

        return new Response('', 302, ['Location' => '/admin/devstore/packages?subtab=list']);
    }

    private function handleSuperUpload(): array
    {
        if (!isset($_FILES['package']['tmp_name']) || !is_uploaded_file($_FILES['package']['tmp_name'])) {
            return ['ok' => false, 'error' => Lang::get('DEVSTORE.ERROR_INVALID_UPLOAD')];
        }

        $ext = strtolower(pathinfo($_FILES['package']['name'] ?? '', PATHINFO_EXTENSION));
        if ($ext !== 'zip') {
            return ['ok' => false, 'error' => Lang::get('DEVSTORE.ERROR_INVALID_PACKAGE')];
        }

        $uploadsDir = __DIR__ . '/../../../storage/devstore/uploads';
        if (!is_dir($uploadsDir) && !mkdir($uploadsDir, 0775, true)) {
            return ['ok' => false, 'error' => Lang::get('DEVSTORE.ERROR_STORAGE')];
        }
        $archiveName = date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.zip';
        $archivePath = $uploadsDir . '/' . $archiveName;
        if (!move_uploaded_file($_FILES['package']['tmp_name'], $archivePath)) {
            return ['ok' => false, 'error' => Lang::get('DEVSTORE.ERROR_STORAGE')];
        }

        $stageDir = __DIR__ . '/../../../storage/devstore/staging/' . bin2hex(random_bytes(6));
        if (!mkdir($stageDir, 0775, true)) {
            return ['ok' => false, 'error' => Lang::get('DEVSTORE.ERROR_STORAGE')];
        }

        $extractDir = $stageDir . '/extract';
        if (!$this->safeExtract($archivePath, $extractDir)) {
            $this->cleanup($stageDir);
            return ['ok' => false, 'error' => Lang::get('DEVSTORE.ERROR_INVALID_PACKAGE')];
        }

        $manifestPath = $extractDir . '/manifest.xml';
        if (!is_file($manifestPath)) {
            Logger::error('devstore', 'Manifest file missing in package', ['user_id' => (int)Session::get('user_id', 0)]);
            $this->cleanup($stageDir);
            return ['ok' => false, 'error' => Lang::get('DEVSTORE.ERROR_MANIFEST')];
        }

        $manifest = \Core\Models\DevstoreComplianceService::loadManifest($manifestPath);
        if (!$manifest) {
            Logger::error('devstore', 'Manifest file invalid', ['user_id' => (int)Session::get('user_id', 0), 'path' => $manifestPath]);
            $this->cleanup($stageDir);
            return ['ok' => false, 'error' => Lang::get('DEVSTORE.ERROR_MANIFEST')];
        }

        $existing = DevstorePackagesModel::findByKeyVersion($manifest['package_type'], $manifest['key'], $manifest['version']);
        if ($existing && in_array($existing['status'] ?? '', ['approved', 'published'], true)) {
            $this->cleanup($stageDir);
            return ['ok' => false, 'error' => Lang::get('DEVSTORE.ERROR_IMMUTABLE')];
        }

        $sha256 = hash_file('sha256', $archivePath) ?: '';
        $compliance = \Core\Models\DevstoreComplianceService::inspectPackage($extractDir, $manifest);
        $status = $compliance['ok'] ? 'approved' : 'rejected';

        if ($existing && ($existing['status'] ?? '') === 'rejected') {
            // Update existing rejected package
            DevstorePackagesModel::updateStatus((int)$existing['id'], $status, $sha256, $archiveName);
            $packageId = (int)$existing['id'];
        } else {
            // Create new package with super user as author
            $packageId = DevstorePackagesModel::create([
                'developer_id' => 0, // Super user, no developer
                'package_type' => $manifest['package_type'],
                'package_key' => $manifest['key'],
                'name' => $manifest['name'],
                'version' => $manifest['version'],
                'description' => $manifest['description'],
                'author' => $manifest['author'],
                'status' => $status,
                'sha256' => $sha256,
                'archive_name' => $archiveName,
            ]);
        }

        DevstoreComplianceReportsModel::create($packageId, $compliance['ok'], $compliance['report']);

        if ($compliance['ok']) {
            // Auto-publish approved packages
            $this->publishPackage($packageId, (int)Session::get('user_id', 0));
        }

        $this->cleanup($stageDir);

        if (!$compliance['ok']) {
            Logger::error('devstore', 'Compliance failed', ['user_id' => (int)Session::get('user_id', 0), 'violations' => $compliance['violations']]);
            return ['ok' => false, 'error' => $compliance['report']];
        }

        return ['ok' => true];
    }

    private function safeExtract(string $zipPath, string $dest): bool
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

    private function cleanup(string $dir): void
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

    private function publishPackage(int $id, int $userId): void
    {
        $package = DevstorePackagesModel::find($id);
        if (!$package || $package['status'] !== 'approved') {
            return;
        }

        $type = $package['package_type'];
        $key = $package['package_key'];
        $version = $package['version'];
        $typeDir = $type === 'theme' ? 'themes' : 'extension';

        $repoPath = $typeDir . '/' . $key . '/' . $version;
        $source = __DIR__ . '/../../../storage/devstore/uploads/' . $package['archive_name'];
        $repoBase = __DIR__ . '/../../../storage/devstore/repository/' . $repoPath;

        if (!is_dir($repoBase)) {
            mkdir($repoBase, 0775, true);
        }

        $newFilename = $key . '-' . $version . '.zip';
        copy($source, $repoBase . '/' . $newFilename);
        DevstorePackagesModel::update($id, ['archive_name' => $newFilename]);

        // Create signature files
        $signature = \Core\Models\DevstoreSigningService::signPayload($type, $key, $version, $package['sha256'] ?? '');
        if ($signature !== null) {
            $payload = $this->buildPayload($package, $signature);
            file_put_contents($repoBase . '/signed_payload.xml', $payload);
            file_put_contents($repoBase . '/signature.sig', $signature);
        }

        // Extract preview
        $zip = new \ZipArchive();
        if ($zip->open($repoBase . '/' . $key . '.zip') === true) {
            if ($zip->locateName('preview.png') !== false) {
                $zip->extractTo($repoBase, 'preview.png');
            }
            if ($zip->locateName('preview.jpg') !== false) {
                $zip->extractTo($repoBase, 'preview.jpg');
            }
            $zip->close();
        }

        DevstorePackagesModel::setPublished($id, $repoPath, $userId);

        // Timeline
        \Core\Models\FreegateModel::addTimeline([
            'event_type' => 'devstore',
            'title' => 'FFCMS.FREEGATE_TIMELINE_PACKAGE_PUBLISHED',
            'details' => $key . ' ' . $version,
            'severity' => 'low',
            'correlation_id' => null,
            'actor_user_id' => $userId,
        ]);
    }

    private function publishToRepository(array $package, string $source, string $signature): string
    {
        $typeDir = $package['package_type'] === 'theme' ? 'theme' : 'extension';
        $key = $package['package_key'];
        $version = $package['version'];

        $base = __DIR__ . '/../../../storage/devstore/repository/' . $typeDir . '/' . $key . '/' . $version;
        if (!is_dir($base) && !mkdir($base, 0775, true)) {
            return '';
        }

        // Use the original archive name instead of hardcoding 'package.zip'
        $archiveName = $package['archive_name'] ?? 'package.zip';
        $packageTarget = $base . '/' . $archiveName;
        if (!copy($source, $packageTarget)) {
            return '';
        }

        $payload = $this->buildPayload($package, $signature);
        file_put_contents($base . '/signed_payload.xml', $payload);
        file_put_contents($base . '/signature.sig', $signature);

        // Per specification: Create or append feed XML for this package
        // Feed XML should be in the package directory (not version-specific) and append versions
        $packageDir = __DIR__ . '/../../../storage/devstore/repository/' . $typeDir . '/' . $key;
        $this->generateFeedXml($package, $packageDir, $signature);

        $this->extractPreview($source, $base);

        return $typeDir . '/' . $key . '/' . $version;
    }

    /**
     * Generate feed XML for the package
     *
     * Per specification: When placing a package in the repository,
     * create or append the current available version number and details in feed.xml
     *
     * @param array $package Package information
     * @param string $packageDir Package directory (not version-specific)
     * @param string $signature Package signature
     */
    private function generateFeedXml(array $package, string $packageDir, string $signature): void
    {
        // Ensure package directory exists
        if (!is_dir($packageDir) && !mkdir($packageDir, 0775, true)) {
            return;
        }

        $feedPath = $packageDir . '/feed.xml';
        
        // Check if feed.xml already exists
        if (is_file($feedPath)) {
            // Append to existing feed
            $xml = simplexml_load_file($feedPath);
            if ($xml === false) {
                $xml = new \SimpleXMLElement('<feed></feed>');
            }
        } else {
            // Create new feed
            $xml = new \SimpleXMLElement('<feed></feed>');
            $xml->addAttribute('type', $package['package_type']);
            $xml->addAttribute('key', $package['package_key']);
        }

        // Add package entry for this version
        $packageNode = $xml->addChild('package');
        $packageNode->addAttribute('version', $package['version']);
        $packageNode->addChild('name', $package['name']);
        $packageNode->addChild('author', $package['author'] ?? '');
        $packageNode->addChild('description', $package['description'] ?? '');
        $packageNode->addChild('zip', $package['archive_name']);
        $packageNode->addChild('checksum', $package['sha256'] ?? '');
        $packageNode->addChild('signature', $signature);
        $packageNode->addChild('published', date('c'));

        // Sign the feed
        $xmlString = $xml->asXML();
        $dom = new \DOMDocument();
        $dom->loadXML($xmlString);
        $canonical = $dom->C14N();
        $privateKeyPath = __DIR__ . '/../../keys/freeflow_private.pem';
        $privateKey = file_get_contents($privateKeyPath);
        if ($privateKey !== false && openssl_sign($canonical, $feedSignature, $privateKey, OPENSSL_ALGO_SHA256)) {
            $feedSignature = base64_encode($feedSignature);
            $xml->addChild('signature', $feedSignature);
        }

        // Save the feed
        file_put_contents($feedPath, $xml->asXML());
    }

    private function buildPayload(array $package, string $signature): string
    {
        $xml = new \SimpleXMLElement('<signedPayload></signedPayload>');
        $xml->addChild('type', (string)$package['package_type']);
        $xml->addChild('key', (string)$package['package_key']);
        $xml->addChild('version', (string)$package['version']);
        $xml->addChild('sha256', (string)($package['sha256'] ?? ''));
        $xml->addChild('signature', $signature);
        return $xml->asXML() ?: '';
    }

    private function extractPreview(string $zipPath, string $base): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return;
        }
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if (!$stat || !isset($stat['name'])) {
                continue;
            }
            $name = strtolower($stat['name']);
            if (str_ends_with($name, 'preview.png') || str_ends_with($name, 'preview.jpg') || str_ends_with($name, 'preview.jpeg')) {
                $stream = $zip->getStream($stat['name']);
                if ($stream) {
                    $dest = $base . '/' . basename($stat['name']);
                    $out = fopen($dest, 'wb');
                    if ($out) {
                        while (!feof($stream)) {
                            fwrite($out, fread($stream, 8192));
                        }
                        fclose($out);
                    }
                    fclose($stream);
                }
                break;
            }
        }
        $zip->close();
    }

    private function deletePackageFiles(array $package): void
    {
        // Delete from uploads
        $uploadPath = __DIR__ . '/../../../storage/devstore/uploads/' . $package['archive_name'];
        if (is_file($uploadPath)) {
            unlink($uploadPath);
        }

        // Delete from repository
        if (!empty($package['repo_path'])) {
            $repoPath = __DIR__ . '/../../../storage/devstore/repository/' . $package['repo_path'];
            if (is_dir($repoPath)) {
                $this->cleanup($repoPath);
            }
        }
    }
}
