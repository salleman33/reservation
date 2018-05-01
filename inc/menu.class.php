<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

// function getLinkforItem($item) {
//    $itemLink = $item->getFormUrl();
//    $argConcatenator = "?";
//    if (strpos($itemLink, '?') !== false) {
//       $argConcatenator = "&amp;";
//    }
//    echo "<a target='_blank' href='" . $itemLink . $argConcatenator . "id=" . $item->fields["id"] . "'>" . $item->fields["name"] . "</a>";
// }

function getToolTipforItem($item) {
   $config = new PluginReservationConfig();

   $show_toolTip = $config->getConfigurationValue("tooltip");
   if (!$show_toolTip) {
      return;
   }

   $show_comment = $config->getConfigurationValue("comment");

   $show_location = $config->getConfigurationValue("location");
   $show_serial = $config->getConfigurationValue("serial");
   $show_inventory = $config->getConfigurationValue("inventory");
   $show_group = $config->getConfigurationValue("group");
   $show_man_model = $config->getConfigurationValue("man_model");
   $show_status = $config->getConfigurationValue("status");
   $toolTip = "";
   if ($show_comment && array_key_exists("comment", $item->fields)) {
      $toolTip .= $item->fields["comment"];
   }
   if ($show_location && array_key_exists("locations_id", $item->fields)) {
      $location = getLocationFromItem($item);
      $toolTip .= "<br><b>" . __('Location') . " : </b>" . $location;
   }
   if ($show_group && array_key_exists("groups_id", $item->fields)) {
      $group_name = getGroupFromItem($item);
      $toolTip .= "<br><b>" . __('Group') . " : </b>" . $group_name;
   }
   if ($show_inventory && array_key_exists("otherserial", $item->fields)) {
      $toolTip .= "<br><b>" . __('Inventory number') . " : </b>" . $item->fields["otherserial"];
   }
   if ($show_serial && array_key_exists("serial", $item->fields)) {
      $toolTip .= "<br><b>" . __('Serial number') . " : </b>" . $item->fields["serial"];
   }
   if ($show_man_model) {
      $typemodel = getModelFromItem($item);
      $manufacturer = getManufacturerFromItem($item);
      $toolTip .= "<br><b>" . __('Manufacturer') . " & " . __('Model') . " : </b>" . $manufacturer . " | " . $typemodel;
   }
   if ($show_status) {
      $status = getStatusFromItem($item);
      $toolTip .= "<br><b>" . __('Status')  . " : </b>" . $status;
   }
   $tooltip = nl2br($toolTip);
   Html::showToolTip($tooltip, null);
}

function getGroupFromItem($item) {
   $group_id = $item->fields["groups_id"];
   $group_tmp = new Group();
   $group_tmp->getFromDB($group_id);
   return $group_tmp->getName();
}

function getLocationFromItem($item) {
   $location_id = $item->fields["locations_id"];
   $location_tmp = new Location();
   $location_tmp->getFromDB($location_id);
   return $location_tmp->getName();
}

function getStatusFromItem($item) {
   $states_id = $item->fields["states_id"];
   $states_tmp = new Location();
   $states_tmp->getFromDB($states_id);
   return $states_tmp->getName();
}

function getManufacturerFromItem($item) {
   $manufacturer_id = $item->fields["manufacturers_id"];
   $manufacturer_tmp = new Manufacturer();
   $manufacturer_tmp->getFromDB($manufacturer_id);
   return $manufacturer_tmp->getName();
}

function getModelFromItem($item) {
   global $DB;
   $typemodel = "N/A";
   $modeltable = getSingular($item->getTable()) . "models";
   if ($modeltable) {
      $modelfield = getForeignKeyFieldForTable($modeltable);
      if ($modelfield) {
         if (!$item->isField($modelfield)) {
            // field not found, trying other method
            $modeltable = substr($item->getTable(), 0, -1) . "models";
            $modelfield = getForeignKeyFieldForTable($modeltable);
         }
         if ($DB->tableExists($modeltable)) {
            $query = "SELECT `$modeltable`.`name` AS model
            FROM `$modeltable` WHERE
            `$modeltable`.`id` = " . $item->fields[$modelfield];
            if ($resmodel = $DB->query($query)) {
               while ($rowModel = $DB->fetch_assoc($resmodel)) {
                  $typemodel = $rowModel["model"];
               }
            }
         }
      }

   }
   return $typemodel;
}



