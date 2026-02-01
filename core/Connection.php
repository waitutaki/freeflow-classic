<?php
namespace Core;

use PDO;
use PDOException;

class Connection
{
    private static ?PDO $pdo = null;
    private static string $prefix = '';

    public static function pdo(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $db = Config::get('db', []);
        $host = $db['host'] ?? 'localhost';
        $port = $db['port'] ?? '3306';
        $name = $db['name'] ?? '';
        $user = $db['user'] ?? '';
        $pass = $db['password'] ?? '';
        self::$prefix = $db['prefix'] ?? '';

        $dsn = 'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $name . ';charset=utf8mb4';

        try {
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            die('Database connection failed.');
        }

        return self::$pdo;
    }

    public static function applyPrefix(string $sql): string
    {
        if (self::$prefix === '') {
            return $sql;
        }
        return str_replace('#__', self::$prefix, $sql);
    }

    public static function prepare(string $sql)
    {
        $pdo = self::pdo();
        $sql = self::applyPrefix($sql);
        self::enforcePolicy($sql);
        return $pdo->prepare($sql);
    }

    public static function query(string $sql)
    {
        $pdo = self::pdo();
        $sql = self::applyPrefix($sql);
        self::enforcePolicy($sql);
        return $pdo->query($sql);
    }

    private static function enforcePolicy(string $sql): void
    {
        if (!ExtensionRuntime::isActive()) {
            return;
        }

        $normalized = ltrim($sql);
        if ($normalized === '') {
            return;
        }

        if (preg_match('/^(SELECT|SHOW|DESCRIBE|EXPLAIN)\\b/i', $normalized)) {
            return;
        }

        $tables = self::extractWriteTables($normalized);
        if (!$tables) {
            return;
        }

        $extKey = ExtensionRuntime::currentKey() ?? '';
        $allowedPrefix = strtolower(self::$prefix . 'ext_' . $extKey . '_');
        foreach ($tables as $table) {
            $table = strtolower($table);
            if (!str_starts_with($table, $allowedPrefix)) {
                Logger::error('error', 'Extension write blocked', ['ext_key' => $extKey, 'table' => $table]);
                throw new \RuntimeException('Extension write blocked.');
            }
        }
    }

    private static function extractWriteTables(string $sql): array
    {
        $tables = [];
        $patterns = [
            '/\\bINSERT\\s+INTO\\s+`?([a-z0-9_]+)`?/i',
            '/\\bREPLACE\\s+INTO\\s+`?([a-z0-9_]+)`?/i',
            '/\\bUPDATE\\s+`?([a-z0-9_]+)`?/i',
            '/\\bDELETE\\s+FROM\\s+`?([a-z0-9_]+)`?/i',
            '/\\bCREATE\\s+TABLE\\s+(?:IF\\s+NOT\\s+EXISTS\\s+)?`?([a-z0-9_]+)`?/i',
            '/\\bALTER\\s+TABLE\\s+`?([a-z0-9_]+)`?/i',
            '/\\bDROP\\s+TABLE\\s+(?:IF\\s+EXISTS\\s+)?`?([a-z0-9_]+)`?/i',
            '/\\bRENAME\\s+TABLE\\s+`?([a-z0-9_]+)`?/i',
            '/\\bTRUNCATE\\s+TABLE\\s+`?([a-z0-9_]+)`?/i',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $sql, $matches)) {
                foreach ($matches[1] as $table) {
                    $tables[] = $table;
                }
            }
        }
        return array_values(array_unique($tables));
    }
}
