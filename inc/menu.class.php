<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
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
      $ong[$i] = __('Available Hardware');
      return $ong;
   }


   public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      $config = new PluginReservationConfig();
      $tabcoming = $config->getConfigurationValue("tabcoming");
      switch ($tabnum) {
         case 1: //"My first tab"
            self::displayTabContentForCurrentReservations($item);
            break;
         case 2: //"My second tab""
            if ($tabcoming) {
               self::displayTabContentForAllReservations($item);
            } else {
               self::displayTabContentForAvailableHardware($item);
            }
            break;
         case 3: //"My third tab""
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

   public static function arrayGroupBy($array, $element) {
      $result = [];
      foreach ($array as $one) {
         $result[$one[$element]][] = $one;
      }
      return $result;
   }

   public static function displayTabContentForCurrentReservations($item) {
      global $DB, $CFG_GLPI; //, $FORM_DATES;
      $form_dates = $_SESSION['glpi_plugin_reservation_form_dates'];

      $showentity = Session::isMultiEntitiesMode();
      $config = new PluginReservationConfig();
      $includeFuture = $config->getConfigurationValue("tabcoming");
      $mode = $config->getConfigurationValue("mode_auto");

      $begin = $form_dates["begin"];
      $end = $form_dates["end"];

      echo "<div class='center'>";
      echo "<table class='tab_cadre'>";
      echo "<thead>";
      echo "<tr><th colspan='" . ($showentity ? "11" : "10") . "'>" . ($includeFuture ? __('Current and future reservations in the selected timeline') : __('Reservations in the selected timeline')) . "</th></tr>\n";
      echo "<tr class='tab_bg_2'>";
      echo "<th>" . __('User') . "</a></th>";
      echo "<th colspan='2'>" . __('Item') . "</a></th>";
      echo "<th>" . __('Begin') . "</a></th>";
      echo "<th>" . __('End') . "</a></th>";
      echo "<th>" . __('Comment') . "</a></th>";
      echo "<th>" . __('Moves', 'reservation') . "</a></th>";
      echo "<th>" . __('Checkout', 'reservation') . "</th>";
      echo "<th colspan='" . ($mode == "manual" ? 3 : 2) . "'>" . __('Action') . "</th>";

      echo "</tr></thead>";
      echo "<tbody>";

      $list = PluginReservationReservation::getAllReservationsFromDates($begin, $end);
      $ReservationsByUser = self::arrayGroupBy($list, 'users_id');

      foreach ($ReservationsByUser as $reservation_user => $reservations_user_list) {
         uasort($reservations_user_list, function ($a, $b) {
            return strnatcmp($a['begin'], $b['begin']);
         });
         $user = new User();
         $user->getFromDB($reservation_user);
         Toolbox::logInFile('sylvain', "USER : ".json_encode($user)."\n", $force = false);

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
            Toolbox::logInFile('sylvain', "RESERVATIONITEM : ".json_encode($reservationitems)."\n", $force = false);

            $item = $reservationitems->getConnexityItem($reservationitems->fields['itemtype'], 'items_id');
            Toolbox::logInFile('sylvain', "ITEM : ".json_encode($item)."\n", $force = false);

            $color = "";
            if ($reservation_user_info["begin"] > date("Y-m-d H:i:s", time())) {
               $color = "bgcolor=\"lightgrey\"";
            }
            if ($reservation_user_info['baselinedate'] < date("Y-m-d H:i:s", time()) && $reservation_user_info['effectivedate'] == null) {
               $color = "bgcolor=\"red\"";
               //$flagSurveille = 1;
            }

            // item
            echo "<td $color>";
            getLinkforItem($item);
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
               if (date("Y-m-d", strtotime($reservation->fields['end'])) == date("Y-m-d", strtotime($end))) {
                  echo "<img title=\"\" alt=\"\" src=\"../pics/down-icon.png\"></img>";
               }
               echo "</center></td>";
            }



            // checkout buttons or date checkout
            if ($reservation_user_info['effectivedate'] != null) {
               echo "<td>" . date(self::getDateFormat()." \à H:i:s", strtotime($reservation_user_info['effectivedate'])) . "</td>";
            } else {
               echo "<td><center><a href=\"reservation.php?resareturn=" . $reservation_user_info['reservations_id'] . "\"><img title=\"" . _x('tooltip', 'Set As Returned') . "\" alt=\"\" src=\"../pics/greenbutton.png\"></img></a></center></td>";
            }

            // action
            $available_reservationsitem = [];
            echo "<td>";
            echo "<ul>";

            echo "<li><span class=\"bouton\" id=\"bouton_add" . $reservation_user_info['reservations_id'] . "\" onclick=\"javascript:afficher_cacher('add" . $reservation_user_info['reservations_id'] . "');\">" . _sx('button', 'Add an item') . "</span>
            <div id=\"add" . $reservation_user_info['reservations_id'] . "\" style=\"display:none;\">
            <form method='POST' name='form' action='" . Toolbox::getItemTypeSearchURL(__CLASS__) . "'>";
            echo '<select name="add_item">';
            foreach ($available_reservationsitem as $item) {
               echo "\t", '<option value="', key($item), '">', current($item), '</option>';
            }
            echo "<input type='hidden' name='add_item_to_reservation' value='" . $reservation_user_info['reservations_id'] . "'>";
            echo "<input type='submit' class='submit' name='submit' value=" . _sx('button', 'Add') . ">";
            Html::closeForm();
            echo "</div></li>";

            echo "<li><span class=\"bouton\" id=\"bouton_replace" . $reservation_user_info['reservations_id'] . "\" onclick=\"javascript:afficher_cacher('replace" . $reservation_user_info['reservations_id'] . "');\">" . _sx('button', 'Replace an item') . "</span>
            <div id=\"replace" . $reservation_user_info['reservations_id'] . "\" style=\"display:none;\">
            <form method='post' name='form' action='" . Toolbox::getItemTypeSearchURL(__CLASS__) . "'>";
            echo '<select name="switch_item">';
            foreach ($available_reservationsitem as $item) {
               echo "\t", '<option value="', key($item), '">', current($item), '</option>';
            }
            echo "<input type='hidden' name='switch_item_to_reservation' value='" . $reservation_user_info['id'] . "'>";
            echo "<input type='submit' class='submit' name='submit' value=" . _sx('button', 'Save') . ">";
            Html::closeForm();
            echo "</div></li>";
            echo "</ul>";
            echo "</td>";

            echo "<td>";
            echo "<ul>";
            echo "<li><a class=\"bouton\" title=\"Editer la reservation\" href='../../../front/reservation.form.php?id=" . $reservation_user_info['reservations_id'] . "'>" . _sx('button', 'Edit') . "</a></li>";
            echo "</ul>";
            echo "</td>";

            

            if ($mode == "manual") {
               echo "<td>";
               echo "<ul>";
               if ($reservation_user_info['baselinedate'] < date("Y-m-d H:i:s", time()) && $reservation_user_info['effectivedate'] == null) {
                  echo "<li><a class=\"bouton\" title=\"" . _sx('tooltip', 'Send an e-mail for the late reservation') . "\" href=\"reservation.php?mailuser=" . $resa['resaid'] . "\">" . _sx('button', 'Send an e-mail') . "</a></li>";
                  if (isset($reservation_user_info['mailingdate'])) {
                     echo "<li>" . __('Last e-mail sent on') . " </li>";
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

         //Toolbox::logInFile('sylvain', "TESSSST : ".json_encode(array_column($list, 'reservations_id'))."\n", $force = false);
         //Toolbox::logInFile('sylvain', "TESSSST : ".json_encode(array_count_values(array_column($list, 'users_id')))."\n", $force = false);

      }

      echo "</tbody>";
      echo "</table>\n";
      echo "</div>\n";
   }



   public static function displayTabContentForAllReservations() {
      echo "displayTabContentForAllReservations";
   }


   public static function displayTabContentForAvailableHardware() {
      echo "TdisplayTabContentForAvailableHardwareTAT";
   }


   public function getFormDates() {
      //global $FORM_DATES;
      $form_dates = $_SESSION['glpi_plugin_reservation_form_dates'];

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
   **/
   public function showFormDate() {
      //global $FORM_DATES;

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
      echo "<th colspan='5'>" . __('Date') . "</th>";
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
      echo "</td></tr>";

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
      echo "<tr><th colspan='2'>" . __('Reservations This Month') . "</th></tr>\n";
      echo "<td>";
      echo "<img src='" . $CFG_GLPI["root_doc"] . "/pics/reservation.png' alt=''>";
      echo "</td>";
      echo "<td >$all</td>\n";
      echo "</table>";
      echo "</div>";

   }

}
