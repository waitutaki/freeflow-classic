<?php
use Core\Lang;
use Core\Template;
?>
<div class="container-fluid">
    <div class="zulu-panel zulu-login-panel">
        <h1 class="mb-4"><?php echo Lang::get('FFCMS_ADMIN_LOGIN'); ?></h1>

        <?php if (!empty($error)) : ?>
            <div class="alert alert-danger" role="alert"><?php echo Template::escape($error); ?></div>
        <?php endif; ?>

        <form method="post">
            <?php echo \Core\Csrf::input(); ?>
            <?php echo \Core\AntiSpam::renderFields('login'); ?>
            <div class="mb-3">
                <label class="form-label" for="identifier"><?php echo Lang::get('FFCMS_LOGIN_IDENTIFIER'); ?></label>
                <input class="form-control" type="text" id="identifier" name="identifier" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="password"><?php echo Lang::get('FFCMS_PASSWORD'); ?></label>
                <input class="form-control" type="password" id="password" name="password" required>
            </div>
            <button class="btn btn-primary" type="submit">
                <span class="me-2" aria-hidden="true"><i class="fa-solid fa-right-to-bracket"></i></span>
                <?php echo Lang::get('FFCMS_LOGIN'); ?>
            </button>
        </form>
    </div>
</div>
