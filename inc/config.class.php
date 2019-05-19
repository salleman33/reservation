<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

include_once GLPI_ROOT . "/plugins/reservation/inc/includes.php";

class PluginReservationConfig extends CommonDBTM
{

   public function getConfigurationValue($name, $defaultValue = 0)
   {
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

   public function setConfigurationValue($name, $value = '')
   {
      global $DB;

      if ($value != '') {
         $query = "INSERT INTO glpi_plugin_reservation_configs (name,value) VALUES('" . $name . "','" . $value . "') ON DUPLICATE KEY UPDATE value=Values(value)";
         $DB->query($query) or die($DB->error());
      }
   }

   public function setMailAutomaticAction($value = 1)
   {
      global $DB;

      $query = "UPDATE `glpi_crontasks` SET state='" . $value . "' WHERE name = 'sendMailLateReservations'";
      $DB->query($query) or die($DB->error());
   }


   public function showForm()
   {

      $mode_auto = $this->getConfigurationValue("mode_auto");
      $mode_duration_added = $this->getConfigurationValue("mode_duration_added", 'default');
      $conflict_action = $this->getConfigurationValue("conflict_action");

      echo "<form id=\"formPluginReservationConfigs\" method='post' action='" . $this->getFormURL() . "'>";

      echo "<div class='center'>";

      echo "<table class='tab_cadre_fixe'  cellpadding='2'>";

      echo "<th>" . __('Configuration') . "</th>";

      echo '<tr class="tab_bg_2">';
      echo "<td>";
      echo __('Duration added to reservations expiring and not checkout', "reservation");
      echo "<br>";
      echo __('By defaut, use value of "step for the hours (minutes)" defined in General Setup > Assistance)', "reservation") . " : ";
      echo "<select name=\"mode_duration_added\">";
      echo "<option value=\"default\" " . ($mode_duration_added == 'default' ? 'selected="selected"' : '') . ">" . __('Default', "reservation") . "</option>";
      for ($h = 1; $h <= 8; $h++) {
         echo "<option value=\"" . $h . "\" " . ($mode_duration_added == $h ? 'selected="selected"' : '') . ">" . $h . " " . __('hour', "reservation") . "</option>";
      }
      echo "</select>";
      echo "</td>";
      echo "</tr>";

      echo '<tr class="tab_bg_2">';
      echo "<td>";
      echo __('Method used to send e-mails to users with late reservations', "reservation") . " : ";
      echo "<select name=\"mode_auto\">";
      echo "<option value=\"1\" " . ($mode_auto ? 'selected="selected"' : '') . ">" . __('Automatic', "reservation") . "</option>";
      echo "<option value=\"0\" " . ($mode_auto ? '' : 'selected="selected"') . ">" . __('Manual', "reservation") . "</option>";
      echo "</select>";
      echo "</td>";
      echo "</tr>";

      echo '<tr class="tab_bg_2">';
      echo "<td>";
      echo __('Method used when there is a conflicted reservation', "reservation") . " : ";
      echo "<select name=\"conflict_action\">";
      echo "<option value=\"delete\" " . ($conflict_action == 'delete' ? 'selected="selected"' : '') . ">" . __('Delete the conflicted reservation', "reservation") . "</option>";
      echo "<option value=\"delay\" " . ($conflict_action == 'delay' ? 'selected="selected"' : '') . ">" . __('Delay the start of the conflicted reservation', "reservation") . "</option>";
      echo "</select>";
      echo "</td>";
      echo "</tr>";
      echo "</table>";

      echo "<table class='tab_cadre_fixe'  cellpadding='2'>";
      echo "<th>" . __('Tab Configuration', "reservation") . "</th>";
      $tabcurrent = $this->getConfigurationValue("tabcurrent", 1);
      echo '<tr class="tab_bg_2">';
      echo "<input type=\"hidden\" name=\"tabcurrent\" value=\"0\">";
      echo "<td style=\"padding-left:20px;\">";
      echo "<input type=\"checkbox\" name=\"tabcurrent\" value=\"1\" " . ($tabcurrent ? 'checked' : '') . "> ";
      echo __('Current Reservation tab', "reservation") . "</td>";
      echo "</tr>";

      $tabcoming = $this->getConfigurationValue("tabcoming");
      echo '<tr class="tab_bg_2">';
      echo "<input type=\"hidden\" name=\"tabcoming\" value=\"0\">";
      echo "<td style=\"padding-left:20px;\">";
      echo "<input type=\"checkbox\" name=\"tabcoming\" value=\"1\" " . ($tabcoming ? 'checked' : '') . "> ";
      echo __('Incoming Reservation tab', "reservation") . "</td>";
      echo "</tr>";
      echo "</table>";

      echo "<table class='tab_cadre_fixe'  cellpadding='2'>";
      echo "<th>" . __('Categories Configuration', "reservation") . "</th>";
      $custom_categories = $this->getConfigurationValue("custom_categories", 0);
      echo '<tr class="tab_bg_2">';
      echo "<input type=\"hidden\" name=\"custom_categories\" value=\"0\">";
      echo "<td style=\"padding-left:20px;\">";
      echo "<input type=\"checkbox\" name=\"custom_categories\" value=\"1\" " . ($custom_categories ? 'checked' : '') . "> ";
      echo __('Use custom categories', "reservation") . "</td>";
      echo "</tr>";
      if ($custom_categories) {
         echo '<tr class="tab_bg_2">';
         echo $this->showConfigCategoriesForm();
         echo "</tr>";
      }
      echo "</table>";

      echo "<table class='tab_cadre_fixe'  cellpadding='2'>";
      echo "<th>" . __('ToolTip Configuration', "reservation") . "</th>";
      $tooltip = $this->getConfigurationValue("tooltip");
      echo '<tr class="tab_bg_2">';
      echo "<input type=\"hidden\" name=\"tooltip\" value=\"0\">";
      echo "<td> ";
      echo "<input type=\"checkbox\" name=\"tooltip\" value=\"1\" " . ($tooltip ? 'checked' : '') . "> ";
      echo __('ToolTip', "reservation") . "</td>";
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
         echo "<td style=\"padding-left:20px;\">";
         echo "<input type=\"checkbox\" name=\"status\" value=\"1\" " . ($status ? 'checked' : '') . "> " . __('Status') . "</td>";
         echo "</tr>";
      }
      echo "</table>";
      echo "<input class=\"submit\" type=\"submit\" value='" . _sx('button', 'Save') . "'>";
      echo "</div>";

      Html::closeForm();
   }

