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
            <button class="btn btn-primary" form="assignment-form" type="submit">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-floppy-disk"></i></span>
                <?php echo Lang::get('FFCMS_SAVE'); ?>
            </button>
            <a class="btn btn-outline-secondary" href="/admin/elements/assignments?context=<?php echo Template::escape($values['context'] ?? 'site'); ?>">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-xmark"></i></span>
                <?php echo Lang::get('FFCMS_CLOSE'); ?>
            </a>
        </div>
    </div>

    <div class="zulu-panel">
        <h1 class="mb-4"><?php echo Lang::get('FFCMS_ELEMENT_ASSIGNMENTS'); ?></h1>

        <?php if (!empty($errors['csrf'])) : ?>
            <div class="alert alert-danger" role="alert"><?php echo Template::escape($errors['csrf']); ?></div>
        <?php endif; ?>

        <form id="assignment-form" method="post">
            <?php echo Csrf::input(); ?>
            <div class="mb-3">
                <label class="form-label" for="context"><?php echo Lang::get('FFCMS_CONTEXT'); ?></label>
                <select class="form-select" id="context" name="context">
                    <option value="site" <?php echo ($values['context'] ?? '') === 'site' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_CONTEXT_SITE'); ?></option>
                    <option value="admin" <?php echo ($values['context'] ?? '') === 'admin' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_CONTEXT_ADMIN'); ?></option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label" for="target_type"><?php echo Lang::get('FFCMS_TARGET_TYPE'); ?></label>
                <select class="form-select" id="target_type" name="target_type">
                    <option value="global" <?php echo ($values['target_type'] ?? '') === 'global' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_GLOBAL'); ?></option>
                    <option value="route" <?php echo ($values['target_type'] ?? '') === 'route' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_ROUTE'); ?></option>
                    <option value="route_prefix" <?php echo ($values['target_type'] ?? '') === 'route_prefix' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_ROUTE_PREFIX'); ?></option>
                </select>
                <?php if (!empty($errors['target_type'])) : ?>
                    <div class="text-danger"><?php echo Template::escape($errors['target_type']); ?></div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label" for="target_value"><?php echo Lang::get('FFCMS_TARGET_VALUE'); ?></label>
                <input class="form-control" type="text" id="target_value" name="target_value" value="<?php echo Template::escape($values['target_value'] ?? ''); ?>">
                <?php if (!empty($errors['target_value'])) : ?>
                    <div class="text-danger"><?php echo Template::escape($errors['target_value']); ?></div>
                <?php endif; ?>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="is_enabled" name="is_enabled" value="1" <?php echo !empty($values['is_enabled']) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="is_enabled"><?php echo Lang::get('FFCMS_ENABLED'); ?></label>
            </div>
        </form>
    </div>
</div>
