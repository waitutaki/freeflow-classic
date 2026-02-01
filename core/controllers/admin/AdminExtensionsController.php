<?php
namespace Core\Controllers\Admin;

use Core\Auth;
use Core\ACL;
use Core\Controller;
use Core\Csrf;
use Core\Lang;
use Core\Response;
use Core\Session;
use Core\Logger;
use Core\Models\extensionModel;
use Core\Models\ThemesModel;
use Core\Models\UpdateChecksModel;
use Core\PackageInstaller;
use Core\UpdateService;
use Core\Trust;

class AdminextensionController extends Controller
{
    public function index(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requireAdmin();

        $tab = $_GET['tab'] ?? 'extension';
        if ($tab === 'themes') {
            Auth::requirePermission('manage_themes');
        } else if ($tab === 'web') {
            Auth::requirePermission('install_extension');
        } else {
            Auth::requirePermission('manage_extension');
            $tab = 'extension';
        }

        // Per specification: Extensions Manager is the single control center for updates
        // Integrate update status information into the extension and themes lists
        // Fetch update status from the database
        $updateMap = $this->getUpdateStatusMap();

        // Add update status to extensions
        $extension = extensionModel::all();
        foreach ($extension as &$ext) {
            $key = 'extension:' . $ext['ext_key'];
            $ext['update_status'] = $updateMap[$key]['status'] ?? 'unknown';
            $ext['available_version'] = $updateMap[$key]['available_version'] ?? null;
        }

        // Add update status to themes
        $themes = ThemesModel::all();
        foreach ($themes as &$theme) {
            $key = 'theme:' . $theme['theme_key'];
            $theme['update_status'] = $updateMap[$key]['status'] ?? 'unknown';
            $theme['available_version'] = $updateMap[$key]['available_version'] ?? null;
        }

        // Get available packages from repository API for web tab
        $devstorePackages = [];
        if ($tab === 'web') {
            // Use UpdateService to fetch available packages via API
            $devstorePackages = UpdateService::fetchAvailablePackages();
        }

        return $this->render('admin/extension', [
            'page_title' => Lang::get('FFCMS_extension'),
            'tab' => $tab,
            'extension' => $extension,
            'themes' => $themes,
            'devstorePackages' => $devstorePackages,
        ], 'Zulu');
    }

    public function installForm(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requireAdmin();

        $tab = $_GET['tab'] ?? 'extension';
        if ($tab === 'themes') {
            Auth::requirePermission('install_themes');
        } else {
            Auth::requirePermission('install_extension');
            $tab = 'extension';
        }

        return $this->render('admin/extension_install', [
            'page_title' => Lang::get('FFCMS_INSTALL_PACKAGE'),
            'tab' => $tab,
        ], 'Zulu');
    }

