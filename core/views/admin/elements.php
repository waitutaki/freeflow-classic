<?php
use Core\Lang;
use Core\Template;

$elements = $elements ?? [];
$assignments = $assignments ?? [];
$assignment = $assignment ?? null;
$context = $context ?? 'site';

$assignmentMap = [];
foreach ($assignments as $row) {
    $label = Lang::get('FFCMS_GLOBAL');
    if (($row['target_type'] ?? '') === 'route') {
        $label = Lang::get('FFCMS_ROUTE') . ': ' . ($row['target_value'] ?? '');
    } elseif (($row['target_type'] ?? '') === 'route_prefix') {
        $label = Lang::get('FFCMS_ROUTE_PREFIX') . ': ' . ($row['target_value'] ?? '');
    }
    $assignmentMap[(int)$row['id']] = $label;
}
?>
<div class="container-fluid">
    <h1 class="mb-4"><?php echo Lang::get('FFCMS_ELEMENTS'); ?></h1>
    <div class="zulu-toolbar-row">
        <div class="btn-group">
            <a class="btn btn-primary" href="/admin/elements/new?context=<?php echo Template::escape($context); ?>">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-plus"></i></span>
                <?php echo Lang::get('FFCMS_NEW'); ?>
            </a>
            <a class="btn btn-outline-secondary" href="/admin/elements/builder?context=<?php echo Template::escape($context); ?>">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-layer-group"></i></span>
                <?php echo Lang::get('FFCMS_BUILDER'); ?>
            </a>
            <a class="btn btn-outline-secondary" href="/admin/elements/assignments?context=<?php echo Template::escape($context); ?>">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-map"></i></span>
                <?php echo Lang::get('FFCMS_ASSIGNMENTS'); ?>
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
            <div class="col-sm-5">
                <label class="form-label" for="assignment_id"><?php echo Lang::get('FFCMS_ASSIGNMENT'); ?></label>
                <select class="form-select" id="assignment_id" name="assignment_id">
                    <?php foreach ($assignments as $row) : ?>
                        <option value="<?php echo (int)$row['id']; ?>" <?php echo $assignment && (int)$assignment['id'] === (int)$row['id'] ? 'selected' : ''; ?>>
                            <?php echo Template::escape($assignmentMap[(int)$row['id']] ?? Lang::get('FFCMS_GLOBAL')); ?>
                        </option>
                    <?php endforeach; ?>
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
                    <th><?php echo Lang::get('FFCMS_TITLE'); ?></th>
                    <th><?php echo Lang::get('FFCMS_TYPE'); ?></th>
                    <th><?php echo Lang::get('FFCMS_POSITION'); ?></th>
                    <th><?php echo Lang::get('FFCMS_ASSIGNMENT'); ?></th>
                    <th><?php echo Lang::get('FFCMS_STATUS'); ?></th>
                    <th><?php echo Lang::get('FFCMS_ORDERING'); ?></th>
                    <th><?php echo Lang::get('FFCMS_ACTIONS'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($elements as $element) : ?>
                    <?php
                    $isPublished = !empty($element['is_published']);
                    $statusLabel = $isPublished ? Lang::get('FFCMS_ENABLED') : Lang::get('FFCMS_DISABLED');
                    $statusClass = $isPublished ? 'text-success' : 'text-danger';
                    $assignmentLabel = $assignmentMap[(int)($element['assignment_id'] ?? 0)] ?? Lang::get('FFCMS_GLOBAL');
                    ?>
                    <tr>
                        <td><?php echo Template::escape($element['title'] ?? ''); ?></td>
                        <td><?php echo Template::escape($element['type_name'] ?? ''); ?></td>
                        <td><?php echo Template::escape($element['position_name'] ?? ''); ?></td>
                        <td><?php echo Template::escape($assignmentLabel); ?></td>
                        <td>
                            <span class="<?php echo $statusClass; ?>" aria-label="<?php echo Template::escape($statusLabel); ?>">
                                <i class="fa-solid <?php echo $isPublished ? 'fa-circle-check' : 'fa-circle-xmark'; ?>" aria-hidden="true"></i>
                                <span class="visually-hidden"><?php echo Template::escape($statusLabel); ?></span>
                            </span>
                        </td>
                        <td><?php echo (int)($element['ordering'] ?? 0); ?></td>
                        <td>
                            <div class="ff-action-group">
                                <a class="ff-action-icon" href="/admin/elements/<?php echo (int)$element['id']; ?>/edit" aria-label="<?php echo Template::escape(Lang::get('FFCMS_EDIT')); ?>">
                                    <span aria-hidden="true"><i class="fa-solid fa-pen"></i></span>
                                </a>
                                <a class="ff-action-icon" href="/admin/elements/<?php echo (int)$element['id']; ?>/delete" aria-label="<?php echo Template::escape(Lang::get('FFCMS_DELETE')); ?>">
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
