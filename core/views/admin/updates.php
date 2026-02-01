<?php
use Core\Lang;
use Core\Template;
use Core\Csrf;

$components = $components ?? [];

function update_status_icon(string $status): array
{
    return match ($status) {
        'up_to_date' => ['fa-circle-check', 'text-success', Lang::get('FFCMS_UP_TO_DATE')],
        'update_available' => ['fa-download', 'text-warning', Lang::get('FFCMS_UPDATE_AVAILABLE')],
        'error' => ['fa-triangle-exclamation', 'text-danger', Lang::get('FFCMS_UPDATE_ERROR')],
        default => ['fa-circle-question', 'text-muted', Lang::get('FFCMS_UPDATE_UNKNOWN')],
    };
}
?>
<div class="container-fluid">
    <h1 class="mb-4"><?php echo Lang::get('FFCMS_UPDATES'); ?></h1>

    <div class="zulu-toolbar-row">
        <div class="btn-group">
            <form method="post" action="/admin/updates/check">
                <?php echo Csrf::input(); ?>
                <button class="btn btn-primary" type="submit">
                    <span class="me-1" aria-hidden="true"><i class="fa-solid fa-rotate"></i></span>
                    <?php echo Lang::get('FFCMS_CHECK_UPDATES'); ?>
                </button>
            </form>
            <form method="post" action="/admin/updates/run-batch/all">
                <?php echo Csrf::input(); ?>
                <button class="btn btn-outline-secondary" type="submit">
                    <span class="me-1" aria-hidden="true"><i class="fa-solid fa-play"></i></span>
                    <?php echo Lang::get('FFCMS_UPDATE_ALL'); ?>
                </button>
            </form>
            <form method="post" action="/admin/updates/run-batch/system">
                <?php echo Csrf::input(); ?>
                <button class="btn btn-outline-secondary" type="submit">
                    <span class="me-1" aria-hidden="true"><i class="fa-solid fa-gear"></i></span>
                    <?php echo Lang::get('FFCMS_UPDATE_SYSTEM'); ?>
                </button>
            </form>
            <form method="post" action="/admin/updates/run-batch/extension">
                <?php echo Csrf::input(); ?>
                <button class="btn btn-outline-secondary" type="submit">
                    <span class="me-1" aria-hidden="true"><i class="fa-solid fa-boxes-stacked"></i></span>
                    <?php echo Lang::get('FFCMS_UPDATE_extension'); ?>
                </button>
            </form>
            <form method="post" action="/admin/updates/run-batch/themes">
                <?php echo Csrf::input(); ?>
                <button class="btn btn-outline-secondary" type="submit">
                    <span class="me-1" aria-hidden="true"><i class="fa-solid fa-paintbrush"></i></span>
                    <?php echo Lang::get('FFCMS_UPDATE_THEMES'); ?>
                </button>
            </form>
        </div>
    </div>

    <div class="zulu-panel">
        <table class="table">
            <thead>
                <tr>
                    <th><?php echo Lang::get('FFCMS_NAME'); ?></th>
                    <th><?php echo Lang::get('FFCMS_VERSION'); ?></th>
                    <th><?php echo Lang::get('FFCMS_STATUS'); ?></th>
                    <th><?php echo Lang::get('FFCMS_ACTIONS'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($components as $component) : ?>
                    <?php
                        $check = $component['check'] ?? null;
                        $status = $check['status'] ?? 'unknown';
                        [$icon, $class, $label] = update_status_icon($status);
                        $available = $check['available_version'] ?? '';
                    ?>
                    <tr>
                        <td><?php echo Template::escape($component['name']); ?></td>
                        <td>
                            <?php echo Template::escape($component['installed_version']); ?>
                            <?php if ($available !== '' && $status === 'update_available') : ?>
                                <span class="text-muted">→ <?php echo Template::escape($available); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="me-1 <?php echo Template::escape($class); ?>" aria-hidden="true"><i class="fa-solid <?php echo Template::escape($icon); ?>"></i></span>
                            <span><?php echo Template::escape($label); ?></span>
                        </td>
                        <td>
                            <?php if ($status === 'update_available') : ?>
                                <form method="post" action="/admin/updates/run/<?php echo Template::escape($component['type']); ?>/<?php echo Template::escape($component['key']); ?>">
                                    <?php echo Csrf::input(); ?>
                                    <?php if (in_array($component['type'], ['extension', 'theme'], true)) : ?>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="safety_override_<?php echo Template::escape($component['type']); ?>_<?php echo Template::escape($component['key']); ?>" name="safety_override" value="1">
                                            <label class="form-check-label" for="safety_override_<?php echo Template::escape($component['type']); ?>_<?php echo Template::escape($component['key']); ?>">
                                                <?php echo Lang::get('FFCMS_OVERRIDE_SAFETY'); ?>
                                            </label>
                                        </div>
                                    <?php endif; ?>
                                    <button class="ff-action-icon" type="submit" aria-label="<?php echo Template::escape(Lang::get('FFCMS_UPDATE_NOW')); ?>">
                                        <span aria-hidden="true"><i class="fa-solid fa-download"></i></span>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
