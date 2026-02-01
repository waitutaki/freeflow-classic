<?php
use Core\Lang;
use Core\Csrf;

$enabled = !empty($enabled);
$devstore_tab = 'settings';
?>
<div class="container-fluid">
    <h1 class="mb-4"><?php echo Lang::get('DEVSTORE.SETTINGS_TITLE'); ?></h1>

    <?php require __DIR__ . '/partials/devstore_tabs.php'; ?>

    <div class="zulu-toolbar-row">
        <div class="btn-group">
            <button class="btn btn-primary" form="devstore-settings" type="submit">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-floppy-disk"></i></span>
                <?php echo Lang::get('FFCMS_SAVE'); ?>
            </button>
        </div>
    </div>

    <form id="devstore-settings" method="post" class="zulu-panel">
        <?php echo Csrf::input(); ?>
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="devstore_enabled" name="devstore_enabled" value="1" <?php echo $enabled ? 'checked' : ''; ?>>
            <label class="form-check-label" for="devstore_enabled">
                <?php echo Lang::get('DEVSTORE.ENABLED'); ?>
            </label>
        </div>
    </form>
</div>
