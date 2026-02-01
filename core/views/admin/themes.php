<?php
use Core\Lang;
use Core\Template;
use Core\Csrf;

$themes = $themes ?? [];
?>
<div class="container-fluid">
    <h1 class="mb-4"><?php echo Lang::get('FFCMS_THEMES'); ?></h1>
    <div class="zulu-toolbar-row">
        <div class="d-flex flex-wrap gap-2">
            <form method="post" action="/admin/themes/upload" enctype="multipart/form-data" class="d-flex flex-wrap gap-2 align-items-center">
                <?php echo Csrf::input(); ?>
                <input class="form-control w-auto" type="file" name="package" accept=".zip">
                <button class="btn btn-primary" type="submit">
                    <span class="me-1" aria-hidden="true"><i class="fa-solid fa-upload"></i></span>
                    <?php echo Lang::get('FFCMS_UPLOAD'); ?>
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
                    <th><?php echo Lang::get('FFCMS_NAME'); ?></th>
                    <th><?php echo Lang::get('FFCMS_THEME_KEY'); ?></th>
                    <th><?php echo Lang::get('FFCMS_CONTEXT'); ?></th>
                    <th><?php echo Lang::get('FFCMS_STATUS'); ?></th>
                    <th><?php echo Lang::get('FFCMS_ACTIONS'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($themes as $theme) : ?>
                    <?php
                    $isActive = !empty($theme['is_active']);
                    $context = $theme['context'] ?? 'site';
                    $statusLabel = $isActive ? Lang::get('FFCMS_ACTIVE') : Lang::get('FFCMS_INACTIVE');
                    $statusIcon = $isActive ? 'fa-circle-check' : 'fa-circle-xmark';
                    $statusClass = $isActive ? 'text-success' : 'text-danger';
                    $themeKey = (string)($theme['theme_key'] ?? '');
                    ?>
                    <tr>
                        <td><?php echo Template::escape($theme['name'] ?? ''); ?></td>
                        <td><?php echo Template::escape($themeKey); ?></td>
                        <td><?php echo $context === 'admin' ? Lang::get('FFCMS_CONTEXT_ADMIN') : Lang::get('FFCMS_CONTEXT_SITE'); ?></td>
                        <td>
                            <span class="<?php echo $statusClass; ?>" aria-label="<?php echo Template::escape($statusLabel); ?>">
                                <span class="me-1" aria-hidden="true"><i class="fa-solid <?php echo $statusIcon; ?>"></i></span>
                                <?php echo $statusLabel; ?>
                            </span>
                        </td>
                        <td>
                            <div class="ff-action-group">
                                <?php if ($context === 'site' && !$isActive) : ?>
                                    <form method="post" action="/admin/themes/activate/<?php echo (int)$theme['id']; ?>">
                                        <?php echo Csrf::input(); ?>
                                        <button class="ff-action-icon" type="submit" aria-label="<?php echo Template::escape(Lang::get('FFCMS_ACTIVATE')); ?>">
                                            <span aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($context === 'site' && $isActive && $themeKey !== 'alpha') : ?>
                                    <form method="post" action="/admin/themes/deactivate/<?php echo (int)$theme['id']; ?>">
                                        <?php echo Csrf::input(); ?>
                                        <button class="ff-action-icon" type="submit" aria-label="<?php echo Template::escape(Lang::get('FFCMS_DEACTIVATE')); ?>">
                                            <span aria-hidden="true"><i class="fa-solid fa-circle-xmark"></i></span>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($context === 'site' && $isActive) : ?>
                                    <a class="ff-action-icon" href="/admin/themes/customize/<?php echo Template::escape($themeKey); ?>" aria-label="<?php echo Template::escape(Lang::get('FFCMS_CUSTOMIZE')); ?>">
                                        <span aria-hidden="true"><i class="fa-solid fa-palette"></i></span>
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
