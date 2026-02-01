<?php
use Core\Lang;
use Core\Template;

$assignments = $assignments ?? [];
$context = $context ?? 'site';
?>
<div class="container-fluid">
    <h1 class="mb-4"><?php echo Lang::get('FFCMS_ELEMENT_ASSIGNMENTS'); ?></h1>
    <div class="zulu-toolbar-row">
        <div class="btn-group">
            <a class="btn btn-primary" href="/admin/elements/assignments/new?context=<?php echo Template::escape($context); ?>">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-plus"></i></span>
                <?php echo Lang::get('FFCMS_NEW'); ?>
            </a>
            <a class="btn btn-outline-secondary" href="/admin/elements?context=<?php echo Template::escape($context); ?>">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-layer-group"></i></span>
                <?php echo Lang::get('FFCMS_ELEMENTS'); ?>
            </a>
            <a class="btn btn-outline-secondary" href="/admin/">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-xmark"></i></span>
                <?php echo Lang::get('FFCMS_CLOSE'); ?>
            </a>
        </div>
    </div>

    <div class="zulu-panel mb-3">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-sm-3">
                <label class="form-label" for="context"><?php echo Lang::get('FFCMS_CONTEXT'); ?></label>
                <select class="form-select" id="context" name="context">
                    <option value="site" <?php echo $context === 'site' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_CONTEXT_SITE'); ?></option>
                    <option value="admin" <?php echo $context === 'admin' ? 'selected' : ''; ?>><?php echo Lang::get('FFCMS_CONTEXT_ADMIN'); ?></option>
                </select>
            </div>
            <div class="col-sm-2">
                <button class="btn btn-outline-secondary w-100" type="submit">
                    <span class="me-1" aria-hidden="true"><i class="fa-solid fa-filter"></i></span>
                    <?php echo Lang::get('FFCMS_APPLY'); ?>
                </button>
            </div>
        </form>
    </div>

    <div class="zulu-panel">
        <table class="table">
            <thead>
                <tr>
                    <th><?php echo Lang::get('FFCMS_CONTEXT'); ?></th>
                    <th><?php echo Lang::get('FFCMS_TARGET_TYPE'); ?></th>
                    <th><?php echo Lang::get('FFCMS_TARGET_VALUE'); ?></th>
                    <th><?php echo Lang::get('FFCMS_STATUS'); ?></th>
                    <th><?php echo Lang::get('FFCMS_ACTIONS'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($assignments as $assignment) : ?>
                    <?php
                    $status = !empty($assignment['is_enabled']);
                    $statusLabel = $status ? Lang::get('FFCMS_ENABLED') : Lang::get('FFCMS_DISABLED');
                    $statusClass = $status ? 'text-success' : 'text-danger';
                    ?>
                    <tr>
                        <td><?php echo Template::escape($assignment['context'] ?? ''); ?></td>
                        <td><?php echo Template::escape($assignment['target_type'] ?? ''); ?></td>
                        <td><?php echo Template::escape($assignment['target_value'] ?? ''); ?></td>
                        <td>
                            <span class="<?php echo $statusClass; ?>" aria-label="<?php echo Template::escape($statusLabel); ?>">
                                <i class="fa-solid <?php echo $status ? 'fa-circle-check' : 'fa-circle-xmark'; ?>" aria-hidden="true"></i>
                                <span class="visually-hidden"><?php echo Template::escape($statusLabel); ?></span>
                            </span>
                        </td>
                        <td>
                            <div class="ff-action-group">
                                <a class="ff-action-icon" href="/admin/elements/assignments/<?php echo (int)$assignment['id']; ?>/edit" aria-label="<?php echo Template::escape(Lang::get('FFCMS_EDIT')); ?>">
                                    <span aria-hidden="true"><i class="fa-solid fa-pen"></i></span>
                                </a>
                                <a class="ff-action-icon" href="/admin/elements/assignments/<?php echo (int)$assignment['id']; ?>/delete" aria-label="<?php echo Template::escape(Lang::get('FFCMS_DELETE')); ?>">
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
