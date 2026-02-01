<?php
use Core\Lang;
use Core\Template;

$profile = $profile ?? [];
$canDevstore = (bool)($can_devstore ?? false);

$displayName = trim((string)($profile['name_first'] ?? '') . ' ' . (string)($profile['name_last'] ?? ''));
if ($displayName === '') {
    $displayName = (string)($profile['username'] ?? '');
}

function profileMediaPath(?string $path): string
{
    $path = trim((string)$path);
    if ($path === '') {
        return '';
    }
    if (str_starts_with($path, '/storage/')) {
        return $path;
    }
    if (str_starts_with($path, 'storage/')) {
        return '/' . $path;
    }
    if (str_starts_with($path, 'media/')) {
        return '/storage/' . $path;
    }
    if ($path[0] === '/') {
        return $path;
    }
    return '/' . $path;
}

$coverUrl = profileMediaPath($profile['cover_path'] ?? '');
$avatarUrl = profileMediaPath($profile['avatar_path'] ?? '');
$roleTitle = (string)($profile['role_title'] ?? $profile['role_id'] ?? '');
$statusActive = ((int)($profile['status'] ?? 0) === 1) && ((int)($profile['is_enabled'] ?? 0) === 1);
$statusLabel = $statusActive ? Lang::get('FFCMS_PROFILE_STATUS_ACTIVE') : Lang::get('FFCMS_PROFILE_STATUS_INACTIVE');
?>
<div class="container-fluid ff-profile">
    <div class="ff-profile-cover mb-3 <?php echo $coverUrl === '' ? 'ff-profile-cover-placeholder' : ''; ?>">
        <?php if ($coverUrl !== '') : ?>
            <img class="ff-profile-cover-image" src="<?php echo Template::escape($coverUrl); ?>" alt="<?php echo Template::escape(Lang::get('FFCMS_PROFILE_COVER_ALT')); ?>">
        <?php endif; ?>
    </div>

    <div class="card mb-4 ff-profile-header">
        <div class="card-body ff-profile-header-body">
            <div class="d-flex flex-wrap align-items-end gap-3">
                <div class="ff-profile-avatar-wrap">
                    <?php if ($avatarUrl !== '') : ?>
                        <img class="ff-profile-avatar" src="<?php echo Template::escape($avatarUrl); ?>" alt="<?php echo Template::escape(Lang::get('FFCMS_PROFILE_AVATAR_ALT')); ?>">
                    <?php else : ?>
                        <div class="ff-profile-avatar-placeholder" aria-hidden="true">
                            <i class="fa-solid fa-user"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="ff-profile-header-meta">
                    <h1 class="h4 mb-1"><?php echo Template::escape($displayName); ?></h1>
                    <div class="text-muted">@<?php echo Template::escape((string)($profile['username'] ?? '')); ?></div>
                </div>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs" id="profile-tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="profile-overview-tab" data-bs-toggle="tab" data-bs-target="#profile-overview" type="button" role="tab" aria-controls="profile-overview" aria-selected="true">
                <?php echo Lang::get('FFCMS_PROFILE_OVERVIEW'); ?>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="profile-details-tab" data-bs-toggle="tab" data-bs-target="#profile-details" type="button" role="tab" aria-controls="profile-details" aria-selected="false">
                <?php echo Lang::get('FFCMS_PROFILE_DETAILS'); ?>
            </button>
        </li>
        <?php if ($canDevstore) : ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="profile-devstore-tab" data-bs-toggle="tab" data-bs-target="#profile-devstore" type="button" role="tab" aria-controls="profile-devstore" aria-selected="false">
                    <?php echo Lang::get('FFCMS_PROFILE_DEVSTORE'); ?>
                </button>
            </li>
        <?php endif; ?>
    </ul>
    <div class="tab-content border border-top-0 rounded-bottom p-3">
        <div class="tab-pane fade show active" id="profile-overview" role="tabpanel" aria-labelledby="profile-overview-tab">
            <div class="row g-3">
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h2 class="h6"><?php echo Lang::get('FFCMS_PROFILE_OVERVIEW'); ?></h2>
                            <dl class="row mb-0">
                                <dt class="col-sm-4"><?php echo Lang::get('FFCMS_PROFILE_USERNAME'); ?></dt>
                                <dd class="col-sm-8"><?php echo Template::escape((string)($profile['username'] ?? '')); ?></dd>
                                <dt class="col-sm-4"><?php echo Lang::get('FFCMS_PROFILE_EMAIL'); ?></dt>
                                <dd class="col-sm-8"><?php echo Template::escape((string)($profile['email'] ?? '')); ?></dd>
                                <dt class="col-sm-4"><?php echo Lang::get('FFCMS_PROFILE_ROLE'); ?></dt>
                                <dd class="col-sm-8"><?php echo Template::escape($roleTitle); ?></dd>
                                <dt class="col-sm-4"><?php echo Lang::get('FFCMS_PROFILE_STATUS'); ?></dt>
                                <dd class="col-sm-8"><?php echo Template::escape($statusLabel); ?></dd>
                                <dt class="col-sm-4"><?php echo Lang::get('FFCMS_PROFILE_MEMBER_SINCE'); ?></dt>
                                <dd class="col-sm-8"><?php echo Template::escape((string)($profile['created_at'] ?? '')); ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h2 class="h6"><?php echo Lang::get('FFCMS_PROFILE_DETAILS'); ?></h2>
                            <p class="text-muted mb-0"><?php echo Lang::get('FFCMS_PROFILE_NO_DETAILS'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="tab-pane fade" id="profile-details" role="tabpanel" aria-labelledby="profile-details-tab">
            <div class="card">
                <div class="card-body">
                    <h2 class="h6"><?php echo Lang::get('FFCMS_PROFILE_DETAILS'); ?></h2>
                    <p class="text-muted mb-0"><?php echo Lang::get('FFCMS_PROFILE_NO_DETAILS'); ?></p>
                </div>
            </div>
        </div>
        <?php if ($canDevstore) : ?>
            <div class="tab-pane fade" id="profile-devstore" role="tabpanel" aria-labelledby="profile-devstore-tab">
                <div class="card">
                    <div class="card-body">
                        <h2 class="h6"><?php echo Lang::get('FFCMS_PROFILE_DEVSTORE'); ?></h2>
                        <p class="mb-3"><?php echo Lang::get('FFCMS_PROFILE_DEVSTORE_APPROVED'); ?></p>
                        <a class="btn btn-outline-primary" href="/devstore">
                            <span class="me-1" aria-hidden="true"><i class="fa-solid fa-store"></i></span>
                            <?php echo Lang::get('FFCMS_PROFILE_DEVSTORE_LINK'); ?>
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
