<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

include GLPI_ROOT . "/plugins/reservation/inc/includes.php";

class PluginReservationConfig extends CommonDBTM
{

   public function getConfigurationValue($name, $defaultValue = 0) {
      global $DB;
      $query = "SELECT * FROM glpi_plugin_reservation_configs WHERE `name`='" . $name . "'";
      $value = $defaultValue;
      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) > 0) {
            while ($row = $DB->fetch_assoc($result)) {
               $value = $row['value'];
            }
         }
      }
      return $value;

   }

   public function setConfigurationValue($name, $value = '') {
      global $DB;

      if ($value != '') {
         $query = "INSERT INTO glpi_plugin_reservation_configs (name,value) VALUES('" . $name . "','" . $value . "') ON DUPLICATE KEY UPDATE value=Values(value)";
         $DB->query($query) or die($DB->error());
      }
   }

   public function setMailAutomaticAction($value = 1) {
      global $DB;

      $query = "UPDATE `glpi_crontasks` SET state='" . $value . "' WHERE name = 'sendMailLateReservations'";
      $DB->query($query) or die($DB->error());
   }

   public function getConfigurationWeek() {
      global $DB;
      $config  = [];
      foreach (['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'] as $day) {
         $query = "SELECT * FROM glpi_plugin_reservation_configs WHERE `name`='$day' and `value` = 1";
         if ($result = $DB->query($query)) {
            if ($DB->numrows($result) > 0) {
               $config[$day] = '1';
            }
         }
      }
      return $config;
   }

   public function setConfigurationWeek($week = null) {
      global $DB;
      foreach (['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'] as $day) {
         $query = "UPDATE glpi_plugin_reservation_configs SET `value`=0 where `name` = '$day'";
         $DB->query($query) or die($DB->error());
      }
      foreach ($week as $day) {
         $query = "UPDATE glpi_plugin_reservation_configs SET `value`=1 WHERE `name` = '$day'";
         $DB->query($query) or die($DB->error());
      }
   }

   public function showForm() {
      $mode_auto = $this->getConfigurationValue("mode_auto");
      $config = $this->getConfigurationWeek();

      echo "<form method='post' action='" . $this->getFormURL() . "'>";

      echo "<div class='center'>";

      echo "<table class='tab_cadre_fixe'  cellpadding='2'>";

      echo "<th>" . __('Method used to send e-mails to users with late reservations') . "</th>";
      echo "<tr>";
      echo "<td>";
      //echo "<input type=\"hidden\" name=\"mode_auto\" value=\"0\">";
      echo HTML::getCheckbox([
         'name' => "mode_auto",
         "checked" => $mode_auto,
         ]);
      echo __('Automatic') . " (" . __('Using the configurable automatic action') . ") ";
      echo "</td>";
//echo "<input type=\"checkbox\" name=\"mode_auto\" value=\"1\" " . ($mode_auto ? 'checked' : '') . "> " . __('Automatic') . " (" . __('Using the configurable automatic action') . ") </td>";
      echo "</tr>";

      echo "</table>";

      if ($mode_auto) {
         echo "<table class='tab_cadre_fixe'  cellpadding='2'>";
         echo "<th colspan=2>" . __('Days when e-mails for late reservations are sent') . "</th>";
         echo "<tr>";
         echo "<td> " . __('Monday') . " : </td><td>";
         echo HTML::getCheckbox([
            'name' => "week[]",
            "checked" => isset($config['lundi']),
            "value" => 'lundi'
         ]);
         echo "</td>";
         echo "</tr>";
         echo "<tr>";
         echo "<td> " . __('Tuesday') . " : </td><td>";
         echo HTML::getCheckbox([
            'name' => "week[]",
            "checked" => isset($config['mardi']),
            "value" => 'mardi'
         ]);
         echo "</td>";
         echo "</tr>";
         echo "<tr>";
         echo "<td> " . __('Wednesday') . " : </td><td>";
         echo HTML::getCheckbox([
            'name' => "week[]",
            "checked" => isset($config['mercredi']),
            "value" => 'mercredi'
         ]);
         echo "</td>";
         echo "</tr>";
         echo "<tr>";
         echo "<td> " . __('Thursday') . " : </td><td>";
         echo HTML::getCheckbox([
            'name' => "week[]",
            "checked" => isset($config['jeudi']),
            "value" => 'jeudi'
         ]);
         echo "</td>";
         echo "</tr>";
         echo "<tr>";
         echo "<td> " . __('Friday') . " : </td><td>";
         echo HTML::getCheckbox([
            'name' => "week[]",
            "checked" => isset($config['vendredi']),
            "value" => 'vendredi'
         ]);
         echo "</td>";
         echo "</tr>";
         echo "<tr>";
         echo "<td> " . __('Saturday') . " : </td><td>";
         echo HTML::getCheckbox([
            'name' => "week[]",
            "checked" => isset($config['samedi']),
            "value" => 'samedi'
         ]);
         echo "</td>";
         echo "</tr>";
         echo "<tr>";
         echo "<td> " . __('Sunday') . " : </td><td>";
         echo HTML::getCheckbox([
            'name' => "week[]",
            "checked" => isset($config['dimanche']),
            "value" => 'dimanche'
         ]);
         echo "</td>";

         echo "</tr>";
         echo "</table>";
      }

      echo "<table class='tab_cadre_fixe'  cellpadding='2'>";

      echo "<th>" . ('Tab Configuration') . "</th>",
      $tabcurrent = $this->getConfigurationValue("tabcurrent", 1);
      echo "<tr>";
      echo "<input type=\"hidden\" name=\"tabcurrent\" value=\"0\">";
      echo "<td style=\"padding-left:20px;\">";
      echo HTML::getCheckbox([
         'name' => "tabcurrent",
         "checked" => $tabcurrent,
         "value" => '1'
      ]);
      //echo "<input type=\"checkbox\" name=\"tabcurrent\" value=\"1\" " . ($tabcurrent ? 'checked' : '') . "> "
      echo __('Current Reservation tab') . "</td>";
      echo "</tr>";

      $tabcoming = $this->getConfigurationValue("tabcoming");
      echo "<tr>";
      echo "<input type=\"hidden\" name=\"tabcoming\" value=\"0\">";
      echo "<td style=\"padding-left:20px;\">";
      echo HTML::getCheckbox([
         'name' => "tabcoming",
         "checked" => $tabcoming,
         "value" => '1'
      ]);
      //echo "<input type=\"checkbox\" name=\"tabcoming\" value=\"1\" " . ($tabcoming ? 'checked' : '') . "> " . 
      echo __('Incoming Reservation tab') . "</td>";
      echo "</tr>";
      /*}
      */
      echo "</table>";

      echo "<table class='tab_cadre_fixe'  cellpadding='2'>";

      echo "<th>" . __('ToolTip Configuration') . "</th>";

      $tooltip = $this->getConfigurationValue("tooltip");
      echo "<tr>";
      echo "<input type=\"hidden\" name=\"tooltip\" value=\"0\">";
      echo HTML::getCheckbox([
         'name' => "tooltip",
         "checked" => $tooltip,
         "value" => '1'
      ]);
      //echo "<td> <input type=\"checkbox\" name=\"tooltip\" value=\"1\" " . ($tooltip ? 'checked' : '') . "> " . 
      echo __('ToolTip') . "</td>";
      echo "</tr>";

      if ($tooltip) {

         $comment = $this->getConfigurationValue("comment");
         echo "<tr>";
         echo "<input type=\"hidden\" name=\"comment\" value=\"0\">";
         echo "<td style=\"padding-left:20px;\">";
         echo "<input type=\"checkbox\" name=\"comment\" value=\"1\" " . ($comment ? 'checked' : '') . "> " . __('Comment') . "</td>";
         echo "</tr>";

         $location = $this->getConfigurationValue("location");
         echo "<tr>";
         echo "<input type=\"hidden\" name=\"location\" value=\"0\">";
         echo "<td style=\"padding-left:20px;\">";
         echo "<input type=\"checkbox\" name=\"location\" value=\"1\" " . ($location ? 'checked' : '') . "> " . __('Location') . "</td>";
         echo "</tr>";

         $serial = $this->getConfigurationValue("serial");
         echo "<tr>";
         echo "<input type=\"hidden\" name=\"serial\" value=\"0\">";
         echo "<td style=\"padding-left:20px;\">";
         echo "<input type=\"checkbox\" name=\"serial\" value=\"1\" " . ($serial ? 'checked' : '') . "> " . __('Serial number') . "</td>";
         echo "</tr>";

         $inventory = $this->getConfigurationValue("inventory");
         echo "<tr>";
         echo "<input type=\"hidden\" name=\"inventory\" value=\"0\">";
         echo "<td style=\"padding-left:20px;\">";
         echo "<input type=\"checkbox\" name=\"inventory\" value=\"1\" " . ($inventory ? 'checked' : '') . "> " . __('Inventory number') . "</td>";
         echo "</tr>";

         $group = $this->getConfigurationValue("group");
         echo "<tr>";
         echo "<input type=\"hidden\" name=\"group\" value=\"0\">";
         echo "<td style=\"padding-left:20px;\">";
         echo "<input type=\"checkbox\" name=\"group\" value=\"1\" " . ($group ? 'checked' : '') . "> " . __('Group') . "</td>";
         echo "</tr>";

         $man_model = $this->getConfigurationValue("man_model");
         echo "<tr>";
         echo "<input type=\"hidden\" name=\"man_model\" value=\"0\">";
         echo "<td style=\"padding-left:20px;\">";
         echo "<input type=\"checkbox\" name=\"man_model\" value=\"1\" " . ($man_model ? 'checked' : '') . "> " . __('Manufacturer') . " & " . __('Model') . "</td>";
         echo "</tr>";

         $status = $this->getConfigurationValue("status");
         echo "<tr>";
         echo "<input type=\"hidden\" name=\"status\" value=\"0\">";
         //  echo "<td> <input type=\"checkbox\" name=\"status\" value=\"1\" ".($status? 'checked':'')."> ".__('Status')."</td>";
         echo "</tr>";
      }

      echo "</table>";

      echo "<input type=\"submit\" value='" . _sx('button', 'Save') . "'>";
      echo "</div>";

      Html::closeForm();

   }

}
