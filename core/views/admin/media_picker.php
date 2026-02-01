<?php
use Core\Lang;
use Core\Template;
use Core\Csrf;
use Core\Asset;

$items = $items ?? [];
$message = $message ?? '';
$bucket = $bucket ?? 'users';
$isSuper = $is_super ?? false;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo Template::escape(Lang::get('FFCMS_MEDIA_PICKER')); ?></title>
    <link rel="stylesheet" href="<?php echo Asset::url('core', 'lib/bootstrap/css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo Asset::url('core', 'css/core.css'); ?>">
    <link rel="stylesheet" href="<?php echo Asset::url('core', 'lib/fontawesome6/css/all.min.css'); ?>">
</head>
<body class="p-3">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="mb-0"><?php echo Lang::get('FFCMS_MEDIA_PICKER'); ?></h1>
            <?php if ($isSuper) : ?>
                <form method="get" class="d-flex align-items-center">
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
        <?php if ($message) : ?>
            <div class="alert alert-danger"><?php echo Template::escape($message); ?></div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data" class="mb-4">
            <?php echo Csrf::input(); ?>
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
                        <div class="ff-action-group">
                            <button class="ff-action-icon" type="button" data-media-action="insert" data-media-path="<?php echo Template::escape($item['path']); ?>" aria-label="<?php echo Template::escape(Lang::get('FFCMS_INSERT')); ?>">
                                <span aria-hidden="true"><i class="fa-solid fa-plus"></i></span>
                            </button>
                            <button class="ff-action-icon" type="button" data-media-action="featured" data-media-path="<?php echo Template::escape($item['path']); ?>" aria-label="<?php echo Template::escape(Lang::get('FFCMS_FEATURED')); ?>">
                                <span aria-hidden="true"><i class="fa-solid fa-star"></i></span>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <script src="<?php echo Asset::url('core', 'lib/bootstrap/js/bootstrap.bundle.min.js'); ?>"></script>
    <script src="<?php echo Asset::url('core', 'js/admin.js'); ?>"></script>
</body>
</html>
