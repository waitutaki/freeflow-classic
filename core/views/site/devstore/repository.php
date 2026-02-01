<?php
use Core\Lang;
use Core\Template;

$packages = $packages ?? [];
?>
<div class="container">
    <h1 class="mb-4"><?php echo Lang::get('DEVSTORE.REPOSITORY_TITLE'); ?></h1>

    <?php if (!$packages) : ?>
        <div class="alert alert-info" role="alert">
            <?php echo Lang::get('DEVSTORE.REPOSITORY_EMPTY'); ?>
        </div>
    <?php else : ?>
        <div class="row">
            <?php foreach ($packages as $package) : ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-150">
                        <?php
                        $previewUrl = '/storage/devstore/repository/' . ($package['package_type'] === 'extension' ? 'extension' : 'themes') . '/' . $package['package_key'] . '/' . $package['version'] . '/preview.png';
                        if (!is_file(__DIR__ . '/../../../../' . $previewUrl)) {
                            $previewUrl = '/storage/devstore/repository/' . ($package['package_type'] === 'extension' ? 'extension' : 'themes') . '/' . $package['package_key'] . '/' . $package['version'] . '/preview.jpg';
                        }
                        if (!is_file(__DIR__ . '/../../../../' . $previewUrl)) {
                            $previewUrl = '/assets/lib/fontawesome6/svgs/solid/box-open.svg'; // default icon
                        }
                        ?>
                        <img src="<?php echo $previewUrl; ?>" class="card-img-top" alt="<?php echo Template::escape($package['name']); ?>" style="height: 200px; object-fit: cover;">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo Template::escape($package['name']); ?></h5>
                            <div class="mt-auto">
                                <div class="d-flex align-items-center">
                                    <small class="text-muted me-auto">V<?php echo Template::escape($package['version']); ?></small>
                                    <div>
                                        <a href="/repository/download/<?php echo $package['package_type']; ?>/<?php echo $package['package_key']; ?>" class="text-decoration-none me-2" title="<?php echo Lang::get('DEVSTORE.DOWNLOAD'); ?>">
                                            <span aria-hidden="true"><i class="fa-solid fa-file-arrow-down"></i></span>
                                        </a>
                                        <a href="/repository/install/<?php echo $package['package_type']; ?>/<?php echo $package['package_key']; ?>" class="text-decoration-none me-2" title="<?php echo Lang::get('DEVSTORE.INSTALL'); ?>">
                                            <span aria-hidden="true"><i class="fa-solid fa-play"></i></span>
                                        </a>
                                        <a href="/directory/<?php echo $package['package_type']; ?>s/<?php echo $package['package_key']; ?>" class="text-decoration-none" title="<?php echo Lang::get('DEVSTORE.MORE_INFO'); ?>">
                                            <span aria-hidden="true"><i class="fa-solid fa-info-circle"></i></span>
                                        </a>
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