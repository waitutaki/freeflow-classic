<?php
use Core\Lang;
use Core\Template;
use Core\Csrf;

$extension = $extension ?? [];
$canActivate = $can_activate ?? false;
?>
<div class="container-fluid">
    <h1 class="mb-4"><?php echo Lang::get('FFCMS_EXTENSION_INACTIVE'); ?></h1>
    <div class="zulu-toolbar-row">
        <div class="btn-group">
            <?php if ($canActivate && !empty($extension['ext_key'])) : ?>
                <form class="d-inline" method="post" action="/admin/extension/<?php echo Template::escape($extension['ext_key']); ?>/toggle">
                    <?php echo Csrf::input(); ?>
                    <button class="btn btn-primary" type="submit">
                        <span class="me-1" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                        <?php echo Lang::get('FFCMS_ACTIVATE'); ?>
                    </button>
                </form>
            <?php endif; ?>
            <a class="btn btn-outline-secondary" href="/admin/extension?tab=extension">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-xmark"></i></span>
                <?php echo Lang::get('FFCMS_CLOSE'); ?>
            </a>
        </div>
    </div>

    <div class="zulu-panel">
        <p><?php echo Lang::get('FFCMS_EXTENSION_INACTIVE_MESSAGE'); ?></p>
        <p><strong><?php echo Template::escape($extension['name'] ?? ''); ?></strong></p>
    </div>
</div>
