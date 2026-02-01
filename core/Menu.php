<?php
namespace Core;

class Menu
{
    public static function adminMenu(): array
    {
        $stmt = Connection::prepare('SELECT m.id, m.label_key, m.icon, m.route, m.parent_id, m.perm_key, m.sort_order, m.is_enabled, m.owner_type, m.owner_key, e.is_active AS ext_active FROM #__admin_menu_entries m LEFT JOIN #__extension e ON m.owner_type = \'ext\' AND m.owner_key = e.ext_key WHERE m.is_enabled = 1 ORDER BY m.sort_order ASC, m.id ASC');
        $stmt->execute();
        $rows = $stmt->fetchAll();
        return $rows ?: [];
    }

    public static function renderAdminMenu(): string
    {
        $items = self::adminMenu();
        if (!$items) {
            return '';
        }

        $tree = [];
        $map = [];
        foreach ($items as $item) {
            $item['children'] = [];
            $map[$item['id']] = $item;
        }
        foreach ($map as $id => $item) {
            $parentId = $item['parent_id'] ?? null;
            if ($parentId && isset($map[$parentId])) {
                $map[$parentId]['children'][] = $id;
            } else {
                $tree[] = $id;
            }
        }

        $html = '<ul class="zulu-menu">';
        foreach ($tree as $id) {
            $html .= self::renderItem($map, $id);
        }
        $html .= '</ul>';

        return $html;
    }

    private static function renderItem(array $map, int $id): string
    {
        $item = $map[$id] ?? null;
        if (!$item) {
            return '';
        }

        $permKey = $item['perm_key'] ?? '';
        if ($permKey !== '' && !ACL::can($permKey)) {
            return '';
        }

        $label = $item['label_key'] ? Lang::get($item['label_key']) : '';
        $icon = $item['icon'] ?? '';
        $route = $item['route'] ?? '#';
        $inactive = false;
        if (($item['owner_type'] ?? '') === 'ext') {
            $inactive = empty($item['ext_active']);
            if ($inactive) {
                $route = '/admin/extension/inactive/' . ($item['owner_key'] ?? '');
            }
        }

        $html = '<li class="zulu-menu-item' . ($inactive ? ' is-inactive' : '') . '">';
        $html .= '<a class="zulu-menu-link" href="' . Template::escape($route) . '">';
        if ($icon !== '') {
            $html .= '<span class="zulu-menu-icon" aria-hidden="true"><i class="fa-solid ' . Template::escape($icon) . '"></i></span>';
        }
        $html .= '<span class="zulu-menu-label">' . Template::escape($label) . '</span>';
        if ($inactive) {
            $html .= '<span class="zulu-menu-state text-muted">' . Template::escape(Lang::get('FFCMS_INACTIVE')) . '</span>';
        }
        $html .= '</a>';

        if (!empty($item['children'])) {
            $html .= '<ul class="zulu-menu-sub">';
            foreach ($item['children'] as $childId) {
                $html .= self::renderItem($map, (int)$childId);
            }
            $html .= '</ul>';
        }

        $html .= '</li>';
        return $html;
    }
}
