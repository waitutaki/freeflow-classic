<?php
require 'core/bootstrap.php';

use Core\Models\ApiRoutesModel;

$routes = ApiRoutesModel::match('devstore', 'GET', 'feeds/main/index');
var_export($routes);