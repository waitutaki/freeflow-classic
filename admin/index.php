<?php
define('FF_CONTEXT_ADMIN', true);
file_put_contents(__DIR__ . '/../storage/logs/debug.log', date('Y-m-d H:i:s') . ' Admin index executed: ' . $_SERVER['REQUEST_URI'] . PHP_EOL, FILE_APPEND);
require __DIR__ . '/../core/bootstrap.php';

$app = Core\Application::instance(Core\Application::CONTEXT_ADMIN);
$app->run();
