<?php
namespace Core;

class Settings
{
    private static array $cache = [];

    public static function get(string $key, $default = null, string $scope = 'global'): mixed
    {
        $scope = trim($scope);
        $key = trim($key);

        if ($scope === '' || $key === '') {
            return $default;
        }

        // Cache (request lifetime)
        $cacheKey = $scope . '.' . $key;
        if (array_key_exists($cacheKey, self::$cache)) {
            return self::$cache[$cacheKey];
        }

        // Helper: primary fetch using your normal prefix replacement
        $fetch = function (string $s, string $k): ?array {
            $stmt = Connection::prepare(
                'SELECT setting_value, value_type
                 FROM #__settings
                 WHERE scope = :scope AND setting_key = :key
                 LIMIT 1'
            );
            $stmt->execute([':scope' => $s, ':key' => $k]);
            $row = $stmt->fetch();
            return $row ?: null;
        };

        // 1) Primary lookup: (scope, key)
        $row = $fetch($scope, $key);
        if ($row) {
            $value = self::castValue($row['setting_value'], $row['value_type'] ?? 'string');
            self::$cache[$cacheKey] = $value;
            return $value;
        }

        // 2) Fallback A: scoped -> global dotted
        // e.g. (freegate, mode) -> (global, freegate.mode)
        if ($scope !== 'global') {
            $fallbackKey = $scope . '.' . $key;
            $row = $fetch('global', $fallbackKey);
            if ($row) {
                $value = self::castValue($row['setting_value'], $row['value_type'] ?? 'string');
                self::$cache[$cacheKey] = $value;
                return $value;
            }
        }

        // 3) Fallback B: global dotted -> scoped plain
        // e.g. (global, freegate.mode) -> (freegate, mode)
        if ($scope === 'global' && str_contains($key, '.')) {
            [$fallbackScope, $fallbackKey] = explode('.', $key, 2);
            if ($fallbackScope !== '' && $fallbackKey !== '') {
                $row = $fetch($fallbackScope, $fallbackKey);
                if ($row) {
                    $value = self::castValue($row['setting_value'], $row['value_type'] ?? 'string');
                    self::$cache[$cacheKey] = $value;
                    return $value;
                }
            }
        }

        // Miss -> default
        if (defined('APP_DEBUG') && APP_DEBUG === true) {
            error_log(sprintf('[Settings] MISS key="%s" scope="%s" → default used', $key, $scope));
        }

        self::$cache[$cacheKey] = $default;
        return $default;
    }

    public static function set(string $key, string $value, string $type = 'string', bool $sensitive = false, string $scope = 'global'): void
    {
        
        $scope = trim($scope);
        $key = trim($key);
        if ($scope === '' || $key === '') {
            return;
        }

        $value = self::stringify($value, $type);
        $stmt = Connection::prepare(
            
            'INSERT INTO #__settings (scope, setting_group, setting_key, setting_value, value_type, is_sensitive, is_protected, updated_at)
             VALUES (:scope, :group, :key, :value, :type, :sensitive, :protected, :updated_at)
             ON DUPLICATE KEY UPDATE
                setting_value = VALUES(setting_value),
                value_type = VALUES(value_type),
                is_sensitive = VALUES(is_sensitive),
                is_protected = VALUES(is_protected),
                updated_at = VALUES(updated_at)'
        );
        
        $stmt->execute([
            ':scope' => $scope,
            ':group' => 'global',
            ':key' => $key,
            ':value' => $value,
            ':type' => $type,
            ':sensitive' => $sensitive ? '1' : '0',
            ':protected' => '0',
            ':updated_at' => date('Y-m-d H:i:s'),
        ]);

        self::$cache[$scope . '.' . $key] = self::castValue($value, $type);

    }

    
    private static function castValue(string $value, string $type): mixed
    {
        return match ($type) {
            'int' => (int)$value,
            'bool' => $value === '1',
            'text' => $value,
            default => $value,
        };
    }

    public static function setMany(array $pairs, string $groupKey): void
    {
        if (empty($pairs) || empty($groupKey)) {
            return;
        }

        foreach ($pairs as $key => $config) {
            $value = $config['value'] ?? '';
            $type = $config['type'] ?? 'string';
            $sensitive = $config['sensitive'] ?? false;
            $scope = $config['scope'] ?? 'global';

            self::set($key, $value, $type, $sensitive, $scope);
        }
    }

    public static function getGroup(string $groupKey): array
    {
        if (empty($groupKey)) {
            return [];
        }

        $stmt = Connection::prepare('SELECT setting_key, setting_value, value_type FROM #__settings WHERE setting_group = :group_key');
        $stmt->execute([':group_key' => $groupKey]);
        $rows = $stmt->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $result[$row['setting_key']] = self::castValue($row['setting_value'], $row['value_type'] ?? 'string');
        }

        return $result;
    }

    private static function stringify(mixed $value, string $type): string
    {
        return match ($type) {
            'int' => (string)(int)$value,
            'bool' => $value ? '1' : '0',
            'text' => (string)$value,
            default => (string)$value,
        };
    }
}
