<?php
namespace Core\Controllers\Admin;

use Core\Auth;
use Core\Controller;
use Core\Csrf;
use Core\Lang;
use Core\Response;
use Core\Session;
use Core\Settings;
use Core\Template;
use Core\FreegateScanner;
use Core\Models\FreegateModel;

class AdminFreegateController extends Controller
{
    public function index(): Response
    {
        Session::startAdmin();
        Auth::requireSuper();

        $tab = $_GET['tab'] ?? 'overview';
        $tabs = ['overview', 'protection', 'rules', 'scan', 'traffic', 'tools'];
        if (!in_array($tab, $tabs, true)) {
            $tab = 'overview';
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
                return new Response('Invalid CSRF', 400, ['Content-Type' => 'text/plain; charset=utf-8']);
            }

            $action = $_POST['action'] ?? '';
            $userId = (int)Session::get('user_id', 0);

            if ($action === 'save_protection') {
                $previousMode = (string)Settings::get('freegate.mode', 'external');
                $mode = $_POST['mode'] ?? 'external';
                $mode = in_array($mode, ['external', 'home'], true) ? $mode : 'external';
                Settings::set('freegate.mode', $mode, 'string', false);
                if ($mode !== $previousMode) {
                    FreegateModel::addTimeline([
                        'event_type' => 'learning',
                        'title' => 'FFCMS.FREEGATE_TIMELINE_MODE_SWITCH',
                        'details' => $previousMode . ' -> ' . $mode,
                        'severity' => 'medium',
                        'correlation_id' => null,
                        'actor_user_id' => $userId,
                    ]);
                }

                $rate = (int)($_POST['rate_limit_per_min'] ?? 120);
                if ($rate < 10) {
                    $rate = 10;
                }
                Settings::set('freegate.rate_limit_per_min', $rate, 'int', false);

                $adaptive = (int)($_POST['adaptive_enabled'] ?? 0) === 1 ? 1 : 0;
                Settings::set('freegate.adaptive_enabled', $adaptive, 'int', false);

                $maxRequest = (int)($_POST['max_request_kb'] ?? 512);
                if ($maxRequest < 64) {
                    $maxRequest = 64;
                }
                Settings::set('freegate.max_request_kb', $maxRequest, 'int', false);

                $challenge = $_POST['challenge'] ?? 'none';
                $challenge = in_array($challenge, ['none', 'delay', 'js'], true) ? $challenge : 'none';
                Settings::set('freegate.challenge', $challenge, 'string', false);

                $blockMinutes = (int)($_POST['block_minutes'] ?? 15);
                if ($blockMinutes < 1) {
                    $blockMinutes = 15;
                }
                Settings::set('freegate.block_minutes', $blockMinutes, 'int', false);

                $emailUsers = (int)($_POST['notify_users'] ?? 0) === 1 ? 1 : 0;
                $emailAdmins = (int)($_POST['notify_admins'] ?? 0) === 1 ? 1 : 0;
                Settings::set('freegate.notify_users', $emailUsers, 'int', false);
                Settings::set('freegate.notify_admins', $emailAdmins, 'int', false);

                $helpLink = trim((string)($_POST['help_link'] ?? ''));
                Settings::set('freegate.help_link', $helpLink, 'string', false);

                $retentionDays = (int)($_POST['retention_days'] ?? 30);
                if ($retentionDays < 1) {
                    $retentionDays = 1;
                }
                Settings::set('freegate.retention_days', $retentionDays, 'int', false);

                return new Response('', 302, ['Location' => '/admin/freegate?tab=protection']);
            }

            if ($action === 'rule_create') {
                $ruleKey = trim((string)($_POST['rule_key'] ?? ''));
                $pattern = trim((string)($_POST['pattern'] ?? ''));
                $actionValue = $_POST['rule_action'] ?? 'allow';
                $actionValue = in_array($actionValue, ['allow', 'block'], true) ? $actionValue : 'allow';
                if ($ruleKey !== '' && $pattern !== '') {
                    FreegateModel::createRule([
                        'rule_key' => $ruleKey,
                        'action' => $actionValue,
                        'pattern' => $pattern,
                        'is_enabled' => 1,
                        'source' => 'manual',
                    ]);
                }
                return new Response('', 302, ['Location' => '/admin/freegate?tab=rules']);
            }

            if ($action === 'rule_toggle') {
                $ruleId = (int)($_POST['rule_id'] ?? 0);
                $enabled = (int)($_POST['is_enabled'] ?? 0) === 1 ? 1 : 0;
                if ($ruleId > 0) {
                    FreegateModel::toggleRule($ruleId, $enabled);
                }
                return new Response('', 302, ['Location' => '/admin/freegate?tab=rules']);
            }

            if ($action === 'rule_delete') {
                $ruleId = (int)($_POST['rule_id'] ?? 0);
                if ($ruleId > 0) {
                    FreegateModel::deleteRule($ruleId);
                }
                return new Response('', 302, ['Location' => '/admin/freegate?tab=rules']);
            }

            if ($action === 'suggestion_approve') {
                $suggestionId = (int)($_POST['suggestion_id'] ?? 0);
                if ($suggestionId > 0) {
                    FreegateModel::approveSuggestion($suggestionId, $userId);
                }
                return new Response('', 302, ['Location' => '/admin/freegate?tab=rules']);
            }

            if ($action === 'suggestion_reject') {
                $suggestionId = (int)($_POST['suggestion_id'] ?? 0);
                if ($suggestionId > 0) {
                    FreegateModel::rejectSuggestion($suggestionId, $userId);
                }
                return new Response('', 302, ['Location' => '/admin/freegate?tab=rules']);
            }

            if ($action === 'scan_run') {
                $scanType = $_POST['scan_type'] ?? 'quick';
                $scanType = in_array($scanType, ['quick', 'full', 'integrity', 'risk'], true) ? $scanType : 'quick';
                FreegateScanner::run($scanType);
                return new Response('', 302, ['Location' => '/admin/freegate?tab=scan']);
            }

            if ($action === 'tools_clear_blocks') {
                FreegateModel::clearBlocks();
                return new Response('', 302, ['Location' => '/admin/freegate?tab=tools']);
            }

            if ($action === 'tools_recovery_enable') {
                $minutes = (int)($_POST['recovery_minutes'] ?? 15);
                if ($minutes < 5) {
                    $minutes = 5;
                }
                $until = date('Y-m-d H:i:s', time() + ($minutes * 60));
                Settings::set('freegate', 'recovery_until', $until, 'string', $userId);
                return new Response('', 302, ['Location' => '/admin/freegate?tab=tools']);
            }

            if ($action === 'tools_recovery_disable') {
                Settings::set('freegate', 'recovery_until', '', 'string', $userId);
                return new Response('', 302, ['Location' => '/admin/freegate?tab=tools']);
            }

            if ($action === 'tools_purge_events') {
                FreegateModel::purgeEvents();
                return new Response('', 302, ['Location' => '/admin/freegate?tab=tools']);
            }

            if ($action === 'tools_purge_traffic') {
                FreegateModel::purgeTraffic();
                return new Response('', 302, ['Location' => '/admin/freegate?tab=tools']);
            }

            return new Response('', 302, ['Location' => '/admin/freegate?tab=' . $tab]);
        }

