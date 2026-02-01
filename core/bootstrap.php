<?php
if (defined('FF_CMS')) {
    die('Bootstrap already loaded.');
}
define('FF_CMS', true);

$hasSite = defined('FF_CONTEXT_SITE');
$hasAdmin = defined('FF_CONTEXT_ADMIN');

if ($hasSite === $hasAdmin) {
    echo 'Invalid execution context.';
    exit;
}

require __DIR__ . '/Autoloader.php';
Core\Autoloader::register();
Core\ErrorHandler::register();

$configPath = __DIR__ . '/admin/config/config.php';
clearstatcache(true, $configPath);
$installed = is_file($configPath);

if (!$installed) {
    if (is_file(__DIR__ . '/../install/.lock')) {
        echo 'Installer is locked.';
        exit;
    }

    require __DIR__ . '/../install/index.php';
    exit;
}

Core\Config::load();
Core\Lang::init();

$context = $hasSite ? Core\Application::CONTEXT_SITE : Core\Application::CONTEXT_ADMIN;
$app = Core\Application::instance($context);
$app->run();
