<?php
use Core\Lang;
use Core\Template;
use Core\Csrf;

$role = $role ?? [];
?>
<div class="container-fluid">
    <div class="zulu-toolbar-row">
        <div class="btn-group">
            <button class="btn btn-danger" form="role-delete-form" type="submit">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-trash"></i></span>
                <?php echo Lang::get('FFCMS_DELETE'); ?>
            </button>
            <a class="btn btn-outline-secondary" href="/admin/roles">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-xmark"></i></span>
                <?php echo Lang::get('FFCMS_CLOSE'); ?>
            </a>
        </div>
    </div>
    <div class="zulu-panel">
        <h1 class="mb-4"><?php echo Lang::get('FFCMS_DELETE'); ?></h1>
        <p><?php echo Lang::get('FFCMS_CONFIRM_DELETE'); ?></p>
        <form id="role-delete-form" method="post">
            <?php echo Csrf::input(); ?>
        </form>
    </div>
</div>
