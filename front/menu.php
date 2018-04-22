<?php

include '../../../inc/includes.php';


if ($_SESSION["glpiactiveprofile"]["interface"] == "central") {
   Html::header(PluginReservationMenu::getTypeName(2), $_SERVER['PHP_SELF'], "plugins", "pluginreservationmenu", "reservation");
} else {
   Html::helpHeader(__('Simplified interface'), $_SERVER['PHP_SELF'], $_SESSION["glpiname"]);
}

// Check if plugin is activated...
$plugin = new Plugin();
if (!$plugin->isInstalled('reservation') || !$plugin->isActivated('reservation')) {
   Html::displayNotFoundError();
}

Session::checkRight("reservation", [CREATE, UPDATE, DELETE]);



$menu = new PluginReservationMenu();
$menu->showFormDate();

$menu->display($_POST);

if ($_SESSION["glpiactiveprofile"]["interface"] == "central") {
   Html::footer();
} else {
   Html::helpFooter();
}