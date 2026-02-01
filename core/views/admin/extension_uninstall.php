<?php
use Core\Lang;
use Core\Template;
use Core\Csrf;

$extension = $extension ?? [];
?>
<div class="container-fluid">
    <h1 class="mb-4"><?php echo Lang::get('FFCMS_UNINSTALL'); ?></h1>
    <div class="zulu-toolbar-row">
        <div class="btn-group">
            <button class="btn btn-danger" form="extension-uninstall-form" type="submit">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-trash"></i></span>
                <?php echo Lang::get('FFCMS_UNINSTALL'); ?>
            </button>
            <a class="btn btn-outline-secondary" href="/admin/extension?tab=extension">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-xmark"></i></span>
                <?php echo Lang::get('FFCMS_CLOSE'); ?>
            </a>
        </div>
    </div>

    <div class="zulu-panel">
        <p><?php echo Lang::get('FFCMS_CONFIRM_DELETE'); ?></p>
        <p><strong><?php echo Template::escape($extension['name'] ?? ''); ?></strong></p>
        <form id="extension-uninstall-form" method="post" action="/admin/extension/<?php echo Template::escape($extension['ext_key'] ?? ''); ?>/uninstall">
            <?php echo Csrf::input(); ?>
        </form>
    </div>
</div>
