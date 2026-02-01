<?php
use Core\Lang;
use Core\Csrf;
use Core\Template;

$path = $path ?? '';
?>
<div class="container-fluid">
    <div class="zulu-toolbar-row">
        <div class="btn-group">
            <button class="btn btn-danger" form="media-delete-form" type="submit">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-trash"></i></span>
                <?php echo Lang::get('FFCMS_DELETE'); ?>
            </button>
            <a class="btn btn-outline-secondary" href="/admin/media">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-xmark"></i></span>
                <?php echo Lang::get('FFCMS_CLOSE'); ?>
            </a>
        </div>
    </div>
    <div class="zulu-panel">
        <h1 class="mb-4"><?php echo Lang::get('FFCMS_DELETE'); ?></h1>
        <p><?php echo Lang::get('FFCMS_CONFIRM_DELETE'); ?></p>
        <form id="media-delete-form" method="post">
            <?php echo Csrf::input(); ?>
            <input type="hidden" name="path" value="<?php echo Template::escape($path); ?>">
        </form>
    </div>
</div>
