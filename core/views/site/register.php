<?php
use Core\Lang;
use Core\Template;

$values = $values ?? [];
$errors = $errors ?? [];
?>
<div class="alpha-content">
    <h1 class="alpha-title"><?php echo Lang::get('FFCMS_REGISTER'); ?></h1>

    <form method="post" class="mt-3" id="ffcms-register-form">
        <?php echo \Core\Csrf::input(); ?>
        <?php if (!empty($errors['csrf'])) : ?>
            <div class="alert alert-danger" role="alert"><?php echo Template::escape($errors['csrf']); ?></div>
        <?php endif; ?>
        <?php if (!empty($errors['spam'])) : ?>
            <div class="alert alert-danger" role="alert"><?php echo Template::escape($errors['spam']); ?></div>
        <?php endif; ?>
        <?php echo \Core\AntiSpam::renderFields('registration'); ?>
        <div class="mb-3">
            <label class="form-label" for="name_first"><?php echo Lang::get('FFCMS_FIRST_NAME'); ?></label>
            <input class="form-control" type="text" id="name_first" name="name_first" value="<?php echo Template::escape($values['name_first'] ?? ''); ?>" required>
            <div class="form-text"><?php echo Lang::get('FFCMS_FIRST_NAME_HELP'); ?></div>
            <?php if (!empty($errors['name_first'])) : ?>
                <div class="text-danger"><?php echo Template::escape($errors['name_first']); ?></div>
            <?php endif; ?>
        </div>
        <div class="mb-3">
            <label class="form-label" for="name_last"><?php echo Lang::get('FFCMS_LAST_NAME'); ?></label>
            <input class="form-control" type="text" id="name_last" name="name_last" value="<?php echo Template::escape($values['name_last'] ?? ''); ?>" required>
            <div class="form-text"><?php echo Lang::get('FFCMS_LAST_NAME_HELP'); ?></div>
            <?php if (!empty($errors['name_last'])) : ?>
                <div class="text-danger"><?php echo Template::escape($errors['name_last']); ?></div>
            <?php endif; ?>
        </div>
        <div class="mb-3">
            <label class="form-label" for="email"><?php echo Lang::get('FFCMS_EMAIL'); ?></label>
            <input class="form-control" type="email" id="email" name="email" value="<?php echo Template::escape($values['email'] ?? ''); ?>" required>
            <div class="form-text"><?php echo Lang::get('FFCMS_EMAIL_HELP'); ?></div>
            <?php if (!empty($errors['email'])) : ?>
                <div class="text-danger"><?php echo Template::escape($errors['email']); ?></div>
            <?php endif; ?>
        </div>
        <div class="mb-3">
            <label class="form-label" for="username"><?php echo Lang::get('FFCMS_USERNAME'); ?></label>
            <input class="form-control" type="text" id="username" name="username" value="<?php echo Template::escape($values['username'] ?? ''); ?>">
            <div class="form-text"><?php echo Lang::get('FFCMS_USERNAME_HELP'); ?></div>
            <div class="form-text" id="ffcms-username-status"></div>
            <?php if (!empty($errors['username'])) : ?>
                <div class="text-danger"><?php echo Template::escape($errors['username']); ?></div>
            <?php endif; ?>
        </div>
        <div class="mb-3">
            <label class="form-label" for="password"><?php echo Lang::get('FFCMS_PASSWORD'); ?></label>
            <input class="form-control" type="password" id="password" name="password" required>
            <div class="form-text"><?php echo Lang::get('FFCMS_PASSWORD_HELP'); ?></div>
            <?php if (!empty($errors['password'])) : ?>
                <div class="text-danger"><?php echo Template::escape($errors['password']); ?></div>
            <?php endif; ?>
        </div>
        <div class="mb-3">
            <label class="form-label" for="password_confirm"><?php echo Lang::get('FFCMS_PASSWORD_CONFIRM'); ?></label>
            <input class="form-control" type="password" id="password_confirm" name="password_confirm" required>
            <div class="form-text"><?php echo Lang::get('FFCMS_PASSWORD_CONFIRM_HELP'); ?></div>
            <?php if (!empty($errors['password_confirm'])) : ?>
                <div class="text-danger"><?php echo Template::escape($errors['password_confirm']); ?></div>
            <?php endif; ?>
        </div>
        <button class="btn btn-primary" type="submit">
            <span class="me-2" aria-hidden="true"><i class="fa-solid fa-user-plus"></i></span>
            <?php echo Lang::get('FFCMS_REGISTER'); ?>
        </button>
    </form>
</div>
