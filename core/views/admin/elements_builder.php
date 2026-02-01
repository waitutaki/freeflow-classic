<?php
use Core\Lang;
use Core\Template;
use Core\Csrf;

$context = $context ?? 'site';
$assignment = $assignment ?? null;
$assignments = $assignments ?? [];
$positions = $positions ?? [];
$elements = $elements ?? [];

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

$elementsByPosition = [];
foreach ($elements as $element) {
    $posId = (int)($element['template_position_id'] ?? 0);
    if (!isset($elementsByPosition[$posId])) {
        $elementsByPosition[$posId] = [];
    }
    $elementsByPosition[$posId][] = $element;
}
?>
<div class="container-fluid">
    <h1 class="mb-4"><?php echo Lang::get('FFCMS_ELEMENTS_BUILDER'); ?></h1>
    <div class="zulu-toolbar-row">
        <div class="btn-group">
            <a class="btn btn-outline-secondary" href="/admin/elements?context=<?php echo Template::escape($context); ?>">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-layer-group"></i></span>
                <?php echo Lang::get('FFCMS_ELEMENTS'); ?>
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
        <?php foreach ($positions as $position) : ?>
            <?php
            $posId = (int)$position['id'];
            $posElements = $elementsByPosition[$posId] ?? [];
            ?>
            <div class="mb-4">
                <h5 class="mb-3"><?php echo Template::escape($position['position'] ?? ''); ?></h5>
                <ul class="list-group">
                    <?php foreach ($posElements as $element) : ?>
                        <?php
                        $isPublished = !empty($element['is_published']);
                        $statusLabel = $isPublished ? Lang::get('FFCMS_ENABLED') : Lang::get('FFCMS_DISABLED');
                        $statusClass = $isPublished ? 'text-success' : 'text-danger';
                        ?>
                        <li class="list-group-item d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <span class="<?php echo $statusClass; ?>" aria-label="<?php echo Template::escape($statusLabel); ?>">
                                    <i class="fa-solid <?php echo $isPublished ? 'fa-circle-check' : 'fa-circle-xmark'; ?>" aria-hidden="true"></i>
                                    <span class="visually-hidden"><?php echo Template::escape($statusLabel); ?></span>
                                </span>
                                <span><?php echo Template::escape($element['title'] ?? ''); ?></span>
                                <span class="text-muted"><?php echo Template::escape($element['type_name'] ?? ''); ?></span>
                            </div>
                            <div class="ff-action-group">
                                <form class="d-inline" method="post" action="/admin/elements/<?php echo (int)$element['id']; ?>/move">
                                    <?php echo Csrf::input(); ?>
                                    <input type="hidden" name="direction" value="up">
                                    <button class="ff-action-icon" type="submit" aria-label="<?php echo Template::escape(Lang::get('FFCMS_MOVE_UP')); ?>">
                                        <span aria-hidden="true"><i class="fa-solid fa-arrow-up"></i></span>
                                    </button>
                                </form>
                                <form class="d-inline" method="post" action="/admin/elements/<?php echo (int)$element['id']; ?>/move">
                                    <?php echo Csrf::input(); ?>
                                    <input type="hidden" name="direction" value="down">
                                    <button class="ff-action-icon" type="submit" aria-label="<?php echo Template::escape(Lang::get('FFCMS_MOVE_DOWN')); ?>">
                                        <span aria-hidden="true"><i class="fa-solid fa-arrow-down"></i></span>
                                    </button>
                                </form>
                                <a class="ff-action-icon" href="/admin/elements/<?php echo (int)$element['id']; ?>/edit" aria-label="<?php echo Template::escape(Lang::get('FFCMS_EDIT')); ?>">
                                    <span aria-hidden="true"><i class="fa-solid fa-pen"></i></span>
                                </a>
                                <a class="ff-action-icon" href="/admin/elements/<?php echo (int)$element['id']; ?>/delete" aria-label="<?php echo Template::escape(Lang::get('FFCMS_DELETE')); ?>">
                                    <span aria-hidden="true"><i class="fa-solid fa-trash"></i></span>
                                </a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                    <?php if (!$posElements) : ?>
                        <li class="list-group-item text-muted"><?php echo Lang::get('FFCMS_NO_ELEMENTS'); ?></li>
                    <?php endif; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </div>
</div>
