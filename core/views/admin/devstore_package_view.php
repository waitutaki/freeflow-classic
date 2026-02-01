<?php
use Core\Lang;
use Core\Template;

$package = $package ?? [];
$report = $report ?? null;
$devstore_tab = 'packages';
?>
<div class="container-fluid">
    <h1 class="mb-4"><?php echo Lang::get('DEVSTORE.PACKAGE_VIEW_TITLE'); ?></h1>

    <?php require __DIR__ . '/partials/devstore_tabs.php'; ?>

    <div class="zulu-panel mb-4">
        <div class="row">
            <div class="col-md-6 mb-3"><strong><?php echo Lang::get('FFCMS_NAME'); ?>:</strong> <?php echo Template::escape($package['name'] ?? ''); ?></div>
            <div class="col-md-6 mb-3"><strong><?php echo Lang::get('FFCMS_TYPE'); ?>:</strong> <?php echo Template::escape($package['package_type'] ?? ''); ?></div>
            <div class="col-md-6 mb-3"><strong><?php echo Lang::get('FFCMS_VERSION'); ?>:</strong> <?php echo Template::escape($package['version'] ?? ''); ?></div>
            <div class="col-md-6 mb-3"><strong><?php echo Lang::get('FFCMS_STATUS'); ?>:</strong> <?php echo Template::escape($package['status'] ?? ''); ?></div>
            <div class="col-12"><strong><?php echo Lang::get('FFCMS_DESCRIPTION'); ?>:</strong> <?php echo Template::escape($package['description'] ?? ''); ?></div>
        </div>
    </div>

    <div class="zulu-panel">
        <h2 class="h5 mb-3"><?php echo Lang::get('DEVSTORE.COMPLIANCE_REPORT'); ?></h2>
        <?php if ($report) : ?>
            <pre class="mb-0"><?php echo Template::escape($report['report_text'] ?? ''); ?></pre>
        <?php else : ?>
            <p class="mb-0"><?php echo Lang::get('DEVSTORE.COMPLIANCE_EMPTY'); ?></p>
        <?php endif; ?>
    </div>
</div>
