<?php
use Core\Lang;
use Core\Template;
use Core\Csrf;
$devstore_tab = 'packages';
$subtab = $subtab ?? 'list';
?>
<div class="container-fluid">
    <h1 class="mb-4"><?php echo Lang::get('DEVSTORE.PACKAGES_TITLE'); ?></h1>

    <?php require __DIR__ . '/partials/devstore_tabs.php'; ?>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link <?php echo $subtab === 'list' ? 'active' : ''; ?>" href="/admin/devstore/packages?subtab=list"><?php echo Lang::get('FFCMS_LIST'); ?></a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $subtab === 'upload' ? 'active' : ''; ?>" href="/admin/devstore/packages?subtab=upload"><?php echo Lang::get('FFCMS_UPLOAD'); ?></a>
        </li>
    </ul>

    <?php if ($subtab === 'list') : ?>
        <div class="zulu-panel">
            <table class="table">
                <thead>
                    <tr>
                        <th><?php echo Lang::get('FFCMS_NAME'); ?></th>
                        <th><?php echo Lang::get('FFCMS_TYPE'); ?></th>
                        <th><?php echo Lang::get('FFCMS_VERSION'); ?></th>
                        <th><?php echo Lang::get('FFCMS_STATUS'); ?></th>
                        <th><?php echo Lang::get('FFCMS_UPDATED'); ?></th>
                        <th><?php echo Lang::get('FFCMS_ACTIONS'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($packages ?? []) as $package) : ?>
                        <tr>
                            <td><?php echo Template::escape($package['name'] ?? ''); ?></td>
                            <td><?php echo Template::escape($package['package_type'] ?? ''); ?></td>
                            <td><?php echo Template::escape($package['version'] ?? ''); ?></td>
                            <td><?php echo Template::escape($package['status'] ?? ''); ?></td>
                            <td><?php echo Template::escape($package['updated_at'] ?? ''); ?></td>
                            <td>
                                <div class="ff-action-group">
                                    <a class="ff-action-icon" href="/admin/devstore/packages/<?php echo (int)$package['id']; ?>" aria-label="<?php echo Template::escape(Lang::get('FFCMS_VIEW')); ?>">
                                        <span aria-hidden="true"><i class="fa-solid fa-eye"></i></span>
                                    </a>
                                    <?php if (($package['status'] ?? '') !== 'published') : ?>
                                        <form method="post" action="/admin/devstore/packages/<?php echo (int)$package['id']; ?>/publish">
                                            <?php echo Csrf::input(); ?>
                                            <button class="ff-action-icon" type="submit" aria-label="<?php echo Template::escape(Lang::get('DEVSTORE.PUBLISH')); ?>">
                                                <span aria-hidden="true"><i class="fa-solid fa-arrow-up-from-bracket"></i></span>
                                            </button>
                                        </form>
                                    <?php else : ?>
                                        <form method="post" action="/admin/devstore/packages/<?php echo (int)$package['id']; ?>/unpublish">
                                            <?php echo Csrf::input(); ?>
                                            <button class="ff-action-icon" type="submit" aria-label="<?php echo Template::escape(Lang::get('DEVSTORE.UNPUBLISH')); ?>">
                                                <span aria-hidden="true"><i class="fa-solid fa-ban"></i></span>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="post" action="/admin/devstore/packages/<?php echo (int)$package['id']; ?>/delete" onsubmit="return confirm('<?php echo Template::escape(Lang::get('DEVSTORE.DELETE_CONFIRM')); ?>')">
                                        <?php echo Csrf::input(); ?>
                                        <button class="ff-action-icon text-danger" type="submit" aria-label="<?php echo Template::escape(Lang::get('FFCMS_DELETE')); ?>">
                                            <span aria-hidden="true"><i class="fa-solid fa-trash"></i></span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php elseif ($subtab === 'upload') : ?>
        <?php if (!empty($error)) : ?>
            <div class="alert alert-danger" role="alert">
                <?php echo Template::escape($error); ?>
            </div>
        <?php endif; ?>
        <div class="zulu-panel">
            <form method="post" enctype="multipart/form-data" action="/admin/devstore/packages/upload">
                <?php echo Csrf::input(); ?>
                <h2 class="h5 mb-3"><?php echo Lang::get('DEVSTORE.PACKAGE_UPLOAD_TITLE'); ?></h2>
                <div class="mb-3">
                    <label class="form-label" for="package"><?php echo Lang::get('DEVSTORE.PACKAGE_FILE'); ?></label>
                    <input class="form-control" type="file" id="package" name="package" accept=".zip" required>
                    <div class="form-text"><?php echo Lang::get('DEVSTORE.PACKAGE_UPLOAD_HELP'); ?></div>
                </div>
                <button class="btn btn-primary" type="submit">
                    <span class="me-1" aria-hidden="true"><i class="fa-solid fa-upload"></i></span>
                    <?php echo Lang::get('FFCMS_UPLOAD'); ?>
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>
