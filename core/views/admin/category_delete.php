<?php
use Core\Lang;
use Core\Template;
use Core\Csrf;

$category = $category ?? [];
?>
<div class="container-fluid">
    <div class="zulu-toolbar-row">
        <div class="btn-group">
            <button class="btn btn-danger" form="category-delete-form" type="submit">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-trash"></i></span>
                <?php echo Lang::get('FFCMS_DELETE'); ?>
            </button>
            <a class="btn btn-outline-secondary" href="/admin/content/categories">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-xmark"></i></span>
                <?php echo Lang::get('FFCMS_CLOSE'); ?>
            </a>
        </div>
    </div>
    <div class="zulu-panel">
        <h1 class="mb-4"><?php echo Lang::get('FFCMS_CATEGORIES'); ?></h1>
        <div class="alert alert-warning" role="alert"><?php echo Lang::get('FFCMS_CONFIRM_DELETE'); ?></div>
        <p><?php echo Template::escape($category['title'] ?? ''); ?></p>
        <form id="category-delete-form" method="post">
            <?php echo Csrf::input(); ?>
        </form>
    </div>
</div>
