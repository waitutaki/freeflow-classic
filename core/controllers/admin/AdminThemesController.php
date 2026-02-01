<?php
namespace Core\Controllers\Admin;

use Core\Auth;
use Core\Controller;
use Core\Csrf;
use Core\Lang;
use Core\Response;
use Core\Session;
use Core\Logger;
use Core\Theme;
use Core\ThemeInstaller;
use Core\Models\ThemesModel;
use Core\Models\ThemeOverridesModel;
use Core\Trust;

class AdminThemesController extends Controller
{
    public function index(): Response
    {
        Session::startAdmin();
        Auth::requireSuper();

        $themes = ThemesModel::all();
        return $this->render('admin/themes', [
            'page_title' => Lang::get('FFCMS_THEMES'),
            'themes' => $themes,
        ], 'Zulu');
    }

    public function upload(): Response
    {
        Session::startAdmin();
        Auth::requireSuper();

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        if (empty($_FILES['package'])) {
            return new Response('', 302, ['Location' => '/admin/themes']);
        }

        $trust = Trust::requireTrusted();
        if ($trust instanceof Response) {
            return $trust;
        }

        $result = \Core\PackageInstaller::installFromUpload($_FILES['package'], 'theme', (int)Session::get('user_id', 0), false);
        if (!$result['ok']) {
            Logger::error('error', 'Theme upload failed', ['reason' => $result['error'] ?? 'unknown']);
        } else {
            Logger::info('admin', 'Theme installed', ['theme_key' => $result['key'] ?? '']);
        }
        return new Response('', 302, ['Location' => '/admin/themes']);
    }

    public function activate(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requireSuper();

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
        return new Response('', 302, ['Location' => '/admin/themes']);
    }

    public function deactivate(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requireSuper();

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
        return new Response('', 302, ['Location' => '/admin/themes']);
    }

    public function customize(\Core\Request $request): Response
    {
        Session::startAdmin();
        Auth::requireSuper();

        $key = strtolower($request->param('key'));
        $theme = ThemesModel::findByKey($key);
        if (!$theme) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
        if ((int)$theme['is_active'] !== 1) {
            return new Response('Forbidden', 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $definition = Theme::loadDefinition($key);
        if (!$definition) {
            return new Response('Invalid theme', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $overrides = ThemeOverridesModel::allForTheme((int)$theme['id'], $theme['context']);
        return $this->render('admin/theme_customizer', [
            'page_title' => Lang::get('FFCMS_THEME_CUSTOMIZER'),
            'theme' => $theme,
            'definition' => $definition,
            'overrides' => $overrides,
        ], 'Zulu', [
            'footer_scripts' => '<script src="' . \Core\Asset::url('core', 'lib/irocolor/iro.js') . '"></script>'
                . '<script src="' . \Core\Asset::url('core', 'js/theme_customizer.js') . '"></script>',
        ]);
    }

    public function saveToken(): Response
    {
        Session::startAdmin();
        Auth::requireSuper();

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $themeKey = strtolower(trim($_POST['theme_key'] ?? ''));
        $token = trim($_POST['token'] ?? '');
        $value = trim($_POST['value'] ?? '');
        $context = trim($_POST['context'] ?? '');

        $theme = ThemesModel::findByKey($themeKey, $context);
        if (!$theme) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $definition = Theme::loadDefinition($themeKey);
        if (!$definition || !isset($definition['tokens'][$token])) {
            Logger::error('error', 'Invalid theme token', ['theme_key' => $themeKey, 'token' => $token]);
            return new Response('Invalid token', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $validated = Theme::validateTokenValue($definition['tokens'][$token], $value);
        if ($validated === null) {
            Logger::error('error', 'Invalid theme value', ['theme_key' => $themeKey, 'token' => $token]);
            return new Response('Invalid value', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        ThemeOverridesModel::saveOverride((int)$theme['id'], $context, $token, $validated, (int)Session::get('user_id', 0));
        Logger::info('admin', 'Theme autosave', ['theme_key' => $themeKey, 'token' => $token]);
        return new Response('OK', 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    public function saveAll(): Response
    {
        Session::startAdmin();
        Auth::requireSuper();

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $themeKey = strtolower(trim($_POST['theme_key'] ?? ''));
        $context = trim($_POST['context'] ?? '');
        $theme = ThemesModel::findByKey($themeKey, $context);
        if (!$theme) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $definition = Theme::loadDefinition($themeKey);
        if (!$definition) {
            return new Response('Invalid theme', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $userId = (int)Session::get('user_id', 0);
        foreach ($definition['tokens'] as $token => $def) {
            $value = trim($_POST['token_' . $token] ?? '');
            $validated = Theme::validateTokenValue($def, $value);
            if ($validated === null) {
                Logger::error('error', 'Invalid theme value', ['theme_key' => $themeKey, 'token' => $token]);
                continue;
            }
            ThemeOverridesModel::saveOverride((int)$theme['id'], $context, $token, $validated, $userId);
        }

        Logger::info('admin', 'Theme saved', ['theme_key' => $themeKey]);
        return new Response('', 302, ['Location' => '/admin/themes/customize/' . $themeKey]);
    }
}
