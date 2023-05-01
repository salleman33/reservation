<?php

// Définition de la variable GLPI_ROOT obligatoire pour l'instanciation des class
define('GLPI_ROOT', getAbsolutePath());
// // Récupération du fichier includes de GLPI, permet l'accès au cœur
include GLPI_ROOT . "inc/includes.php";

include_once GLPI_ROOT . "plugins/reservation/inc/includes.php";

$plugin = new Plugin();
if ($plugin->isActivated("reservation")) {
    $PluginReservationConfig = new PluginReservationConfig();
    Session::checkRightsOr("config", [CREATE, UPDATE, DELETE]);

    if (isset($_POST["mode_auto"])) {
        $PluginReservationConfig->setMailAutomaticAction($_POST["mode_auto"]);
        $PluginReservationConfig->setConfigurationValue("mode_auto", $_POST["mode_auto"]);
    }
    if (isset($_POST["extension_time"])) {
        $PluginReservationConfig->setConfigurationValue("extension_time", $_POST["extension_time"]);
    }
    if (isset($_POST["conflict_action"])) {
        $PluginReservationConfig->setConfigurationValue("conflict_action", $_POST["conflict_action"]);
    }
    if (isset($_POST["checkin"])) {
        $PluginReservationConfig->setConfigurationValue("checkin", $_POST["checkin"]);
        $PluginReservationConfig->setConfigurationValue("checkin_timeout", $_POST["checkin_timeout"]);
        $PluginReservationConfig->setConfigurationValue("checkin_action", $_POST["checkin_action"]);
        $PluginReservationConfig->setConfigurationValue("auto_checkin", $_POST["auto_checkin"]);
        $PluginReservationConfig->setConfigurationValue("auto_checkin_time", $_POST["auto_checkin_time"]);
    }
    if (isset($_POST["custom_categories"])) {
        $PluginReservationConfig->setConfigurationValue("custom_categories", $_POST["custom_categories"]);
    }
    if (isset($_POST["use_items_types"])) {
        $PluginReservationConfig->setConfigurationValue("use_items_types", $_POST["use_items_types"]);
    }
    if (isset($_POST['configCategoriesForm'])) {
        $PluginReservationCategory = new PluginReservationCategory();
        $PluginReservationCategory->applyCategoriesConfig($_POST);
    }
    if (isset($_POST['configCategoryItems'])) {
        $PluginReservationCategory = new PluginReservationCategory();
        $PluginReservationCategory->applyCategoryItem($_POST);
    }

    foreach ($toolTipConfig as $config) {
        if (isset($_POST[$config])) {
            $PluginReservationConfig->setConfigurationValue($config, $_POST[$config]);
        }
    }

    foreach ($tabConfig as $config) {
        if (isset($_POST[$config])) {
            $PluginReservationConfig->setConfigurationValue($config, $_POST[$config]);
        }
    }

    Html::header(PluginReservationReservation::getTypeName(2), '', "plugins", "Reservation");
    if (isset($_POST['configCategorySubmit'])) {
        $PluginReservationConfig->showForm(2, [$_POST['configCategorySubmit']]);
    } else {
        $PluginReservationConfig->showForm(1);
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
    return realpath("../../..") . "/";
}
