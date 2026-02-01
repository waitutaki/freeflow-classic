<?php
define('FF_CONTEXT_SITE', true);
require __DIR__ . '/core/bootstrap.php';

$app = Core\Application::instance(Core\Application::CONTEXT_SITE);
$app->run();
