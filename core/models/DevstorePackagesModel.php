<?php
namespace Core\Models;

use Core\Connection;

class DevstorePackagesModel
{
    public static function all(): array
    {
        $stmt = Connection::prepare('SELECT p.*, d.user_id, u.username FROM #__ext_devstore_packages p LEFT JOIN #__ext_devstore_developers d ON d.id = p.developer_id LEFT JOIN #__users u ON u.id = d.user_id ORDER BY p.created_at DESC');
        $stmt->execute();
        $packages = $stmt->fetchAll() ?: [];
        // For packages uploaded by super users (developer_id=0), set username to 'Super User'
        foreach ($packages as &$package) {
            if (empty($package['user_id'])) {
                $package['username'] = 'Super User';
            }
        }
        return $packages;
    }

    public static function allPublished(): array
    {
        $stmt = Connection::prepare('SELECT * FROM #__ext_devstore_packages WHERE status = \'published\' ORDER BY created_at DESC');
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public static function allPublishedByType(string $type): array
    {
        $stmt = Connection::prepare('SELECT * FROM #__ext_devstore_packages WHERE package_type = :type AND status = \'published\' ORDER BY created_at DESC');
        $stmt->execute([':type' => $type]);
        return $stmt->fetchAll() ?: [];
    }

    public static function find(int $id): ?array
    {
        $stmt = Connection::prepare('SELECT * FROM #__ext_devstore_packages WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findByKeyVersion(string $type, string $key, string $version): ?array
    {
        $stmt = Connection::prepare('SELECT * FROM #__ext_devstore_packages WHERE package_type = :type AND package_key = :key AND version = :version LIMIT 1');
        $stmt->execute([':type' => $type, ':key' => $key, ':version' => $version]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = Connection::prepare('INSERT INTO #__ext_devstore_packages (developer_id, package_type, package_key, name, version, description, author, status, sha256, archive_name, created_at, updated_at) VALUES (:developer_id, :package_type, :package_key, :name, :version, :description, :author, :status, :sha256, :archive_name, NOW(), NOW())');
        $stmt->execute([
            ':developer_id' => $data['developer_id'],
            ':package_type' => $data['package_type'],
            ':package_key' => $data['package_key'],
            ':name' => $data['name'],
            ':version' => $data['version'],
            ':description' => $data['description'],
            ':author' => $data['author'],
            ':status' => $data['status'],
            ':sha256' => $data['sha256'],
            ':archive_name' => $data['archive_name'],
        ]);
        return (int)Connection::pdo()->lastInsertId();
    }

    public static function updateStatus(int $id, string $status, string $sha256 = '', string $archiveName = ''): void
    {
        $set = 'status = :status, updated_at = NOW()';
        $params = [':status' => $status, ':id' => $id];
        if ($sha256 !== '') {
            $set .= ', sha256 = :sha256';
            $params[':sha256'] = $sha256;
        }
        if ($archiveName !== '') {
            $set .= ', archive_name = :archive_name';
            $params[':archive_name'] = $archiveName;
        }
        $stmt = Connection::prepare('UPDATE #__ext_devstore_packages SET ' . $set . ' WHERE id = :id');
        $stmt->execute($params);
    }

    public static function setPublished(int $id, string $repoPath, int $userId): void
    {
        $stmt = Connection::prepare('UPDATE #__ext_devstore_packages SET status = \'published\', repo_path = :repo_path, published_at = NOW(), published_by = :user_id, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            ':repo_path' => $repoPath,
            ':user_id' => $userId,
            ':id' => $id,
        ]);
    }

    public static function setUnpublished(int $id): void
    {
        $stmt = Connection::prepare('UPDATE #__ext_devstore_packages SET status = \'approved\', updated_at = NOW() WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public static function listByDeveloper(int $developerId): array
    {
        $stmt = Connection::prepare('SELECT * FROM #__ext_devstore_packages WHERE developer_id = :developer_id ORDER BY created_at DESC');
        $stmt->execute([':developer_id' => $developerId]);
        return $stmt->fetchAll() ?: [];
    }

    public static function update(int $id, array $data): void
    {
        $set = [];
        $params = [':id' => $id];
        foreach ($data as $key => $value) {
            $set[] = $key . ' = :' . $key;
            $params[':' . $key] = $value;
        }
        $set[] = 'updated_at = NOW()';
        $stmt = Connection::prepare('UPDATE #__ext_devstore_packages SET ' . implode(', ', $set) . ' WHERE id = :id');
        $stmt->execute($params);
    }

    public static function delete(int $id): void
    {
        $stmt = Connection::prepare('DELETE FROM #__ext_devstore_packages WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
