<?php
namespace Core\Controllers\Site;

use Core\Auth;
use Core\Controller;
use Core\Csrf;
use Core\ElementRenderer;
use Core\Lang;
use Core\Logger;
use Core\Response;
use Core\Session;
use Core\Settings;
use Core\Theme;
use Core\Models\DevstoreDevelopersModel;
use Core\Models\DevstorePackagesModel;
use Core\Models\DevstoreComplianceReportsModel;
use Core\Models\DevstoreComplianceService;

class DevstoreController extends Controller
{
    public function portal(): Response
    {
        Session::startSite();
        $themeKey = Theme::activeKey('site');
        $regions = ElementRenderer::renderRegions('site', $themeKey, '/devstore');

        if (!$this->isEnabled()) {
            return $this->render('site/devstore/portal', [
                'page_title' => Lang::get('DEVSTORE.PORTAL_TITLE'),
                'disabled' => true,
            ], $themeKey, $regions);
        }

        $developer = null;
        if (Auth::isAuthenticated()) {
            $developer = DevstoreDevelopersModel::findByUserId((int)Session::get('user_id', 0));
        }

        return $this->render('site/devstore/portal', [
            'page_title' => Lang::get('DEVSTORE.PORTAL_TITLE'),
            'developer' => $developer,
            'disabled' => false,
        ], $themeKey, $regions);
    }

    public function register(): Response
    {
        Session::startSite();
        if (!Auth::isAuthenticated()) {
            return new Response('', 302, ['Location' => '/login']);
        }
        if (!$this->isEnabled()) {
            return new Response('', 302, ['Location' => '/devstore']);
        }
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        DevstoreDevelopersModel::createRequest((int)Session::get('user_id', 0));
        Logger::info('admin', 'Devstore access requested', ['user_id' => (int)Session::get('user_id', 0)]);

        return new Response('', 302, ['Location' => '/devstore']);
    }

    public function upload(): Response
    {
        Session::startSite();
        if (!Auth::isAuthenticated()) {
            return new Response('', 302, ['Location' => '/login']);
        }
        if (!$this->isEnabled()) {
            return new Response('', 302, ['Location' => '/devstore']);
        }

        $developer = DevstoreDevelopersModel::findByUserId((int)Session::get('user_id', 0));
        if (!$developer || $developer['status'] !== 'approved') {
            return new Response('', 302, ['Location' => '/devstore']);
        }

        $themeKey = Theme::activeKey('site');
        $regions = ElementRenderer::renderRegions('site', $themeKey, '/devstore/upload');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
                return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
            }
            if (empty($_POST['acknowledge_immutability'])) {
                return new Response('', 302, ['Location' => '/devstore/upload?error=ack']);
            }

            $result = $this->handleUpload($developer);
            if (!$result['ok']) {
                return $this->render('site/devstore/portal_upload', [
                    'page_title' => Lang::get('DEVSTORE.PORTAL_UPLOAD_TITLE'),
                    'error' => $result['error'],
                ], $themeKey, $regions);
            }