   private function showConfigCategoriesForm()
   {
      $config_categories = PluginReservationCategory::getCategoriesConfig();
      $menu = "<table class='tab_cadre_fixe'  cellpadding='2'>";
      $menu .= "<input type=\"hidden\" name=\"configCategoriesForm\">";
      $menu .= "<th colspan=\"2\">" . __('Categories customisation', "reservation") . "</th>";
      $menu .= '<tr>';
      $menu .= "<td>" . __('Make your own category !', "reservation") . "</td>";
      $menu .= "<td>" . __('Drag and drop items on a custom category :', "reservation") . "</td>";
      $menu .= "</tr>";
      $menu .= '<tr>';

      $menu .= '<td>';
      $menu .= '<input class="noEnterSubmit" onkeydown="createCategoryEnter()" type="text" id="newCategoryTitle" size="15"  title="Please enter a type">';
      $menu .= '<button type="button" onclick="createCategory()">' . _sx('button', 'create', "reservation") . '</button>';
      $menu .= '<div style="clear: left;" id="categoriesContainer">';

      
      foreach ($config_categories as $category_name => $category_items) {  
         $it = 0;     
         if ($category_name === "notcategorized") {
            continue;
         }
         $menu .= $this->openCategoryHtml($category_name, $category_name);
         foreach ($category_items as $id) {
            $it++;
            $menu .= $this->makeItemHtml($id, $category_name, $it);
         }
         $menu .= $this->closeCategoryHtml();
      }
      $menu .= '</div>';
      $menu .= '</td>';
      $menu .= '<td>';

      $menu .= $this->openCategoryHtml('notcategorized', __('Reservable item'), false);
      if (array_key_exists('notcategorized', $config_categories)) {
         $it = 0;
         foreach ($config_categories['notcategorized'] as $id) {
            $it++;
            $menu .= $this->makeItemHtml($id, 'notcategorized',$it);
         }
      }
      $menu .= $this->closeCategoryHtml();

      $menu .= '<div style="clear: left;"></div>';
      $menu .= '</td>';
      $menu .= "</tr>";
      $menu .= "</table>";

      return $menu;
   }

   private function makeItemHtml($item_id, $category_name, $index)
   {
      $name = PluginReservationCategory_Item::getItemNameFromId($item_id);
      $comment = PluginReservationCategory_Item::getItemCommentFromId($item_id);
      $html = '<tr class="draggable" id="item_' . $item_id . '">';
      $html .= '<input type="hidden" name="item_' . $item_id . '" value="' . $category_name . '">';      
      $html .= '<td>' . $name. '</td>';
      $html .= '<td>' . nl2br($comment) . '</td>';
      $html .= '<td class="index">' . $index . '</td>';
      $html .= '</tr>';
      return $html;
   }

   private function openCategoryHtml($category_name, $category_title, $deletable = true)
   {
      $html = '<table class="dropper" id="itemsCategory_' . $category_name . '">';
      $html .= '<thead>';
      $html .= '<th colspan="3" class="categoryTitle">' . $category_title . '</th>';
      $deletable && $html .= '<td onclick="deleteCategory(\'' . $category_name . '\')" class="categoryClose" >X</td>';
      $html .= '</thead>';      
      $html .= '<input type="hidden" name="category_' . $category_name . '" value="' . $category_name . '">';
      $html .= '<tbody>';
      return $html;
   }

   private function closeCategoryHtml() {
      $html = '</tbody>';
      $html .= "</table>";
      return $html;
   }
}
