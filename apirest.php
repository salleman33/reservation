<?php

define('GLPI_ROOT',  __DIR__.'/../../');
include (GLPI_ROOT . '/inc/autoload.function.php');
include (GLPI_ROOT . '/inc/includes.php');
include 'inc/api.php';

define('DO_NOT_CHECK_HTTP_REFERER', 1);
ini_set('session.use_cookies', 0);

$api = new PluginReservationApi;
$api->call();
