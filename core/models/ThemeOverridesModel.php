<?php
namespace Core\Models;

use Core\Connection;

class ThemeOverridesModel
{
    public static function allForTheme(int $themeId, string $context): array
    {
        $stmt = Connection::prepare('SELECT token, value, updated_at FROM #__theme_style_overrides WHERE theme_id = :theme_id AND context = :context');
        $stmt->execute([':theme_id' => $themeId, ':context' => $context]);
        $rows = $stmt->fetchAll() ?: [];
        $map = [];
        foreach ($rows as $row) {
            $map[$row['token']] = $row;
        }
        return $map;
    }

    public static function saveOverride(int $themeId, string $context, string $token, string $value, int $userId): void
    {
        $stmt = Connection::prepare('INSERT INTO #__theme_style_overrides (theme_id, context, token, value, updated_at, updated_by) VALUES (:theme_id, :context, :token, :value, :updated_at, :updated_by) ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = VALUES(updated_at), updated_by = VALUES(updated_by)');
        $stmt->execute([
            ':theme_id' => $themeId,
            ':context' => $context,
            ':token' => $token,
            ':value' => $value,
            ':updated_at' => date('Y-m-d H:i:s'),
            ':updated_by' => $userId,
        ]);
    }

    public static function latestRevision(int $themeId, string $context): int
    {
        $stmt = Connection::prepare('SELECT MAX(updated_at) AS updated_at FROM #__theme_style_overrides WHERE theme_id = :theme_id AND context = :context');
        $stmt->execute([':theme_id' => $themeId, ':context' => $context]);
        $row = $stmt->fetch();
        if (!$row || !$row['updated_at']) {
            return 0;
        }
        return strtotime((string)$row['updated_at']) ?: 0;
    }
}
