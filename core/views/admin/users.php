<?php
use Core\Lang;
use Core\Template;

$users = $users ?? [];
?>
<div class="container-fluid">
    <h1 class="mb-4"><?php echo Lang::get('FFCMS_USERS'); ?></h1>
    <div class="zulu-toolbar-row">
        <div class="btn-group">
            <a class="btn btn-primary" href="/admin/users/new">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-plus"></i></span>
                <?php echo Lang::get('FFCMS_NEW'); ?>
            </a>
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
                    <th><?php echo Lang::get('FFCMS_USERNAME'); ?></th>
                    <th><?php echo Lang::get('FFCMS_EMAIL'); ?></th>
                    <th><?php echo Lang::get('FFCMS_ROLE'); ?></th>
                    <th><?php echo Lang::get('FFCMS_ENABLED'); ?></th>
                    <th><?php echo Lang::get('FFCMS_ACTIONS'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user) : ?>
                    <tr>
                        <td><?php echo Template::escape($user['username'] ?? ''); ?></td>
                        <td><?php echo Template::escape($user['email'] ?? ''); ?></td>
                        <td><?php echo Template::escape((string)($user['role_title'] ?? $user['role_id'] ?? '')); ?></td>
                        <td><?php echo !empty($user['is_enabled']) ? Lang::get('FFCMS_YES') : Lang::get('FFCMS_NO'); ?></td>
                        <td>
                            <div class="ff-action-group">
                                <a class="ff-action-icon" href="/admin/users/edit/<?php echo (int)$user['id']; ?>" aria-label="<?php echo Template::escape(Lang::get('FFCMS_EDIT')); ?>">
                                    <span aria-hidden="true"><i class="fa-solid fa-pen"></i></span>
                                </a>
                                <a class="ff-action-icon" href="/admin/users/delete/<?php echo (int)$user['id']; ?>" aria-label="<?php echo Template::escape(Lang::get('FFCMS_DELETE')); ?>">
                                    <span aria-hidden="true"><i class="fa-solid fa-trash"></i></span>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
