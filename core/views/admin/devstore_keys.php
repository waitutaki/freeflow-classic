<?php
use Core\Lang;
use Core\Template;
use Core\Csrf;

$status = $status ?? [];
$message = $message ?? '';
$devstore_tab = 'keys';
$recognized = !empty($status['fingerprint_match']);
$privateExists = !empty($status['private_exists']);
$publicExists = !empty($status['public_exists']);
?>
<div class="container-fluid">
    <h1 class="mb-4"><?php echo Lang::get('DEVSTORE.KEYS_TITLE'); ?></h1>

    <?php require __DIR__ . '/partials/devstore_tabs.php'; ?>

    <?php if ($message !== '') : ?>
        <div class="alert alert-info" role="alert"><?php echo Template::escape($message); ?></div>
    <?php endif; ?>

    <div class="alert alert-warning" role="alert">
        <?php echo Lang::get('DEVSTORE.KEYS_WARNING'); ?>
    </div>

    <div class="zulu-toolbar-row">
        <div class="btn-group">
            <button class="btn btn-primary" form="devstore-keys-form" type="submit" name="generate_keys" value="1">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-key"></i></span>
                <?php echo Lang::get('DEVSTORE.KEYS_GENERATE'); ?>
            </button>
        </div>
    </div>

    <form id="devstore-keys-form" method="post" class="zulu-panel">
        <?php echo Csrf::input(); ?>
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="mb-3">
                    <strong><?php echo Lang::get('DEVSTORE.KEYS_PRIVATE_KEY'); ?>:</strong>
                    <?php echo $privateExists ? Lang::get('DEVSTORE.KEYS_PRESENT') : Lang::get('DEVSTORE.KEYS_MISSING'); ?>
                </div>
                <div class="mb-3">
                    <strong><?php echo Lang::get('DEVSTORE.KEYS_PUBLIC_KEY'); ?>:</strong>
                    <?php echo $publicExists ? Lang::get('DEVSTORE.KEYS_PRESENT') : Lang::get('DEVSTORE.KEYS_MISSING'); ?>
                </div>
                <div class="mb-3">
                    <strong><?php echo Lang::get('DEVSTORE.KEYS_FINGERPRINT'); ?>:</strong>
                    <div class="text-muted"><?php echo Template::escape($status['fingerprint'] ?? ''); ?></div>
                </div>
                <div class="mb-3">
                    <strong><?php echo Lang::get('DEVSTORE.KEYS_TRUSTED_FINGERPRINT'); ?>:</strong>
                    <div class="text-muted"><?php echo Template::escape($status['trusted_fingerprint'] ?? ''); ?></div>
                </div>
                <div class="mb-3">
                    <strong><?php echo Lang::get('DEVSTORE.KEYS_STATUS'); ?>:</strong>
                    <?php if ($recognized) : ?>
                        <span class="badge bg-success"><?php echo Lang::get('DEVSTORE.KEYS_MATCHED'); ?></span>
                    <?php else : ?>
                        <span class="badge bg-danger"><?php echo Lang::get('DEVSTORE.KEYS_MISMATCH'); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </form>
</div>