            return new Response('', 302, ['Location' => '/devstore/my-submissions']);
        }

        return $this->render('site/devstore/portal_upload', [
            'page_title' => Lang::get('DEVSTORE.PORTAL_UPLOAD_TITLE'),
        ], $themeKey, $regions);
    }

    public function submissions(): Response
    {
        Session::startSite();
        if (!Auth::isAuthenticated()) {
            return new Response('', 302, ['Location' => '/login']);
        }
        if (!$this->isEnabled()) {
            return new Response('', 302, ['Location' => '/devstore']);
        }

        $developer = DevstoreDevelopersModel::findByUserId((int)Session::get('user_id', 0));
        if (!$developer) {
            return new Response('', 302, ['Location' => '/devstore']);
        }

        $themeKey = Theme::activeKey('site');
        $regions = ElementRenderer::renderRegions('site', $themeKey, '/devstore/my-submissions');

        return $this->render('site/devstore/portal_submissions', [
            'page_title' => Lang::get('DEVSTORE.PORTAL_SUBMISSIONS_TITLE'),
            'packages' => DevstorePackagesModel::listByDeveloper((int)$developer['id']),
        ], $themeKey, $regions);
    }

    public function directoryextension(): Response
    {
        return $this->renderDirectory('extension');
    }

    public function directoryThemes(): Response
    {
        return $this->renderDirectory('theme');
    }

    public function directoryExtensionDetail(\Core\Request $request): Response
    {
        return $this->renderDirectoryDetail('extension', $request->param('key'));
    }

    public function directoryThemeDetail(\Core\Request $request): Response
    {
        return $this->renderDirectoryDetail('theme', $request->param('key'));
    }

    public function repository(): Response
    {
        Session::startSite();
        if (!$this->isEnabled()) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $themeKey = Theme::activeKey('site');
        $regions = ElementRenderer::renderRegions('site', $themeKey, '/repository');

        $packages = array_merge(
            \Core\Models\DevstorePackagesModel::allPublishedByType('extension'),
            \Core\Models\DevstorePackagesModel::allPublishedByType('theme')
        );

        return $this->render('site/devstore/repository', [
            'page_title' => Lang::get('DEVSTORE.REPOSITORY_TITLE'),
            'packages' => $packages,
        ], $themeKey, $regions);
    }

    public function installFromRepository(\Core\Request $request): Response
    {
        Session::startSite();
        if (!$this->isEnabled()) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        // Require admin for installation
        if (!Auth::isAuthenticated() || !Auth::isAdmin()) {
            return new Response('', 302, ['Location' => '/login']);
        }

        $type = $request->param('type');
        $key = $request->param('key');

        if (!in_array($type, ['extension', 'theme'], true)) {
            return new Response('Invalid type', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        // Find latest published version
        $packages = \Core\Models\DevstorePackagesModel::allPublishedByType($type);
        $packages = array_values(array_filter($packages, static fn ($item) => $item['package_key'] === $key));
        if (!$packages) {
            return new Response('Package not found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        usort($packages, static fn ($a, $b) => version_compare($b['version'], $a['version']));
        $package = $packages[0];

        // Download the ZIP
        $repoPath = $package['repo_path'];
        $zipPath = __DIR__ . '/../../../storage/devstore/repository/' . $repoPath . '/' . $key . '-' . $package['version'] . '.zip';
        if (!is_file($zipPath)) {
            return new Response('Package file not found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        // Install
        $result = \Core\PackageInstaller::installFromPath($zipPath, $type, (int)Session::get('user_id'), false);
        if (!$result['ok']) {
            return new Response('Installation failed: ' . $result['error'], 500, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        return new Response('', 302, ['Location' => '/admin/extension']);
    }

    public function downloadFromRepository(\Core\Request $request): Response
    {
        Session::startSite();
        if (!$this->isEnabled()) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $type = $request->param('type');
        $key = $request->param('key');

        if (!in_array($type, ['extension', 'theme'], true)) {
            return new Response('Invalid type', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        // Find latest published version
        $packages = \Core\Models\DevstorePackagesModel::allPublishedByType($type);
        $packages = array_values(array_filter($packages, static fn ($item) => $item['package_key'] === $key));
        if (!$packages) {
            return new Response('Package not found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        usort($packages, static fn ($a, $b) => version_compare($b['version'], $a['version']));
        $package = $packages[0];

        // Serve the ZIP
        $repoPath = $package['repo_path'];
        $zipPath = __DIR__ . '/../../../storage/devstore/repository/' . $repoPath . '/' . $key . '-' . $package['version'] . '.zip';
        if (!is_file($zipPath)) {
            return new Response('Package file not found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $content = file_get_contents($zipPath);
        if ($content === false) {
            return new Response('File read error', 500, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        return new Response($content, 200, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="' . $key . '-' . $package['version'] . '.zip"',
            'Content-Length' => strlen($content),
        ]);
    }

    private function renderDirectory(string $type): Response
    {
        Session::startSite();
        if (!$this->isEnabled()) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $themeKey = Theme::activeKey('site');
        $regions = ElementRenderer::renderRegions('site', $themeKey, '/devstore/' . $type . 's');

        return $this->render('site/devstore/directory_list', [
            'page_title' => Lang::get('DEVSTORE.DIRECTORY_TITLE'),
            'type' => $type,
            'packages' => \Core\Models\DevstorePackagesModel::allPublishedByType($type),
        ], $themeKey, $regions);
    }

    private function renderDirectoryDetail(string $type, string $key): Response
    {
        Session::startSite();
        if (!$this->isEnabled()) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $key = strtolower($key);
        $packages = \Core\Models\DevstorePackagesModel::allPublishedByType($type);
        $packages = array_values(array_filter($packages, static fn ($item) => $item['package_key'] === $key));
        if (!$packages) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $themeKey = Theme::activeKey('site');
        $regions = ElementRenderer::renderRegions('site', $themeKey, '/devstore/' . $type . 's/' . $key);

        return $this->render('site/devstore/directory_detail', [
            'page_title' => Lang::get('DEVSTORE.DIRECTORY_DETAIL_TITLE'),
            'type' => $type,
            'packages' => $packages,
        ], $themeKey, $regions);
    }

    private function handleUpload(array $developer): array
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
            Logger::error('devstore', 'Manifest file missing in package', ['developer_id' => $developer['id']]);
            $this->cleanup($stageDir);
            return ['ok' => false, 'error' => Lang::get('DEVSTORE.ERROR_MANIFEST')];
        }

        $manifest = DevstoreComplianceService::loadManifest($manifestPath);
        if (!$manifest) {
            Logger::error('devstore', 'Manifest file invalid', ['developer_id' => $developer['id'], 'path' => $manifestPath]);
            $this->cleanup($stageDir);
            return ['ok' => false, 'error' => Lang::get('DEVSTORE.ERROR_MANIFEST')];
        }

        $existing = DevstorePackagesModel::findByKeyVersion($manifest['package_type'], $manifest['key'], $manifest['version']);
        if ($existing && in_array($existing['status'] ?? '', ['approved', 'published'], true)) {
            $this->cleanup($stageDir);
            return ['ok' => false, 'error' => Lang::get('DEVSTORE.ERROR_IMMUTABLE')];
        }

        $sha256 = hash_file('sha256', $archivePath) ?: '';
        $compliance = DevstoreComplianceService::inspectPackage($extractDir, $manifest);
        $status = $compliance['ok'] ? 'approved' : 'rejected';

        if ($existing && ($existing['status'] ?? '') === 'rejected') {
            // Update existing rejected package
            DevstorePackagesModel::updateStatus((int)$existing['id'], $status, $sha256, $archiveName);
            $packageId = (int)$existing['id'];
        } else {
            // Create new package
            $packageId = DevstorePackagesModel::create([
                'developer_id' => (int)$developer['id'],
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
            $this->publishPackage($packageId, (int)$developer['user_id']);
        }

        $this->cleanup($stageDir);

        if (!$compliance['ok']) {
            Logger::error('devstore', 'Compliance failed', ['developer_id' => $developer['id'], 'violations' => $compliance['violations']]);
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
        $typeDir = $type . 's'; // extension or themes

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
        if ($zip->open($repoBase . '/' . $newFilename) === true) {
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

    private function isEnabled(): bool
    {
        return (bool)Settings::get('devstore', 'enabled', true);
    }
}
