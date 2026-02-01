<?php
use Core\Lang;
use Core\Template;
use Core\Csrf;
use Core\Asset;

$menu = $menu ?? [];
$values = $values ?? [];
$errors = $errors ?? [];
$items = $items ?? [];
$pages = $pages ?? [];
$posts = $posts ?? [];
$isEdit = $is_edit ?? false;
?>
<div class="container-fluid">
    <div class="zulu-toolbar-row">
        <div class="btn-group">
            <button class="btn btn-primary" form="menu-item-form" type="submit">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-floppy-disk"></i></span>
                <?php echo Lang::get('FFCMS_SAVE'); ?>
            </button>
            <a class="btn btn-outline-secondary" href="/admin/menus/<?php echo (int)$menu['id']; ?>/items">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-xmark"></i></span>
                <?php echo Lang::get('FFCMS_CLOSE'); ?>
            </a>
        </div>
    </div>

    <div class="zulu-panel">
        <h1 class="mb-4"><?php echo Lang::get('FFCMS_MENU_ITEMS'); ?></h1>

        <?php if (!empty($errors['csrf'])) : ?>
            <div class="alert alert-danger" role="alert"><?php echo Template::escape($errors['csrf']); ?></div>
        <?php endif; ?>

        <form id="menu-item-form" method="post" data-menu-item-form="1">
            <?php echo Csrf::input(); ?>
            <div class="mb-3">
                <label class="form-label" for="title"><?php echo Lang::get('FFCMS_TITLE'); ?></label>
                <input class="form-control" type="text" id="title" name="title" value="<?php echo Template::escape($values['title'] ?? ''); ?>" required>
                <div class="form-text"><?php echo Template::escape(Lang::get('FFCMS_MENU_DYNAMIC_HINT')); ?></div>
                <?php if (!empty($errors['title'])) : ?>
                    <div class="text-danger"><?php echo Template::escape($errors['title']); ?></div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label" for="type"><?php echo Lang::get('FFCMS_TYPE'); ?></label>
                <select class="form-select" id="type" name="type" data-menu-type="1">
                    <option value="url" <?php echo ($values['type'] ?? '') === 'url' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_MENU_TYPE_URL'); ?></option>
                    <option value="page" <?php echo ($values['type'] ?? '') === 'page' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_MENU_TYPE_PAGE'); ?></option>
                    <option value="post" <?php echo ($values['type'] ?? '') === 'post' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_MENU_TYPE_POST'); ?></option>
                </select>
                <?php if (!empty($errors['type'])) : ?>
                    <div class="text-danger"><?php echo Template::escape($errors['type']); ?></div>
                <?php endif; ?>
            </div>
            <div class="mb-3" data-menu-type-section="url">
                <label class="form-label" for="url"><?php echo Lang::get('FFCMS_URL'); ?></label>
                <input class="form-control" type="text" id="url" name="url" value="<?php echo Template::escape($values['url'] ?? ''); ?>">
                <?php if (!empty($errors['url'])) : ?>
                    <div class="text-danger"><?php echo Template::escape($errors['url']); ?></div>
                <?php endif; ?>
            </div>
            <div class="mb-3 d-none" data-menu-type-section="page">
                <label class="form-label" for="page_id"><?php echo Lang::get('FFCMS_PAGE'); ?></label>
                <select class="form-select" id="page_id" name="page_id">
                    <option value="0"><?php echo Lang::get('FFCMS_SELECT_PAGE'); ?></option>
                    <?php foreach ($pages as $page) : ?>
                        <option value="<?php echo (int)$page['id']; ?>" <?php echo (int)($values['page_id'] ?? 0) === (int)$page['id'] ? 'selected' : ''; ?>>
                            <?php echo Template::escape($page['title'] ?? ''); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['page_id'])) : ?>
                    <div class="text-danger"><?php echo Template::escape($errors['page_id']); ?></div>
                <?php endif; ?>
            </div>
            <div class="mb-3 d-none" data-menu-type-section="post">
                <label class="form-label" for="post_id"><?php echo Lang::get('FFCMS_POST'); ?></label>
                <select class="form-select" id="post_id" name="post_id">
                    <option value="0"><?php echo Lang::get('FFCMS_SELECT_POST'); ?></option>
                    <?php foreach ($posts as $post) : ?>
                        <option value="<?php echo (int)$post['id']; ?>" <?php echo (int)($values['post_id'] ?? 0) === (int)$post['id'] ? 'selected' : ''; ?>>
                            <?php echo Template::escape($post['title'] ?? ''); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['post_id'])) : ?>
                    <div class="text-danger"><?php echo Template::escape($errors['post_id']); ?></div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label" for="parent_id"><?php echo Lang::get('FFCMS_PARENT'); ?></label>
                <select class="form-select" id="parent_id" name="parent_id">
                    <option value="0"><?php echo Lang::get('FFCMS_NONE'); ?></option>
                    <?php foreach ($items as $item) : ?>
                        <?php if ((int)$item['id'] === (int)($values['id'] ?? 0)) : ?>
                            <?php continue; ?>
                        <?php endif; ?>
                        <option value="<?php echo (int)$item['id']; ?>" <?php echo (int)($values['parent_id'] ?? 0) === (int)$item['id'] ? 'selected' : ''; ?>>
                            <?php echo Template::escape($item['title'] ?? ''); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['parent_id'])) : ?>
                    <div class="text-danger"><?php echo Template::escape($errors['parent_id']); ?></div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label" for="css_class"><?php echo Lang::get('FFCMS_CSS_CLASS'); ?></label>
                <input class="form-control" type="text" id="css_class" name="css_class" value="<?php echo Template::escape($values['css_class'] ?? ''); ?>">
                <?php if (!empty($errors['css_class'])) : ?>
                    <div class="text-danger"><?php echo Template::escape($errors['css_class']); ?></div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label" for="target"><?php echo Lang::get('FFCMS_TARGET'); ?></label>
                <select class="form-select" id="target" name="target">
                    <option value=""><?php echo Lang::get('FFCMS_DEFAULT'); ?></option>
                    <option value="_self" <?php echo ($values['target'] ?? '') === '_self' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_TARGET_SELF'); ?></option>
                    <option value="_blank" <?php echo ($values['target'] ?? '') === '_blank' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_TARGET_BLANK'); ?></option>
                </select>
                <?php if (!empty($errors['target'])) : ?>
                    <div class="text-danger"><?php echo Template::escape($errors['target']); ?></div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label" for="rel"><?php echo Lang::get('FFCMS_REL'); ?></label>
                <input class="form-control" type="text" id="rel" name="rel" value="<?php echo Template::escape($values['rel'] ?? ''); ?>">
                <?php if (!empty($errors['rel'])) : ?>
                    <div class="text-danger"><?php echo Template::escape($errors['rel']); ?></div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label" for="visibility"><?php echo Lang::get('FFCMS_VISIBILITY'); ?></label>
                <select class="form-select" id="visibility" name="visibility">
                    <?php $visibilityValue = $values['visibility'] ?? 'all'; ?>
                    <option value="all" <?php echo $visibilityValue === 'all' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_ALL'); ?></option>
                    <option value="logged_in" <?php echo $visibilityValue === 'logged_in' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_LOGGED_IN'); ?></option>
                    <option value="logged_out" <?php echo $visibilityValue === 'logged_out' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_LOGGED_OUT'); ?></option>
                    <option value="none" <?php echo $visibilityValue === 'none' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_NONE'); ?></option>
                </select>
                <?php if (!empty($errors['visibility'])) : ?>
                    <div class="text-danger"><?php echo Template::escape($errors['visibility']); ?></div>
                <?php endif; ?>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="is_enabled" name="is_enabled" value="1" <?php echo !empty($values['is_enabled']) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="is_enabled"><?php echo Lang::get('FFCMS_ENABLED'); ?></label>
            </div>
        </form>
    </div>
</div>
<?php
return [
    'footer_scripts' => '<script src="' . Asset::url('core', 'js/menu_manager.js') . '"></script>',
];
?>
