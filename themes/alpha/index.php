<?php
defined('FF_CMS') or die('Restricted');
use Core\Asset;
use Core\Template;
use Core\Lang;
use Core\Theme;

$pageTitle = $page_title ?? Lang::get('FFCMS_SITE_NAME');
$hasLeft = Template::hasRegion($regions, 'sidebar_left');
$hasRight = Template::hasRegion($regions, 'sidebar_right');

$contentClass = 'col-12';
if ($hasLeft && $hasRight) {
    $contentClass = 'col-12 col-lg-6';
} elseif ($hasLeft || $hasRight) {
    $contentClass = 'col-12 col-lg-9';
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
    <link rel="stylesheet" href="<?php echo Theme::overridesUrl($theme_key, 'site'); ?>">
</head>
<body class="alpha-body">
    <div class="alpha-band alpha-header">
        <div class="container">
            <?php if (Template::hasRegion($regions, 'topbar')) : ?>
                <div class="alpha-topbar"><?php echo Template::region($regions, 'topbar'); ?></div>
            <?php endif; ?>

            <?php if (Template::hasRegion($regions, 'header_left') || Template::hasRegion($regions, 'header_right')) : ?>
                <div class="row alpha-header-row">
                    <?php if (Template::hasRegion($regions, 'header_left')) : ?>
                        <div class="col-md-6 alpha-header-left"><?php echo Template::region($regions, 'header_left'); ?></div>
                    <?php endif; ?>
                    <?php if (Template::hasRegion($regions, 'header_right')) : ?>
                        <div class="col-md-6 alpha-header-right"><?php echo Template::region($regions, 'header_right'); ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (Template::hasRegion($regions, 'menu')) : ?>
                <div class="alpha-menu-row"><?php echo Template::region($regions, 'menu'); ?></div>
            <?php endif; ?>

            <?php if (Template::hasRegion($regions, 'below_menu')) : ?>
                <div class="alpha-below-menu"><?php echo Template::region($regions, 'below_menu'); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="alpha-band alpha-main">
        <div class="container">
            <?php if (Template::hasRegion($regions, 'above_content')) : ?>
                <div class="alpha-above-content"><?php echo Template::region($regions, 'above_content'); ?></div>
            <?php endif; ?>
            <div class="row alpha-content-row">
                <?php if ($hasLeft) : ?>
                    <aside class="col-12 col-lg-3 alpha-sidebar alpha-sidebar-left"><?php echo Template::region($regions, 'sidebar_left'); ?></aside>
                <?php endif; ?>
                <main class="<?php echo $contentClass; ?> alpha-content-main"><?php echo Template::region($regions, 'content'); ?></main>
                <?php if ($hasRight) : ?>
                    <aside class="col-12 col-lg-3 alpha-sidebar alpha-sidebar-right"><?php echo Template::region($regions, 'sidebar_right'); ?></aside>
                <?php endif; ?>
            </div>
            <?php if (Template::hasRegion($regions, 'below_content')) : ?>
                <div class="alpha-below-content"><?php echo Template::region($regions, 'below_content'); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="alpha-band alpha-footer">
        <div class="container">
            <?php
            $aboveFooter = ['above_footer_1', 'above_footer_2', 'above_footer_3', 'above_footer_4'];
            $footer = ['footer_1', 'footer_2', 'footer_3', 'footer_4'];
            $hasAbove = false;
            foreach ($aboveFooter as $key) {
                $hasAbove = $hasAbove || Template::hasRegion($regions, $key);
            }
            if ($hasAbove) :
            ?>
                <div class="row alpha-footer-row">
                    <?php foreach ($aboveFooter as $key) : ?>
                        <?php if (Template::hasRegion($regions, $key)) : ?>
                            <div class="col-12 col-md-3"><?php echo Template::region($regions, $key); ?></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php
            $hasFooter = false;
            foreach ($footer as $key) {
                $hasFooter = $hasFooter || Template::hasRegion($regions, $key);
            }
            if ($hasFooter) :
            ?>
                <div class="row alpha-footer-row">
                    <?php foreach ($footer as $key) : ?>
                        <?php if (Template::hasRegion($regions, $key)) : ?>
                            <div class="col-12 col-md-3"><?php echo Template::region($regions, $key); ?></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (Template::hasRegion($regions, 'below_footer')) : ?>
                <div class="alpha-below-footer"><?php echo Template::region($regions, 'below_footer'); ?></div>
            <?php endif; ?>

            <?php if (Template::hasRegion($regions, 'copyright')) : ?>
                <div class="alpha-copyright"><?php echo Template::region($regions, 'copyright'); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <script src="<?php echo Asset::url('core', 'lib/bootstrap/js/bootstrap.bundle.min.js'); ?>"></script>
    <script src="<?php echo Asset::url('theme', $theme_key . '/js/template.js'); ?>"></script>
</body>
</html>
