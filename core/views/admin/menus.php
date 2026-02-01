<?php
use Core\Lang;
use Core\Template;

$menus = $menus ?? [];
?>
<div class="container-fluid">
    <h1 class="mb-4"><?php echo Lang::get('FFCMS_MENUS'); ?></h1>
    <div class="zulu-toolbar-row">
        <div class="btn-group">
            <a class="btn btn-primary" href="/admin/menus/new">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-plus"></i></span>
                <?php echo Lang::get('FFCMS_NEW'); ?>
            </a>
            <a class="btn btn-outline-secondary" href="/admin/">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-xmark"></i></span>
                <?php echo Lang::get('FFCMS_CLOSE'); ?>
            </a>
        </div>
    </div>
    <div class="zulu-panel">
        <table class="table">
            <thead>
                <tr>
                    <th><?php echo Lang::get('FFCMS_TITLE'); ?></th>
                    <th><?php echo Lang::get('FFCMS_KEY'); ?></th>
                    <th><?php echo Lang::get('FFCMS_ITEMS_COUNT'); ?></th>
                    <th><?php echo Lang::get('FFCMS_UPDATED'); ?></th>
                    <th><?php echo Lang::get('FFCMS_ACTIONS'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($menus as $menu) : ?>
                    <tr>
                        <td><?php echo Template::escape($menu['title'] ?? ''); ?></td>
                        <td><?php echo Template::escape($menu['key'] ?? ''); ?></td>
                        <td><?php echo Template::escape((string)($menu['item_count'] ?? 0)); ?></td>
                        <td><?php echo Template::escape((string)($menu['modified_at'] ?? $menu['created_at'] ?? '')); ?></td>
                        <td>
                            <div class="ff-action-group">
                                <a class="ff-action-icon" href="/admin/menus/<?php echo (int)$menu['id']; ?>/edit" aria-label="<?php echo Template::escape(Lang::get('FFCMS_EDIT')); ?>">
                                    <span aria-hidden="true"><i class="fa-solid fa-pen"></i></span>
                                </a>
                                <a class="ff-action-icon" href="/admin/menus/<?php echo (int)$menu['id']; ?>/items" aria-label="<?php echo Template::escape(Lang::get('FFCMS_MANAGE_ITEMS')); ?>">
                                    <span aria-hidden="true"><i class="fa-solid fa-gear"></i></span>
                                </a>
                                <a class="ff-action-icon" href="/admin/menus/<?php echo (int)$menu['id']; ?>/delete" aria-label="<?php echo Template::escape(Lang::get('FFCMS_DELETE')); ?>">
                                    <span aria-hidden="true"><i class="fa-solid fa-trash"></i></span>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
