<?php
defined('FF_CMS') or die('Restricted');
use Core\Asset;
use Core\Template;
use Core\Lang;
use Core\Theme;

$pageTitle = $page_title ?? Lang::get('FFCMS_ADMIN');
$dashboardKeys = ['dashboard_1', 'dashboard_2', 'dashboard_3', 'dashboard_4', 'dashboard_5', 'dashboard_6'];
$hasDashboard = false;
foreach ($dashboardKeys as $key) {
    $hasDashboard = $hasDashboard || Template::hasRegion($regions, $key);
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo Template::escape($pageTitle); ?></title>
    <link rel="stylesheet" href="<?php echo Asset::url('core', 'lib/bootstrap/css/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo Asset::url('core', 'css/core.css'); ?>">
    <link rel="stylesheet" href="<?php echo Asset::url('core', 'lib/fontawesome6/css/all.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo Asset::url('theme', $theme_key . '/css/template.css'); ?>">
    <link rel="stylesheet" href="<?php echo Theme::overridesUrl($theme_key, 'admin'); ?>">
    <?php echo Template::region($regions, 'head_links'); ?>
</head>
<body class="zulu-body">
    <header class="zulu-topbar">
        <div class="zulu-topbar-inner">
            <?php echo Template::region($regions, 'topbar_logo'); ?>
        </div>
    </header>

    <div class="zulu-shell">
        <aside class="zulu-sidebar">
            <div class="zulu-sidebar-toggle"><?php echo Template::region($regions, 'sidebar_toggle'); ?></div>
            <nav class="zulu-sidebar-menu"><?php echo Template::region($regions, 'sidebar_menu'); ?></nav>
        </aside>

        <main class="zulu-workspace">
            <div class="zulu-toolbar">
                <div class="zulu-toolbar-left"><?php echo Template::region($regions, 'toolbar_left'); ?></div>
                <div class="zulu-toolbar-notices">
                    <?php if (Template::hasRegion($regions, 'toolbar_notices')) : ?>
                        <?php echo Template::region($regions, 'toolbar_notices'); ?>
                    <?php endif; ?>
                </div>
                <div class="zulu-toolbar-right"><?php echo Template::region($regions, 'toolbar_right'); ?></div>
            </div>

            <div class="zulu-content">
                <?php if ($hasDashboard) : ?>
                    <div class="zulu-dashboard-grid">
                        <?php foreach ($dashboardKeys as $key) : ?>
                            <?php if (Template::hasRegion($regions, $key)) : ?>
                                <div class="zulu-dashboard-zone"><?php echo Template::region($regions, $key); ?></div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <?php echo Template::region($regions, 'content'); ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="<?php echo Asset::url('core', 'lib/bootstrap/js/bootstrap.bundle.min.js'); ?>"></script>
    <script src="<?php echo Asset::url('core', 'js/admin.js'); ?>"></script>
    <script src="<?php echo Asset::url('theme', $theme_key . '/js/template.js'); ?>"></script>
    <?php echo Template::region($regions, 'footer_scripts'); ?>
</body>
</html>
