<?php
namespace Core\Models;

use Core\Connection;

class MenuItemsModel
{
    public static function allForMenu(int $menuId): array
    {
        $stmt = Connection::prepare('SELECT mi.*, p.slug AS page_slug, ps.slug AS post_slug FROM #__menu_items mi LEFT JOIN #__pages p ON p.id = mi.page_id LEFT JOIN #__posts ps ON ps.id = mi.post_id WHERE mi.menu_id = :menu_id ORDER BY mi.parent_id ASC, mi.ordering ASC, mi.id ASC');
        $stmt->execute([':menu_id' => $menuId]);
        return $stmt->fetchAll() ?: [];
    }

    public static function find(int $id): ?array
    {
        $stmt = Connection::prepare('SELECT * FROM #__menu_items WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = Connection::prepare('INSERT INTO #__menu_items (menu_id, type, title, parent_id, ordering, is_enabled, url, target, page_id, post_id, css_class, rel, visibility) VALUES (:menu_id, :type, :title, :parent_id, :ordering, :is_enabled, :url, :target, :page_id, :post_id, :css_class, :rel, :visibility)');
        $stmt->execute([
            ':menu_id' => $data['menu_id'],
            ':type' => $data['type'],
            ':title' => $data['title'],
            ':parent_id' => $data['parent_id'],
            ':ordering' => $data['ordering'],
            ':is_enabled' => $data['is_enabled'],
            ':url' => $data['url'],
            ':target' => $data['target'],
            ':page_id' => $data['page_id'],
            ':post_id' => $data['post_id'],
            ':css_class' => $data['css_class'],
            ':rel' => $data['rel'],
            ':visibility' => $data['visibility'],
        ]);
        return (int)Connection::pdo()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Connection::prepare('UPDATE #__menu_items SET type = :type, title = :title, parent_id = :parent_id, ordering = :ordering, is_enabled = :is_enabled, url = :url, target = :target, page_id = :page_id, post_id = :post_id, css_class = :css_class, rel = :rel, visibility = :visibility WHERE id = :id');
        $stmt->execute([
            ':type' => $data['type'],
            ':title' => $data['title'],
            ':parent_id' => $data['parent_id'],
            ':ordering' => $data['ordering'],
            ':is_enabled' => $data['is_enabled'],
            ':url' => $data['url'],
            ':target' => $data['target'],
            ':page_id' => $data['page_id'],
            ':post_id' => $data['post_id'],
            ':css_class' => $data['css_class'],
            ':rel' => $data['rel'],
            ':visibility' => $data['visibility'],
            ':id' => $id,
        ]);
    }

    public static function delete(int $id): void
    {
        $stmt = Connection::prepare('DELETE FROM #__menu_items WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public static function updateOrdering(int $menuId, array $items): void
    {
        $stmt = Connection::prepare('UPDATE #__menu_items SET parent_id = :parent_id, ordering = :ordering WHERE id = :id AND menu_id = :menu_id');
        foreach ($items as $item) {
            $stmt->execute([
                ':parent_id' => $item['parent_id'],
                ':ordering' => $item['ordering'],
                ':id' => $item['id'],
                ':menu_id' => $menuId,
            ]);
        }
    }
}