    public function install(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requireAdmin();

        $tab = $_GET['tab'] ?? 'extension';
        if ($tab === 'themes') {
            Auth::requirePermission('install_themes');
        } else {
            Auth::requirePermission('install_extension');
            $tab = 'extension';
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $trust = Trust::requireTrusted();
        if ($trust instanceof Response) {
            return $trust;
        }

        $override = !empty($_POST['safety_override']) && Auth::isSuper();
        $type = $tab === 'themes' ? 'theme' : 'extension';
        if ($override) {
            Logger::warning('admin', 'Safety override requested', ['type' => $type, 'user_id' => (int)Session::get('user_id', 0)]);
        }
        $result = PackageInstaller::installFromUpload($_FILES['package'] ?? [], $type, (int)Session::get('user_id', 0), $override);
        if (!$result['ok']) {
            Logger::error('error', 'Package install failed', ['type' => $type, 'reason' => $result['error'] ?? 'unknown']);
        } else {
            Logger::info('admin', 'Package installed', ['type' => $type, 'key' => $result['key'] ?? '']);
        }

        return new Response('', 302, ['Location' => '/admin/extension?tab=' . $tab]);
    }

    public function uninstallConfirm(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requirePermission('install_extension');

        $extKey = strtolower((string)$request->param('ext_key'));
        $ext = extensionModel::findByKey($extKey);
        if (!$ext) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        return $this->render('admin/extension_uninstall', [
            'page_title' => Lang::get('FFCMS_UNINSTALL'),
            'extension' => $ext,
        ], 'Zulu');
    }

    public function uninstall(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requirePermission('install_extension');

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $extKey = strtolower((string)$request->param('ext_key'));
        $result = PackageInstaller::uninstallExtension($extKey, (int)Session::get('user_id', 0));
        if (!$result['ok']) {
            Logger::error('error', 'Extension uninstall failed', ['ext_key' => $extKey, 'reason' => $result['error'] ?? 'unknown']);
        } else {
            Logger::info('admin', 'Extension uninstalled', ['ext_key' => $extKey]);
        }

        return new Response('', 302, ['Location' => '/admin/extension?tab=extension']);
    }

    public function toggleExtension(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requirePermission('manage_extension');

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $extKey = strtolower((string)$request->param('ext_key'));
        $ext = extensionModel::findByKey($extKey);
        if (!$ext) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        if (!empty($ext['is_core'])) {
            return new Response('', 302, ['Location' => '/admin/extension?tab=extension']);
        }

        $newState = empty($ext['is_active']);
        extensionModel::setActive($extKey, $newState);
        Logger::info('admin', 'Extension toggled', ['ext_key' => $extKey, 'is_active' => $newState ? 1 : 0]);

        return new Response('', 302, ['Location' => '/admin/extension?tab=extension']);
    }

    public function activateTheme(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requirePermission('manage_themes');

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $id = (int)$request->param('id');
        $theme = ThemesModel::findById($id);
        if (!$theme || $theme['context'] !== 'site') {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        ThemesModel::setActive($id, 'site');
        Logger::info('admin', 'Theme activated', ['theme_key' => $theme['theme_key'] ?? '']);
        return new Response('', 302, ['Location' => '/admin/extension?tab=themes']);
    }

    public function deactivateTheme(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requirePermission('manage_themes');

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $id = (int)$request->param('id');
        $theme = ThemesModel::findById($id);
        if (!$theme || $theme['context'] !== 'site') {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        $fallback = ThemesModel::findByKey('alpha', 'site');
        if ($fallback) {
            ThemesModel::setActive((int)$fallback['id'], 'site');
            Logger::info('admin', 'Theme deactivated', ['theme_key' => $theme['theme_key'] ?? '']);
        }
        return new Response('', 302, ['Location' => '/admin/extension?tab=themes']);
    }

    public function uninstallThemeConfirm(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requirePermission('install_themes');

        $id = (int)$request->param('id');
        $theme = ThemesModel::findById($id);
        if (!$theme) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        return $this->render('admin/themes_uninstall', [
            'page_title' => Lang::get('FFCMS_UNINSTALL'),
            'theme' => $theme,
        ], 'Zulu');
    }

    public function uninstallTheme(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requirePermission('install_themes');

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $id = (int)$request->param('id');
        $theme = ThemesModel::findById($id);
        if (!$theme) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $result = PackageInstaller::uninstallTheme($theme, (int)Session::get('user_id', 0));
        if (!$result['ok']) {
            Logger::error('error', 'Theme uninstall failed', ['theme_key' => $theme['theme_key'] ?? '', 'reason' => $result['error'] ?? 'unknown']);
        } else {
            Logger::info('admin', 'Theme uninstalled', ['theme_key' => $theme['theme_key'] ?? '']);
        }

        return new Response('', 302, ['Location' => '/admin/extension?tab=themes']);
    }

    public function inactive(\Core\Request $request): Response
    {
        return self::inactiveResponse((string)$request->param('ext_key'));
    }

    public static function inactiveResponse(string $extKey): Response
    {
        Session::startAdmin();
        Auth::requireAdmin();

        $extKey = strtolower($extKey);
        $ext = extensionModel::findByKey($extKey);
        if (!$ext) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        return (new self())->render('admin/extension_inactive', [
            'page_title' => Lang::get('FFCMS_EXTENSION_INACTIVE'),
            'extension' => $ext,
            'can_activate' => ACL::can('manage_extension'),
        ], 'Zulu');
    }

    /**
     * Install package from DevStore
     */
    public function installFromWeb(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requireAdmin();
        Auth::requirePermission('install_extension');

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            Session::setFlash('error', 'Invalid CSRF token');
            return new Response('', 302, ['Location' => '/admin/extension?tab=web']);
        }

        Session::setFlash('info', 'Web install: request received');

        $type = $_POST['type'] ?? '';
        $key = trim((string)($_POST['key'] ?? ''));

        $version = $_POST['version'] ?? '';

        if (!in_array($type, ['extension', 'theme'], true) || $key === '') {
            Session::setFlash('error', 'Invalid package type or key');
            return new Response('', 302, ['Location' => '/admin/extension?tab=web']);
        }

        Session::setFlash('info', 'Web install: parameters accepted');

        Session::setFlash('info', 'Web install: starting download');

        // Download and install from repository API
        $result = $this->installFromWebApi($type, $key, $version);
        if (!$result['ok']) {
            Logger::error('error', 'Web install failed', [
                'type' => $type,
                'key' => $key,
                'version' => $version,
                'reason' => $result['error'] ?? 'unknown'
            ]);
            Session::setFlash('error', 'Web install: installation failed: ' . ($result['error'] ?? 'Unknown error'));
            return new Response('', 302, ['Location' => '/admin/extension?tab=web']);
        } else {
            Logger::info('admin', 'Package installed from DevStore', [
                'type' => $type,
                'key' => $key,
                'version' => $version
            ]);
            Session::setFlash('success', 'Web install: installation complete');
        }

        return new Response('', 302, ['Location' => '/admin/extension?tab=extension']);
    }

  /**
 * Install package from repository API
 */
private function installFromWebApi(string $type, string $key, string $version): array
{
   // Download package from repository API
   $downloadResult = UpdateService::downloadPackage($type, $key, $version);

   if (!$downloadResult['ok']) {
       return [
           'ok' => false,
           'error' => $downloadResult['error'] ?? 'Download failed'
       ];
   }

   $zipPath = $downloadResult['path'] ?? '';
   $resolved = $downloadResult['resolved'] ?? null;

   if ($zipPath === '' || !is_file($zipPath)) {
       return [
           'ok' => false,
           'error' => 'Package file not found'
       ];
   }

   // Log resolved details
   if ($resolved) {
       Logger::info('updates', 'Web install resolved', [
           'type' => $resolved['package_type'],
           'key' => $resolved['package_key'],
           'version' => $resolved['version'],
           'resolved_repo_path' => $resolved['repo_path'],
           'download_url' => $resolved['download_url'],
           'filename' => $resolved['filename'],
           'sha256' => $resolved['sha256'],
           'http_status' => $downloadResult['http_status'] ?? null,
           'sha256_result' => $downloadResult['sha256_result'] ?? 'unknown',
       ]);
   }

   Session::setFlash('info', 'Web install: starting installer');

   return PackageInstaller::installFromPath(
       $zipPath,
       $type,
       (int) Session::get('user_id', 0),
       false
   );
}

    /**
     * Get update status map from the database
     */
    private function getUpdateStatusMap(): array
    {
        $updateMap = [];
        $checks = UpdateChecksModel::all();
        foreach ($checks as $check) {
            $key = $check['component_type'] . ':' . $check['component_key'];
            $updateMap[$key] = [
                'status' => $check['status'] ?? 'unknown',
                'available_version' => $check['available_version'] ?? null,
            ];
        }
        return $updateMap;
    }
}
