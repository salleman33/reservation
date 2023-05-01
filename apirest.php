<?php

define('GLPI_ROOT', __DIR__ . '/../../');
define('DO_NOT_CHECK_HTTP_REFERER', 1);
ini_set('session.use_cookies', 0);

include(GLPI_ROOT . '/inc/autoload.function.php');
include 'inc/api.php';

$api = new PluginReservationApi();
$api->call();
