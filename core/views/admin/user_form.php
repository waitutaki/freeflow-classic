<?php
use Core\Lang;
use Core\Template;
use Core\Csrf;

$values = $values ?? [];
$errors = $errors ?? [];
$roles = $roles ?? [];
$isEdit = $is_edit ?? false;
?>
<div class="container-fluid">
    <div class="zulu-toolbar-row">
        <div class="btn-group">
            <button class="btn btn-primary" form="user-form" type="submit">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-floppy-disk"></i></span>
                <?php echo Lang::get('FFCMS_SAVE'); ?>
            </button>
            <a class="btn btn-outline-secondary" href="/admin/users">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-xmark"></i></span>
                <?php echo Lang::get('FFCMS_CLOSE'); ?>
            </a>
        </div>
    </div>
    <div class="zulu-panel">
        <h1 class="mb-4"><?php echo Lang::get('FFCMS_USERS'); ?></h1>
        <?php if (!empty($errors['csrf'])) : ?>
            <div class="alert alert-danger" role="alert"><?php echo Template::escape($errors['csrf']); ?></div>
        <?php endif; ?>
        <form id="user-form" method="post">
            <?php echo Csrf::input(); ?>
            <div class="mb-3">
                <label class="form-label" for="name_first"><?php echo Lang::get('FFCMS_FIRST_NAME'); ?></label>
                <input class="form-control" type="text" id="name_first" name="name_first" value="<?php echo Template::escape($values['name_first'] ?? ''); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="name_last"><?php echo Lang::get('FFCMS_LAST_NAME'); ?></label>
                <input class="form-control" type="text" id="name_last" name="name_last" value="<?php echo Template::escape($values['name_last'] ?? ''); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="username"><?php echo Lang::get('FFCMS_USERNAME'); ?></label>
                <input class="form-control" type="text" id="username" name="username" value="<?php echo Template::escape($values['username'] ?? ''); ?>">
                <?php if (!empty($errors['username'])) : ?>
                    <div class="text-danger"><?php echo Template::escape($errors['username']); ?></div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label" for="email"><?php echo Lang::get('FFCMS_EMAIL'); ?></label>
                <input class="form-control" type="email" id="email" name="email" value="<?php echo Template::escape($values['email'] ?? ''); ?>" required>
                <?php if (!empty($errors['email'])) : ?>
                    <div class="text-danger"><?php echo Template::escape($errors['email']); ?></div>
                <?php endif; ?>
            </div>
            <?php if (!$isEdit) : ?>
                <div class="mb-3">
                    <label class="form-label" for="password"><?php echo Lang::get('FFCMS_PASSWORD'); ?></label>
                    <input class="form-control" type="password" id="password" name="password" required>
                </div>
            <?php else : ?>
                <div class="mb-3">
                    <label class="form-label" for="password"><?php echo Lang::get('FFCMS_PASSWORD'); ?></label>
                    <input class="form-control" type="password" id="password" name="password" placeholder="<?php echo Lang::get('FFCMS_PASSWORD_OPTIONAL'); ?>">
                </div>
            <?php endif; ?>
            <div class="mb-3">
                <label class="form-label" for="role_id"><?php echo Lang::get('FFCMS_ROLE'); ?></label>
                <select class="form-select" id="role_id" name="role_id">
                    <?php foreach ($roles as $role) : ?>
                        <option value="<?php echo (int)$role['id']; ?>" <?php echo ((int)($values['role_id'] ?? 0) === (int)$role['id']) ? 'selected' : ''; ?>>
                            <?php echo Template::escape($role['title'] ?? ''); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label" for="status"><?php echo Lang::get('FFCMS_STATUS'); ?></label>
                <select class="form-select" id="status" name="status">
                    <option value="1" <?php echo (int)($values['status'] ?? 1) === 1 ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_STATUS_ACTIVE'); ?></option>
                    <option value="2" <?php echo (int)($values['status'] ?? 1) === 2 ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_STATUS_PENDING'); ?></option>
                    <option value="0" <?php echo (int)($values['status'] ?? 1) === 0 ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_STATUS_DISABLED'); ?></option>
                </select>
            </div>
            <div class="form-check">
                <input type="hidden" name="is_enabled" value="0">
                <input class="form-check-input" type="checkbox" id="is_enabled" name="is_enabled" value="1" <?php echo !empty($values['is_enabled']) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="is_enabled"><?php echo Lang::get('FFCMS_ENABLED'); ?></label>
            </div>
        </form>
    </div>
</div>
