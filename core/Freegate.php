<?php
namespace Core;

use Core\Models\FreegateModel;

class Freegate
{
    public static function evaluate(Request $request): ?Response
    {
        $mode = (string)Settings::get('freegate.mode', 'external');
        $ip = self::clientIp();
        $path = self::requestPath();
        $method = $request->method();
        $correlationId = self::correlationId();

        if (self::isRecoveryActive()) {
            return null;
        }

        if (self::isSuperRequest($request)) {
            return null;
        }

        if (FreegateModel::isBlocked($ip)) {
            self::logEvent('block', 'block_list', $ip, $path, $method, 0.95, null, $correlationId);
            return new Response('Blocked', 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        if (self::isAllowRule($path)) {
            self::logEvent('allow', 'rule_allow', $ip, $path, $method, 0.2, null, $correlationId);
            return null;
        }

        if (self::isDenyRule($path)) {
            self::logEvent('block', 'rule_block', $ip, $path, $method, 0.85, null, $correlationId);
            FreegateModel::addBlock($ip, 'rule_block', self::blockMinutes());
            return new Response('Blocked', 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        if (!self::isMethodAllowed($method)) {
            self::logEvent('block', 'method', $ip, $path, $method, 0.7, null, $correlationId);
            return new Response('Blocked', 405, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        if (!self::isDevstoreExempt($path) && self::isRequestTooLarge(self::effectiveMaxRequestKb())) {
            self::logEvent('block', 'payload_size', $ip, $path, $method, 0.8, null, $correlationId);
            return new Response('Blocked', 413, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $skipHeuristics = false;
        if ($mode === 'home' && self::isDevstoreExempt($path)) {
            self::logEvent('allow', 'devstore_exempt', $ip, $path, $method, 0.1, null, $correlationId);
            $skipHeuristics = true;
        }

        if (self::isRateLimited($ip, self::effectiveRateLimit())) {
            self::logEvent('throttle', 'rate_limit', $ip, $path, $method, 0.9, null, $correlationId);
            return new Response('Throttled', 429, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        if (!$skipHeuristics && self::hasBadPayload()) {
            $challenge = (string)Settings::get('freegate', 'challenge', 'none');
            if ($challenge !== 'none' && self::shouldChallenge()) {
                self::logEvent('challenge', 'heuristic', $ip, $path, $method, 0.7, null, $correlationId);
                return self::challengeResponse($challenge);
            }
            self::logEvent('block', 'heuristic', $ip, $path, $method, 0.8, null, $correlationId);
            self::maybeSuggestRule($path);
            FreegateModel::addBlock($ip, 'heuristic', self::blockMinutes());
            return new Response('Blocked', 403, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        self::updateAdaptiveState($correlationId);
        return null;
    }

    private static function isAllowRule(string $path): bool
    {
        $stmt = Connection::prepare('SELECT pattern FROM #__freegate_rules WHERE is_enabled = 1 AND action = :action');
        $stmt->execute([':action' => 'allow']);
        $rows = $stmt->fetchAll() ?: [];
        foreach ($rows as $row) {
            if (self::matchPattern($path, (string)$row['pattern'])) {
                return true;
            }
        }
        return false;
    }

    private static function isDenyRule(string $path): bool
    {
        $stmt = Connection::prepare('SELECT pattern FROM #__freegate_rules WHERE is_enabled = 1 AND action = :action');
        $stmt->execute([':action' => 'block']);
        $rows = $stmt->fetchAll() ?: [];
        foreach ($rows as $row) {
            if (self::matchPattern($path, (string)$row['pattern'])) {
                return true;
            }
        }
        return false;
    }

    private static function isDevstoreExempt(string $path): bool
    {
        if (!self::devstoreDetected()) {
            return false;
        }
        $patterns = [
            '/api/devstore',
            '/admin/devstore',
            '/devstore',
        ];
        foreach ($patterns as $pattern) {
            if (str_starts_with($path, $pattern)) {
                return true;
            }
        }
        return false;
    }

    private static function isRateLimited(string $ip, int $limit): bool
    {
        if ($limit <= 0) {
            return false;
        }

        $stmt = Connection::prepare('SELECT COUNT(*) AS total FROM #__freegate_traffic WHERE ip = :ip AND created_at >= :cutoff');
        $stmt->execute([
            ':ip' => $ip,
            ':cutoff' => date('Y-m-d H:i:s', time() - 60),
        ]);
        $row = $stmt->fetch();
        $count = $row ? (int)$row['total'] : 0;

        FreegateModel::addTraffic($ip);

        return $count >= $limit;
    }

    private static function hasBadPayload(): bool
    {
        $targets = [
            $_SERVER['REQUEST_URI'] ?? '',
            $_SERVER['QUERY_STRING'] ?? '',
        ];
        $bad = ['<script', 'union select', 'drop table', 'or 1=1', 'sleep(', '../', '<?php', 'base64_decode'];
        foreach ($targets as $target) {
            $lower = strtolower($target);
            foreach ($bad as $needle) {
                if (str_contains($lower, $needle)) {
                    return true;
                }
            }
        }
        return false;
    }

    private static function matchPattern(string $path, string $pattern): bool
    {
        $pattern = str_replace('*', '.*', preg_quote($pattern, '#'));
        return (bool)preg_match('#^' . $pattern . '$#', $path);
    }

    private static function logEvent(string $action, string $reason, string $ip, string $path, string $method, float $confidence, ?int $ruleId, string $correlationId): void
    {
        FreegateModel::addEvent([
            'action' => $action,
            'reason_code' => $reason,
            'confidence' => $confidence,
            'ip' => $ip,
            'path' => $path,
            'method' => $method,
            'rule_id' => $ruleId,
            'correlation_id' => $correlationId,
        ]);

        $titleKey = match ($action) {
            'allow' => 'FFCMS.FREEGATE_TIMELINE_ALLOW',
            'block' => 'FFCMS.FREEGATE_TIMELINE_BLOCK',
            'throttle' => 'FFCMS.FREEGATE_TIMELINE_THROTTLE',
            'challenge' => 'FFCMS.FREEGATE_TIMELINE_CHALLENGE',
            default => 'FFCMS.FREEGATE_TIMELINE_EVENT',
        };
        FreegateModel::addTimeline([
            'event_type' => 'waf',
            'title' => $titleKey,
            'details' => $reason . ' ' . $path,
            'severity' => $action === 'allow' ? 'low' : 'high',
            'correlation_id' => $correlationId,
            'actor_user_id' => null,
        ]);

        if (in_array($action, ['block', 'throttle', 'challenge'], true)) {
            self::maybeNotify($action, $reason, $ip, $path, $method);
        }
    }

    public static function logAuthFailure(string $context, string $reason): void
    {
        $ip = self::clientIp();
        $path = self::requestPath();
        $correlationId = self::correlationId();
        FreegateModel::addEvent([
            'action' => 'alert',
            'reason_code' => 'login_fail',
            'confidence' => 0.6,
            'ip' => $ip,
            'path' => $path,
            'method' => strtoupper($context),
            'rule_id' => null,
            'correlation_id' => $correlationId,
        ]);
        FreegateModel::addTimeline([
            'event_type' => 'login',
            'title' => 'FFCMS.FREEGATE_TIMELINE_LOGIN_FAIL',
            'details' => $reason,
            'severity' => 'medium',
            'correlation_id' => $correlationId,
            'actor_user_id' => null,
        ]);

        $limit = (int)Settings::get('freegate', 'trigger_login_fail', 8);
        if ($limit > 0 && self::countByReason('login_fail', 10) === $limit) {
            self::notifyAdmins('FREEGATE.ALERT.SUSPICIOUS_LOGIN', [
                'ip' => $ip,
            ]);
        }
    }

    public static function logAuthSuccess(int $userId, string $context): void
    {
        $correlationId = self::correlationId();
        FreegateModel::addTimeline([
            'event_type' => 'login',
            'title' => 'FFCMS.FREEGATE_TIMELINE_LOGIN_OK',
            'details' => $context,
            'severity' => 'low',
            'correlation_id' => $correlationId,
            'actor_user_id' => $userId,
        ]);
    }

    public static function logNotFound(string $path): void
    {
        $ip = self::clientIp();
        $correlationId = self::correlationId();
        FreegateModel::addEvent([
            'action' => 'alert',
            'reason_code' => 'not_found',
            'confidence' => 0.3,
            'ip' => $ip,
            'path' => $path,
            'method' => 'GET',
            'rule_id' => null,
            'correlation_id' => $correlationId,
        ]);
        FreegateModel::addTimeline([
            'event_type' => 'waf',
            'title' => 'FFCMS.FREEGATE_TIMELINE_NOT_FOUND',
            'details' => $path,
            'severity' => 'low',
            'correlation_id' => $correlationId,
            'actor_user_id' => null,
        ]);
    }

    private static function isMethodAllowed(string $method): bool
    {
        $allowed = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'];
        return in_array($method, $allowed, true);
    }

    private static function isRequestTooLarge(int $maxKb): bool
    {
        if ($maxKb <= 0) {
            return false;
        }
        $length = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
        return $length > ($maxKb * 1024);
    }

    private static function effectiveRateLimit(): int
    {
        $base = (int)Settings::get('freegate', 'rate_limit_per_min', 120);
        $level = (int)Settings::get('freegate', 'adaptive_level', 1);
        if ($level <= 1) {
            return $base;
        }
        $multiplier = max(0.4, 1 - (0.2 * ($level - 1)));
        return (int)max(10, round($base * $multiplier));
    }

    private static function effectiveMaxRequestKb(): int
    {
        $base = (int)Settings::get('freegate', 'max_request_kb', 512);
        $level = (int)Settings::get('freegate', 'adaptive_level', 1);
        if ($level <= 1) {
            return $base;
        }
        $multiplier = max(0.5, 1 - (0.15 * ($level - 1)));
        return (int)max(64, round($base * $multiplier));
    }

    private static function challengeResponse(string $type): Response
    {
        if ($type === 'delay') {
            $headers = ['Retry-After' => '5', 'Content-Type' => 'text/plain; charset=utf-8'];
            return new Response('Delayed', 429, $headers);
        }

        $body = '<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Challenge</title></head><body><p>Checking your browser...</p><script>document.cookie="ff_challenge=1; path=/; max-age=300"; window.location.reload();</script></body></html>';
        return new Response($body, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    private static function shouldChallenge(): bool
    {
        return !isset($_COOKIE['ff_challenge']);
    }

    private static function updateAdaptiveState(string $correlationId): void
    {
        $enabled = (int)Settings::get('freegate', 'adaptive_enabled', 1);
        if ($enabled !== 1) {
            return;
        }

        $level = (int)Settings::get('freegate', 'adaptive_level', 1);
        $maxLevel = (int)Settings::get('freegate', 'adaptive_max', 3);
        if ($maxLevel < 1) {
            $maxLevel = 1;
        }

        $blockRate = (int)Settings::get('freegate', 'trigger_block_rate', 15);
        $loginFails = (int)Settings::get('freegate', 'trigger_login_fail', 8);
        $notFound = (int)Settings::get('freegate', 'trigger_404', 20);
        $scanCritical = (int)Settings::get('freegate', 'trigger_scan_critical', 1);

        $triggered = self::countByAction('block', 5) >= $blockRate
            || self::countByReason('login_fail', 10) >= $loginFails
            || self::countByReason('not_found', 10) >= $notFound
            || self::countScanFindings('high', 1440) >= $scanCritical;

        $newLevel = $level;
        if ($triggered) {
            $newLevel = min($level + 1, $maxLevel);
        } elseif ($level > 1) {
            $newLevel = $level - 1;
        }

        if ($newLevel !== $level) {
            Settings::set('freegate', 'adaptive_level', $newLevel, 'int', 0);
            FreegateModel::addTimeline([
                'event_type' => 'learning',
                'title' => 'FFCMS.FREEGATE_TIMELINE_ADAPTIVE_LEVEL',
                'details' => 'Level ' . $level . ' -> ' . $newLevel,
                'severity' => 'medium',
                'correlation_id' => $correlationId,
                'actor_user_id' => null,
            ]);
        }
    }

    private static function countByReason(string $reasonCode, int $minutes): int
    {
        $cutoff = date('Y-m-d H:i:s', time() - ($minutes * 60));
        $stmt = Connection::prepare('SELECT COUNT(*) AS total FROM #__freegate_events WHERE reason_code = :reason AND created_at >= :cutoff');
        $stmt->execute([
            ':reason' => $reasonCode,
            ':cutoff' => $cutoff,
        ]);
        $row = $stmt->fetch();
        return $row ? (int)$row['total'] : 0;
    }

    private static function countByAction(string $action, int $minutes): int
    {
        $cutoff = date('Y-m-d H:i:s', time() - ($minutes * 60));
        $stmt = Connection::prepare('SELECT COUNT(*) AS total FROM #__freegate_events WHERE action = :action AND created_at >= :cutoff');
        $stmt->execute([
            ':action' => $action,
            ':cutoff' => $cutoff,
        ]);
        $row = $stmt->fetch();
        return $row ? (int)$row['total'] : 0;
    }

    private static function countScanFindings(string $severity, int $minutes): int
    {
        $cutoff = date('Y-m-d H:i:s', time() - ($minutes * 60));
        $stmt = Connection::prepare('SELECT COUNT(*) AS total FROM #__freegate_scan_findings WHERE severity = :severity AND created_at >= :cutoff');
        $stmt->execute([
            ':severity' => $severity,
            ':cutoff' => $cutoff,
        ]);
        $row = $stmt->fetch();
        return $row ? (int)$row['total'] : 0;
    }

    private static function blockMinutes(): int
    {
        $minutes = (int)Settings::get('freegate', 'block_minutes', 15);
        return $minutes < 1 ? 15 : $minutes;
    }

    public static function devstoreDetected(): bool
    {
        return class_exists('Core\\Controllers\\Admin\\DevstoreDashboardController') && class_exists('Core\\Controllers\\Site\\DevstoreController');
    }

    private static function clientIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    private static function requestPath(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        return $path === null ? '/' : $path;
    }

    private static function correlationId(): string
    {
        return bin2hex(random_bytes(8));
    }

    private static function isSuperRequest(Request $request): bool
    {
        if ($request->context() !== Application::CONTEXT_ADMIN) {
            return false;
        }
        if (!isset($_COOKIE['ff_admin'])) {
            return false;
        }
        Session::startAdmin();
        return Auth::isSuper();
    }

    private static function isRecoveryActive(): bool
    {
        $until = (string)Settings::get('freegate', 'recovery_until', '');
        if ($until === '') {
            return false;
        }
        return strtotime($until) > time();
    }

    private static function maybeSuggestRule(string $path): void
    {
        $threshold = (int)Settings::get('freegate', 'trigger_block_rate', 15);
        if ($threshold < 5) {
            $threshold = 5;
        }
        $cutoff = date('Y-m-d H:i:s', time() - 3600);
        $stmt = Connection::prepare('SELECT COUNT(*) AS total FROM #__freegate_events WHERE reason_code = :reason AND path = :path AND created_at >= :cutoff');
        $stmt->execute([
            ':reason' => 'heuristic',
            ':path' => $path,
            ':cutoff' => $cutoff,
        ]);
        $row = $stmt->fetch();
        $count = $row ? (int)$row['total'] : 0;
        if ($count < $threshold) {
            return;
        }

        $ruleKey = 'learn_block_' . substr(hash('sha1', $path), 0, 8);
        if (FreegateModel::suggestionExists($ruleKey)) {
            return;
        }

        FreegateModel::createSuggestion([
            'rule_key' => $ruleKey,
            'action' => 'block',
            'pattern' => $path . '*',
            'confidence' => 0.75,
            'status' => 'pending',
        ]);

        FreegateModel::addTimeline([
            'event_type' => 'learning',
            'title' => 'FFCMS.FREEGATE_TIMELINE_RULE_SUGGESTED',
            'details' => $path,
            'severity' => 'medium',
            'correlation_id' => null,
            'actor_user_id' => null,
        ]);
    }

    private static function maybeNotify(string $action, string $reason, string $ip, string $path, string $method): void
    {
        $notifyUsers = (int)Settings::get('freegate', 'notify_users', 0) === 1;
        $notifyAdmins = (int)Settings::get('freegate', 'notify_admins', 1) === 1;
        $helpLink = (string)Settings::get('freegate', 'help_link', '');

        if ($notifyUsers) {
            $userId = self::currentUserId();
            if ($userId > 0) {
                $email = self::userEmail($userId);
                if ($email !== '') {
                    MailService::sendTemplate($email, 'FREEGATE.LOCKOUT.USER', [
                        'help_link' => $helpLink,
                    ]);
                }
            }
        }

        if ($notifyAdmins) {
            self::notifyAdmins('FREEGATE.LOCKOUT.ADMIN', [
                'ip' => $ip,
                'path' => $path,
                'action' => $action,
                'reason' => $reason,
                'method' => $method,
            ]);
        }
    }

    private static function notifyAdmins(string $templateKey, array $vars): void
    {
        $emails = self::adminEmails();
        foreach ($emails as $email) {
            MailService::sendTemplate($email, $templateKey, $vars);
        }
        if ($emails) {
            FreegateModel::addTimeline([
                'event_type' => 'alerts',
                'title' => 'FFCMS.FREEGATE_TIMELINE_EMAIL_SENT',
                'details' => $templateKey,
                'severity' => 'low',
                'correlation_id' => null,
                'actor_user_id' => null,
            ]);
        }
    }

    private static function adminEmails(): array
    {
        $stmt = Connection::prepare('SELECT u.email FROM #__users u LEFT JOIN #__user_roles ur ON ur.user_id = u.id WHERE u.role_id = 998 OR ur.role_id = 998');
        $stmt->execute();
        $rows = $stmt->fetchAll() ?: [];
        $emails = [];
        foreach ($rows as $row) {
            $email = (string)($row['email'] ?? '');
            if ($email !== '') {
                $emails[] = $email;
            }
        }
        return array_values(array_unique($emails));
    }

    private static function currentUserId(): int
    {
        if (isset($_COOKIE['ff_admin'])) {
            Session::startAdmin();
            return (int)Session::get('user_id', 0);
        }
        if (isset($_COOKIE['ff_site'])) {
            Session::startSite();
            return (int)Session::get('user_id', 0);
        }
        return 0;
    }

    private static function userEmail(int $userId): string
    {
        $stmt = Connection::prepare('SELECT email FROM #__users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch();
        return $row ? (string)($row['email'] ?? '') : '';
    }
}
