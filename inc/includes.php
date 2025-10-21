<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}



function logIfDebug($message = '', $data = '')
{
    $_SESSION['glpi_use_mode'] && Toolbox::logInFile('reservations_plugin', $message . " : " . json_encode($data) . "\n", $force = false);
}
