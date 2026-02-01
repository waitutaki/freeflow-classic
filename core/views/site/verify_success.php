<?php
use Core\Lang;
?>
<div class="alpha-content">
    <h1 class="alpha-title"><?php echo Lang::get('FFCMS_VERIFY_EMAIL'); ?></h1>
    <p><?php echo Lang::get('FFCMS_VERIFY_SUCCESS'); ?></p>
    <a class="btn btn-primary" href="/login">
        <span class="me-2" aria-hidden="true"><i class="fa-solid fa-right-to-bracket"></i></span>
        <?php echo Lang::get('FFCMS_LOGIN'); ?>
    </a>
</div>
