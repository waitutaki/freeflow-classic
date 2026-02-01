<?php
use Core\Lang;
use Core\Template;

$categories = $categories ?? [];
?>
<div class="container-fluid">
    <h1 class="mb-4"><?php echo Lang::get('FFCMS_CATEGORIES'); ?></h1>
    <div class="zulu-toolbar-row">
        <div class="btn-group">
            <a class="btn btn-primary" href="/admin/content/categories/new">
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
                    <th><?php echo Lang::get('FFCMS_SLUG'); ?></th>
                    <th><?php echo Lang::get('FFCMS_PARENT'); ?></th>
                    <th><?php echo Lang::get('FFCMS_ACTIONS'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category) : ?>
                    <tr>
                        <td><?php echo Template::escape($category['title'] ?? ''); ?></td>
                        <td><?php echo Template::escape($category['slug'] ?? ''); ?></td>
                        <td><?php echo Template::escape($category['parent_title'] ?? ''); ?></td>
                        <td>
                            <div class="ff-action-group">
                                <a class="ff-action-icon" href="/admin/content/categories/<?php echo (int)$category['id']; ?>/edit" aria-label="<?php echo Template::escape(Lang::get('FFCMS_EDIT')); ?>">
                                    <span aria-hidden="true"><i class="fa-solid fa-pen"></i></span>
                                </a>
                                <a class="ff-action-icon" href="/admin/content/categories/<?php echo (int)$category['id']; ?>/delete" aria-label="<?php echo Template::escape(Lang::get('FFCMS_DELETE')); ?>">
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
