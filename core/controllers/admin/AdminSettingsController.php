<?php
namespace Core\Controllers\Admin;

use Core\Auth;
use Core\Connection;
use Core\Controller;
use Core\Csrf;
use Core\Freegate;
use Core\Lang;
use Core\Logger;
use Core\Response;
use Core\Session;
use Core\Settings;
use Core\Template;
use Core\Models\SettingsModel;
use Core\Models\MailTemplatesModel;
use Core\Models\MailWrappersModel;

class AdminSettingsController extends Controller
{



public function index(): Response
    {
        Session::startAdmin();
        Auth::requireAdmin();
        $tab = $_GET['tab'] ?? 'global';
        $subtab = $_GET['subtab'] ?? 'general';
        
        // Handle the new global settings structure
        if ($tab === 'global') {
            $validSubtabs = ['general', 'maintenance', 'seo', 'meta', 'media', 'locale'];
            $subtab = in_array($subtab, $validSubtabs, true) ? $subtab : 'general';
            
            // Check subtab permissions
            $this->checkGlobalSubtabPermission($subtab);
        } elseif ($tab === 'mail') {
            // Handle mail tab with subtabs
            $validSubtabs = ['templates', 'wrappers', 'settings'];
            $subtab = in_array($subtab, $validSubtabs, true) ? $subtab : 'templates';
            
            // Check mail tab permissions based on subtab
            if ($subtab === 'templates') {
                Auth::requirePermission('manage_settings_mail_templates');
            } elseif ($subtab === 'wrappers') {
                Auth::requirePermission('manage_settings_mail_wrappers');
            } elseif ($subtab === 'settings') {
                Auth::requirePermission('manage_settings_mail');
            }
        } else {
            // For non-global tabs, validate the tab and check permissions
            $validTabs = ['logs', 'security', 'maintenance', 'freegate', 'mail'];
            if (!in_array($tab, $validTabs, true)) {
                // If an invalid tab is specified, default to global but show main settings view
                $tab = 'global';
            }

            if ($tab === 'logs') {
                Auth::requireSuper();
            } elseif ($tab === 'maintenance') {
                Auth::requireSuper();
            } elseif ($tab === 'freegate') {
                Auth::requireSuper();
            } elseif ($tab === 'security') {
                Auth::requirePermission('manage_settings_security');
            }
        }

        $maintenanceResult = null;
        $errors = [];
        $success = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
                return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
            }

            if (defined('APP_DEBUG') && APP_DEBUG === true) {
                error_log('[FORMFIX] ' . __METHOD__ . ' POST keys=' . implode(',', array_keys($_POST)));
            }

            $userId = (int)Session::get('user_id', 0);
            
