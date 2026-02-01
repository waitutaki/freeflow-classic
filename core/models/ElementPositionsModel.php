<?php
namespace Core\Models;

use Core\Connection;

class ElementPositionsModel
{
    public static function forTheme(string $themeKey, string $context): array
    {
        $stmt = Connection::prepare('SELECT * FROM #__element_positions WHERE theme_key = :theme_key AND context = :context ORDER BY position ASC');
        $stmt->execute([':theme_key' => $themeKey, ':context' => $context]);
        return $stmt->fetchAll() ?: [];
    }

    public static function upsertPositions(string $themeKey, string $context, array $positions): void
    {
        $stmt = Connection::prepare('INSERT INTO #__element_positions (theme_key, position, context) VALUES (:theme_key, :position, :context)');
        foreach ($positions as $position) {
            $position = trim($position);
            if ($position === '') {
                continue;
            }
            $stmt->execute([
                ':theme_key' => $themeKey,
                ':position' => $position,
                ':context' => $context,
            ]);
        }
    }
}
