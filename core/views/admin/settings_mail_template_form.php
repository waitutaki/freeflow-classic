<?php
use Core\Lang;
use Core\Template;
use Core\Csrf;
use Core\Asset;

$values = $values ?? [];
$errors = $errors ?? [];
$preview = $preview ?? ['html' => '', 'text' => '', 'wrapped_html' => '', 'wrapped_text' => ''];
$isEdit = $is_edit ?? false;
?>
<div class="container-fluid">
    <div class="zulu-toolbar-row">
        <div class="btn-group">
            <button class="btn btn-primary" form="mail-template-form" type="submit">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-floppy-disk"></i></span>
                <?php echo Lang::get('FFCMS_SAVE'); ?>
            </button>
            <a class="btn btn-outline-secondary" href="/admin/settings?tab=mail&subtab=templates">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-xmark"></i></span>
                <?php echo Lang::get('FFCMS_CLOSE'); ?>
            </a>
        </div>
    </div>

    <div class="zulu-panel">
        <h1 class="mb-4"><?php echo Lang::get('FFCMS_MAIL_TEMPLATES'); ?></h1>

        <?php if (!empty($errors['csrf'])) : ?>
            <div class="alert alert-danger" role="alert"><?php echo Template::escape($errors['csrf']); ?></div>
        <?php endif; ?>

        <form id="mail-template-form" method="post" data-ff-freewrite-form="1">
            <?php echo Csrf::input(); ?>
            <div class="row g-3">
                <div class="col-lg-6">
                    <div class="mb-3">
                        <label class="form-label" for="name"><?php echo Lang::get('FFCMS_NAME'); ?></label>
                        <input class="form-control" type="text" id="name" name="name" value="<?php echo Template::escape($values['name'] ?? ''); ?>" required>
                        <?php if (!empty($errors['name'])) : ?>
                            <div class="text-danger"><?php echo Template::escape($errors['name']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="template_key"><?php echo Lang::get('FFCMS_TEMPLATE_KEY'); ?></label>
                        <input class="form-control" type="text" id="template_key" name="template_key" value="<?php echo Template::escape($values['template_key'] ?? ''); ?>" <?php echo $isEdit ? 'readonly' : ''; ?> required>
                        <?php if (!empty($errors['template_key'])) : ?>
                            <div class="text-danger"><?php echo Template::escape($errors['template_key']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="language_tag"><?php echo Lang::get('FFCMS_LANGUAGE'); ?></label>
                        <input class="form-control" type="text" id="language_tag" name="language_tag" value="<?php echo Template::escape($values['language_tag'] ?? 'en-GB'); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="subject"><?php echo Lang::get('FFCMS_SUBJECT'); ?></label>
                        <input class="form-control" type="text" id="subject" name="subject" value="<?php echo Template::escape($values['subject'] ?? ''); ?>" required>
                        <?php if (!empty($errors['subject'])) : ?>
                            <div class="text-danger"><?php echo Template::escape($errors['subject']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo Lang::get('FFCMS_CONTENT'); ?></label>
                        <?php
                        $content_xml = $values['body_doc_xml'] ?? '';
                        $field_name = 'body_doc_xml';
                        $can_html = true;
                        $can_embed = false;
                        require __DIR__ . '/partials/freewrite.php';
                        ?>
                        <?php if (!empty($errors['body_doc_xml'])) : ?>
                            <div class="text-danger"><?php echo Template::escape($errors['body_doc_xml']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="is_enabled" name="is_enabled" value="1" <?php echo !empty($values['is_enabled']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_enabled"><?php echo Lang::get('FFCMS_ENABLED'); ?></label>
                    </div>
                    <?php if (!$isEdit || empty($values['is_core'])) : ?>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="is_core" name="is_core" value="1">
                            <label class="form-check-label" for="is_core"><?php echo Lang::get('FFCMS_MARK_CORE'); ?></label>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-lg-6">
                    <div class="zulu-panel">
                        <h2 class="h5 mb-3"><?php echo Lang::get('FFCMS_PREVIEW'); ?></h2>
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="template-preview-html-tab" data-bs-toggle="tab" data-bs-target="#template-preview-html" type="button" role="tab" aria-controls="template-preview-html" aria-selected="true"><?php echo Lang::get('FFCMS_RENDERED_HTML'); ?></button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="template-preview-text-tab" data-bs-toggle="tab" data-bs-target="#template-preview-text" type="button" role="tab" aria-controls="template-preview-text" aria-selected="false"><?php echo Lang::get('FFCMS_RENDERED_TEXT'); ?></button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="template-preview-wrapped-tab" data-bs-toggle="tab" data-bs-target="#template-preview-wrapped" type="button" role="tab" aria-controls="template-preview-wrapped" aria-selected="false"><?php echo Lang::get('FFCMS_WRAPPED_PREVIEW'); ?></button>
                            </li>
                        </ul>
                        <div class="tab-content border border-top-0 p-3">
                            <div class="tab-pane fade show active" id="template-preview-html" role="tabpanel" aria-labelledby="template-preview-html-tab">
                                <div class="ff-mail-preview-html"><?php echo $preview['html'] ?? ''; ?></div>
                            </div>
                            <div class="tab-pane fade" id="template-preview-text" role="tabpanel" aria-labelledby="template-preview-text-tab">
                                <pre class="ff-mail-preview-text"><?php echo Template::escape($preview['text'] ?? ''); ?></pre>
                            </div>
                            <div class="tab-pane fade" id="template-preview-wrapped" role="tabpanel" aria-labelledby="template-preview-wrapped-tab">
                                <div class="ff-mail-preview-html"><?php echo $preview['wrapped_html'] ?? ''; ?></div>
                                <pre class="ff-mail-preview-text mt-3"><?php echo Template::escape($preview['wrapped_text'] ?? ''); ?></pre>
                            </div>
                        </div>
                    </div>
                </div>
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
