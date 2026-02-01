<?php
use Core\Lang;
use Core\Template;
use Core\Csrf;
use Core\Auth;

$tab = $tab ?? 'extension';
?>
<div class="container-fluid">
    <h1 class="mb-4"><?php echo Lang::get('FFCMS_INSTALL_PACKAGE'); ?></h1>
    <div class="zulu-toolbar-row">
        <div class="btn-group">
            <button class="btn btn-primary" form="package-install-form" type="submit">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-floppy-disk"></i></span>
                <?php echo Lang::get('FFCMS_INSTALL'); ?>
            </button>
            <a class="btn btn-outline-secondary" href="/admin/extension?tab=<?php echo Template::escape($tab); ?>">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-xmark"></i></span>
                <?php echo Lang::get('FFCMS_CLOSE'); ?>
            </a>
        </div>
    </div>

    <div class="zulu-panel">
        <form id="package-install-form" method="post" enctype="multipart/form-data" action="/admin/extension/install?tab=<?php echo Template::escape($tab); ?>">
            <?php echo Csrf::input(); ?>
            <div class="mb-3">
                <label class="form-label" for="package"><?php echo Lang::get('FFCMS_PACKAGE_FILE'); ?></label>
                <input class="form-control" type="file" id="package" name="package" accept=".zip" required>
            </div>
            <?php if (Auth::isSuper()) : ?>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="safety_override" name="safety_override" value="1">
                    <label class="form-check-label" for="safety_override"><?php echo Lang::get('FFCMS_OVERRIDE_SAFETY'); ?></label>
                </div>
                <div class="form-text"><?php echo Lang::get('FFCMS_OVERRIDE_SAFETY_HELP'); ?></div>
            <?php endif; ?>
        </form>
    </div>
</div>
