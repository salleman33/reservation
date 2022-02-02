<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

$toolTipConfig = ["tooltip", "comment", "location", "serial", "inventory", "group", "man_model", "status"];
$tabConfig = ["tabmine", "tabcurrent", "tabcoming"];


function logIfDebug($message = '', $data = '')
{
    $_SESSION['glpi_use_mode'] && Toolbox::logInFile('reservations_plugin', $message . " : " . json_encode($data) . "\n", $force = false);
}
