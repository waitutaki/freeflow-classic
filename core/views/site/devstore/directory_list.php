<?php
use Core\Lang;
use Core\Template;

$type = $type ?? 'extension';
$packages = $packages ?? [];
$titleKey = $type === 'theme' ? 'DEVSTORE.DIRECTORY_THEMES' : 'DEVSTORE.DIRECTORY_extension';
?>
<div class="container">
    <h1 class="mb-4"><?php echo Lang::get($titleKey); ?></h1>

    <div class="list-group">
        <?php foreach ($packages as $package) : ?>
            <a class="list-group-item list-group-item-action" href="/directory/<?php echo $type === 'theme' ? 'themes' : 'extension'; ?>/<?php echo Template::escape($package['package_key'] ?? ''); ?>">
                <strong><?php echo Template::escape($package['name'] ?? ''); ?></strong>
                <span class="text-muted">(<?php echo Template::escape($package['version'] ?? ''); ?>)</span>
            </a>
        <?php endforeach; ?>
    </div>
</div>
