<?php
use Core\Lang;
use Core\Csrf;

$error = $error ?? '';
?>
<div class="container">
    <h1 class="mb-4"><?php echo Lang::get('DEVSTORE.PORTAL_UPLOAD_TITLE'); ?></h1>

    <?php if ($error !== '') : ?>
        <div class="alert alert-danger" role="alert">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="zulu-panel">
        <?php echo Csrf::input(); ?>
        <div class="mb-3">
            <label class="form-label" for="package"><?php echo Lang::get('DEVSTORE.PACKAGE_FILE'); ?></label>
            <input class="form-control" type="file" id="package" name="package" accept=".zip" required>
        </div>
        <div class="alert alert-warning" role="alert">
            <?php echo Lang::get('DEVSTORE.IMMUTABILITY_NOTICE'); ?>
        </div>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="acknowledge_immutability" name="acknowledge_immutability" value="1" required>
            <label class="form-check-label" for="acknowledge_immutability">
                <?php echo Lang::get('DEVSTORE.IMMUTABILITY_CONFIRM'); ?>
            </label>
        </div>
        <button class="btn btn-primary" type="submit">
            <span class="me-1" aria-hidden="true"><i class="fa-solid fa-plus"></i></span>
            <?php echo Lang::get('DEVSTORE.SUBMIT_PACKAGE'); ?>
        </button>
    </form>
</div>
