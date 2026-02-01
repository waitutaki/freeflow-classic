<?php
require 'core/bootstrap.php';

use Core\Models\ApiRoutesModel;

// Add the missing API route for devstore feeds
$route = [
    'ext_key' => 'devstore',
    'route_path' => 'feeds/main/index',
    'http_method' => 'GET',
    'controller' => 'DevstoreFeedsApiController',
    'action' => 'mainFeed',
    'is_public' => 1,
    'required_permission' => null,
    'is_enabled' => 1,
    'created_at' => date('Y-m-d H:i:s'),
    'created_by' => null,
];

ApiRoutesModel::insert($route);

echo "Devstore API route added successfully.";