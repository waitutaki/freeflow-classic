<?php
use Core\Lang;
use Core\Template;
use Core\Csrf;

$sections = $sections ?? [];
?>
<div class="container-fluid">
    <h1 class="mb-4"><?php echo Lang::get('FFCMS_DOCS_BUILDER'); ?></h1>

    <div class="zulu-toolbar-row">
        <div class="btn-group">
            <form method="post" action="/admin/docs/build">
                <?php echo Csrf::input(); ?>
                <button class="btn btn-primary" type="submit">
                    <span class="me-1" aria-hidden="true"><i class="fa-solid fa-hammer"></i></span>
                    <?php echo Lang::get('FFCMS_BUILD_DOCS'); ?>
                </button>
            </form>
            <a class="btn btn-outline-secondary" href="/admin/">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-xmark"></i></span>
                <?php echo Lang::get('FFCMS_CLOSE'); ?>
            </a>
        </div>
    </div>

    <div class="zulu-panel">
        <table class="table">
            <thead>
                <tr>
                    <th><?php echo Lang::get('FFCMS_SECTION'); ?></th>
                    <th><?php echo Lang::get('FFCMS_COUNT'); ?></th>
                    <th><?php echo Lang::get('FFCMS_LAST_BUILD'); ?></th>
                    <th><?php echo Lang::get('FFCMS_STATUS'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sections as $section => $info) : ?>
                    <tr>
                        <td><?php echo Template::escape(ucfirst($section)); ?></td>
                        <td><?php echo Template::escape((string)($info['count'] ?? 0)); ?></td>
                        <td><?php echo Template::escape((string)($info['last_build'] ?? '')); ?></td>
                        <td><?php echo Template::escape((string)($info['last_result'] ?? '')); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
