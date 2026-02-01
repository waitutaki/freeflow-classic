<?php
namespace Core;

class DbSessionHandler implements \SessionHandlerInterface
{
    private string $context;
    private int $lifetime;

    public function __construct(string $context, int $lifetime)
    {
        $this->context = $context;
        $this->lifetime = $lifetime;
    }

    public function open(string $savePath, string $sessionName): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string
    {
        $stmt = Connection::prepare('SELECT data, expires_at FROM #__sessions WHERE session_id = :id AND context = :context');
        $stmt->execute([':id' => $id, ':context' => $this->context]);
        $row = $stmt->fetch();
        if (!$row) {
            return '';
        }
        if ((int)$row['expires_at'] < time()) {
            $this->destroy($id);
            return '';
        }
        return (string)$row['data'];
    }

    public function write(string $id, string $data): bool
    {
        $expires = time() + $this->lifetime;
        $stmt = Connection::prepare('REPLACE INTO #__sessions (session_id, context, data, expires_at, updated_at) VALUES (:id, :context, :data, :expires_at, :updated_at)');
        return $stmt->execute([
            ':id' => $id,
            ':context' => $this->context,
            ':data' => $data,
            ':expires_at' => $expires,
            ':updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function destroy(string $id): bool
    {
        $stmt = Connection::prepare('DELETE FROM #__sessions WHERE session_id = :id AND context = :context');
        return $stmt->execute([':id' => $id, ':context' => $this->context]);
    }

    public function gc(int $max_lifetime): int|false
    {
        $stmt = Connection::prepare('DELETE FROM #__sessions WHERE expires_at < :expires_at');
        $stmt->execute([':expires_at' => time()]);
        return $stmt->rowCount();
    }
}
