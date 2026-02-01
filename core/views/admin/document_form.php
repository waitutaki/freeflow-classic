<?php
use Core\Lang;
use Core\Template;
use Core\Csrf;

$document = $document ?? [];
$canManageAll = $can_manage_all ?? false;
?>
<div class="container-fluid">
    <div class="zulu-toolbar-row">
        <div class="btn-group">
            <button class="btn btn-primary" form="document-form" type="submit">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-floppy-disk"></i></span>
                <?php echo Lang::get('FFCMS_SAVE'); ?>
            </button>
            <a class="btn btn-outline-secondary" href="/admin/documents">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-xmark"></i></span>
                <?php echo Lang::get('FFCMS_CLOSE'); ?>
            </a>
        </div>
    </div>
    <div class="zulu-panel">
        <h1 class="mb-4"><?php echo Lang::get('FFCMS_DOCUMENTS'); ?></h1>
        <form id="document-form" method="post">
            <?php echo Csrf::input(); ?>
            <div class="mb-3">
                <label class="form-label" for="display_name"><?php echo Lang::get('FFCMS_NAME'); ?></label>
                <input class="form-control" type="text" id="display_name" name="display_name" value="<?php echo Template::escape($document['display_name'] ?? ''); ?>">
            </div>
            <div class="mb-3">
                <label class="form-label" for="stored_name"><?php echo Lang::get('FFCMS_FILENAME'); ?></label>
                <input class="form-control" type="text" id="stored_name" name="stored_name" value="<?php echo Template::escape($document['stored_name'] ?? ''); ?>">
            </div>
            <div class="mb-3">
                <label class="form-label" for="owner_user_id"><?php echo Lang::get('FFCMS_USER'); ?></label>
                <input class="form-control" type="number" id="owner_user_id" name="owner_user_id" value="<?php echo Template::escape((string)($document['owner_user_id'] ?? '')); ?>" <?php echo $canManageAll ? '' : 'disabled'; ?>>
            </div>
        </form>
    </div>
</div>
