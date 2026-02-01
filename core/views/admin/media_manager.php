<?php
use Core\Lang;
use Core\Template;
use Core\Csrf;

$items = $items ?? [];
$bucket = $bucket ?? 'system';
$isSuper = $is_super ?? false;
?>
<div class="container-fluid">
    <h1 class="mb-3"><?php echo Lang::get('FFCMS_MEDIA_MANAGER'); ?></h1>
    <div class="zulu-toolbar-row">
        <div class="btn-group">
            <a class="btn btn-outline-secondary" href="/admin/">
                <span class="me-1" aria-hidden="true"><i class="fa-solid fa-xmark"></i></span>
                <?php echo Lang::get('FFCMS_CLOSE'); ?>
            </a>
        </div>
        <?php if ($isSuper) : ?>
            <form method="get" action="/admin/media" class="d-flex align-items-center ms-3">
                <select class="form-select form-select-sm w-auto" name="bucket" onchange="this.form.submit()">
                    <?php foreach (['system','users','extension','themes'] as $bucketOption) : ?>
                        <option value="<?php echo Template::escape($bucketOption); ?>" <?php echo $bucket === $bucketOption ? 'selected' : ''; ?>>
                            <?php echo Template::escape($bucketOption); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        <?php endif; ?>
    </div>

    <form method="post" action="/admin/media/upload" enctype="multipart/form-data" class="mb-3">
        <?php echo Csrf::input(); ?>
        <input type="hidden" name="bucket" value="<?php echo Template::escape($bucket); ?>">
        <div class="row">
            <div class="col-md-6 mb-2">
                <input class="form-control" type="file" name="image" accept=".jpg,.jpeg,.png,.webp">
            </div>
            <div class="col-md-6 mb-2">
                <button class="btn btn-primary" type="submit">
                    <span class="me-1" aria-hidden="true"><i class="fa-solid fa-upload"></i></span>
                    <?php echo Lang::get('FFCMS_UPLOAD'); ?>
                </button>
            </div>
        </div>
    </form>

    <div class="row g-3">
        <?php foreach ($items as $item) : ?>
            <?php if (empty($item['thumb']) || empty($item['path'])) : ?>
                <?php continue; ?>
            <?php endif; ?>
            <div class="col-6 col-md-3">
                <div class="card">
                    <img src="<?php echo Template::escape($item['thumb']); ?>" class="card-img-top" alt="">
                    <div class="card-body ff-action-group">
                        <form method="post" action="/admin/media/rotate" class="d-inline">
                            <?php echo Csrf::input(); ?>
                            <input type="hidden" name="path" value="<?php echo Template::escape($item['path']); ?>">
                            <input type="hidden" name="bucket" value="<?php echo Template::escape($bucket); ?>">
                            <button class="ff-action-icon" type="submit" aria-label="<?php echo Template::escape(Lang::get('FFCMS_ROTATE')); ?>">
                                <span aria-hidden="true"><i class="fa-solid fa-rotate-right"></i></span>
                            </button>
                        </form>
                        <a class="ff-action-icon" href="/admin/media/delete?path=<?php echo Template::escape($item['path']); ?>" aria-label="<?php echo Template::escape(Lang::get('FFCMS_DELETE')); ?>">
                            <span aria-hidden="true"><i class="fa-solid fa-trash"></i></span>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
