<?php
use Core\Lang;
use Core\Template;
use Core\Csrf;

$values = $values ?? [];
$errors = $errors ?? [];
$roles = $roles ?? [];
$isEdit = $is_edit ?? false;
$role = $role ?? null;
?>
<div class="container-fluid">
    <div class="zulu-toolbar-row">
        <div class="btn-group">
            <button class="btn btn-primary" form="role-form" type="submit">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-floppy-disk"></i></span>
                <?php echo Lang::get('FFCMS_SAVE'); ?>
            </button>
            <a class="btn btn-outline-secondary" href="/admin/roles">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-xmark"></i></span>
                <?php echo Lang::get('FFCMS_CLOSE'); ?>
            </a>
        </div>
    </div>

    <div class="zulu-panel">
        <h1 class="mb-4"><?php echo Lang::get('FFCMS_ROLE'); ?></h1>
        <?php if (!empty($errors['csrf'])) : ?>
            <div class="alert alert-danger" role="alert"><?php echo Template::escape($errors['csrf']); ?></div>
        <?php endif; ?>
        <form id="role-form" method="post">
            <?php echo Csrf::input(); ?>
            <div class="mb-3">
                <label class="form-label" for="title"><?php echo Lang::get('FFCMS_NAME'); ?></label>
                <input class="form-control" type="text" id="title" name="title" value="<?php echo Template::escape($values['title'] ?? ''); ?>" required>
                <?php if (!empty($errors['title'])) : ?>
                    <div class="text-danger"><?php echo Template::escape($errors['title']); ?></div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label" for="parent_role_id"><?php echo Lang::get('FFCMS_PARENT_ROLE'); ?></label>
                <select class="form-select" id="parent_role_id" name="parent_role_id" <?php echo (!empty($role['is_system'])) ? 'disabled' : ''; ?>>
                    <option value="0"><?php echo Lang::get('FFCMS_NONE'); ?></option>
                    <?php foreach ($roles as $r) : ?>
                        <option value="<?php echo (int)$r['id']; ?>" <?php echo ((int)($values['parent_role_id'] ?? 0) === (int)$r['id']) ? 'selected' : ''; ?>>
                            <?php echo Template::escape($r['title'] ?? ''); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['parent_role_id'])) : ?>
                    <div class="text-danger"><?php echo Template::escape($errors['parent_role_id']); ?></div>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>
