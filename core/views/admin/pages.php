<?php
use Core\Lang;
use Core\Template;

$pages = $pages ?? [];
$canPublish = $can_publish ?? false;
$canDelete = $can_delete ?? false;
$canManageAll = $can_manage_all ?? false;
$currentUserId = (int)($current_user_id ?? 0);
?>
<div class="container-fluid">
    <h1 class="mb-4"><?php echo Lang::get('FFCMS_PAGES'); ?></h1>
    <div class="zulu-toolbar-row">
        <div class="btn-group">
            <a class="btn btn-primary" href="/admin/content/pages/new">
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
                    <th><?php echo Lang::get('FFCMS_TITLE'); ?></th>
                    <th><?php echo Lang::get('FFCMS_SLUG'); ?></th>
                    <th><?php echo Lang::get('FFCMS_STATUS'); ?></th>
                    <th><?php echo Lang::get('FFCMS_UPDATED'); ?></th>
                    <th><?php echo Lang::get('FFCMS_ACTIONS'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pages as $page) : ?>
                    <?php
                    $status = $page['status'] ?? '';
                    $isPublished = $status === 'published';
                    $statusLabel = $isPublished ? Lang::get('FFCMS_STATUS_PUBLISHED') : Lang::get('FFCMS_STATUS_UNPUBLISHED');
                    $statusClass = $isPublished ? 'text-success' : 'text-danger';
                    $dateValue = $page['modified_at'] ?? $page['created_at'] ?? '';
                    ?>
                    <tr>
                        <td><?php echo Template::escape($page['title'] ?? ''); ?></td>
                        <td><?php echo Template::escape($page['slug'] ?? ''); ?></td>
                        <td>
                            <span class="<?php echo $statusClass; ?>" aria-label="<?php echo Template::escape($statusLabel); ?>">
                                <i class="fa-solid <?php echo $isPublished ? 'fa-circle-check' : 'fa-circle-xmark'; ?>" aria-hidden="true"></i>
                                <span class="visually-hidden"><?php echo Template::escape($statusLabel); ?></span>
                            </span>
                        </td>
                        <td><?php echo Template::escape((string)$dateValue); ?></td>
                        <td>
                            <div class="ff-action-group">
                                <a class="ff-action-icon" href="/admin/content/pages/<?php echo (int)$page['id']; ?>/edit" aria-label="<?php echo Template::escape(Lang::get('FFCMS_EDIT')); ?>">
                                    <span aria-hidden="true"><i class="fa-solid fa-pen"></i></span>
                                </a>
                                <?php if ($canPublish && ($canManageAll || (int)($page['created_by'] ?? 0) === $currentUserId)) : ?>
                                    <form method="post" action="/admin/content/pages/<?php echo (int)$page['id']; ?>/toggle-publish" class="d-inline">
                                        <?php echo \Core\Csrf::input(); ?>
                                        <button class="ff-action-icon" type="submit" aria-label="<?php echo Template::escape(Lang::get('FFCMS_TOGGLE_PUBLISH')); ?>">
                                            <span aria-hidden="true"><i class="fa-solid <?php echo $isPublished ? 'fa-circle-xmark' : 'fa-circle-check'; ?>"></i></span>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($canDelete && ($canManageAll || (int)($page['created_by'] ?? 0) === $currentUserId)) : ?>
                                    <a class="ff-action-icon" href="/admin/content/pages/<?php echo (int)$page['id']; ?>/delete" aria-label="<?php echo Template::escape(Lang::get('FFCMS_DELETE')); ?>">
                                        <span aria-hidden="true"><i class="fa-solid fa-trash"></i></span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
