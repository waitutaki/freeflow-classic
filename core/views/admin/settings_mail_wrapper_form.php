<?php
use Core\Lang;
use Core\Template;
use Core\Csrf;
use Core\Asset;

$values = $values ?? [];
$errors = $errors ?? [];
$preview = $preview ?? ['wrapped_html' => '', 'wrapped_text' => ''];
$isEdit = $is_edit ?? false;
?>
<div class="container-fluid">
    <div class="zulu-toolbar-row">
        <div class="btn-group">
            <button class="btn btn-primary" form="mail-wrapper-form" type="submit">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-floppy-disk"></i></span>
                <?php echo Lang::get('FFCMS_SAVE'); ?>
            </button>
            <a class="btn btn-outline-secondary" href="/admin/settings?tab=mail&subtab=wrappers">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-xmark"></i></span>
                <?php echo Lang::get('FFCMS_CLOSE'); ?>
            </a>
        </div>
    </div>

    <?php if (!empty($errors['csrf'])) : ?>
        <div class="alert alert-danger" role="alert"><?php echo Template::escape($errors['csrf']); ?></div>
    <?php endif; ?>

    <form id="mail-wrapper-form" method="post" data-ff-freewrite-form="1">
        <?php echo Csrf::input(); ?>
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="zulu-panel">
                    <h1 class="mb-4"><?php echo Lang::get('FFCMS_MAIL_WRAPPERS'); ?></h1>
                    <div class="mb-3">
                        <label class="form-label" for="name"><?php echo Lang::get('FFCMS_NAME'); ?></label>
                        <input class="form-control" type="text" id="name" name="name" value="<?php echo Template::escape($values['name'] ?? ''); ?>" required>
                        <?php if (!empty($errors['name'])) : ?>
                            <div class="text-danger"><?php echo Template::escape($errors['name']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="wrapper_key"><?php echo Lang::get('FFCMS_WRAPPER_KEY'); ?></label>
                        <input class="form-control" type="text" id="wrapper_key" name="wrapper_key" value="<?php echo Template::escape($values['wrapper_key'] ?? ''); ?>" <?php echo $isEdit ? 'readonly' : ''; ?> required>
                        <?php if (!empty($errors['wrapper_key'])) : ?>
                            <div class="text-danger"><?php echo Template::escape($errors['wrapper_key']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo Lang::get('FFCMS_CONTENT'); ?></label>
                        <?php
                        $content_xml = $values['wrapper_doc_xml'] ?? '';
                        $field_name = 'wrapper_doc_xml';
                        $can_html = true;
                        $can_embed = false;
                        $allow_mailcontent = true;
                        require __DIR__ . '/partials/freewrite.php';
                        ?>
                        <?php if (!empty($errors['wrapper_doc_xml'])) : ?>
                            <div class="text-danger"><?php echo Template::escape($errors['wrapper_doc_xml']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="is_default" name="is_default" value="1" <?php echo !empty($values['is_default']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_default"><?php echo Lang::get('FFCMS_DEFAULT'); ?></label>
                        <?php if (!empty($errors['is_default'])) : ?>
                            <div class="text-danger"><?php echo Template::escape($errors['is_default']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="is_enabled" name="is_enabled" value="1" <?php echo !empty($values['is_enabled']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_enabled"><?php echo Lang::get('FFCMS_ENABLED'); ?></label>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="zulu-panel">
                    <h2 class="h5 mb-3"><?php echo Lang::get('FFCMS_PREVIEW'); ?></h2>
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="wrapper-preview-html-tab" data-bs-toggle="tab" data-bs-target="#wrapper-preview-html" type="button" role="tab" aria-controls="wrapper-preview-html" aria-selected="true"><?php echo Lang::get('FFCMS_HTML'); ?></button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="wrapper-preview-text-tab" data-bs-toggle="tab" data-bs-target="#wrapper-preview-text" type="button" role="tab" aria-controls="wrapper-preview-text" aria-selected="false"><?php echo Lang::get('FFCMS_TEXT'); ?></button>
                        </li>
                    </ul>
                    <div class="tab-content border border-top-0 p-3">
                        <div class="tab-pane fade show active" id="wrapper-preview-html" role="tabpanel" aria-labelledby="wrapper-preview-html-tab">
                            <div class="ff-mail-preview-html"><?php echo $preview['wrapped_html'] ?? ''; ?></div>
                        </div>
                        <div class="tab-pane fade" id="wrapper-preview-text" role="tabpanel" aria-labelledby="wrapper-preview-text-tab">
                            <pre class="ff-mail-preview-text"><?php echo Template::escape($preview['wrapped_text'] ?? ''); ?></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
<?php
return [
    'head_links' => '<link rel="stylesheet" href="' . Asset::url('core', 'lib/editors/freewrite/freewrite.css') . '">',
    'footer_scripts' => '<script src="' . Asset::url('core', 'lib/irocolor/iro.js') . '"></script>'
        . '<script src="' . Asset::url('core', 'lib/editors/freewrite/freewrite.js') . '"></script>',
];
?>