            if ($tab === 'global') {
                $errors = $this->saveGlobalSubtab($subtab, $_POST, $userId);
                if (empty($errors)) {
                    $success = Lang::get('FFCMS_SETTINGS_SAVED');
                    // Audit logging
                    Logger::info('admin', 'Global settings saved', [
                        'user_id' => $userId,
                        'subtab' => $subtab,
                        'keys_changed' => array_keys($_POST)
                    ]);
                    return new Response('', 302, ['Location' => '/admin/settings?tab=global&subtab=' . urlencode($subtab)]);
                }
            } elseif ($tab === 'security') {
                $errors = SettingsModel::saveSecurity($_POST, $userId);
                if (!$errors) {
                    return new Response('', 302, ['Location' => '/admin/settings?tab=security']);
                }
            } elseif ($tab === 'maintenance') {
                $maintenanceResult = $this->runMaintenance($userId);
            } elseif ($tab === 'freegate') {
                $previousMode = (string)\Core\Settings::get('freegate.mode', 'external');
                $mode = $_POST['freegate_mode'] ?? 'external';
                $mode = in_array($mode, ['external', 'home'], true) ? $mode : 'external';
                \Core\Settings::set('freegate_mode', $mode, 'string', false, 'global');
                if ($mode !== $previousMode) {
                    \Core\Models\FreegateModel::addTimeline([
                        'event_type' => 'learning',
                        'title' => 'FFCMS.FREEGATE_TIMELINE_MODE_SWITCH',
                        'details' => $previousMode . ' -> ' . $mode,
                        'severity' => 'medium',
                        'correlation_id' => null,
                        'actor_user_id' => $userId,
                    ]);
                }
                return new Response('', 302, ['Location' => '/admin/settings?tab=freegate']);
            } elseif ($tab === 'mail' && $subtab === 'settings') {
                $errors = $this->saveMailSettings($_POST, $userId);
                if (empty($errors)) {
                    $success = Lang::get('FFCMS_SETTINGS_SAVED');
                    Logger::info('admin', 'Mail settings saved', [
                        'user_id' => $userId,
                        'keys_changed' => array_keys($_POST)
                    ]);
                    return new Response('', 302, ['Location' => '/admin/settings?tab=mail&subtab=settings']);
                }
            }
        }

        $logsData = $this->logsState();
        $templates = MailTemplatesModel::all();
        $missingTemplates = $this->missingTemplates($templates);
        $wrappers = MailWrappersModel::all();

        $data = [
            'page_title' => Lang::get('FFCMS_SETTINGS'),
            'tab' => $tab,
            'subtab' => $subtab,
            'logs' => $logsData,
            'mail_templates' => $templates,
            'missing_templates' => $missingTemplates,
            'mail_wrappers' => $wrappers,
            'maintenance_result' => $maintenanceResult,
            'errors' => $errors,
            'success' => $success,
            'site_name' => Settings::get('site_name', '', 'global'),
            'site_tagline' => Settings::get('site_tagline', '', 'global'),
            'site_base_url' => Settings::get('site_base_url', '', 'global'),
            'maintenance_enabled' => Settings::get('maintenance_enabled', false, 'global'),
            'maintenance_message' => Settings::get('maintenance_message', '', 'global'),
            'maintenance_retry_after_minutes' => Settings::get('maintenance_retry_after_minutes', 60, 'global'),
            'maintenance_allowlist_ips' => Settings::get('maintenance_allowlist_ips', '', 'global'),
            'seo_default_title' => Settings::get('seo_default_title', '', 'global'),
            'seo_default_description' => Settings::get('seo_default_description', '', 'global'),
            'seo_default_robots_index' => Settings::get('seo_default_robots_index', true, 'global'),
            'seo_default_robots_follow' => Settings::get('seo_default_robots_follow', true, 'global'),
            'seo_canonical_base_url_override' => Settings::get('seo_canonical_base_url_override', '', 'global'),
            'seo_og_site_name' => Settings::get('seo_og_site_name', '', 'global'),
            'seo_og_default_image_asset_id' => Settings::get('seo_og_default_image_asset_id', 0, 'global'),
            'seo_twitter_card_type' => Settings::get('seo_twitter_card_type', 'summary_large_image', 'global'),
            'seo_sitemap_enabled' => Settings::get('seo_sitemap_enabled', true, 'global'),
            'meta_post_enabled' => Settings::get('meta_post_enabled', true, 'global'),
            'meta_post_show_author' => Settings::get('meta_post_show_author', true, 'global'),
            'meta_post_author_position' => Settings::get('meta_post_author_position', 'above', 'global'),
            'meta_post_show_publish_date' => Settings::get('meta_post_show_publish_date', true, 'global'),
            'meta_post_publish_date_position' => Settings::get('meta_post_publish_date_position', 'above', 'global'),
            'meta_post_show_updated_date' => Settings::get('meta_post_show_updated_date', true, 'global'),
            'meta_post_updated_date_position' => Settings::get('meta_post_updated_date_position', 'above', 'global'),
            'meta_post_show_categories' => Settings::get('meta_post_show_categories', true, 'global'),
            'meta_post_categories_position' => Settings::get('meta_post_categories_position', 'above', 'global'),
            'meta_post_show_tags' => Settings::get('meta_post_show_tags', true, 'global'),
            'meta_post_tags_position' => Settings::get('meta_post_tags_position', 'above', 'global'),
            'meta_page_enabled' => Settings::get('meta_page_enabled', true, 'global'),
            'meta_page_show_author' => Settings::get('meta_page_show_author', true, 'global'),
            'meta_page_author_position' => Settings::get('meta_page_author_position', 'above', 'global'),
            'meta_page_show_publish_date' => Settings::get('meta_page_show_publish_date', true, 'global'),
            'meta_page_publish_date_position' => Settings::get('meta_page_publish_date_position', 'above', 'global'),
            'meta_page_show_updated_date' => Settings::get('meta_page_show_updated_date', true, 'global'),
            'meta_page_updated_date_position' => Settings::get('meta_page_updated_date_position', 'above', 'global'),
            'meta_page_show_categories' => Settings::get('meta_page_show_categories', true, 'global'),
            'meta_page_categories_position' => Settings::get('meta_page_categories_position', 'above', 'global'),
            'meta_page_show_tags' => Settings::get('meta_page_show_tags', true, 'global'),
            'meta_page_tags_position' => Settings::get('meta_page_tags_position', 'above', 'global'),
            'media_upload_max_mb' => Settings::get('media_upload_max_mb', 10, 'global'),
            'media_image_max_width' => Settings::get('media_image_max_width', 4000, 'global'),
            'media_image_max_height' => Settings::get('media_image_max_height', 4000, 'global'),
            'media_image_quality' => Settings::get('media_image_quality', 85, 'global'),
            'media_image_generate_thumbnails' => Settings::get('media_image_generate_thumbnails', true, 'global'),
            'default_language' => Settings::get('default_language', 'en-GB', 'global'),
            'language_auto_detect' => Settings::get('language_auto_detect', true, 'global'),
            'force_language' => Settings::get('force_language', 'off', 'global'),
            'date_format' => Settings::get('date_format', 'Y-m-d', 'global'),
            'time_format' => Settings::get('time_format', 'H:i:s', 'global'),
            'datetime_format' => Settings::get('datetime_format', 'd M Y - H:i:s', 'global'),
            'mail_transport' => Settings::get('mail_transport', 'php_mail', 'global'),
            'mail_from_email' => Settings::get('mail_from_email', '', 'global'),
            'mail_from_name' => Settings::get('mail_from_name', '', 'global'),
            'mail_replyto_email' => Settings::get('mail_replyto_email', '', 'global'),
            'smtp_host' => Settings::get('smtp_host', '', 'global'),
            'smtp_port' => Settings::get('smtp_port', 587, 'global'),
            'smtp_security' => Settings::get('smtp_security', 'none', 'global'),
            'smtp_username' => Settings::get('smtp_username', '', 'global'),
            'mail_force_plain_text' => Settings::get('mail_force_plain_text', false, 'global'),
            'mail_log_enabled' => Settings::get('mail_log_enabled', true, 'global'),
            'captcha_provider' => Settings::get('captcha_provider', 'none', 'global'),
            'antispam_enabled' => Settings::get('antispam_enabled', true, 'global'),
            'antispam_honeypot_default' => Settings::get('antispam_honeypot_default', true, 'global'),
            'antispam_time_default' => Settings::get('antispam_time_default', true, 'global'),
            'antispam_min_seconds' => Settings::get('antispam_min_seconds', 3, 'global'),
            'failcount_enabled' => Settings::get('failcount_enabled', false, 'global'),
            'failcount_limit' => Settings::get('failcount_limit', 3, 'global'),
            'failcount_cooldown_enabled' => Settings::get('failcount_cooldown_enabled', false, 'global'),
            'failcount_cooldown_minutes' => Settings::get('failcount_cooldown_minutes', 5, 'global'),
            'freegate_mode' => Settings::get('freegate.mode', 'external', 'global'),


            'devstore_detected' => Freegate::devstoreDetected(),
        ];

        // Always render the main settings view since we've consolidated everything
        return $this->render('admin/settings', $data, 'Zulu');
    }

    private function logsState(): array
    {
        $channels = Logger::files();
        $selectedLines = $this->resolveLineLimit();

        $download = $_GET['download'] ?? '';
        if ($download !== '') {
            $response = $this->downloadLog($channels, $download);
            $response->send();
            exit;
        }

        $clear = $_GET['clear'] ?? '';
        $confirm = $_POST['confirm'] ?? '';
        if ($clear !== '') {
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && !Csrf::validate($_POST['csrf_token'] ?? null)) {
                return [];
            }
            $response = $this->clearLog($channels, $clear, $confirm === '1');
            $response->send();
            exit;
        }

        $view = $_GET['view'] ?? '';
        $viewContent = '';
        if ($view !== '') {
            $viewContent = $this->readLog($channels, $view, $selectedLines);
        }

        $datetimeFormat = Settings::get('datetime_format', 'Y-m-d H:i:s', 'global');
        $logFiles = [];
        foreach ($channels as $channel => $file) {
            $path = __DIR__ . '/../../../storage/logs/' . $file;
            $logFiles[] = [
                'channel' => $channel,
                'file' => $file,
                'size' => file_exists($path) ? filesize($path) : 0,
                'modified_at' => file_exists($path) ? date($datetimeFormat, filemtime($path)) : '',
            ];
        }

        return [
            'log_files' => $logFiles,
            'selected_lines' => $selectedLines,
            'view_log' => $view,
            'view_content' => $viewContent,
            'confirm_clear' => '',
        ];
    }

    private function resolveLineLimit(): int
    {
        $lines = isset($_GET['lines']) ? (int)$_GET['lines'] : 0;
        $allowed = [10, 25, 50, 100, 0];
        if (!in_array($lines, $allowed, true)) {
            $lines = 50;
        }
        Session::set('log_lines', $lines);
        return $lines;
    }

    private function readLog(array $channels, string $name, int $lines): string
    {
        $file = $channels[$name] ?? '';
        if ($file === '') {
            return '';
        }
        $path = __DIR__ . '/../../../storage/logs/' . $file;
        if (!is_file($path)) {
            return '';
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return '';
        }

        $rows = preg_split('/\\r\\n|\\r|\\n/', $content);
        $rows = array_filter($rows, static fn ($line) => $line !== '');
        if ($lines > 0) {
            $rows = array_slice($rows, -$lines);
        }

        $safe = array_map(static fn ($line) => Template::escape($line), $rows);
        return implode("\n", $safe);
    }

    private function downloadLog(array $channels, string $name): Response
    {
        $file = $channels[$name] ?? '';
        if ($file === '') {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $path = __DIR__ . '/../../../storage/logs/' . $file;
        if (!is_file($path)) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        return new Response($content, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $file . '"',
        ]);
    }

    private function clearLog(array $channels, string $name, bool $confirmed): Response
    {
        $file = $channels[$name] ?? '';
        if ($file === '') {
            return new Response('Not Found', 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $path = __DIR__ . '/../../../storage/logs/' . $file;
        if (!$confirmed) {
            return new Response('', 302, ['Location' => '/admin/settings?tab=logs&confirm=' . urlencode($name)]);
        }

        Logger::info('admin', 'Attempting to clear log', ['path' => $path, 'size_before' => file_exists($path) ? filesize($path) : 'not exists']);
        $result = file_put_contents($path, '');
        if ($result === false) {
            Logger::error('admin', 'Failed to clear log file', ['path' => $path]);
        } else {
            Logger::info('admin', 'Log cleared successfully', ['path' => $path, 'size_after' => filesize($path)]);
        }
        return new Response('', 302, ['Location' => '/admin/settings?tab=logs']);
    }

    private function missingTemplates(array $templates): array
    {
        $required = ['register_verify', 'welcome'];
        $found = [];
        foreach ($templates as $template) {
            $found[] = $template['template_key'];
        }
        $missing = [];
        foreach ($required as $key) {
            if (!in_array($key, $found, true)) {
                $missing[] = $key;
            }
        }
        return $missing;
    }

    private function runMaintenance(int $userId): ?array
    {
        $action = $_POST['maintenance_action'] ?? '';
        if ($action === '') {
            return null;
        }

        try {
            return match ($action) {
                'cleanup_tmp' => $this->logMaintenance($userId, 'cleanup_tmp', \Core\Maintenance::cleanupTemp()),
                'cleanup_updates' => $this->logMaintenance($userId, 'cleanup_updates', \Core\Maintenance::cleanupUpdates()),
                'truncate_logs' => $this->runLogTruncate($userId),
                'cleanup_thumbs' => $this->logMaintenance($userId, 'cleanup_thumbs', \Core\Maintenance::cleanupOrphanThumbnails()),
                'scan_orphans' => $this->logMaintenance($userId, 'scan_orphans', \Core\Maintenance::scanOrphanMediaReferences()),
                default => null,
            };
        } catch (\Throwable $e) {
            Logger::error('error', 'Maintenance failed', ['action' => $action, 'user_id' => $userId]);
            return ['action' => $action, 'status' => 'error'];
        }
    }

    private function runLogTruncate(int $userId): ?array
    {
        $confirmed = !empty($_POST['confirm_truncate']);
        if (!$confirmed) {
            return ['action' => 'truncate_logs', 'status' => 'confirm_required'];
        }
        $result = \Core\Maintenance::truncateLogs();
        return $this->logMaintenance($userId, 'truncate_logs', ['cleared' => $result]);
    }

    private function checkGlobalSubtabPermission(string $subtab): void
    {
        // Only check additional permissions for specific subtabs
        // The basic 'general' subtab doesn't need extra permission checks
        $permissionMap = [
            'maintenance' => 'manage_settings_maintenance',
            'seo' => 'manage_settings_seo',
            'meta' => 'manage_settings_meta',
            'media' => 'manage_settings_media',
            'locale' => 'manage_settings_locale',
        ];
        
        if (isset($permissionMap[$subtab])) {
            Auth::requirePermission($permissionMap[$subtab]);
        }
    }

    private function saveGlobalSubtab(string $subtab, array $post, int $userId): array
    {
        $errors = [];
        
        switch ($subtab) {
            case 'general':
                $errors = $this->saveGeneralSettings($post);
                break;
            case 'maintenance':
                $errors = $this->saveMaintenanceSettings($post);
                break;
            case 'seo':
                $errors = $this->saveSeoSettings($post);
                break;
            case 'meta':
                $errors = $this->saveMetaSettings($post);
                break;
            case 'media':
                $errors = $this->saveMediaSettings($post);
                break;
            case 'locale':
                $errors = $this->saveLocaleSettings($post);
                break;
        }
        
        if (empty($errors)) {
            // Save all settings
            foreach ($post as $key => $value) {
                if (str_starts_with($key, 'csrf_token') || str_starts_with($key, 'subtab')) {
                    continue;
                }
                
                $type = 'string';
                if (is_bool($value) || in_array($key, ['maintenance_enabled', 'seo_default_robots_index', 'seo_default_robots_follow', 'seo_sitemap_enabled', 'meta_post_enabled', 'meta_page_enabled', 'media_image_generate_thumbnails', 'language_auto_detect'])) {
                    $type = 'bool';
                } elseif (is_numeric($value)) {
                    $type = 'int';
                }
                
                Settings::set($key, (string)$value, $type, false, 'global');
            }
        }
        
        return $errors;
    }

    private function saveGeneralSettings(array $post): array
    {
        $errors = [];
        
        $siteName = trim($post['site_name'] ?? '');
        if (empty($siteName)) {
            $errors[] = Lang::get('FFCMS.ADMIN.SETTINGS_SITE_NAME_REQUIRED');
        } elseif (strlen($siteName) > 120) {
            $errors[] = Lang::get('FFCMS.ADMIN.SETTINGS_SITE_NAME_TOO_LONG');
        }
        
        $siteTagline = trim($post['site_tagline'] ?? '');
        if (strlen($siteTagline) > 160) {
            $errors[] = Lang::get('FFCMS.ADMIN.SETTINGS_SITE_TAGLINE_TOO_LONG');
        }
        
        $siteBaseUrl = trim($post['site_base_url'] ?? '');
        if (!empty($siteBaseUrl) && !filter_var($siteBaseUrl, FILTER_VALIDATE_URL)) {
            $errors[] = Lang::get('FFCMS.ADMIN.SETTINGS_SITE_BASE_URL_INVALID');
        }
        
        return $errors;
    }

    private function saveMaintenanceSettings(array $post): array
    {
        
        $errors = [];
        
        $retryMinutes = (int)($post['maintenance_retry_after_minutes'] ?? 60);
        if ($retryMinutes < 5 || $retryMinutes > 10080) {
            $errors[] = Lang::get('FFCMS.ADMIN.SETTINGS_MAINTENANCE_RETRY_RANGE');
        }
        
        $message = trim($post['maintenance_message'] ?? '');
        if (strlen($message) > 1000) {
            $errors[] = Lang::get('FFCMS.ADMIN.SETTINGS_MAINTENANCE_MESSAGE_TOO_LONG');
        }
        
        return $errors;
    }

    private function saveSeoSettings(array $post): array
    {
        $errors = [];
        
        $title = trim($post['seo_default_title'] ?? '');
        if (strlen($title) > 70) {
            $errors[] = Lang::get('FFCMS.ADMIN.SETTINGS_SEO_TITLE_TOO_LONG');
        }
        
        $description = trim($post['seo_default_description'] ?? '');
        if (strlen($description) > 160) {
            $errors[] = Lang::get('FFCMS.ADMIN.SETTINGS_SEO_DESCRIPTION_TOO_LONG');
        }
        
        $canonical = trim($post['seo_canonical_base_url_override'] ?? '');
        if (!empty($canonical) && !filter_var($canonical, FILTER_VALIDATE_URL)) {
            $errors[] = Lang::get('FFCMS.ADMIN.SETTINGS_SEO_CANONICAL_INVALID');
        }
        
        $twitterCard = $post['seo_twitter_card_type'] ?? '';
        if (!in_array($twitterCard, ['summary', 'summary_large_image'], true)) {
            $errors[] = Lang::get('FFCMS.ADMIN.SETTINGS_SEO_TWITTER_CARD_INVALID');
        }
        
        return $errors;
    }

    private function saveMetaSettings(array $post): array
    {
        $errors = [];
        
        // Validate position values
        $validPositions = ['above', 'below'];
        foreach (['author', 'publish_date', 'updated_date', 'categories', 'tags'] as $field) {
            $postKey = 'meta_post_' . $field . '_position';
            $pageKey = 'meta_page_' . $field . '_position';
            
            if (isset($post[$postKey]) && !in_array($post[$postKey], $validPositions, true)) {
                $errors[] = Lang::get('FFCMS.ADMIN.SETTINGS_META_POSITION_INVALID');
            }
            
            if (isset($post[$pageKey]) && !in_array($post[$pageKey], $validPositions, true)) {
                $errors[] = Lang::get('FFCMS.ADMIN.SETTINGS_META_POSITION_INVALID');
            }
        }
        
        return $errors;
    }

    private function saveMediaSettings(array $post): array
    {
        $errors = [];
        
        $uploadMax = (int)($post['media_upload_max_mb'] ?? 0);
        if ($uploadMax < 1 || $uploadMax > 100) {
            $errors[] = Lang::get('FFCMS.ADMIN.SETTINGS_MEDIA_UPLOAD_MAX_RANGE');
        }
        
        $imageMaxWidth = (int)($post['media_image_max_width'] ?? 0);
        if ($imageMaxWidth < 100 || $imageMaxWidth > 8000) {
            $errors[] = Lang::get('FFCMS.ADMIN.SETTINGS_MEDIA_IMAGE_MAX_WIDTH_RANGE');
        }
        
        $imageMaxHeight = (int)($post['media_image_max_height'] ?? 0);
        if ($imageMaxHeight < 100 || $imageMaxHeight > 8000) {
            $errors[] = Lang::get('FFCMS.ADMIN.SETTINGS_MEDIA_IMAGE_MAX_HEIGHT_RANGE');
        }
        
        $imageQuality = (int)($post['media_image_quality'] ?? 0);
        if ($imageQuality < 40 || $imageQuality > 95) {
            $errors[] = Lang::get('FFCMS.ADMIN.SETTINGS_MEDIA_IMAGE_QUALITY_RANGE');
        }
        
        return $errors;
    }

    private function saveLocaleSettings(array $post): array
    {
        $errors = [];

        $defaultLanguage = $post['default_language'] ?? '';
        $availableLanguages = ['en-GB', 'de-DE', 'fr-FR', 'es-ES', 'it-IT'];
        if (!in_array($defaultLanguage, $availableLanguages, true)) {
            $errors[] = Lang::get('FFCMS.ADMIN.SETTINGS_LOCALE_DEFAULT_INVALID');
        }

        $forceLanguage = $post['force_language'] ?? '';
        if (!in_array($forceLanguage, array_merge(['off'], $availableLanguages), true)) {
            $errors[] = Lang::get('FFCMS.ADMIN.SETTINGS_LOCALE_FORCE_INVALID');
        }

        // If no errors and force_language is set to a valid language, update the session
        if (empty($errors) && $forceLanguage !== 'off') {
            Session::set('lang', $forceLanguage);
        }

        return $errors;
    }

    private function logMaintenance(int $userId, string $action, array $result): array
    {
        Logger::info('admin', 'Maintenance action', ['action' => $action, 'user_id' => $userId]);
        return array_merge(['action' => $action, 'status' => 'success'], $result);
    }

    private function saveMailSettings(array $post, int $userId): array
    {
        $errors = [];

        // Validate email settings
        $mailFromEmail = trim($post['mail_from_email'] ?? '');
        if (empty($mailFromEmail)) {
            $errors[] = Lang::get('FFCMS_FROM_EMAIL_REQUIRED');
        } elseif (!filter_var($mailFromEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = Lang::get('FFCMS_FROM_EMAIL_INVALID');
        }

        $mailFromName = trim($post['mail_from_name'] ?? '');
        if (empty($mailFromName)) {
            $errors[] = Lang::get('FFCMS_FROM_NAME_REQUIRED');
        }

        $mailReplyTo = trim($post['mail_replyto_email'] ?? '');
        if (!empty($mailReplyTo) && !filter_var($mailReplyTo, FILTER_VALIDATE_EMAIL)) {
            $errors[] = Lang::get('FFCMS_REPLYTO_EMAIL_INVALID');
        }

        $smtpHost = trim($post['smtp_host'] ?? '');
        $smtpPort = (int)($post['smtp_port'] ?? 0);
        $smtpUsername = trim($post['smtp_username'] ?? '');

        // Validate SMTP settings if SMTP transport is selected
        $mailTransport = $post['mail_transport'] ?? 'php_mail';
        if ($mailTransport === 'smtp') {
            if (empty($smtpHost)) {
                $errors[] = Lang::get('FFCMS_SMTP_HOST_REQUIRED');
            }
            if ($smtpPort < 1 || $smtpPort > 65535) {
                $errors[] = Lang::get('FFCMS_SMTP_PORT_INVALID');
            }
        }

        if (empty($errors)) {
            // Save all mail settings
            $settingsToSave = [
                'mail_transport', 'mail_from_email', 'mail_from_name', 'mail_replyto_email',
                'smtp_host', 'smtp_port', 'smtp_security', 'smtp_username',
                'mail_force_plain_text', 'mail_log_enabled'
            ];

            foreach ($settingsToSave as $key) {
                if (isset($post[$key])) {
                    $value = $post[$key];
                    $type = 'string';
                    if (in_array($key, ['mail_force_plain_text', 'mail_log_enabled'])) {
                        $type = 'bool';
                    } elseif (is_numeric($value)) {
                        $type = 'int';
                    }
                    Settings::set($key, (string)$value, $type, false, 'global');
                }
            }

            // Handle SMTP password separately (only save if provided)
            if (!empty($post['smtp_password'])) {
                Settings::set('smtp_password', $post['smtp_password'], 'string', true, 'global');
            }
        }

        return $errors;
    }

    public function testEmail(): Response
    {
        Session::startAdmin();
        Auth::requireAdmin();

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        // Get super user email
        $stmt = Connection::prepare('SELECT email FROM #__users WHERE role_id = 998 AND is_enabled = 1 LIMIT 1');
        $stmt->execute();
        $superUser = $stmt->fetch();
        $to = $superUser['email'] ?? '';
        if ($to === '') {
            return new Response('No super user email found', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $subject = 'Test Email from ' . Settings::get('site_name', 'FFCMS', 'global');
        $body = 'This is a test email sent from the admin settings. If you received this, your mail configuration is working correctly.';

        $result = \Core\MailService::sendRaw($to, $subject, $body, null);

        if ($result) {
            Logger::info('admin', 'Test email sent', ['to' => $to, 'user_id' => (int)Session::get('user_id', 0)]);
            return new Response('', 302, ['Location' => '/admin/settings?tab=mail&subtab=settings&test_email=success']);
        } else {
            Logger::error('admin', 'Test email failed', ['to' => $to, 'user_id' => (int)Session::get('user_id', 0)]);
            return new Response('', 302, ['Location' => '/admin/settings?tab=mail&subtab=settings&test_email=failed']);
        }
    }
}
