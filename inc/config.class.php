<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

include GLPI_ROOT . "/plugins/reservation/inc/includes.php";

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

   private function getReservationItemsNotCategorised()
   {
      global $DB, $CFG_GLPI;

      $result = [];

     
         global $DB;
 
       $res = [];
       $items = new PluginReservationCategory_Item();
       $table = $items->getTable();
       
 
       
       $query = "SELECT `glpi_reservationitems`.`id`
              FROM `glpi_reservationitems`, `$table`
              WHERE NOT EXISTS 
               (
                  SELECT 1 
                  FROM `$table`
                  WHERE `glpi_reservationitems`.`id` = `$table`.reservationitems_id
              )" ;
 
      //  Toolbox::logInFile('reservations_plugin', "QUERY  : ".$query."\n", $force = false);
 
       if ($result = $DB->query($query)) {
          if ($DB->numrows($result) > 0) {
             while ($row = $DB->fetch_assoc($result)) {
                $res[] = $row;
             }
          }
       }
      
       return $res;




       
      //    if (!($item = getItemForItemtype($itemtype))) {
      //       continue;
      //    }
      //    $itemtable = getTableForItemType($itemtype);
      //    $left = "";
      //    $where = "";

      //    $query = "SELECT `glpi_reservationitems`.`id`,
      //                     `glpi_reservationitems`.`comment`,
      //                     `$itemtable`.`name` AS name,
      //                     `$itemtable`.`entities_id` AS entities_id,
      //                     `glpi_reservationitems`.`items_id` AS items_id
      //              FROM `glpi_reservationitems`
      //              INNER JOIN `$itemtable`
      //                   ON (`glpi_reservationitems`.`itemtype` = '$itemtype'
      //                       AND `glpi_reservationitems`.`items_id` = `$itemtable`.`id`)
      //              $left
      //              WHERE `glpi_reservationitems`.`is_active` = '1'
      //                    AND `glpi_reservationitems`.`is_deleted` = '0'
      //                    AND `$itemtable`.`is_deleted` = '0'
      //                    $where " .
      //       getEntitiesRestrictRequest(
      //          " AND",
      //          $itemtable,
      //          '',
      //          $_SESSION['glpiactiveentities'],
      //          $item->maybeRecursive()
      //       ) . "
      //              ORDER BY `$itemtable`.`entities_id`,
      //                       `$itemtable`.`name` ASC";

      //    if ($res = $DB->query($query)) {
      //       while ($row = $DB->fetch_assoc($res)) {
      //          $result[] = array_merge($row, ['itemtype' => $itemtype]);
      //       }
      //    }
      // }
      // return $result;
   }

   private function showConfigCategoriesForm()
   {
      $currentCategories = PluginReservationCategory::getCategoriesNames();
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
      foreach ($currentCategories as $categoryName) {
         if ($categoryName === "notcategorised") {
            continue;
         }
         $menu .= '<div class="dropper" id="itemsCategory_' . $categoryName . '">';
         $menu .= '<p class="categoryTitle">' . $categoryName . '</p>';
         $menu .= '<div onclick="deleteCategory(\''.$categoryName.'\')" class="categoryClose" >X</div>';

         $menu .= '<input type="hidden" name="category_' . $categoryName . '" value="' . $categoryName . '">';
         $listItemsCategory = PluginReservationCategory_Item::getReservationItemsForCategory($categoryName);
         foreach ($listItemsCategory as $item) {
            $name = PluginReservationCategory_Item::getItemNameFromId($item['id']);
            Toolbox::logInFile('reservations_plugin', "ITEM CATEGORIZED: ".json_encode($name)."\n", $force = false);

            $menu .= '<div class="draggable" id="item_' . $item['id'] . '">' . $name;
            $menu .= '<input type="hidden" name="item_' . $item['id'] . '" value="'.$categoryName.'">';
            $menu .= '</div>';
         }
         $menu .= '</div>';
      }
      $menu .= '</div>';
      $menu .= '</td>';

      $menu .= '<td><div class="dropper" id="itemsCategory_notcategorised">';
      $menu .= '<input type="hidden" name="category_notcategorised" value="notcategorised">';
      // Toolbox::logInFile('reservations_plugin', "TEST ITEMTYPE RESULT : ".json_encode($list)."\n", $force = false);
      $listItemsCategory = PluginReservationCategory_Item::getReservationItemsForCategory("notcategorised");
      foreach ($listItemsCategory as $item) {
         $name = PluginReservationCategory_Item::getItemNameFromId($item['id']);
          Toolbox::logInFile('reservations_plugin', "ITEM NOT CATEGORIZED: ".json_encode($name)."\n", $force = false);

         $menu .= '<div class="draggable" id="item_' . $item['id'] . '">' . $name;
         $menu .= '<input type="hidden" name="item_' . $item['id'] . '" value="notcategorised">';
         $menu .= '</div>';
      }
      $listReservationItemsNotCategorised = $this->getReservationItemsNotCategorised();
      foreach ($listReservationItemsNotCategorised as $item) {
         $name = PluginReservationCategory_Item::getItemNameFromId($item['id']);
          Toolbox::logInFile('reservations_plugin', "ITEM NOT CATEGORIZED: ".json_encode($name)."\n", $force = false);

         $menu .= '<div class="draggable" id="item_' . $item['id'] . '">' . $name;
         $menu .= '<input type="hidden" name="item_' . $item['id'] . '" value="notcategorised">';
         $menu .= '</div>';
      }
      $menu .= '</div>';
      $menu .= '<div style="clear: left;"></div>';
      $menu .= '</td>';
      $menu .= "</tr>";
      $menu .= "</table>";

      return $menu;
   }

   public function applyCategoriesConfig($POST)
   {
      $categories = [];
      $items = [];
      foreach ($POST as $key => $val) {
         if (preg_match('/^item_([0-9])+$/', $key, $match)) { 
            if (array_key_exists($val,$items)) {
               array_push($items[$val],$match[1]);
            }
            else {
               $items[$val] = [];
               array_push($items[$val],$match[1]);
            }
            
         }
         if (preg_match('/^category_([a-zA-Z0-9]+)$/', $key, $match)) {
            array_push($categories, $val);
         }      
      }

      $_SESSION['glpi_use_mode'] && Toolbox::logInFile('reservations_plugin', "TEST ITEM RESULT : ".json_encode($items)."\n", $force = false);
      PluginReservationCategory::updateCategories($categories);
      PluginReservationCategory::updateCategoriesItems($items);

      
   }
}
