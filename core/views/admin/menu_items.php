<?php
use Core\Lang;
use Core\Template;
use Core\Csrf;
use Core\Asset;

$menu = $menu ?? [];
$items = $items ?? [];
$csrfToken = Csrf::token();

function renderMenuItems(array $items, int $depth = 1): string {
    $html = '<ul class="list-group ff-menu-tree" data-menu-list="1" data-depth="' . $depth . '">';
    foreach ($items as $item) {
        $invalid = !empty($item['invalid']);
        $status = !empty($item['is_enabled']);
        $statusLabel = $status ? Lang::get('FFCMS_ENABLED') : Lang::get('FFCMS_DISABLED');
        $statusClass = $status ? 'text-success' : 'text-danger';
        $visibility = $item['visibility'] ?? 'all';
        $visibilityIcon = 'fa-eye';
        $visibilityLabel = Lang::get('FFCMS_ALL');
        $visibilityClass = 'text-muted';
        if ($visibility === 'logged_in') {
            $visibilityIcon = 'fa-user';
            $visibilityLabel = Lang::get('FFCMS_LOGGED_IN');
            $visibilityClass = 'ff-icon-green';
        } elseif ($visibility === 'logged_out') {
            $visibilityIcon = 'fa-user-slash';
            $visibilityLabel = Lang::get('FFCMS_LOGGED_OUT');
            $visibilityClass = 'ff-icon-red';
        } elseif ($visibility === 'none') {
            $visibilityIcon = 'fa-eye-slash';
            $visibilityLabel = Lang::get('FFCMS_NONE');
            $visibilityClass = 'text-muted';
        }
        $type = $item['type'] ?? '';
        $typeIcon = 'fa-link';
        if ($type === 'page') {
            $typeIcon = 'fa-file-lines';
        } elseif ($type === 'post') {
            $typeIcon = 'fa-newspaper';
        }
        $html .= '<li class="list-group-item ff-menu-item" draggable="true" data-menu-item="1" data-item-id="' . (int)$item['id'] . '" data-depth="' . $depth . '">';
        $html .= '<div class="d-flex align-items-center justify-content-between gap-2">';
        $html .= '<div class="d-flex align-items-center gap-2">';
        $html .= '<span aria-hidden="true"><i class="fa-solid ' . $typeIcon . '"></i></span>';
        $html .= '<span>' . Template::escape($item['title'] ?? '') . '</span>';
        if ($invalid) {
            $html .= '<span class="text-danger ms-2" aria-label="' . Template::escape(Lang::get('FFCMS_INVALID_ITEM')) . '"><i class="fa-solid fa-circle-xmark" aria-hidden="true"></i></span>';
        }
        $html .= '</div>';
        $html .= '<div class="ff-action-group">';
        $html .= '<span class="' . $statusClass . '" aria-label="' . Template::escape($statusLabel) . '"><i class="fa-solid ' . ($status ? 'fa-circle-check' : 'fa-circle-xmark') . '" aria-hidden="true"></i><span class="visually-hidden">' . Template::escape($statusLabel) . '</span></span>';
        $html .= '<span class="' . $visibilityClass . '" aria-label="' . Template::escape($visibilityLabel) . '"><i class="fa-solid ' . $visibilityIcon . '" aria-hidden="true"></i><span class="visually-hidden">' . Template::escape($visibilityLabel) . '</span></span>';
        $html .= '<form method="post" action="/admin/menus/' . (int)$item['menu_id'] . '/items/' . (int)$item['id'] . '/move" class="d-inline">';
        $html .= Csrf::input();
        $html .= '<input type="hidden" name="direction" value="up">';
        $html .= '<button class="ff-action-icon" type="submit" aria-label="' . Template::escape(Lang::get('FFCMS_MOVE_UP')) . '"><span aria-hidden="true"><i class="fa-solid fa-arrow-up"></i></span></button>';
        $html .= '</form>';
        $html .= '<form method="post" action="/admin/menus/' . (int)$item['menu_id'] . '/items/' . (int)$item['id'] . '/move" class="d-inline">';
        $html .= Csrf::input();
        $html .= '<input type="hidden" name="direction" value="down">';
        $html .= '<button class="ff-action-icon" type="submit" aria-label="' . Template::escape(Lang::get('FFCMS_MOVE_DOWN')) . '"><span aria-hidden="true"><i class="fa-solid fa-arrow-down"></i></span></button>';
        $html .= '</form>';
        $html .= '<a class="ff-action-icon" href="/admin/menus/' . (int)$item['menu_id'] . '/items/' . (int)$item['id'] . '/edit" aria-label="' . Template::escape(Lang::get('FFCMS_EDIT')) . '"><span aria-hidden="true"><i class="fa-solid fa-pen"></i></span></a>';
        $html .= '<a class="ff-action-icon" href="/admin/menus/' . (int)$item['menu_id'] . '/items/' . (int)$item['id'] . '/delete" aria-label="' . Template::escape(Lang::get('FFCMS_DELETE')) . '"><span aria-hidden="true"><i class="fa-solid fa-trash"></i></span></a>';
        $html .= '</div>';
        $html .= '</div>';
        if (!empty($item['children'])) {
            $html .= '<div class="mt-2 ms-4 ff-menu-children">';
            $html .= renderMenuItems($item['children'], $depth + 1);
            $html .= '</div>';
        }
        $html .= '</li>';
    }
    $html .= '</ul>';
    return $html;
}
?>
<div class="container-fluid">
    <h1 class="mb-4"><?php echo Lang::get('FFCMS_MENU_ITEMS'); ?>: <?php echo Template::escape($menu['title'] ?? ''); ?></h1>
    <div class="zulu-toolbar-row">
        <div class="btn-group">
            <a class="btn btn-primary" href="/admin/menus/<?php echo (int)$menu['id']; ?>/items/new">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-plus"></i></span>
                <?php echo Lang::get('FFCMS_NEW'); ?>
            </a>
            <a class="btn btn-outline-secondary" href="/admin/menus">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-xmark"></i></span>
                <?php echo Lang::get('FFCMS_CLOSE'); ?>
            </a>
        </div>
    </div>
    <div class="zulu-panel" data-menu-tree-root="1" data-reorder-url="/admin/menus/<?php echo (int)$menu['id']; ?>/items/reorder" data-reorder-csrf="<?php echo Template::escape($csrfToken); ?>">
        <?php echo renderMenuItems($items, 1); ?>
    </div>
</div>
<?php
return [
    'footer_scripts' => '<script src="' . Asset::url('core', 'js/menu_manager.js') . '"></script>',
];
?>
