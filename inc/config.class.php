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
            while ($row = $DB->fetchAssoc($result)) {
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

      

      echo "<form id=\"formPluginReservationConfigs\" method='post' action='" . $this->getFormURL() . "'>";

      echo "<div class='center'>";

      echo "<table class='tab_cadre_fixe'  cellpadding='2'>";

      echo "<th>" . __('Configuration') . "</th>";

      // extension time
      $extension_time = $this->getConfigurationValue("extension_time", 'default');
      echo '<tr class="tab_bg_2">';
      echo "<td>";
      echo __('Duration added (in hour) to reservations expiring and not checkout', "reservation");
      echo "<br>";
      echo __('By defaut, use value of <b>step for the hours</b> defined in <I>General Setup > Assistance</I>', "reservation") . " : ";
      echo "<select name=\"extension_time\">";
      echo "<option value=\"default\" " . ($extension_time == 'default' ? 'selected="selected"' : '') . ">" . __('Default', "reservation") . "</option>";
      for ($h = 1; $h <= 24; $h++) {
         echo "<option value=\"" . $h . "\" " . ($extension_time == $h ? 'selected="selected"' : '') . ">" . $h . " </option>";
      }
      echo "</select>";
      echo "</td>";
      echo "</tr>";

      // mode_auto for mailing
      $mode_auto = $this->getConfigurationValue("mode_auto");
      echo '<tr class="tab_bg_2">';
      echo "<td>";
      echo __('Method used to send e-mails to users with late reservations', "reservation") . " : ";
      echo "<select name=\"mode_auto\">";
      echo "<option value=\"1\" " . ($mode_auto ? 'selected="selected"' : '') . ">" . __('Automatic', "reservation") . "</option>";
      echo "<option value=\"0\" " . ($mode_auto ? '' : 'selected="selected"') . ">" . __('Manual', "reservation") . "</option>";
      echo "</select>";
      echo "</td>";
      echo "</tr>";

      // conflicted reservation
      $conflict_action = $this->getConfigurationValue("conflict_action");
      echo '<tr class="tab_bg_2">';
      echo "<td>";
      echo __('Method used when there is a conflicted reservation', "reservation") . " : ";
      echo "<select name=\"conflict_action\">";
      echo "<option value=\"delete\" " . ($conflict_action == 'delete' ? 'selected="selected"' : '') . ">" . __('Delete the conflicted reservation', "reservation") . "</option>";
      echo "<option value=\"delay\" " . ($conflict_action == 'delay' ? 'selected="selected"' : '') . ">" . __('Delay the start of the conflicted reservation', "reservation") . "</option>";
      echo "</select>";
      echo "</td>";
      echo "</tr>";

      // checkin
      $checkin = $this->getConfigurationValue("checkin", 0);
      $checkin_timeout = $this->getConfigurationValue("checkin_timeout", 1);
      $checkin_action = $this->getConfigurationValue("checkin_action", '2');
      echo '<tr class="tab_bg_2">';
      echo "<td style=\"padding-left:20px;\">";      
      echo "<input type=\"hidden\" name=\"checkin\" value=\"0\">";      
      echo "<input onclick=\"javascript:afficher_cacher_simple('checkin_config');\" type=\"checkbox\" name=\"checkin\" value=\"1\" " . ($checkin ? 'checked' : '') . "> ";
      echo __('Enable check in', "reservation"). "</td>";
      echo '</tr>';

      // checkin action
      echo '<tr class="tab_bg_2">';
      echo "<td>";
      if ($checkin) {
         echo '<table id="checkin_config">';
      } else {
         echo '<table id="checkin_config" style="display:none;" >';
      }
      echo '<tr>';
      echo "<td>";
      echo __('Action when reservation are not checkin', "reservation") . " : ";
      echo "<select name=\"checkin_action\">";
      echo "<option value=\"0\" " . ($checkin_action == '0' ? 'selected="selected"' : '') . ">" . __('Do Nothing', "reservation") . "</option>";
      echo "<option value=\"1\" " . ($checkin_action == '1' ? 'selected="selected"' : '') . ">" . __('Warn', "reservation") . "</option>";
      echo "<option value=\"2\" " . ($checkin_action == '2' ? 'selected="selected"' : '') . ">" . __('Warn and delete unclaimed reservation', "reservation") . "</option>";
      echo "</select>";
      echo "</td>";
      echo "</tr>";

      // checkin timeout
      echo '<tr>';
      echo "<td>";
      echo __('Waiting time (in hour) to cancel the unclaimed reservation', "reservation") . " : ";
      echo "<select name=\"checkin_timeout\">";
      for ($h = 1; $h <= 24; $h++) {
         echo "<option value=\"" . $h . "\" " . ($checkin_timeout == $h ? 'selected="selected"' : '') . ">" . $h . " </option>";
      }
      echo "</select>";
      echo '</td>'; 
      echo "</tr>";      
      echo '</table>';
      echo "</table>";      

      // define tabs
      echo "<table class='tab_cadre_fixe'  cellpadding='2'>";
      echo "<th>" . __('Tab Configuration', "reservation") . "</th>";
      // current reservation tab
      $tabcurrent = $this->getConfigurationValue("tabcurrent", 1);
      echo '<tr class="tab_bg_2">';
      echo "<input type=\"hidden\" name=\"tabcurrent\" value=\"0\">";
      echo "<td style=\"padding-left:20px;\">";
      echo "<input type=\"checkbox\" name=\"tabcurrent\" value=\"1\" " . ($tabcurrent ? 'checked' : '') . "> ";
      echo __('Current Reservation tab', "reservation") . "</td>";
      echo "</tr>";
      // incoming reservation tab
      $tabcoming = $this->getConfigurationValue("tabcoming");
      echo '<tr class="tab_bg_2">';
      echo "<input type=\"hidden\" name=\"tabcoming\" value=\"0\">";
      echo "<td style=\"padding-left:20px;\">";
      echo "<input type=\"checkbox\" name=\"tabcoming\" value=\"1\" " . ($tabcoming ? 'checked' : '') . "> ";
      echo __('Incoming Reservation tab', "reservation") . "</td>";
      echo "</tr>";
      echo "</table>";

      // custom categories
      echo "<table class='tab_cadre_fixe'  cellpadding='2'>";
      echo "<th>" . __('Categories Configuration', "reservation") . "</th>";
      $custom_categories = $this->getConfigurationValue("custom_categories", 0);
      echo '<tr class="tab_bg_2">';
      echo "<input type=\"hidden\" name=\"custom_categories\" value=\"0\">";
      echo "<td style=\"padding-left:20px;\">";
      echo "<input onclick=\"javascript:afficher_cacher_simple('custom_categories_view');\" type=\"checkbox\" name=\"custom_categories\" value=\"1\" " . ($custom_categories ? 'checked' : '') . "> ";
      echo __('Use custom categories', "reservation") . "</td>";
      echo "</tr>";
      if ($custom_categories) {
         $use_items_types = $this->getConfigurationValue("use_items_types", 0);
         echo '<tr class="tab_bg_2">';
         echo "<input type=\"hidden\" name=\"use_items_types\" value=\"0\">";
         echo "<td style=\"padding-left:20px;\">";
         echo "<input onclick=\"javascript:afficher_cacher_simple('use_items_types_view');\" type=\"checkbox\" name=\"use_items_types\" value=\"1\" " . ($use_items_types ? 'checked' : '') . "> ";
         echo __('Use items types when not in a custom category', "reservation") . "</td>";
         echo "</tr>";
         echo '<tr class="tab_bg_2" id="use_items_types">';
         echo $this->showConfigCategoriesForm();
         echo "</tr>";
      } 
      echo "</table>";

      // tooltip
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
      $menu .= '<button type="button" onclick="createCategory()">' . _sx('button', 'Add') . '</button>';
      $menu .= '<div style="clear: left;" id="categoriesContainer">';

      
      $categories_names = PluginReservationCategory::getCategoriesNames();
      $all_reservation_items = PluginReservationCategory::getReservationItems('', '', false, [ "filter_is_active" => false ]);
      foreach ($categories_names as $category_name ) {
         $filtered_array = array_filter($all_reservation_items,
            function ($element) use ($category_name) {
               return ($element['category_name'] == $category_name);
            } );

         $it = 0;     
         if ($category_name === "zzpluginnotcategorized") {
            continue;
         }
         $menu .= $this->openCategoryHtml($category_name, $category_name);
         foreach ($filtered_array as $reservation_item) {
            $it++;
            $menu .= $this->makeItemHtml($reservation_item, $category_name, $it);
         }
         $menu .= $this->closeCategoryHtml();
      }
      $menu .= '</div>';
      $menu .= '</td>';
      $menu .= '<td>';

      $menu .= $this->openCategoryHtml('zzpluginnotcategorized', '', false);
      // if (in_array('zzpluginnotcategorized', $categories_names)) {
         $filtered_array = array_filter($all_reservation_items,
            function ($element) {
               return ($element['category_name'] === 'zzpluginnotcategorized' || is_null($element['category_name']));
            } );
         $it = 0;
         foreach ($filtered_array as $reservation_item) {
            $it++;
            $menu .= $this->makeItemHtml($reservation_item, 'zzpluginnotcategorized',$it);
         }
      // }
      $menu .= $this->closeCategoryHtml();

      $menu .= '<div style="clear: left;"></div>';
      $menu .= '</td>';
      $menu .= "</tr>";
      $menu .= "</table>";

      return $menu;
   }

   /**
    * make html code for an item 
    * @param hash $reservation_item item 
    * @param string $category_name name of the category item
    * @param integer $index index of the item 
    * @return string code html
    */
   private function makeItemHtml($reservation_item, $category_name, $index)
   {
      $html = '<tr class="draggable" ' . ($reservation_item['is_active'] == '1' ? '' : 'style="background-color:#f36647 "') . ' id="item_' . $reservation_item['id'] . '">';
      $html .= '<input type="hidden" name="item_' . $reservation_item['id'] . '" value="' . $category_name . '">';      
      $html .= '<td>' . $reservation_item['name']. '</td>';
      $html .= '<td>' . nl2br($reservation_item['comment']) . '</td>';
      $html .= '<td class="index">' . $index . '</td>';
      $html .= '</tr>';
      return $html;
   }

   /**
    * make html code to open category 
    * @param string $category_name name of this category 
    * @param string $category_name title displayed 
    * @param boolean $deletable category is deletable or not
    * @return string code html
    */
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

   /**
    * make html code to close category
    * @return string code html
    */
   private function closeCategoryHtml() {
      $html = '</tbody>';
      $html .= "</table>";
      return $html;
   }
}
