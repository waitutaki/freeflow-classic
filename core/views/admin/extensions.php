<?php
use Core\Lang;
use Core\Template;
use Core\Csrf;

$tab = $tab ?? 'extension';
$extension = $extension ?? [];
$themes = $themes ?? [];
$devstorePackages = $devstorePackages ?? [];

function update_status_icon(string $status): array
{
    return match ($status) {
        'up_to_date' => ['fa-circle-check', 'text-success', Lang::get('FFCMS_UP_TO_DATE')],
        'update_available' => ['fa-download', 'text-warning', Lang::get('FFCMS_UPDATE_AVAILABLE')],
        'error' => ['fa-triangle-exclamation', 'text-danger', Lang::get('FFCMS_UPDATE_ERROR')],
        default => ['fa-circle-question', 'text-muted', Lang::get('FFCMS_UPDATE_UNKNOWN')],
    };
}

function getPackagePreviewUrl(array $package): string
{
    // Use the preview URL from the feed if available
    $previewUrl = $package['preview_url'] ?? '';
    if ($previewUrl !== '') {
        return $previewUrl;
    }
    
    $typeDir = ($package['package_type'] ?? 'extension') === 'theme' ? 'themes' : 'extension';
    $key = $package['package_key'] ?? '';
    $version = $package['version'] ?? '';
    $repoPath = $package['repo_path'] ?? '';
    
    // If repo_path is provided, use it to construct the preview URL
    if ($repoPath !== '') {
        $previewPath = '/storage/devstore/repository/' . $repoPath . '/preview.png';
        if (is_file(__DIR__ . '/../../../../' . $previewPath)) {
            return $previewPath;
        }
        
        $previewPath = '/storage/devstore/repository/' . $repoPath . '/preview.jpg';
        if (is_file(__DIR__ . '/../../../../' . $previewPath)) {
            return $previewPath;
        }
    }
    
    // Fallback to the old method if repo_path is not provided
    $previewPath = '/storage/devstore/repository/' . $typeDir . '/' . $key . '/' . $version . '/preview.png';
    if (is_file(__DIR__ . '/../../../../' . $previewPath)) {
        return $previewPath;
    }
    
    $previewPath = '/storage/devstore/repository/' . $typeDir . '/' . $key . '/' . $version . '/preview.jpg';
    if (is_file(__DIR__ . '/../../../../' . $previewPath)) {
        return $previewPath;
    }
    
    // Default icon
    return '/storage/media/system/no-image.png';
}
?>
<div class="container-fluid">
    <h1 class="mb-4"><?php echo Lang::get('FFCMS_extension'); ?></h1>
    <div class="zulu-toolbar-row">
        <div class="btn-group">
            <a class="btn btn-primary" href="/admin/extension/install?tab=<?php echo Template::escape($tab); ?>">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-plus"></i></span>
                <?php echo Lang::get('FFCMS_NEW'); ?>
            </a>
            <form method="post" action="/admin/updates/check">
                <?php echo Csrf::input(); ?>
                <button class="btn btn-outline-secondary" type="submit">
                    <span class="me-1" aria-hidden="true"><i class="fa-solid fa-rotate"></i></span>
                    <?php echo Lang::get('FFCMS_CHECK_UPDATES'); ?>
                </button>
            </form>
            <form method="post" action="/admin/updates/update-all">
                <?php echo Csrf::input(); ?>
                <button class="btn btn-outline-secondary" type="submit">
                    <span class="me-1" aria-hidden="true"><i class="fa-solid fa-download"></i></span>
                    <?php echo Lang::get('FFCMS_UPDATE_ALL'); ?>
                </button>
            </form>
            <a class="btn btn-outline-secondary" href="/admin/">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-xmark"></i></span>
                <?php echo Lang::get('FFCMS_CLOSE'); ?>
            </a>
        </div>
    </div>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link <?php echo $tab === 'extension' ? 'active' : ''; ?>" href="/admin/extension?tab=extension"><?php echo Lang::get('FFCMS_extension'); ?></a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $tab === 'themes' ? 'active' : ''; ?>" href="/admin/extension?tab=themes"><?php echo Lang::get('FFCMS_THEMES'); ?></a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $tab === 'web' ? 'active' : ''; ?>" href="/admin/extension?tab=web">
                <i class="fa-solid fa-globe me-1"></i> <?php echo Lang::get('FFCMS_INSTALL_FROM_WEB'); ?>
            </a>
        </li>
    </ul>

    <?php if ($tab === 'extension') : ?>
        <div class="zulu-panel">
            <table class="table">
                <thead>
                    <tr>
                        <th><?php echo Lang::get('FFCMS_NAME'); ?></th>
                        <th><?php echo Lang::get('FFCMS_TYPE'); ?></th>
                        <th><?php echo Lang::get('FFCMS_KEY'); ?></th>
                        <th><?php echo Lang::get('FFCMS_VERSION'); ?></th>
                        <th><?php echo Lang::get('FFCMS_STATUS'); ?></th>
                        <th><?php echo Lang::get('FFCMS_UPDATE_STATUS'); ?></th>
                        <th><?php echo Lang::get('FFCMS_CORE'); ?></th>
                        <th><?php echo Lang::get('FFCMS_ACTIONS'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($extension as $ext) : ?>
                        <?php
                        $isActive = !empty($ext['is_active']);
                        $statusLabel = $isActive ? Lang::get('FFCMS_ENABLED') : Lang::get('FFCMS_DISABLED');
                        $statusClass = $isActive ? 'text-success' : 'text-danger';
                        $isCore = !empty($ext['is_core']);
                        
                        // Update status
                        $updateStatus = $ext['update_status'] ?? 'unknown';
                        [$updateIcon, $updateClass, $updateLabel] = update_status_icon($updateStatus);
                        $availableVersion = $ext['available_version'] ?? '';
                        ?>
                        <tr>
                            <td><?php echo Template::escape($ext['name'] ?? ''); ?></td>
                            <td><?php echo Template::escape($ext['type'] ?? ''); ?></td>
                            <td><?php echo Template::escape($ext['ext_key'] ?? ''); ?></td>
                            <td>
                                <?php echo Template::escape($ext['version'] ?? ''); ?>
                                <?php if ($availableVersion && $updateStatus === 'update_available') : ?>
                                    <span class="text-muted">→ <?php echo Template::escape($availableVersion); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="<?php echo $statusClass; ?>" aria-label="<?php echo Template::escape($statusLabel); ?>">
                                    <i class="fa-solid <?php echo $isActive ? 'fa-circle-check' : 'fa-circle-xmark'; ?>" aria-hidden="true"></i>
                                    <span class="visually-hidden"><?php echo Template::escape($statusLabel); ?></span>
                                </span>
                            </td>
                            <td>
                                <?php if ($updateStatus === 'update_available') : ?>
                                    <a href="/admin/updates/update?type=extension&key=<?php echo Template::escape($ext['ext_key'] ?? ''); ?>">
                                        <span class="me-1 <?php echo Template::escape($updateClass); ?>" aria-hidden="true"><i class="fa-solid <?php echo Template::escape($updateIcon); ?>"></i></span>
                                        <span><?php echo Template::escape($updateLabel); ?></span>
                                    </a>
                                <?php else : ?>
                                    <span class="me-1 <?php echo Template::escape($updateClass); ?>" aria-hidden="true"><i class="fa-solid <?php echo Template::escape($updateIcon); ?>"></i></span>
                                    <span><?php echo Template::escape($updateLabel); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $isCore ? Lang::get('FFCMS_YES') : Lang::get('FFCMS_NO'); ?></td>
                            <td>
                                <div class="ff-action-group">
                                    <form class="d-inline" method="post" action="/admin/extension/<?php echo Template::escape($ext['ext_key'] ?? ''); ?>/toggle">
                                        <?php echo Csrf::input(); ?>
                                        <button class="ff-action-icon" type="submit" <?php echo $isCore ? 'disabled' : ''; ?> aria-label="<?php echo Template::escape($isActive ? Lang::get('FFCMS_DISABLE') : Lang::get('FFCMS_ENABLE')); ?>">
                                            <span aria-hidden="true"><i class="fa-solid <?php echo $isActive ? 'fa-circle-xmark' : 'fa-circle-check'; ?>"></i></span>
                                        </button>
                                    </form>
                                    <a class="ff-action-icon" href="/admin/ext/<?php echo Template::escape($ext['ext_key'] ?? ''); ?>" aria-label="<?php echo Template::escape(Lang::get('FFCMS_CONFIGURE')); ?>">
                                        <span aria-hidden="true"><i class="fa-solid fa-gear"></i></span>
                                    </a>
                                    <?php if ($isCore) : ?>
                                        <span class="ff-action-icon ff-action-disabled" aria-label="<?php echo Template::escape(Lang::get('FFCMS_UNINSTALL')); ?>">
                                            <span aria-hidden="true"><i class="fa-solid fa-trash"></i></span>
                                        </span>
                                    <?php else : ?>
                                        <a class="ff-action-icon" href="/admin/extension/<?php echo Template::escape($ext['ext_key'] ?? ''); ?>/uninstall" aria-label="<?php echo Template::escape(Lang::get('FFCMS_UNINSTALL')); ?>">
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
    <?php endif; ?>

    <?php if ($tab === 'themes') : ?>
        <div class="zulu-panel">
            <table class="table">
                <thead>
                    <tr>
                        <th><?php echo Lang::get('FFCMS_NAME'); ?></th>
                        <th><?php echo Lang::get('FFCMS_KEY'); ?></th>
                        <th><?php echo Lang::get('FFCMS_VERSION'); ?></th>
                        <th><?php echo Lang::get('FFCMS_STATUS'); ?></th>
                        <th><?php echo Lang::get('FFCMS_UPDATE_STATUS'); ?></th>
                        <th><?php echo Lang::get('FFCMS_CORE'); ?></th>
                        <th><?php echo Lang::get('FFCMS_ACTIONS'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($themes as $theme) : ?>
                        <?php
                        $isActive = !empty($theme['is_active']);
                        $statusLabel = $isActive ? Lang::get('FFCMS_ACTIVE') : Lang::get('FFCMS_INACTIVE');
                        $statusClass = $isActive ? 'text-success' : 'text-danger';
                        $isCore = !empty($theme['is_core']);
                        $context = $theme['context'] ?? 'site';
                        
                        // Update status
                        $updateStatus = $theme['update_status'] ?? 'unknown';
                        [$updateIcon, $updateClass, $updateLabel] = update_status_icon($updateStatus);
                        $availableVersion = $theme['available_version'] ?? '';
                        ?>
                        <tr>
                            <td><?php echo Template::escape($theme['name'] ?? ''); ?></td>
                            <td><?php echo Template::escape($theme['theme_key'] ?? ''); ?></td>
                            <td>
                                <?php echo Template::escape($theme['version'] ?? ''); ?>
                                <?php if ($availableVersion && $updateStatus === 'update_available') : ?>
                                    <span class="text-muted">→ <?php echo Template::escape($availableVersion); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="<?php echo $statusClass; ?>" aria-label="<?php echo Template::escape($statusLabel); ?>">
                                    <i class="fa-solid <?php echo $isActive ? 'fa-circle-check' : 'fa-circle-xmark'; ?>" aria-hidden="true"></i>
                                    <span class="visually-hidden"><?php echo Template::escape($statusLabel); ?></span>
                                </span>
                            </td>
                            <td>
                                <?php if ($updateStatus === 'update_available') : ?>
                                    <a href="/admin/updates/update?type=theme&key=<?php echo Template::escape($theme['theme_key'] ?? ''); ?>">
                                        <span class="me-1 <?php echo Template::escape($updateClass); ?>" aria-hidden="true"><i class="fa-solid <?php echo Template::escape($updateIcon); ?>"></i></span>
                                        <span><?php echo Template::escape($updateLabel); ?></span>
                                    </a>
                                <?php else : ?>
                                    <span class="me-1 <?php echo Template::escape($updateClass); ?>" aria-hidden="true"><i class="fa-solid <?php echo Template::escape($updateIcon); ?>"></i></span>
                                    <span><?php echo Template::escape($updateLabel); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $isCore ? Lang::get('FFCMS_YES') : Lang::get('FFCMS_NO'); ?></td>
                            <td>
                                <div class="ff-action-group">
                                    <?php if ($context === 'site' && !$isActive) : ?>
                                        <form class="d-inline" method="post" action="/admin/extension/themes/<?php echo (int)$theme['id']; ?>/activate">
                                            <?php echo Csrf::input(); ?>
                                            <button class="ff-action-icon" type="submit" aria-label="<?php echo Template::escape(Lang::get('FFCMS_ACTIVATE')); ?>">
                                                <span aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($context === 'site' && $isActive && ($theme['theme_key'] ?? '') !== 'alpha') : ?>
                                        <form class="d-inline" method="post" action="/admin/extension/themes/<?php echo (int)$theme['id']; ?>/deactivate">
                                            <?php echo Csrf::input(); ?>
                                            <button class="ff-action-icon" type="submit" aria-label="<?php echo Template::escape(Lang::get('FFCMS_DEACTIVATE')); ?>">
                                                <span aria-hidden="true"><i class="fa-solid fa-circle-xmark"></i></span>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($isCore) : ?>
                                        <span class="ff-action-icon ff-action-disabled" aria-label="<?php echo Template::escape(Lang::get('FFCMS_UNINSTALL')); ?>">
                                            <span aria-hidden="true"><i class="fa-solid fa-trash"></i></span>
                                        </span>
                                    <?php else : ?>
                                        <a class="ff-action-icon" href="/admin/extension/themes/<?php echo (int)$theme['id']; ?>/uninstall" aria-label="<?php echo Template::escape(Lang::get('FFCMS_UNINSTALL')); ?>">
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
    <?php endif; ?>

    <?php if ($tab === 'web') : ?>
        <div class="zulu-panel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0"><?php echo Lang::get('FFCMS_INSTALL_FROM_DEVSTORE'); ?></h2>
            </div>
            
            <?php if (empty($devstorePackages)) : ?>
                <div class="alert alert-info">
                    <?php echo Lang::get('FFCMS_NO_PACKAGES_AVAILABLE'); ?>
                </div>
            <?php else : ?>
                <div class="row">
                    <?php foreach ($devstorePackages as $package) : ?>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <?php
                                $previewUrl = getPackagePreviewUrl($package);
                                $packageType = $package['package_type'] === 'theme' ? 'theme' : 'extension';
                                $packageKey = $package['package_key'];
                                $packageVersion = $package['version'];
                                $packageName = $package['name'];
                                $packageAuthor = $package['author'];
                                $packageDesc = $package['description'];
                                ?>
                                
                                <img src="<?php echo $previewUrl; ?>"
                                     class="card-img-top"
                                     alt="<?php echo Template::escape($packageName); ?>"
                                     style="height: 200px; object-fit: contain; background-color: #f8f9fa;">
                                
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title"><?php echo Template::escape($packageName); ?></h5>
                                    <p class="card-text text-muted small"><?php echo Template::escape($packageAuthor); ?></p>
                                    <p class="card-text flex-grow-1"><?php echo Template::escape($packageDesc); ?></p>
                                    <div class="mt-auto">
                                        <div class="d-flex align-items-center">
                                            <small class="text-muted me-auto">
                                                V<?php echo Template::escape($packageVersion); ?>
                                            </small>
                                            <div>
                                                <!-- Install button -->
                                                <form method="post"
                                                      action="/admin/extension/install-from-web"
                                                      class="d-inline">
                                                    <?php echo Csrf::input(); ?>
                                                    <input type="hidden" name="type" value="<?php echo $packageType; ?>">
                                                    <input type="hidden" name="key" value="<?php echo $packageKey; ?>">
                                                    <input type="hidden" name="version" value="<?php echo $packageVersion; ?>">
                                                    <button type="submit"
                                                            class="btn btn-sm btn-primary me-2"
                                                            title="<?php echo Lang::get('FFCMS_INSTALL'); ?>"
                                                            onclick="this.form.submit(); return false;">
                                                        <i class="fa-solid fa-play"></i> <?php echo Lang::get('FFCMS_INSTALL'); ?>
                                                    </button>
                                                </form>
                                                <!-- More info button -->
                                                <button class="btn btn-sm btn-outline-secondary"
                                                        type="button"
                                                        title="<?php echo Lang::get('FFCMS_MORE_INFO'); ?>"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#packageInfoModal-<?php echo Template::escape($packageKey . '-' . $packageVersion); ?>">
                                                    <i class="fa-solid fa-info-circle"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
