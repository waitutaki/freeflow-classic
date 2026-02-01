<?php
use Core\Lang;
use Core\Template;
use Core\Csrf;

$values = $values ?? [];
$errors = $errors ?? [];
$categories = $categories ?? [];
$isEdit = $is_edit ?? false;
$currentId = $values['id'] ?? 0;
?>
<div class="container-fluid">
    <div class="zulu-toolbar-row">
        <div class="btn-group">
            <button class="btn btn-primary" form="category-form" type="submit">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-floppy-disk"></i></span>
                <?php echo Lang::get('FFCMS_SAVE'); ?>
            </button>
            <a class="btn btn-outline-secondary" href="/admin/content/categories">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-xmark"></i></span>
                <?php echo Lang::get('FFCMS_CLOSE'); ?>
            </a>
        </div>
    </div>

    <div class="zulu-panel">
        <h1 class="mb-4"><?php echo Lang::get('FFCMS_CATEGORIES'); ?></h1>

        <?php if (!empty($errors['csrf'])) : ?>
            <div class="alert alert-danger" role="alert"><?php echo Template::escape($errors['csrf']); ?></div>
        <?php endif; ?>

        <form id="category-form" method="post">
            <?php echo Csrf::input(); ?>
            <div class="mb-3">
                <label class="form-label" for="title"><?php echo Lang::get('FFCMS_TITLE'); ?></label>
                <input class="form-control" type="text" id="title" name="title" value="<?php echo Template::escape($values['title'] ?? ''); ?>" required>
                <?php if (!empty($errors['title'])) : ?>
                    <div class="text-danger"><?php echo Template::escape($errors['title']); ?></div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label" for="slug"><?php echo Lang::get('FFCMS_SLUG'); ?></label>
                <input class="form-control" type="text" id="slug" name="slug" value="<?php echo Template::escape($values['slug'] ?? ''); ?>">
                <?php if (!empty($errors['slug'])) : ?>
                    <div class="text-danger"><?php echo Template::escape($errors['slug']); ?></div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label" for="parent_id"><?php echo Lang::get('FFCMS_PARENT'); ?></label>
                <select class="form-select" id="parent_id" name="parent_id">
                    <option value="0"><?php echo Lang::get('FFCMS_NONE'); ?></option>
                    <?php foreach ($categories as $category) : ?>
                        <?php if ((int)$category['id'] === (int)$currentId) : ?>
                            <?php continue; ?>
                        <?php endif; ?>
                        <?php $selected = (int)($values['parent_id'] ?? 0) === (int)$category['id']; ?>
                        <option value="<?php echo (int)$category['id']; ?>" <?php echo $selected ? 'selected' : ''; ?>>
                            <?php echo Template::escape($category['title'] ?? ''); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['parent_id'])) : ?>
                    <div class="text-danger"><?php echo Template::escape($errors['parent_id']); ?></div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label" for="description"><?php echo Lang::get('FFCMS_DESCRIPTION'); ?></label>
                <textarea class="form-control" id="description" name="description" rows="4"><?php echo Template::escape($values['description'] ?? ''); ?></textarea>
            </div>
        </form>
    </div>
</div>
