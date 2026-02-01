<?php
use Core\Lang;
use Core\Template;
use Core\Csrf;
$devstore_tab = 'developers';
?>
<div class="container-fluid">
    <h1 class="mb-4"><?php echo Lang::get('DEVSTORE.DEVELOPERS_TITLE'); ?></h1>

    <?php require __DIR__ . '/partials/devstore_tabs.php'; ?>

    <div class="zulu-panel">
        <table class="table">
            <thead>
                <tr>
                    <th><?php echo Lang::get('FFCMS_USER'); ?></th>
                    <th><?php echo Lang::get('FFCMS_EMAIL'); ?></th>
                    <th><?php echo Lang::get('FFCMS_STATUS'); ?></th>
                    <th><?php echo Lang::get('FFCMS_UPDATED'); ?></th>
                    <th><?php echo Lang::get('FFCMS_ACTIONS'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($developers ?? []) as $developer) : ?>
                    <?php
                    $status = (string)($developer['status'] ?? 'pending');
                    $statusIcon = 'fa-circle-xmark';
                    $statusClass = 'ff-icon-muted';
                    if ($status === 'approved') {
                        $statusIcon = 'fa-circle-check';
                        $statusClass = 'ff-icon-green';
                    } elseif (in_array($status, ['suspended', 'rejected'], true)) {
                        $statusIcon = 'fa-circle-xmark';
                        $statusClass = 'ff-icon-red';
                    }
                    ?>
                    <tr>
                        <td><?php echo Template::escape($developer['username'] ?? ''); ?></td>
                        <td><?php echo Template::escape($developer['email'] ?? ''); ?></td>
                        <td>
                            <span class="<?php echo $statusClass; ?>" aria-label="<?php echo Template::escape($status); ?>">
                                <i class="fa-solid <?php echo $statusIcon; ?>" aria-hidden="true"></i>
                                <span class="visually-hidden"><?php echo Template::escape($status); ?></span>
                            </span>
                        </td>
                        <td><?php echo Template::escape($developer['updated_at'] ?? ''); ?></td>
                        <td>
                            <div class="ff-action-group">
                                <?php if (($developer['status'] ?? '') !== 'approved') : ?>
                                    <form method="post" action="/admin/devstore/developers/<?php echo (int)$developer['id']; ?>/approve">
                                        <?php echo Csrf::input(); ?>
                                        <button class="ff-action-icon" type="submit" aria-label="<?php echo Template::escape(Lang::get('DEVSTORE.APPROVE')); ?>">
                                            <span aria-hidden="true"><i class="fa-solid fa-check"></i></span>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <?php if (($developer['status'] ?? '') === 'pending') : ?>
                                    <form method="post" action="/admin/devstore/developers/<?php echo (int)$developer['id']; ?>/reject">
                                        <?php echo Csrf::input(); ?>
                                        <button class="ff-action-icon" type="submit" aria-label="<?php echo Template::escape(Lang::get('DEVSTORE.REJECT')); ?>">
                                            <span aria-hidden="true"><i class="fa-solid fa-xmark"></i></span>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <?php if (($developer['status'] ?? '') !== 'suspended') : ?>
                                    <form method="post" action="/admin/devstore/developers/<?php echo (int)$developer['id']; ?>/suspend">
                                        <?php echo Csrf::input(); ?>
                                        <button class="ff-action-icon" type="submit" aria-label="<?php echo Template::escape(Lang::get('DEVSTORE.SUSPEND')); ?>">
                                            <span aria-hidden="true"><i class="fa-solid fa-ban"></i></span>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
