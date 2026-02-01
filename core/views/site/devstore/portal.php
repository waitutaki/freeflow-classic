<?php
use Core\Lang;
use Core\Template;
use Core\Csrf;
use Core\Auth;

$developer = $developer ?? null;
$disabled = !empty($disabled);
?>
<div class="container">
    <h1 class="mb-4"><?php echo Lang::get('DEVSTORE.PORTAL_TITLE'); ?></h1>

    <?php if ($disabled) : ?>
        <div class="alert alert-warning" role="alert">
            <?php echo Lang::get('DEVSTORE.DISABLED_MESSAGE'); ?>
        </div>
    <?php elseif (!Auth::isAuthenticated()) : ?>
        <p><?php echo Lang::get('DEVSTORE.PORTAL_LOGIN'); ?></p>
    <?php else : ?>
        <?php if (!$developer) : ?>
            <p><?php echo Lang::get('DEVSTORE.PORTAL_REQUEST'); ?></p>
            <form method="post" action="/devstore/register">
                <?php echo Csrf::input(); ?>
                <button class="btn btn-primary" type="submit">
                    <span class="me-1" aria-hidden="true"><i class="fa-solid fa-plus"></i></span>
                    <?php echo Lang::get('DEVSTORE.REQUEST_ACCESS'); ?>
                </button>
            </form>
        <?php elseif (($developer['status'] ?? '') !== 'approved') : ?>
            <div class="alert alert-info" role="alert">
                <?php echo Lang::get('DEVSTORE.PENDING_MESSAGE'); ?>
            </div>
        <?php else : ?>
            <div class="list-group">
                <a class="list-group-item list-group-item-action" href="/devstore/upload">
                    <span class="me-2" aria-hidden="true"><i class="fa-solid fa-plus"></i></span>
                    <?php echo Lang::get('DEVSTORE.PORTAL_UPLOAD_TITLE'); ?>
                </a>
                <a class="list-group-item list-group-item-action" href="/devstore/my-submissions">
                    <span class="me-2" aria-hidden="true"><i class="fa-solid fa-eye"></i></span>
                    <?php echo Lang::get('DEVSTORE.PORTAL_SUBMISSIONS_TITLE'); ?>
                </a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
