<?php

// Définition de la variable GLPI_ROOT obligatoire pour l'instanciation des class
define('GLPI_ROOT', getAbsolutePath());
// Récupération du fichier includes de GLPI, permet l'accès au cœur
include GLPI_ROOT . "inc/includes.php";
// Reservation plugin includes
include_once GLPI_ROOT . "plugins/reservation/inc/includes.php";

$plugin = new Plugin();
if ($plugin->isActivated("reservation")) {
    $PluginReservationMultiEdit = new PluginReservationMultiEdit();

    Session::checkRightsOr('reservation', [CREATE, UPDATE, DELETE, PURGE]);

    Html::header(PluginReservationMultiEdit::getTypeName(2), $_SERVER['PHP_SELF'], "plugins", "pluginreservationmenu", "reservation");

    // Handle form submit : update
    if (isset($_POST["update"])) {
        Toolbox::manageBeginAndEndPlanDates($_POST['resa']);
        if (Session::haveRight("reservation", UPDATE) || (Session::getLoginUserID() == $_POST["users_id"])) {
            $_POST['begin']   = $_POST['resa']["begin"];
            $_POST['end']     = $_POST['resa']["end"];

            $PluginReservationMultiEdit->updateMultipleItems($_POST);

            Html::redirect($CFG_GLPI["root_doc"] . "/plugins/reservation/front/menu.php");
        }
    }

    // Handle form submit : purge
    if (isset($_POST["purge"])) {
        if (Session::haveRight("reservation", PURGE) || (Session::getLoginUserID() == $_POST["users_id"])) {
            $PluginReservationMultiEdit->purgeMultipleItems($_POST);

            Html::redirect($CFG_GLPI["root_doc"] . "/plugins/reservation/front/menu.php");
        }
    }

    // Show form
    if (isset($_GET["ids"]) && is_array($_GET["ids"]) && count($_GET["ids"]) >= 2) {
        if (!$PluginReservationMultiEdit->showForm($_GET)) {
            Html::redirect($CFG_GLPI["root_doc"] . "/plugins/reservation/front/menu.php");
        }
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
