<?php
use Core\Lang;
use Core\Template;

$type = $type ?? 'extension';
$packages = $packages ?? [];
$first = $packages[0] ?? [];
$previewUrl = '/storage/devstore/repository/' . ($type === 'extension' ? 'extension' : 'themes') . '/' . ($first['package_key'] ?? '') . '/' . ($first['version'] ?? '') . '/preview.png';
if (!is_file(__DIR__ . '/../../../../' . $previewUrl)) {
    $previewUrl = '/storage/devstore/repository/' . ($type === 'extension' ? 'extension' : 'themes') . '/' . ($first['package_key'] ?? '') . '/' . ($first['version'] ?? '') . '/preview.jpg';
}
if (!is_file(__DIR__ . '/../../../../' . $previewUrl)) {
    $previewUrl = '/assets/lib/fontawesome6/svgs/solid/box-open.svg'; // default icon
}
?>
<div class="container">
    <h1><?php echo Template::escape($first['name'] ?? ''); ?></h1>

    <div class="row">
        <div class="col-md-4">
            <img src="<?php echo $previewUrl; ?>" class="img-fluid" alt="<?php echo Template::escape($first['name'] ?? ''); ?>" style="border: 1px solid #00008B; border-radius: 5px;">
        </div>
        <div class="col-md-8">
            <h2><?php echo Template::escape($first['name'] ?? ''); ?> V<?php echo Template::escape($first['version'] ?? ''); ?></h2>
            <p><strong><?php echo Lang::get('FFCMS_AUTHOR'); ?>:</strong> <?php echo Template::escape($first['author'] ?? ''); ?></p>
            <p><strong><?php echo Lang::get('FFCMS_TYPE'); ?>:</strong> <?php echo ucfirst($type); ?></p>
            <p><strong><?php echo Lang::get('FFCMS_VERSION'); ?>:</strong> <?php echo Template::escape($first['version'] ?? ''); ?></p>
            <!-- Add more details if available -->
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="zulu-panel">
                <h3><?php echo Lang::get('FFCMS_DESCRIPTION'); ?></h3>
                <p><?php echo nl2br(Template::escape($first['description'] ?? '')); ?></p>
            </div>
        </div>
    </div>
</div>
