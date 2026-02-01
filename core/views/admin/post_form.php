<?php
use Core\Lang;
use Core\Template;
use Core\Csrf;
use Core\Asset;

$values = $values ?? [];
$errors = $errors ?? [];
$categories = $categories ?? [];
$isEdit = $is_edit ?? false;
$canPublish = $can_publish ?? false;
$canHtml = $can_html ?? false;
$canEmbed = $can_embed ?? false;
$mediaUrl = '/admin/media/picker?bucket=users';
$docUrl = '/admin/documents/picker';
?>
<div class="container-fluid">
    <div class="zulu-toolbar-row">
        <div class="btn-group">
            <button class="btn btn-primary" form="post-form" type="submit">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-floppy-disk"></i></span>
                <?php echo Lang::get('FFCMS_SAVE'); ?>
            </button>
            <a class="btn btn-outline-secondary" href="/admin/content/posts">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-xmark"></i></span>
                <?php echo Lang::get('FFCMS_CLOSE'); ?>
            </a>
        </div>
    </div>

    <div class="zulu-panel">
        <h1 class="mb-4"><?php echo Lang::get('FFCMS_POSTS'); ?></h1>

        <?php if (!empty($errors['csrf'])) : ?>
            <div class="alert alert-danger" role="alert"><?php echo Template::escape($errors['csrf']); ?></div>
        <?php endif; ?>

        <form id="post-form" method="post" data-ff-freewrite-form="1">
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
                <label class="form-label" for="category_id"><?php echo Lang::get('FFCMS_CATEGORY'); ?></label>
                <select class="form-select" id="category_id" name="category_id">
                    <option value="0"><?php echo Lang::get('FFCMS_NONE'); ?></option>
                    <?php foreach ($categories as $category) : ?>
                        <?php $selected = (int)($values['category_id'] ?? 0) === (int)$category['id']; ?>
                        <option value="<?php echo (int)$category['id']; ?>" <?php echo $selected ? 'selected' : ''; ?>>
                            <?php echo Template::escape($category['title'] ?? ''); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label" for="status"><?php echo Lang::get('FFCMS_STATUS'); ?></label>
                <select class="form-select" id="status" name="status" <?php echo $canPublish ? '' : 'disabled'; ?>>
                    <?php
                    $statusOptions = [
                        'draft' => Lang::get('FFCMS_STATUS_DRAFT'),
                        'published' => Lang::get('FFCMS_STATUS_PUBLISHED'),
                        'unpublished' => Lang::get('FFCMS_STATUS_UNPUBLISHED'),
                        'archived' => Lang::get('FFCMS_STATUS_ARCHIVED'),
                    ];
                    $currentStatus = $values['status'] ?? 'draft';
                    ?>
                    <?php foreach ($statusOptions as $key => $label) : ?>
                        <option value="<?php echo Template::escape($key); ?>" <?php echo $currentStatus === $key ? 'selected' : ''; ?>><?php echo Template::escape($label); ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (!$canPublish) : ?>
                    <input type="hidden" name="status" value="<?php echo Template::escape($currentStatus); ?>">
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label" for="featured_image_path"><?php echo Lang::get('FFCMS_FEATURED_IMAGE'); ?></label>
                <div class="d-flex gap-2">
                    <input class="form-control" type="text" id="featured_image_path" name="featured_image_path" value="<?php echo Template::escape($values['featured_image_path'] ?? ''); ?>" data-featured-input="1" readonly>
                    <button class="btn btn-outline-secondary" type="button" data-featured-button="1" data-featured-url="<?php echo Template::escape($mediaUrl); ?>">
                        <span class="me-1" aria-hidden="true"><i class="fa-solid fa-image"></i></span>
                        <?php echo Lang::get('FFCMS_SELECT_IMAGE'); ?>
                    </button>
                </div>
                <div class="mt-2" data-featured-preview="1">
                    <?php if (!empty($values['featured_image_path'])) : ?>
                        <img class="img-fluid" src="<?php echo Template::escape($values['featured_image_path']); ?>" alt="<?php echo Template::escape(Lang::get('FFCMS_FEATURED_IMAGE')); ?>">
                    <?php endif; ?>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="excerpt"><?php echo Lang::get('FFCMS_EXCERPT'); ?></label>
                <textarea class="form-control" id="excerpt" name="excerpt" rows="3"><?php echo Template::escape($values['excerpt'] ?? ''); ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label"><?php echo Lang::get('FFCMS_CONTENT'); ?></label>
                <?php
                $content_xml = $values['content_xml'] ?? '';
                $field_name = 'content_xml';
                $can_html = $canHtml;
                $can_embed = $canEmbed;
                $media_url = $mediaUrl;
                $doc_url = $docUrl;
                require __DIR__ . '/partials/freewrite.php';
                ?>
                <?php if (!empty($errors['content_xml'])) : ?>
                    <div class="text-danger"><?php echo Template::escape($errors['content_xml']); ?></div>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>
<?php
return [
    'head_links' => '<link rel="stylesheet" href="' . Asset::url('core', 'lib/editors/freewrite/freewrite.css') . '">',
    'footer_scripts' => '<script src="' . Asset::url('core', 'lib/irocolor/iro.js') . '"></script>'
        . '<script src="' . Asset::url('core', 'lib/editors/freewrite/freewrite.js') . '"></script>',
];
?>
