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

if (isset($_GET['mailuser'])) {
   $PluginReservationReservation->sendMail($_GET['sendMail']);
}
if (isset($_GET['checkout'])) {
   PluginReservationReservation::checkoutReservation($_GET['checkout']);
}

if (isset($_POST['add_item_to_reservation'])) {
   $current_reservation = $_POST['add_item_to_reservation'];
   $item_to_add = $_POST['add_item'];
   PluginReservationReservation::addItemToResa($item_to_add, $current_reservation);
}

if (isset($_POST['switch_item_to_reservation'])) {
   $current_reservation = $_POST['switch_item_to_reservation'];
   $item_to_switch = $_POST['switch_item'];
   PluginReservationReservation::switchItemToResa($item_to_switch, $current_reservation);
}


$menu = new PluginReservationMenu();
$menu->showFormDate();

$menu->display($_POST);

if ($_SESSION["glpiactiveprofile"]["interface"] == "central") {
   Html::footer();
} else {
   Html::helpFooter();
}