<?php

// Définition de la variable GLPI_ROOT obligatoire pour l'instanciation des class
define('GLPI_ROOT', getAbsolutePath());
// Récupération du fichier includes de GLPI, permet l'accès au cœur
include GLPI_ROOT . "inc/includes.php";

include GLPI_ROOT . "plugins/reservation/inc/includes.php";

$plugin = new Plugin();
if ($plugin->isActivated("reservation")) {
   $PluginReservationConfig = new PluginReservationConfig();
   Session::checkRight("config", [CREATE, UPDATE, DELETE]);
   if (isset($_POST["week"])) {
      $PluginReservationConfig->setConfigurationWeek($_POST["week"]);
      //Html::back();
   }
   if (isset($_POST["mode_auto"])) {
      $PluginReservationConfig->setMailAutomaticAction($_POST["mode_auto"]);
      $PluginReservationConfig->setConfigurationValue("mode_auto", isset($_POST["mode_auto"]));
      //Html::back();
   }

   foreach ($toolTipConfig as $config) {
      if (isset($_POST[$config])) {
         $PluginReservationConfig->setConfigurationValue($config, $_POST[$config]);
         //Html::back();
      }
   }

   foreach ($tabConfig as $config) {
      if (isset($_POST[$config])) {
         $PluginReservationConfig->setConfigurationValue($config, $_POST[$config]);
         //Html::back();
      }
   }

   Html::header(PluginReservationReservation::getTypeName(2), '', "plugins", "Reservation");
   $PluginReservationConfig->showForm();
   Html::footer();

} else {
   Html::header(__('Setup'), '', "config", "plugins");
   echo "<div class='center'><br><br>" .
      "<img src=\"" . $CFG_GLPI["root_doc"] . "/pics/warning.png\" alt='warning'><br><br>";
   echo "<b>" . __('Please activate the plugin', 'Reservation') . "</b></div>";
   Html::footer();
}

function getAbsolutePath() {
   return str_replace("plugins/reservation/front/config.form.php", "", $_SERVER['SCRIPT_FILENAME']);
}
