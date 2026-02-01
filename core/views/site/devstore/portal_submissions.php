<?php
use Core\Lang;
use Core\Template;

$packages = $packages ?? [];
?>
<div class="container">
    <h1 class="mb-4"><?php echo Lang::get('DEVSTORE.PORTAL_SUBMISSIONS_TITLE'); ?></h1>

    <div class="zulu-panel">
        <table class="table">
            <thead>
                <tr>
                    <th><?php echo Lang::get('FFCMS_NAME'); ?></th>
                    <th><?php echo Lang::get('FFCMS_VERSION'); ?></th>
                    <th><?php echo Lang::get('FFCMS_STATUS'); ?></th>
                    <th><?php echo Lang::get('FFCMS_UPDATED'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($packages as $package) : ?>
                    <tr>
                        <td><?php echo Template::escape($package['name'] ?? ''); ?></td>
                        <td><?php echo Template::escape($package['version'] ?? ''); ?></td>
                        <td><?php echo Template::escape($package['status'] ?? ''); ?></td>
                        <td><?php echo Template::escape($package['updated_at'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
