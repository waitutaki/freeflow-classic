<?php
use Core\Lang;
use Core\Template;

$developerCount = (int)($developer_count ?? 0);
$packageCount = (int)($package_count ?? 0);
$publishedCount = (int)($published_count ?? 0);
$audit = $audit_log ?? [];
$devstore_tab = 'dashboard';
?>
<div class="container-fluid">
    <h1 class="mb-4"><?php echo Lang::get('DEVSTORE.DASHBOARD_TITLE'); ?></h1>

    <?php require __DIR__ . '/partials/devstore_tabs.php'; ?>

    <div class="row g-3 mb-4 row-cols-1 row-cols-md-3">
        <div class="col">
            <div class="zulu-panel h-100">
                <div class="h5 mb-2"><?php echo Lang::get('DEVSTORE.DEVELOPERS_COUNT'); ?></div>
                <div class="display-6"><?php echo $developerCount; ?></div>
            </div>
        </div>
        <div class="col">
            <div class="zulu-panel h-100">
                <div class="h5 mb-2"><?php echo Lang::get('DEVSTORE.PACKAGES_COUNT'); ?></div>
                <div class="display-6"><?php echo $packageCount; ?></div>
            </div>
        </div>
        <div class="col">
            <div class="zulu-panel h-100">
                <div class="h5 mb-2"><?php echo Lang::get('DEVSTORE.REPOSITORY_TITLE'); ?></div>
                <div class="display-6"><?php echo $publishedCount; ?></div>
            </div>
        </div>
    </div>

    <div class="zulu-panel">
        <h2 class="h5 mb-3"><?php echo Lang::get('DEVSTORE.AUDIT_LOG'); ?></h2>
        <table class="table">
            <thead>
                <tr>
                    <th><?php echo Lang::get('FFCMS_ACTIONS'); ?></th>
                    <th><?php echo Lang::get('FFCMS_USER'); ?></th>
                    <th><?php echo Lang::get('FFCMS_UPDATED'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($audit as $row) : ?>
                    <tr>
                        <td><?php echo Template::escape($row['action'] ?? ''); ?></td>
                        <td><?php echo Template::escape((string)($row['actor_user_id'] ?? '')); ?></td>
                        <td><?php echo Template::escape($row['created_at'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
