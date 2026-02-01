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
use Core\Trust;
use Core\UpdateService;
use Core\Models\ExtensionModel;
use Core\Models\ThemesModel;
use Core\Models\UpdateChecksModel;

class AdminUpdatesController extends Controller
{
    public function index(): Response
    {
        Session::startAdmin();
        Auth::requireSuper();

        $checks = UpdateChecksModel::all();
        $checkMap = [];
        foreach ($checks as $row) {
            $checkMap[$row['component_type'] . ':' . $row['component_key']] = $row;
        }

        $components = [];
        $components[] = [
            'type' => 'system',
            'key' => 'system',
            'name' => Lang::get('FFCMS_SYSTEM'),
            'installed_version' => (string)Settings::get('system', 'version', '1.0'),
        ];
        foreach (ExtensionModel::all() as $ext) {
            $components[] = [
                'type' => 'extension',
                'key' => $ext['ext_key'],
                'name' => $ext['name'],
                'installed_version' => (string)($ext['version'] ?? ''),
            ];
        }
        foreach (ThemesModel::all() as $theme) {
            $components[] = [
                'type' => 'theme',
                'key' => $theme['theme_key'],
                'name' => $theme['name'],
                'installed_version' => (string)($theme['version'] ?? ''),
            ];
        }

        foreach ($components as &$component) {
            $lookup = $component['type'] . ':' . $component['key'];
            $component['check'] = $checkMap[$lookup] ?? null;
        }

        return $this->render('admin/updates', [
            'page_title' => Lang::get('FFCMS_UPDATES'),
            'components' => $components,
        ], 'Zulu');
    }

    public function check(): Response
    {
        Session::startAdmin();
        Auth::requireSuper();

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $trust = Trust::requireTrusted();
        if ($trust instanceof Response) {
            return $trust;
        }

        UpdateService::checkAll((int)Session::get('user_id', 0));
        Logger::info('updates', 'Update check run', ['user_id' => (int)Session::get('user_id', 0)]);

        return new Response('', 302, ['Location' => '/admin/updates']);
    }

    public function run(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requireSuper();

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $trust = Trust::requireTrusted();
        if ($trust instanceof Response) {
            return $trust;
        }

        $type = strtolower((string)$request->param('type'));
        $key = strtolower((string)$request->param('key'));
        $override = !empty($_POST['safety_override']) && Auth::isSuper();
        if ($override) {
            Logger::warning('admin', 'Update safety override', ['type' => $type, 'key' => $key, 'user_id' => (int)Session::get('user_id', 0)]);
        }
        $result = UpdateService::runUpdate($type, $key, (int)Session::get('user_id', 0), $override);
        if (!$result['ok']) {
            Logger::error('error', 'Update failed', ['type' => $type, 'key' => $key, 'reason' => $result['error'] ?? 'unknown']);
        } else {
            Logger::info('updates', 'Update completed', ['type' => $type, 'key' => $key]);
        }

        return new Response('', 302, ['Location' => '/admin/updates']);
    }

    public function runBatch(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requireSuper();

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $trust = Trust::requireTrusted();
        if ($trust instanceof Response) {
            return $trust;
        }

        $scope = strtolower((string)$request->param('scope'));
        if (!in_array($scope, ['all', 'system', 'extension', 'themes'], true)) {
            return new Response('Invalid scope', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        UpdateService::runBatch($scope, (int)Session::get('user_id', 0));
        Logger::info('updates', 'Batch update run', ['scope' => $scope, 'user_id' => (int)Session::get('user_id', 0)]);

        return new Response('', 302, ['Location' => '/admin/updates']);
    }
}
