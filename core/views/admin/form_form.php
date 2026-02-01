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
            <button class="btn btn-primary" form="form-form" type="submit">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-floppy-disk"></i></span>
                Save
            </button>
            <a class="btn btn-outline-secondary" href="/admin/forms">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-xmark"></i></span>
                Close
            </a>
        </div>
    </div>

    <div class="zulu-panel">
        <h1 class="mb-4"><?php echo $isEdit ? 'Edit Form' : 'Create Form'; ?></h1>

        <?php if (!empty($errors['csrf'])) : ?>
            <div class="alert alert-danger" role="alert"><?php echo Template::escape($errors['csrf']); ?></div>
        <?php endif; ?>

        <form id="form-form" method="post">
            <?php echo Csrf::input(); ?>
            <div class="mb-3">
                <label class="form-label" for="title">Title</label>
                <input class="form-control" type="text" id="title" name="title" value="<?php echo Template::escape($values['title'] ?? ''); ?>" required>
                <?php if (!empty($errors['title'])) : ?>
                    <div class="text-danger"><?php echo Template::escape($errors['title']); ?></div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label" for="slug">Slug</label>
                <input class="form-control" type="text" id="slug" name="slug" value="<?php echo Template::escape($values['slug'] ?? ''); ?>">
                <?php if (!empty($errors['slug'])) : ?>
                    <div class="text-danger"><?php echo Template::escape($errors['slug']); ?></div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label" for="form_xml">Form XML</label>
                <textarea class="form-control" id="form_xml" name="form_xml" rows="10"><?php echo Template::escape($values['form_xml'] ?? ''); ?></textarea>
                <?php if (!empty($errors['form_xml'])) : ?>
                    <div class="text-danger"><?php echo Template::escape($errors['form_xml']); ?></div>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>