<?php
use Core\Lang;
use Core\Template;
use Core\Csrf;

$page = $page ?? [];
?>
<div class="container-fluid">
    <div class="zulu-toolbar-row">
        <div class="btn-group">
            <button class="btn btn-danger" form="page-delete-form" type="submit">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-trash"></i></span>
                <?php echo Lang::get('FFCMS_DELETE'); ?>
            </button>
            <a class="btn btn-outline-secondary" href="/admin/content/pages">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-xmark"></i></span>
                <?php echo Lang::get('FFCMS_CLOSE'); ?>
            </a>
        </div>
    </div>
    <div class="zulu-panel">
        <h1 class="mb-4"><?php echo Lang::get('FFCMS_PAGES'); ?></h1>
        <div class="alert alert-warning" role="alert"><?php echo Lang::get('FFCMS_CONFIRM_DELETE'); ?></div>
        <p><?php echo Template::escape($page['title'] ?? ''); ?></p>
        <form id="page-delete-form" method="post">
            <?php echo Csrf::input(); ?>
        </form>
    </div>
</div>