class PluginReservationMenu extends CommonGLPI
{

   public static function getTypeName($nb = 0) {
      return PluginReservationReservation::getTypeName($nb);
   }

   public static function getMenuName() {
      return PluginReservationMenu::getTypeName(2);
   }

   public static function canCreate() {
      return Reservation::canCreate();
   }

   public static function canDelete() {
      return Reservation::canDelete();
   }

   public static function canUpdate() {
      return Reservation::canUpdate();
   }

   public static function canView() {
      return Reservation::canView();
   }

   static function getForbiddenActionsForMenu() {
      return ['add','template'];
   }

   public function defineTabs($options = []) {
      $ong = [];
      $this->addStandardTab(__CLASS__, $ong, $options);
      return $ong;
   }

   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      $ong = [];
      $config = new PluginReservationConfig();
      $i = 1;
      if ($config->getConfigurationValue("tabcurrent", 1)) {
         $ong[$i] = __('Current Reservations', "reservation");
         $i++;
      }
      if ($config->getConfigurationValue("tabcoming")) {
         $ong[$i] = __('Current and Incoming Reservations', "reservation");
         $i++;
      }
      $ong[$i] = __('Available Hardware', "reservation");
      return $ong;
   }

   public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      $config = new PluginReservationConfig();
      $tabcoming = $config->getConfigurationValue("tabcoming");
      switch ($tabnum) {
         case 1:
            self::displayTabContentForCurrentReservations($item);
            break;
         case 2:
            if ($tabcoming) {
               self::displayTabContentForAllReservations($item);
            } else {
               self::displayTabContentForAvailableHardware($item);
            }
            break;
         case 3:
            self::displayTabContentForAvailableHardware($item);
            break;
      }
      return true;
   }


   static function getDateFormat() {
      $format = $_SESSION["glpidate_format"];

      switch ($format) {
         case '0':
         return 'Y-m-d';
         break;
         case '1':
         return 'd-m-Y';
         break;
         case '2':
         return 'm-d-y';
         break;
      }
   }

   /**
    *
    */
   public static function arrayGroupBy($array, $element) {
      $result = [];
      foreach ($array as $one) {
         $result[$one[$element]][] = $one;
      }
      return $result;
   }

   /**
    *
    */
   public static function displayTabContentForCurrentReservations() {
      $form_dates = $_SESSION['glpi_plugin_reservation_form_dates'];
      $begin = $form_dates["begin"];
      $end = $form_dates["end"];

      $filters = [ "'".$begin."' < `end`", "'".$end."' > `begin`"];
      $list = PluginReservationReservation::getAllReservations($filters);
      $ReservationsByUser = self::arrayGroupBy($list, 'users_id');
      ksort($ReservationsByUser);
      Toolbox::logInFile('sylvain', "reservations_list : ".json_encode($list)."\n", $force = false);

      self::displayTabReservations($begin, $end, $ReservationsByUser, false);
   }

   /**
    *
    */
   public static function displayTabContentForAllReservations() {
      $form_dates = $_SESSION['glpi_plugin_reservation_form_dates'];
      $begin = $form_dates["begin"];
      $end = $form_dates["end"];

      $filters = [ "'".$begin."' < `end`"];
      $list = PluginReservationReservation::getAllReservations($filters);
      $ReservationsByUser = self::arrayGroupBy($list, 'users_id');
      ksort($ReservationsByUser);

      self::displayTabReservations($begin, $end, $ReservationsByUser, true);
   }

   /**
    *
    */
   private static function displayTabReservations($begin, $end, $ReservationsByUser, $includeFuture) {
      global $DB;

      $showentity = Session::isMultiEntitiesMode();
      $config = new PluginReservationConfig();
      $mode_auto = $config->getConfigurationValue("mode_auto");

      echo "<div class='center'>";
      echo "<table class='tab_cadre'>";
      echo "<thead>";
      if ($includeFuture) {
         echo "<tr><th colspan='" . ($mode_auto ? "10" : "11") . "'>" . __('Current and future reservations') . "</th></tr>\n";
      } else {
         echo "<tr><th colspan='" . ($mode_auto ? "10" : "11") . "'>" . __('Reservations in the selected timeline') . "</th></tr>\n";
      }
      echo "<tr class='tab_bg_2'>";
      echo "<th>" . __('User') . "</a></th>";
      echo "<th colspan='2'>" . __('Item') . "</a></th>";
      echo "<th>" . __('Begin') . "</a></th>";
      echo "<th>" . __('End') . "</a></th>";
      echo "<th>" . __('Comment') . "</a></th>";
      echo "<th>" . __('Moves', 'reservation') . "</a></th>";
      echo "<th>" . __('Checkout', 'reservation') . "</th>";
      echo "<th colspan='" . ($mode_auto ? 2 : 3) . "'>" . __('Action') . "</th>";

      echo "</tr></thead>";
      echo "<tbody>";

      foreach ($ReservationsByUser as $reservation_user => $reservations_user_list) {
         usort($reservations_user_list, function ($a, $b) {
            return strnatcmp($a['begin'], $b['begin']);
         });
         $user = new User();
         $user->getFromDB($reservation_user);

         echo "<tr class='tab_bg_2'>";
         echo "<td colspan='100%' bgcolor='lightgrey' style='padding:1px;'/>";
         echo "</tr>";

         echo "<tr class='tab_bg_2'>";
         // user name
         $formatName = formatUserName($user->fields["id"], $user->fields["name"], $user->fields["realname"], $user->fields["firstname"]);
         echo "<td rowspan=" . count($reservations_user_list) . ">" . $formatName . "</td>";

         $count = 0;
         $rowspan_end = 1;
         foreach ($reservations_user_list as $reservation_user_info) {
            $count++;

            $reservation = new Reservation();
            $reservation->getFromDB($reservation_user_info['reservations_id']);
            $reservationitems = $reservation->getConnexityItem('reservationitem', 'reservationitems_id');
            $item = $reservationitems->getConnexityItem($reservationitems->fields['itemtype'], 'items_id');

            $color = "";
            if ($reservation_user_info["begin"] > $end) {
               $color = "bgcolor=\"lightgrey\"";
            }
            if ($reservation_user_info['baselinedate'] < $end && $reservation_user_info['baselinedate'] < date("Y-m-d H:i:s", time()) && $reservation_user_info['effectivedate'] == null) {
               $color = "bgcolor=\"red\"";
            }

            // item
            echo "<td $color>";
            echo Html::link($item->fields['name'], $item->getFormURLWithID($item->fields['id']));
            echo "</td>";
            echo "<td $color>";
            getToolTipforItem($item);
            echo "</td>";

            $rowspan_line = 1;
            if ($count == $rowspan_end) {
               $i = $count;
               while ($i < count($reservations_user_list)) {
                  if ($reservations_user_list[$i]['begin'] == $reservation_user_info['begin']
                   && $reservations_user_list[$i]['end'] == $reservation_user_info['end']) {
                     $rowspan_line++;
                  } else {
                     break;
                  }
                  $i++;
               }
               if ($rowspan_line > 1) {
                  $rowspan_end = $count + $rowspan_line;
               } else {
                  $rowspan_end++;
               }

               // date begin
               echo "<td rowspan=" . $rowspan_line . " $color>".date(self::getDateFormat()." \à H:i:s", strtotime($reservation->fields['begin']))."</td>";
               // date end
               echo "<td rowspan=" . $rowspan_line . " $color>".date(self::getDateFormat()." \à H:i:s", strtotime($reservation_user_info['baselinedate']))."</td>";

               // comment
               echo "<td rowspan=" . $rowspan_line . " $color>".$reservation->fields['comment']."</td>";

               // moves
               echo "<td rowspan=" . $rowspan_line . " ><center>";
               if (date("Y-m-d", strtotime($reservation->fields['begin'])) == date("Y-m-d", strtotime($begin))) {

                  echo "<img title=\"\" alt=\"\" src=\"../pics/up-icon.png\"></img>";
               }
               if (date("Y-m-d", strtotime($reservation_user_info['baselinedate'])) == date("Y-m-d", strtotime($end))) {
                  echo "<img title=\"\" alt=\"\" src=\"../pics/down-icon.png\"></img>";
               }
               echo "</center></td>";
            }

            // checkout buttons or date checkout
            if ($reservation_user_info['effectivedate'] != null) {
               echo "<td>" . date(self::getDateFormat()." \à H:i:s", strtotime($reservation_user_info['effectivedate'])) . "</td>";
            } else {
               echo "<td><center><a href=\"".Toolbox::getItemTypeSearchURL(__CLASS__)."?checkout=" . $reservation_user_info['reservations_id'] . "\"><img title=\"" . _sx('tooltip', 'Set As Returned', "reservation") . "\" alt=\"\" src=\"../pics/greenbutton.png\"></img></a></center></td>";
            }

            // action
            $available_reservationsitem = PluginReservationReservation::getAvailablesItems($reservation->fields['begin'], $reservation->fields['end']);
            echo "<td>";
            echo "<ul>";

            // add item
            echo "<li><span class=\"bouton\" id=\"bouton_add" . $reservation_user_info['reservations_id'] . "\" onclick=\"javascript:afficher_cacher('add" . $reservation_user_info['reservations_id'] . "');\">" . _sx('button', 'Add an item') . "</span>
            <div id=\"add" . $reservation_user_info['reservations_id'] . "\" style=\"display:none;\">
            <form method='POST' name='form' action='" . Toolbox::getItemTypeSearchURL(__CLASS__) . "'>";
            echo '<select name="add_item">';
            foreach ($available_reservationsitem as $item) {
               echo "\t", '<option value="', $item['id'], '">', getItemForItemtype($item['itemtype'])->getTypeName() .' - '.$item["name"], '</option>';
            }

            echo "<input type='hidden' name='add_item_to_reservation' value='" . $reservation_user_info['reservations_id'] . "'>";
            echo "<input type='submit' class='submit' name='add' value=" . _sx('button', 'Add') . ">";
            Html::closeForm();
            echo "</div></li>";

            // switch item
            echo "<li><span class=\"bouton\" id=\"bouton_replace" . $reservation_user_info['reservations_id'] . "\" onclick=\"javascript:afficher_cacher('replace" . $reservation_user_info['reservations_id'] . "');\">" . _sx('button', 'Replace an item') . "</span>
            <div id=\"replace" . $reservation_user_info['reservations_id'] . "\" style=\"display:none;\">
            <form method='post' name='form' action='" . Toolbox::getItemTypeSearchURL(__CLASS__) . "'>";
            echo '<select name="switch_item">';
            foreach ($available_reservationsitem as $item) {
               echo "\t", '<option value="', $item['id'], '">', getItemForItemtype($item['itemtype'])->getTypeName() .' - '.$item["name"], '</option>';
            }
            echo "<input type='hidden' name='switch_item_to_reservation' value='" . $reservation_user_info['reservations_id'] . "'>";
            echo "<input type='submit' class='submit' name='submit' value=" . _sx('button', 'Save') . ">";
            Html::closeForm();
            echo "</div></li>";
            echo "</ul>";
            echo "</td>";

            echo "<td>";
            echo "<ul>";
            echo "<li><a class=\"bouton\" title=\"".__('Edit')."\" href='".Toolbox::getItemTypeFormURL('Reservation')."?id=" . $reservation_user_info['reservations_id'] . "'>" . _sx('button', 'Edit') . "</a></li>";
            echo "</ul>";
            echo "</td>";

            if (!$mode_auto) {
               echo "<td>";
               echo "<ul>";
               if ($reservation_user_info['baselinedate'] < date("Y-m-d H:i:s", time()) && $reservation_user_info['effectivedate'] == null) {
                  echo "<li><a class=\"bouton\" title=\"" . _sx('tooltip', 'Send an e-mail for the late reservation', "reservation") . "\" href=\"".Toolbox::getItemTypeSearchURL(__CLASS__)."?mailuser=" . $reservation_user_info['reservations_id'] . "\">" . _sx('button', 'Send an e-mail', "reservation") . "</a></li>";
                  if (isset($reservation_user_info['mailingdate'])) {
                     echo "<li>" . __('Last e-mail sent on', "reservation") . " </li>";
                     echo "<li>" . date(self::getDateFormat()." \à H:i:s", strtotime($reservation_user_info['mailingdate'])) . "</li>";
                  }
               }
               echo "</ul>";
               echo "</td>";
            }

            echo "</tr>";
            echo "<tr class='tab_bg_2'>";
         }
         echo "</tr>";
      }

      echo "</tbody>";
      echo "</table>";
      echo "</div>";
   }

   /**
    *
    */
   public static function displayTabContentForAvailableHardware() {
      global $DB, $CFG_GLPI;
      $showentity = Session::isMultiEntitiesMode();
      $config = new PluginReservationConfig();
      $form_dates = $_SESSION['glpi_plugin_reservation_form_dates'];

      $begin = $form_dates["begin"];
      $end = $form_dates["end"];

      $available_reservationsitem = PluginReservationReservation::getAvailablesItems($begin, $end);

      echo "<div class='center'>\n";
      echo "<form name='form' method='GET' action='".Reservation::getFormURL()."'>\n";
      echo "<table class='tab_cadre' style=\"border-spacing:20px;\">\n";
      echo "<tr>";

      $reservations = $CFG_GLPI["reservation_types"];

      foreach ($CFG_GLPI["reservation_types"] as $itemtype) {

         $filtered_array = array_filter($available_reservationsitem,
            function ($element) use ($itemtype) {
               return ($element['itemtype'] == $itemtype);
            } );

         if (!$filtered_array) {
            continue;
         }
         echo "<td valign=\"top\">";

         $item = getItemForItemtype($itemtype);
         echo "\n\t<table class='tab_cadre'>";
         echo "<tr><th colspan='" . ($showentity ? "6" : "5") . "'>" . $item->getTypeName() . "</th></tr>\n";
         foreach ($filtered_array as $reservation_item) {
            $item->getFromDB($reservation_item['items_id']);
            echo "<td>";
            echo HTML::getCheckbox([
               'name' => "item[" . $reservation_item["id"] . "]",
               "value" => $reservation_item["id"],
               "zero_on_empty" => false
            ]);
            echo "</td>";

            echo "<td>";
            echo Html::link($item->fields['name'], $item->getFormURLWithID($item->fields['id']));
            echo "</td>";
            echo "<td>" . nl2br($reservation_item['comment']) . "</td>";

            if ($showentity) {
               echo "<td>".Dropdown::getDropdownName("glpi_entities", $reservation_item["entities_id"])."</td>";
            }

            echo "<td>";
            getToolTipforItem($item);
            echo "</td>";

            echo "<td><a title=\"Show Calendar\" href='../../../front/reservation.php?reservationitems_id=" . $reservation_item['id'] . "'>" . "<img title=\"\" alt=\"\" src=\"../../../pics/reservation-3.png\"></a></td>";
            echo "</tr>\n";
         }
         echo "</table>\n";
         echo "</td>";
      }

      echo "</tr>";
      echo "<tr class='tab_bg_1 center'><td colspan='" . ($showentity ? "5" : "4") . "'>";
      echo "<input type='submit' value='" . __('Create new reservation', "reservation") . "' class='submit'></td></tr>\n";

      echo "</table>\n";

      echo "<input type='hidden' name='id' value=''>";
      echo "<input type='hidden' name='begin' value='" . $begin . "'>";
      echo "<input type='hidden' name='end' value='" . $end . "'>";
      Html::closeForm();
      echo "</div>";
   }

   /**
    *
    */
   public function getFormDates() {
      $form_dates = [];

      if (isset($_SESSION['glpi_plugin_reservation_form_dates'])) {
         $form_dates = $_SESSION['glpi_plugin_reservation_form_dates'];
      }

      $day = date("d", time());
      $month = date("m", time());
      $year = date("Y", time());
      $begin_time = time();

      $form_dates["begin"] = date("Y-m-d H:i:s", $begin_time);
      $form_dates['end'] = date("Y-m-d H:i:s", mktime(23, 59, 59, $month, $day, $year));

      if (isset($_POST['date_begin'])) {
         $form_dates["begin"] = $_POST['date_begin'];
      }
      if (isset($_GET['date_begin'])) {
         $form_dates["begin"] = $_GET['date_begin'];
      }

      if (isset($_POST['date_end'])) {
         $form_dates["end"] = $_POST['date_end'];
      }
      if (isset($_GET['date_end'])) {
         $form_dates["end"] = $_GET['date_end'];
      }
      if (isset($_POST['nextday']) || isset($_GET['nextday'])) {
         $form_dates["begin"] = date("Y-m-d H:i:s", strtotime($form_dates["begin"]) + DAY_TIMESTAMP);
         $form_dates["end"] = date("Y-m-d H:i:s", strtotime($form_dates["end"]) + DAY_TIMESTAMP);
      }
      if (isset($_POST['previousday']) || isset($_GET['previousday'])) {
         $form_dates["begin"] = date("Y-m-d H:i:s", strtotime($form_dates["begin"]) - DAY_TIMESTAMP);
         $form_dates["end"] = date("Y-m-d H:i:s", strtotime($form_dates["end"]) - DAY_TIMESTAMP);
      }

      $_SESSION['glpi_plugin_reservation_form_dates'] = $form_dates;
   }


   /**
   * Display the form with begin and end dates, next day, previous day, etc.
   */
   public function showFormDate() {
      $this->getFormDates();

      $form_dates = $_SESSION['glpi_plugin_reservation_form_dates'];

      echo "<div id='viewresasearch'  class='center'>";
      echo "<table class='tab_cadre' style='background-color:transparent;box-shadow:none'>";

      echo "<tr>";
      echo "<td>";
      $this->showCurrentMonthForAllLink();
      echo "</td>";
      echo "<td>";

      echo "<form method='post' name='form' action='" . Toolbox::getItemTypeSearchURL(__CLASS__) . "'>";
      echo "<table class='tab_cadre'><tr class='tab_bg_2'>";
      echo "<th colspan='6'>" . __('Date') . "</th>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";

      echo "<td rowspan='3'>";
      echo "<input type='submit' class='submit' name='previousday' value='" . __('Previous') . "'>";
      echo "</td>";

      echo "<td>" . __('Start date') . "</td><td>";
      Html::showDateTimeField('date_begin', ['value' => $form_dates["begin"], 'maybeempty' => false]);
      echo "</td><td rowspan='3'>";
      echo "<input type='submit' class='submit' name='submit' value=\"" . _sx('button', 'Search') . "\">";
      echo "</td>";
      echo "<td rowspan='3'>";
      echo "<input type='submit' class='submit' name='nextday' value='" . __('Next') . "'>";
      echo "</td>";
      echo "<td rowspan='3'>";
      echo '<a class="fa fa-undo reset-search" href="'. Toolbox::getItemTypeSearchURL(__CLASS__) . '?reset=reset" title="'.__('Reset').'"><span class="sr-only">'.__('Reset').'</span></a>';
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'><td>" . __('End date') . "</td><td>";
      Html::showDateTimeField('date_end', ['value' => $form_dates["end"], 'maybeempty' => false]);
      echo "</td></tr>";
      echo "</td></tr>";
      echo "</table>";

      Html::closeForm();

      echo "</td>";
      echo "</tr>";
      echo "</table>";

      echo "</div>";
   }

   /**
   * Link with current month reservations
   */
   public function showCurrentMonthForAllLink() {
      global $CFG_GLPI;
      if (!Session::haveRight("reservation", "1")) {
         return false;
      }
      $mois_courant = intval(strftime("%m"));
      $annee_courante = strftime("%Y");

      $mois_courant = intval($mois_courant);

      $all = "<a class='vsubmit' href='../../../front/reservation.php?reservationitems_id=&amp;mois_courant=" . "$mois_courant&amp;annee_courante=$annee_courante'>" . __('Show all') . "</a>";

      echo "<div class='center'>";
      echo "<table class='tab_cadre'>";
      echo "<tr><th colspan='2'>" . __('Reservations This Month', "reservation") . "</th></tr>\n";
      echo "<td>";
      echo "<img src='" . $CFG_GLPI["root_doc"] . "/pics/reservation.png' alt=''>";
      echo "</td>";
      echo "<td >$all</td>\n";
      echo "</table>";
      echo "</div>";
   }

}
