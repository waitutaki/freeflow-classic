<?php
namespace Core\Models;

use Core\Connection;

class FreegateModel
{
    private static int $lastRetentionCheck = 0;

    public static function rules(): array
    {
        $stmt = Connection::prepare('SELECT id, rule_key, action, pattern, is_enabled, source, created_at FROM #__freegate_rules ORDER BY created_at DESC');
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public static function createRule(array $data): void
    {
        $stmt = Connection::prepare('INSERT INTO #__freegate_rules (rule_key, action, pattern, is_enabled, source, created_at) VALUES (:rule_key, :action, :pattern, :is_enabled, :source, :created_at)');
        $stmt->execute([
            ':rule_key' => $data['rule_key'],
            ':action' => $data['action'],
            ':pattern' => $data['pattern'],
            ':is_enabled' => $data['is_enabled'],
            ':source' => $data['source'],
            ':created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function toggleRule(int $id, int $enabled): void
    {
        $stmt = Connection::prepare('UPDATE #__freegate_rules SET is_enabled = :enabled WHERE id = :id');
        $stmt->execute([
            ':enabled' => $enabled,
            ':id' => $id,
        ]);
    }

    public static function deleteRule(int $id): void
    {
        $stmt = Connection::prepare('DELETE FROM #__freegate_rules WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public static function suggestions(): array
    {
        $stmt = Connection::prepare('SELECT id, rule_key, action, pattern, confidence, status, created_at FROM #__freegate_rule_suggestions ORDER BY created_at DESC');
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public static function createSuggestion(array $data): void
    {
        $stmt = Connection::prepare('INSERT INTO #__freegate_rule_suggestions (rule_key, action, pattern, confidence, status, created_at) VALUES (:rule_key, :action, :pattern, :confidence, :status, :created_at)');
        $stmt->execute([
            ':rule_key' => $data['rule_key'],
            ':action' => $data['action'],
            ':pattern' => $data['pattern'],
            ':confidence' => $data['confidence'],
            ':status' => $data['status'],
            ':created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function suggestionExists(string $ruleKey): bool
    {
        $stmt = Connection::prepare('SELECT id FROM #__freegate_rule_suggestions WHERE rule_key = :rule_key LIMIT 1');
        $stmt->execute([':rule_key' => $ruleKey]);
        return (bool)$stmt->fetch();
    }

    public static function approveSuggestion(int $id, int $userId): ?array
    {
        $stmt = Connection::prepare('SELECT id, rule_key, action, pattern FROM #__freegate_rule_suggestions WHERE id = :id AND status = :status LIMIT 1');
        $stmt->execute([':id' => $id, ':status' => 'pending']);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $insert = Connection::prepare('INSERT INTO #__freegate_rules (rule_key, action, pattern, is_enabled, source, created_at) VALUES (:rule_key, :action, :pattern, 1, :source, :created_at)');
        $insert->execute([
            ':rule_key' => $row['rule_key'],
            ':action' => $row['action'],
            ':pattern' => $row['pattern'],
            ':source' => 'learning',
            ':created_at' => date('Y-m-d H:i:s'),
        ]);

        $update = Connection::prepare('UPDATE #__freegate_rule_suggestions SET status = :status, approved_by = :user_id, approved_at = :approved_at WHERE id = :id');
        $update->execute([
            ':status' => 'approved',
            ':user_id' => $userId,
            ':approved_at' => date('Y-m-d H:i:s'),
            ':id' => $id,
        ]);

        return $row;
    }

    public static function rejectSuggestion(int $id, int $userId): ?array
    {
        $stmt = Connection::prepare('SELECT id, rule_key, action, pattern FROM #__freegate_rule_suggestions WHERE id = :id AND status = :status LIMIT 1');
        $stmt->execute([':id' => $id, ':status' => 'pending']);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $update = Connection::prepare('UPDATE #__freegate_rule_suggestions SET status = :status, approved_by = :user_id, approved_at = :approved_at WHERE id = :id');
        $update->execute([
            ':status' => 'rejected',
            ':user_id' => $userId,
            ':approved_at' => date('Y-m-d H:i:s'),
            ':id' => $id,
        ]);

        return $row;
    }

    public static function recentEvents(int $limit = 50): array
    {
        $stmt = Connection::prepare('SELECT id, action, reason_code, confidence, ip, path, method, correlation_id, created_at FROM #__freegate_events ORDER BY created_at DESC LIMIT :limit');
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public static function recentTraffic(int $limit = 50): array
    {
        $stmt = Connection::prepare('SELECT ip, created_at FROM #__freegate_traffic ORDER BY created_at DESC LIMIT :limit');
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public static function addEvent(array $data): void
    {
        $stmt = Connection::prepare('INSERT INTO #__freegate_events (action, reason_code, confidence, ip, path, method, rule_id, correlation_id, created_at) VALUES (:action, :reason_code, :confidence, :ip, :path, :method, :rule_id, :correlation_id, :created_at)');
        $stmt->execute([
            ':action' => $data['action'],
            ':reason_code' => $data['reason_code'],
            ':confidence' => $data['confidence'],
            ':ip' => $data['ip'],
            ':path' => $data['path'],
            ':method' => $data['method'],
            ':rule_id' => $data['rule_id'],
            ':correlation_id' => $data['correlation_id'] ?? '',
            ':created_at' => date('Y-m-d H:i:s'),
        ]);
        self::cleanupRetention();
    }

    public static function addTraffic(string $ip): void
    {
        $stmt = Connection::prepare('INSERT INTO #__freegate_traffic (ip, created_at) VALUES (:ip, :created_at)');
        $stmt->execute([
            ':ip' => $ip,
            ':created_at' => date('Y-m-d H:i:s'),
        ]);
        self::cleanupRetention();
    }

    public static function isBlocked(string $ip): bool
    {
        $stmt = Connection::prepare('SELECT id FROM #__freegate_blocks WHERE ip = :ip AND expires_at > :now LIMIT 1');
        $stmt->execute([
            ':ip' => $ip,
            ':now' => date('Y-m-d H:i:s'),
        ]);
        return (bool)$stmt->fetch();
    }

    public static function addBlock(string $ip, string $reason, int $minutes): void
    {
        $expires = date('Y-m-d H:i:s', time() + ($minutes * 60));
        $stmt = Connection::prepare('INSERT INTO #__freegate_blocks (ip, reason_code, expires_at, created_at) VALUES (:ip, :reason, :expires_at, :created_at)');
        $stmt->execute([
            ':ip' => $ip,
            ':reason' => $reason,
            ':expires_at' => $expires,
            ':created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function clearBlocks(): void
    {
        $stmt = Connection::prepare('DELETE FROM #__freegate_blocks');
        $stmt->execute();
    }

    public static function purgeEvents(): void
    {
        $stmt = Connection::prepare('DELETE FROM #__freegate_events');
        $stmt->execute();
    }

    public static function purgeTraffic(): void
    {
        $stmt = Connection::prepare('DELETE FROM #__freegate_traffic');
        $stmt->execute();
    }

    public static function createScan(string $type): int
    {
        $stmt = Connection::prepare('INSERT INTO #__freegate_scans (scan_type, status, started_at) VALUES (:scan_type, :status, :started_at)');
        $stmt->execute([
            ':scan_type' => $type,
            ':status' => 'running',
            ':started_at' => date('Y-m-d H:i:s'),
        ]);
        return (int)Connection::pdo()->lastInsertId();
    }

    public static function finishScan(int $scanId, string $status, string $summary): void
    {
        $stmt = Connection::prepare('UPDATE #__freegate_scans SET status = :status, summary = :summary, ended_at = :ended_at WHERE id = :id');
        $stmt->execute([
            ':status' => $status,
            ':summary' => $summary,
            ':ended_at' => date('Y-m-d H:i:s'),
            ':id' => $scanId,
        ]);
    }

    public static function addFinding(int $scanId, string $severity, string $message, string $path): void
    {
        $stmt = Connection::prepare('INSERT INTO #__freegate_scan_findings (scan_id, severity, message, path, created_at) VALUES (:scan_id, :severity, :message, :path, :created_at)');
        $stmt->execute([
            ':scan_id' => $scanId,
            ':severity' => $severity,
            ':message' => $message,
            ':path' => $path,
            ':created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function scans(int $limit = 20): array
    {
        $stmt = Connection::prepare('SELECT id, scan_type, status, summary, started_at, ended_at FROM #__freegate_scans ORDER BY started_at DESC LIMIT :limit');
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public static function findingsForScan(int $scanId): array
    {
        $stmt = Connection::prepare('SELECT severity, message, path, created_at FROM #__freegate_scan_findings WHERE scan_id = :scan_id ORDER BY created_at DESC');
        $stmt->execute([':scan_id' => $scanId]);
        return $stmt->fetchAll() ?: [];
    }

    public static function integrityHashes(): array
    {
        $stmt = Connection::prepare('SELECT path, hash FROM #__freegate_integrity');
        $stmt->execute();
        $rows = $stmt->fetchAll() ?: [];
        $map = [];
        foreach ($rows as $row) {
            $map[$row['path']] = $row['hash'];
        }
        return $map;
    }

    public static function saveIntegrity(string $path, string $hash): void
    {
        $stmt = Connection::prepare('INSERT INTO #__freegate_integrity (path, hash, updated_at) VALUES (:path, :hash, :updated_at) ON DUPLICATE KEY UPDATE hash = VALUES(hash), updated_at = VALUES(updated_at)');
        $stmt->execute([
            ':path' => $path,
            ':hash' => $hash,
            ':updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function addTimeline(array $data): void
    {
        $stmt = Connection::prepare('INSERT INTO #__freegate_timeline (event_type, title, details, severity, correlation_id, actor_user_id, created_at) VALUES (:event_type, :title, :details, :severity, :correlation_id, :actor_user_id, :created_at)');
        $stmt->execute([
            ':event_type' => $data['event_type'],
            ':title' => $data['title'],
            ':details' => $data['details'],
            ':severity' => $data['severity'],
            ':correlation_id' => $data['correlation_id'] ?? '',
            ':actor_user_id' => $data['actor_user_id'],
            ':created_at' => date('Y-m-d H:i:s'),
        ]);
        self::cleanupRetention();
    }

    public static function timeline(int $limit = 50): array
    {
        $stmt = Connection::prepare('SELECT event_type, title, details, severity, correlation_id, actor_user_id, created_at FROM #__freegate_timeline ORDER BY created_at DESC LIMIT :limit');
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    private static function cleanupRetention(): void
    {
        $now = time();
        if ($now - self::$lastRetentionCheck < 300) {
            return;
        }
        self::$lastRetentionCheck = $now;

        $days = (int)\Core\Settings::get('freegate', 'retention_days', 30);
        if ($days < 1) {
            return;
        }
        $cutoff = date('Y-m-d H:i:s', $now - ($days * 86400));

        $stmt = Connection::prepare('DELETE FROM #__freegate_events WHERE created_at < :cutoff');
        $stmt->execute([':cutoff' => $cutoff]);
        $stmt = Connection::prepare('DELETE FROM #__freegate_traffic WHERE created_at < :cutoff');
        $stmt->execute([':cutoff' => $cutoff]);
        $stmt = Connection::prepare('DELETE FROM #__freegate_timeline WHERE created_at < :cutoff');
        $stmt->execute([':cutoff' => $cutoff]);
        $stmt = Connection::prepare('DELETE FROM #__freegate_scan_findings WHERE created_at < :cutoff');
        $stmt->execute([':cutoff' => $cutoff]);
        $stmt = Connection::prepare('DELETE FROM #__freegate_scans WHERE started_at < :cutoff');
        $stmt->execute([':cutoff' => $cutoff]);
        $stmt = Connection::prepare('DELETE FROM #__freegate_blocks WHERE expires_at < :cutoff');
        $stmt->execute([':cutoff' => date('Y-m-d H:i:s')]);
    }
}
