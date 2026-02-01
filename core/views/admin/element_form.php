<?php
use Core\Lang;
use Core\Template;
use Core\Csrf;
use Core\Asset;

$context = $context ?? 'site';
$values = $values ?? [];
$errors = $errors ?? [];
$types = $types ?? [];
$assignments = $assignments ?? [];
$positions = $positions ?? [];
$roles = $roles ?? [];
$menus = $menus ?? [];
$pages = $pages ?? [];
$posts = $posts ?? [];
$categories = $categories ?? [];
$isEdit = $is_edit ?? false;

$settings = $values['settings'] ?? [];
?>
<div class="container-fluid">
    <div class="zulu-toolbar-row">
        <div class="btn-group">
            <button class="btn btn-primary" form="element-form" type="submit">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-floppy-disk"></i></span>
                <?php echo Lang::get('FFCMS_SAVE'); ?>
            </button>
            <a class="btn btn-outline-secondary" href="/admin/elements?context=<?php echo Template::escape($context); ?>">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-xmark"></i></span>
                <?php echo Lang::get('FFCMS_CLOSE'); ?>
            </a>
        </div>
    </div>

    <div class="zulu-panel">
        <h1 class="mb-4"><?php echo Lang::get('FFCMS_ELEMENTS'); ?></h1>

        <?php if (!empty($errors['csrf'])) : ?>
            <div class="alert alert-danger" role="alert"><?php echo Template::escape($errors['csrf']); ?></div>
        <?php endif; ?>

        <form id="element-form" method="post" data-element-form="1">
            <?php echo Csrf::input(); ?>
            <div class="mb-3">
                <label class="form-label" for="title"><?php echo Lang::get('FFCMS_TITLE'); ?></label>
                <input class="form-control" type="text" id="title" name="title" value="<?php echo Template::escape($values['title'] ?? ''); ?>" required>
                <?php if (!empty($errors['title'])) : ?>
                    <div class="text-danger"><?php echo Template::escape($errors['title']); ?></div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label" for="element_type_id"><?php echo Lang::get('FFCMS_TYPE'); ?></label>
                <select class="form-select" id="element_type_id" name="element_type_id" data-element-type="1" required>
                    <option value="0"><?php echo Lang::get('FFCMS_SELECT_TYPE'); ?></option>
                    <?php foreach ($types as $type) : ?>
                        <option value="<?php echo (int)$type['id']; ?>" data-type-key="<?php echo Template::escape($type['type_key'] ?? ''); ?>" <?php echo (int)($values['element_type_id'] ?? 0) === (int)$type['id'] ? 'selected' : ''; ?>>
                            <?php echo Template::escape($type['name'] ?? ''); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['element_type_id'])) : ?>
                    <div class="text-danger"><?php echo Template::escape($errors['element_type_id']); ?></div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label" for="assignment_id"><?php echo Lang::get('FFCMS_ASSIGNMENT'); ?></label>
                <select class="form-select" id="assignment_id" name="assignment_id" required>
                    <option value="0"><?php echo Lang::get('FFCMS_SELECT_ASSIGNMENT'); ?></option>
                    <?php foreach ($assignments as $assignment) : ?>
                        <?php
                        $label = Lang::get('FFCMS_GLOBAL');
                        if (($assignment['target_type'] ?? '') === 'route') {
                            $label = Lang::get('FFCMS_ROUTE') . ': ' . ($assignment['target_value'] ?? '');
                        } elseif (($assignment['target_type'] ?? '') === 'route_prefix') {
                            $label = Lang::get('FFCMS_ROUTE_PREFIX') . ': ' . ($assignment['target_value'] ?? '');
                        }
                        ?>
                        <option value="<?php echo (int)$assignment['id']; ?>" <?php echo (int)($values['assignment_id'] ?? 0) === (int)$assignment['id'] ? 'selected' : ''; ?>>
                            <?php echo Template::escape($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['assignment_id'])) : ?>
                    <div class="text-danger"><?php echo Template::escape($errors['assignment_id']); ?></div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label" for="template_position_id"><?php echo Lang::get('FFCMS_POSITION'); ?></label>
                <select class="form-select" id="template_position_id" name="template_position_id" required>
                    <option value="0"><?php echo Lang::get('FFCMS_SELECT_POSITION'); ?></option>
                    <?php foreach ($positions as $position) : ?>
                        <option value="<?php echo (int)$position['id']; ?>" <?php echo (int)($values['template_position_id'] ?? 0) === (int)$position['id'] ? 'selected' : ''; ?>>
                            <?php echo Template::escape($position['position'] ?? ''); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['template_position_id'])) : ?>
                    <div class="text-danger"><?php echo Template::escape($errors['template_position_id']); ?></div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label" for="ordering"><?php echo Lang::get('FFCMS_ORDERING'); ?></label>
                <input class="form-control" type="number" id="ordering" name="ordering" value="<?php echo Template::escape((string)($values['ordering'] ?? 0)); ?>">
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="is_published" name="is_published" value="1" <?php echo !empty($values['is_published']) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="is_published"><?php echo Lang::get('FFCMS_ENABLED'); ?></label>
            </div>

            <div class="mb-3">
                <label class="form-label" for="classes"><?php echo Lang::get('FFCMS_CSS_CLASS'); ?></label>
                <input class="form-control" type="text" id="classes" name="classes" value="<?php echo Template::escape($values['classes'] ?? ''); ?>">
            </div>

            <div class="border rounded p-3 mb-4">
                <h5 class="mb-3"><?php echo Lang::get('FFCMS_VISIBILITY'); ?></h5>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="condition_enabled" name="condition_enabled" value="1" <?php echo !empty($values['condition_enabled']) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="condition_enabled"><?php echo Lang::get('FFCMS_ENABLE_CONDITIONS'); ?></label>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="condition_op"><?php echo Lang::get('FFCMS_CONDITION_OP'); ?></label>
                    <select class="form-select" id="condition_op" name="condition_op">
                        <option value="ALL" <?php echo ($values['condition_op'] ?? 'ALL') === 'ALL' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_MATCH_ALL'); ?></option>
                        <option value="ANY" <?php echo ($values['condition_op'] ?? 'ALL') === 'ANY' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_MATCH_ANY'); ?></option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="condition_login"><?php echo Lang::get('FFCMS_LOGIN_STATE'); ?></label>
                    <select class="form-select" id="condition_login" name="condition_login">
                        <option value=""><?php echo Lang::get('FFCMS_ANY'); ?></option>
                        <option value="1" <?php echo ($values['condition_login'] ?? '') === '1' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_LOGGED_IN'); ?></option>
                        <option value="0" <?php echo ($values['condition_login'] ?? '') === '0' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_LOGGED_OUT'); ?></option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="roles_any"><?php echo Lang::get('FFCMS_ROLES_ANY'); ?></label>
                    <select class="form-select" id="roles_any" name="roles_any[]" multiple>
                        <?php foreach ($roles as $role) : ?>
                            <?php $selected = in_array((int)$role['id'], $values['roles_any'] ?? [], true); ?>
                            <option value="<?php echo (int)$role['id']; ?>" <?php echo $selected ? 'selected' : ''; ?>>
                                <?php echo Template::escape($role['title'] ?? ''); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="roles_all"><?php echo Lang::get('FFCMS_ROLES_ALL'); ?></label>
                    <select class="form-select" id="roles_all" name="roles_all[]" multiple>
                        <?php foreach ($roles as $role) : ?>
                            <?php $selected = in_array((int)$role['id'], $values['roles_all'] ?? [], true); ?>
                            <option value="<?php echo (int)$role['id']; ?>" <?php echo $selected ? 'selected' : ''; ?>>
                                <?php echo Template::escape($role['title'] ?? ''); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="border rounded p-3 mb-4" data-element-settings="menu">
                <h5 class="mb-3"><?php echo Lang::get('FFCMS_MENU_SETTINGS'); ?></h5>
                <div class="mb-3">
                    <label class="form-label" for="menu_key"><?php echo Lang::get('FFCMS_MENU'); ?></label>
                    <select class="form-select" id="menu_key" name="menu_key">
                        <option value=""><?php echo Lang::get('FFCMS_SELECT_MENU'); ?></option>
                        <?php foreach ($menus as $menu) : ?>
                            <option value="<?php echo Template::escape($menu['key'] ?? ''); ?>" <?php echo ($settings['menu_key'] ?? '') === ($menu['key'] ?? '') ? 'selected' : ''; ?>>
                                <?php echo Template::escape($menu['title'] ?? ''); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['menu_key'])) : ?>
                        <div class="text-danger"><?php echo Template::escape($errors['menu_key']); ?></div>
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="menu_style"><?php echo Lang::get('FFCMS_STYLE'); ?></label>
                    <select class="form-select" id="menu_style" name="menu_style">
                        <option value="navbar" <?php echo ($settings['menu_style'] ?? '') === 'navbar' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_NAVBAR'); ?></option>
                        <option value="vertical" <?php echo ($settings['menu_style'] ?? '') === 'vertical' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_VERTICAL'); ?></option>
                        <option value="tabs" <?php echo ($settings['menu_style'] ?? '') === 'tabs' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_TABS'); ?></option>
                        <option value="pills" <?php echo ($settings['menu_style'] ?? '') === 'pills' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_PILLS'); ?></option>
                    </select>
                    <?php if (!empty($errors['menu_style'])) : ?>
                        <div class="text-danger"><?php echo Template::escape($errors['menu_style']); ?></div>
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="menu_depth"><?php echo Lang::get('FFCMS_DEPTH'); ?></label>
                    <select class="form-select" id="menu_depth" name="menu_depth">
                        <?php for ($i = 1; $i <= 3; $i++) : ?>
                            <option value="<?php echo $i; ?>" <?php echo (string)($settings['menu_depth'] ?? '2') === (string)$i ? 'selected' : ''; ?>><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                    <?php if (!empty($errors['menu_depth'])) : ?>
                        <div class="text-danger"><?php echo Template::escape($errors['menu_depth']); ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="border rounded p-3 mb-4 d-none" data-element-settings="pages">
                <h5 class="mb-3"><?php echo Lang::get('FFCMS_PAGES'); ?></h5>
                <div class="mb-3">
                    <label class="form-label" for="page_mode"><?php echo Lang::get('FFCMS_MODE'); ?></label>
                    <select class="form-select" id="page_mode" name="page_mode">
                        <option value="all" <?php echo ($settings['page_mode'] ?? '') === 'all' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_ALL'); ?></option>
                        <option value="top" <?php echo ($settings['page_mode'] ?? '') === 'top' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_TOP'); ?></option>
                        <option value="featured" <?php echo ($settings['page_mode'] ?? '') === 'featured' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_FEATURED'); ?></option>
                        <option value="latest" <?php echo ($settings['page_mode'] ?? '') === 'latest' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_LATEST'); ?></option>
                        <option value="one" <?php echo ($settings['page_mode'] ?? '') === 'one' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_ONE'); ?></option>
                    </select>
                    <?php if (!empty($errors['page_mode'])) : ?>
                        <div class="text-danger"><?php echo Template::escape($errors['page_mode']); ?></div>
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="page_id"><?php echo Lang::get('FFCMS_PAGE'); ?></label>
                    <select class="form-select" id="page_id" name="page_id">
                        <option value="0"><?php echo Lang::get('FFCMS_SELECT_PAGE'); ?></option>
                        <?php foreach ($pages as $page) : ?>
                            <option value="<?php echo (int)$page['id']; ?>" <?php echo (int)($settings['page_id'] ?? 0) === (int)$page['id'] ? 'selected' : ''; ?>>
                                <?php echo Template::escape($page['title'] ?? ''); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['page_id'])) : ?>
                        <div class="text-danger"><?php echo Template::escape($errors['page_id']); ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="border rounded p-3 mb-4 d-none" data-element-settings="posts">
                <h5 class="mb-3"><?php echo Lang::get('FFCMS_POSTS'); ?></h5>
                <div class="mb-3">
                    <label class="form-label" for="post_mode"><?php echo Lang::get('FFCMS_MODE'); ?></label>
                    <select class="form-select" id="post_mode" name="post_mode">
                        <option value="all" <?php echo ($settings['post_mode'] ?? '') === 'all' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_ALL'); ?></option>
                        <option value="top" <?php echo ($settings['post_mode'] ?? '') === 'top' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_TOP'); ?></option>
                        <option value="featured" <?php echo ($settings['post_mode'] ?? '') === 'featured' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_FEATURED'); ?></option>
                        <option value="latest" <?php echo ($settings['post_mode'] ?? '') === 'latest' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_LATEST'); ?></option>
                        <option value="one" <?php echo ($settings['post_mode'] ?? '') === 'one' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_ONE'); ?></option>
                    </select>
                    <?php if (!empty($errors['post_mode'])) : ?>
                        <div class="text-danger"><?php echo Template::escape($errors['post_mode']); ?></div>
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="post_id"><?php echo Lang::get('FFCMS_POST'); ?></label>
                    <select class="form-select" id="post_id" name="post_id">
                        <option value="0"><?php echo Lang::get('FFCMS_SELECT_POST'); ?></option>
                        <?php foreach ($posts as $post) : ?>
                            <option value="<?php echo (int)$post['id']; ?>" <?php echo (int)($settings['post_id'] ?? 0) === (int)$post['id'] ? 'selected' : ''; ?>>
                                <?php echo Template::escape($post['title'] ?? ''); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['post_id'])) : ?>
                        <div class="text-danger"><?php echo Template::escape($errors['post_id']); ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="border rounded p-3 mb-4 d-none" data-element-settings="categories">
                <h5 class="mb-3"><?php echo Lang::get('FFCMS_CATEGORIES'); ?></h5>
                <div class="mb-3">
                    <label class="form-label" for="category_mode"><?php echo Lang::get('FFCMS_MODE'); ?></label>
                    <select class="form-select" id="category_mode" name="category_mode">
                        <option value="all" <?php echo ($settings['category_mode'] ?? '') === 'all' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_ALL'); ?></option>
                        <option value="one" <?php echo ($settings['category_mode'] ?? '') === 'one' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_ONE'); ?></option>
                    </select>
                    <?php if (!empty($errors['category_mode'])) : ?>
                        <div class="text-danger"><?php echo Template::escape($errors['category_mode']); ?></div>
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="category_id"><?php echo Lang::get('FFCMS_CATEGORY'); ?></label>
                    <select class="form-select" id="category_id" name="category_id">
                        <option value="0"><?php echo Lang::get('FFCMS_SELECT_CATEGORY'); ?></option>
                        <?php foreach ($categories as $category) : ?>
                            <option value="<?php echo (int)$category['id']; ?>" <?php echo (int)($settings['category_id'] ?? 0) === (int)$category['id'] ? 'selected' : ''; ?>>
                                <?php echo Template::escape($category['title'] ?? ''); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['category_id'])) : ?>
                        <div class="text-danger"><?php echo Template::escape($errors['category_id']); ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="border rounded p-3 mb-4 d-none" data-element-settings="html">
                <h5 class="mb-3"><?php echo Lang::get('FFCMS_HTML'); ?></h5>
                <div class="mb-3">
                    <label class="form-label" for="html"><?php echo Lang::get('FFCMS_HTML'); ?></label>
                    <textarea class="form-control" id="html" name="html" rows="6"><?php echo Template::escape($settings['html'] ?? ''); ?></textarea>
                    <?php if (!empty($errors['html'])) : ?>
                        <div class="text-danger"><?php echo Template::escape($errors['html']); ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="border rounded p-3 mb-4 d-none" data-element-settings="embed">
                <h5 class="mb-3"><?php echo Lang::get('FFCMS_EMBED'); ?></h5>
                <div class="mb-3">
                    <label class="form-label" for="embed_url"><?php echo Lang::get('FFCMS_URL'); ?></label>
                    <input class="form-control" type="text" id="embed_url" name="embed_url" value="<?php echo Template::escape($settings['embed_url'] ?? ''); ?>">
                    <?php if (!empty($errors['embed_url'])) : ?>
                        <div class="text-danger"><?php echo Template::escape($errors['embed_url']); ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="border rounded p-3 mb-4 d-none" data-element-settings="url">
                <h5 class="mb-3"><?php echo Lang::get('FFCMS_URL'); ?></h5>
                <div class="mb-3">
                    <label class="form-label" for="url"><?php echo Lang::get('FFCMS_URL'); ?></label>
                    <input class="form-control" type="text" id="url" name="url" value="<?php echo Template::escape($settings['url'] ?? ''); ?>">
                    <?php if (!empty($errors['url'])) : ?>
                        <div class="text-danger"><?php echo Template::escape($errors['url']); ?></div>
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="url_label"><?php echo Lang::get('FFCMS_LABEL'); ?></label>
                    <input class="form-control" type="text" id="url_label" name="url_label" value="<?php echo Template::escape($settings['url_label'] ?? ''); ?>">
                </div>
            </div>

            <div class="border rounded p-3 mb-4 d-none" data-element-settings="image">
                <h5 class="mb-3">Image</h5>
                <div class="mb-3">
                    <label class="form-label" for="image_src">Image URL</label>
                    <div class="input-group">
                        <input class="form-control" type="text" id="image_src" name="image_src" value="<?php echo Template::escape($settings['image_src'] ?? ''); ?>">
                        <button class="btn btn-outline-secondary" type="button" data-media-picker-button data-media-picker-target="image_src">
                            <span aria-hidden="true"><i class="fa-solid fa-image"></i></span>
                            <?php echo Lang::get('FFCMS_SELECT_IMAGE'); ?>
                        </button>
                    </div>
                    <?php if (!empty($errors['image_src'])) : ?>
                        <div class="text-danger"><?php echo Template::escape($errors['image_src']); ?></div>
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="image_alt">Alt Text</label>
                    <input class="form-control" type="text" id="image_alt" name="image_alt" value="<?php echo Template::escape($settings['image_alt'] ?? ''); ?>">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="image_width">Width</label>
                        <input class="form-control" type="number" id="image_width" name="image_width" value="<?php echo (int)($settings['image_width'] ?? 0); ?>" min="0">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="image_height">Height</label>
                        <input class="form-control" type="number" id="image_height" name="image_height" value="<?php echo (int)($settings['image_height'] ?? 0); ?>" min="0">
                    </div>
                </div>
            </div>

            <div class="border rounded p-3 mb-4 d-none" data-element-settings="text">
                <h5 class="mb-3">Text</h5>
                <div class="mb-3">
                    <label class="form-label" for="text_content">Content</label>
                    <textarea class="form-control" id="text_content" name="text_content" rows="4"><?php echo Template::escape($settings['text_content'] ?? ''); ?></textarea>
                    <?php if (!empty($errors['text_content'])) : ?>
                        <div class="text-danger"><?php echo Template::escape($errors['text_content']); ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="border rounded p-3 mb-4 d-none" data-element-settings="button">
                <h5 class="mb-3">Button</h5>
                <div class="mb-3">
                    <label class="form-label" for="button_label">Label</label>
                    <input class="form-control" type="text" id="button_label" name="button_label" value="<?php echo Template::escape($settings['button_label'] ?? ''); ?>">
                    <?php if (!empty($errors['button_label'])) : ?>
                        <div class="text-danger"><?php echo Template::escape($errors['button_label']); ?></div>
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="button_url">URL</label>
                    <input class="form-control" type="text" id="button_url" name="button_url" value="<?php echo Template::escape($settings['button_url'] ?? ''); ?>">
                    <?php if (!empty($errors['button_url'])) : ?>
                        <div class="text-danger"><?php echo Template::escape($errors['button_url']); ?></div>
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="button_style">Style</label>
                    <select class="form-select" id="button_style" name="button_style">
                        <option value="primary" <?php echo ($settings['button_style'] ?? 'primary') === 'primary' ? 'selected' : ''; ?>>Primary</option>
                        <option value="secondary" <?php echo ($settings['button_style'] ?? 'primary') === 'secondary' ? 'selected' : ''; ?>>Secondary</option>
                        <option value="success" <?php echo ($settings['button_style'] ?? 'primary') === 'success' ? 'selected' : ''; ?>>Success</option>
                        <option value="danger" <?php echo ($settings['button_style'] ?? 'primary') === 'danger' ? 'selected' : ''; ?>>Danger</option>
                    </select>
                </div>
            </div>
        </form>
    </div>
</div>
<?php
return [
    'footer_scripts' => '<script src="' . Asset::url('core', 'js/elements_manager.js') . '"></script>',
];
?>