        return $this->render('admin/freegate', [
            'page_title' => Lang::get('FFCMS_FREEGATE'),
            'tab' => $tab,
            'mode' => Settings::get('freegate.mode', 'external'),
            'rate_limit' => (int)Settings::get('freegate.rate_limit_per_min', 120),
            'adaptive_enabled' => (int)Settings::get('freegate.adaptive_enabled', 1) === 1,
            'max_request_kb' => (int)Settings::get('freegate.max_request_kb', 512),
            'challenge' => Settings::get('freegate.challenge', 'none'),
            'block_minutes' => (int)Settings::get('freegate.block_minutes', 15),
            'notify_users' => (int)Settings::get('freegate.notify_users', 0) === 1,
            'notify_admins' => (int)Settings::get('freegate.notify_admins', 1) === 1,
            'help_link' => Settings::get('freegate.help_link', ''),
            'retention_days' => (int)Settings::get('freegate.retention_days', 30),
            'recovery_until' => Settings::get('freegate.recovery_until', ''),
            'devstore_detected' => \Core\Freegate::devstoreDetected(),
            'rules' => FreegateModel::rules(),
            'suggestions' => FreegateModel::suggestions(),
            'events' => FreegateModel::recentEvents(50),
            'traffic' => FreegateModel::recentTraffic(50),
            'scans' => FreegateModel::scans(10),
            'timeline' => FreegateModel::timeline(50),
            'scan_id' => (int)($_GET['scan_id'] ?? 0),
            'scan_findings' => ((int)($_GET['scan_id'] ?? 0) > 0) ? FreegateModel::findingsForScan((int)$_GET['scan_id']) : [],
        ], 'Zulu');
    }
}
