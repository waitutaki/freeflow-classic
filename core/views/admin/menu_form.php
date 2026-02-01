<?php
use Core\Lang;
use Core\Template;
use Core\Csrf;

$values = $values ?? [];
$errors = $errors ?? [];
$isEdit = $is_edit ?? false;
?>
<div class="container-fluid">
    <div class="zulu-toolbar-row">
        <div class="btn-group">
            <button class="btn btn-primary" form="menu-form" type="submit">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-floppy-disk"></i></span>
                <?php echo Lang::get('FFCMS_SAVE'); ?>
            </button>
            <a class="btn btn-outline-secondary" href="/admin/menus">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-xmark"></i></span>
                <?php echo Lang::get('FFCMS_CLOSE'); ?>
            </a>
        </div>
    </div>

    <div class="zulu-panel">
        <h1 class="mb-4"><?php echo Lang::get('FFCMS_MENUS'); ?></h1>

        <?php if (!empty($errors['csrf'])) : ?>
            <div class="alert alert-danger" role="alert"><?php echo Template::escape($errors['csrf']); ?></div>
        <?php endif; ?>

        <form id="menu-form" method="post">
            <?php echo Csrf::input(); ?>
            <div class="mb-3">
                <label class="form-label" for="title"><?php echo Lang::get('FFCMS_TITLE'); ?></label>
                <input class="form-control" type="text" id="title" name="title" value="<?php echo Template::escape($values['title'] ?? ''); ?>" required>
                <?php if (!empty($errors['title'])) : ?>
                    <div class="text-danger"><?php echo Template::escape($errors['title']); ?></div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label" for="key"><?php echo Lang::get('FFCMS_KEY'); ?></label>
                <input class="form-control" type="text" id="key" name="key" value="<?php echo Template::escape($values['key'] ?? ''); ?>" required>
                <?php if (!empty($errors['key'])) : ?>
                    <div class="text-danger"><?php echo Template::escape($errors['key']); ?></div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label" for="description"><?php echo Lang::get('FFCMS_DESCRIPTION'); ?></label>
                <textarea class="form-control" id="description" name="description" rows="4"><?php echo Template::escape($values['description'] ?? ''); ?></textarea>
            </div>
        </form>
    </div>
</div>
