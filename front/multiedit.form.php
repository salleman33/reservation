<?php

// Définition de la variable GLPI_ROOT obligatoire pour l'instanciation des class
define('GLPI_ROOT', getAbsolutePath());
// // Récupération du fichier includes de GLPI, permet l'accès au cœur
include GLPI_ROOT . "inc/includes.php";

include_once GLPI_ROOT . "plugins/reservation/inc/includes.php";

$plugin = new Plugin();
if ($plugin->isActivated("reservation")) {
    $PluginReservationMultiEdit = new PluginReservationMultiEdit();
    Session::checkRight("reservation", ReservationItem::RESERVEANITEM);

    Html::header(PluginReservationMultiEdit::getTypeName(2), $_SERVER['PHP_SELF'], "plugins", "pluginreservationmenu", "reservation");

    if (isset($_POST["update"])) {
        $PluginReservationMultiEdit->updateMultipleItems($_POST);
        Html::redirect($CFG_GLPI["root_doc"] . "/plugins/reservation/front/menu.php");
    }

    if (isset($_GET["ids"]) && !empty($_GET["ids"])) {
        $PluginReservationMultiEdit->showForm($_GET);
    } else {
        Html::back();
    }
    Html::footer();
} else {
    Html::header(__('Setup'), '', "config", "plugins");
    echo "<div class='center'><br><br>" .
        "<img src=\"" . $CFG_GLPI["root_doc"] . "/pics/warning.png\" alt='warning'><br><br>";
    echo "<b>" . __('Please activate the plugin', 'reservation') . "</b></div>";
    Html::footer();
}

function getAbsolutePath()
{
    return str_replace("plugins/reservation/front/multiedit.form.php", "", $_SERVER['SCRIPT_FILENAME']);
}
