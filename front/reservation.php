<?php

// Définition de la variable GLPI_ROOT obligatoire pour l'instanciation des class
define('GLPI_ROOT', getAbsolutePath());
// Récupération du fichier includes de GLPI, permet l'accès au cœur
include GLPI_ROOT . "inc/includes.php";

/**
 * Récupère le chemin absolu de l'instance GLPI
 * @return String : le chemin absolu (racine principale)
 */
function getAbsolutePath() {
   return str_replace("plugins/reservation/front/reservation.php", "", $_SERVER['SCRIPT_FILENAME']);
}

$PluginReservationReservation = new PluginReservationReservation();

// Check if plugin is activated...
$plugin = new Plugin();
if (!$plugin->isInstalled('reservation') || !$plugin->isActivated('reservation')) {
   Html::displayNotFoundError();
}

Session::checkRight("reservation", [CREATE, UPDATE, DELETE]);

if ($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
   Html::helpHeader(__('Simplified interface'), $_SERVER['PHP_SELF'], $_SESSION["glpiname"]);
} else {
   Html::header(PluginReservationReservation::getTypeName(2), $_SERVER['PHP_SELF'], "plugins", "reservation");
}

if (!isset($form_dates)) {
   $form_dates = null;
}

if (isset($_POST['create'])) {
   $form_dates = $_POST['create'];
}

if (isset($_GET['checkout'])) {
   $PluginReservationReservation->checkoutReservation($_GET['checkout']);
}

if (isset($_GET['sendMail'])) {
   $PluginReservationReservation->sendMail($_GET['sendMail']);
}

if (isset($_POST['addItem'])) {
   $PluginReservationReservation->addItem($_POST['matDispoAdd'], $_POST['AjouterMatToResa']);
}

if (isset($_POST['replaceItem'])) {
   $PluginReservationReservation->replaceItem($_POST['matDispoReplace'], $_POST['ReplaceMatToResa']);
}

$PluginReservationReservation->display($_POST);
//$PluginReservationReservation->showCurrentResa();
//$PluginReservationReservation->showDispoAndFormResa();

if ($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
   Html::helpFooter();
} else {
   Html::footer();
}
