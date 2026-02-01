<?php
namespace Core;

class TokenService
{
    public static function create(string $type, int $userId, string $email, int $hours): string
    {
        $raw = bin2hex(random_bytes(32));
        $hash = hash('sha256', $raw);
        $expires = date('Y-m-d H:i:s', time() + ($hours * 3600));

        $stmt = Connection::prepare('INSERT INTO #__tokens (user_id, token_hash, token_type, expires_at, created_at) VALUES (:user_id, :token_hash, :token_type, :expires_at, :created_at)');
        $stmt->execute([
            ':user_id' => $userId,
            ':token_hash' => $hash,
            ':token_type' => $type,
            ':expires_at' => $expires,
            ':created_at' => date('Y-m-d H:i:s'),
        ]);

        return $raw;
    }

    public static function validate(string $raw, string $type): ?array
    {
        if ($raw === '') {
            return null;
        }

        $hash = hash('sha256', $raw);
        $stmt = Connection::prepare('SELECT * FROM #__tokens WHERE token_hash = :token_hash AND token_type = :token_type LIMIT 1');
        $stmt->execute([':token_hash' => $hash, ':token_type' => $type]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        if (!empty($row['used_at'])) {
            return null;
        }

        if (strtotime($row['expires_at']) < time()) {
            return null;
        }

        return $row;
    }

    public static function consume(int $tokenId): void
    {
        $stmt = Connection::prepare('UPDATE #__tokens SET used_at = :used_at WHERE id = :id');
        $stmt->execute([
            ':used_at' => date('Y-m-d H:i:s'),
            ':id' => $tokenId,
        ]);
    }
}
