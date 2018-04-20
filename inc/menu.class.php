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

      foreach ($list as $reservation_id => $reservation_info) {
         $reservation = new Reservation();
         $reservation->getFromDB($reservation_id);

         $user = $reservation->getConnexityItem('user', 'users_id');
         Toolbox::logInFile('sylvain', "USER : ".json_encode($user)."\n", $force = false);

         $reservationitems = $reservation->getConnexityItem('reservationitem', 'reservationitems_id');
         Toolbox::logInFile('sylvain', "RESERVATIONITEM : ".json_encode($reservationitems)."\n", $force = false);

         $item = $reservationitems->getConnexityItem($reservationitems->fields['itemtype'], 'items_id');
         Toolbox::logInFile('sylvain', "ITEM : ".json_encode($item)."\n", $force = false);

         //debut TEST
         // echo "<tr class='tab_bg_2'>";
         // echo "<td colspan='100%' bgcolor='lightgrey' style='padding:1px;'/>";
         // echo "</tr>";
         echo "<tr class='tab_bg_2'>";
         echo "<td rowspan=1>".$user->fields['name']."</td>";
         echo "<td>".$item->fields['name']."</td><td></td>";
         echo "<td>".$reservation->fields['begin']."</td>";
         echo "<td>".$reservation_info['baselinedate']."</td>";
         echo "<td>".$reservation->fields['comment']."</td>";
         echo "<td rowspan=1><center>";
         echo "<img title=\"\" alt=\"\" src=\"../pics/up-icon.png\"></img>";
         echo "<img title=\"\" alt=\"\" src=\"../pics/down-icon.png\"></img>";
         echo "</center></td>";

         echo "<td><center><a href=\"reservation.php?resareturn=1\"><img title=\"" . _x('tooltip', 'Set As Returned') . "\" alt=\"\" src=\"../pics/greenbutton.png\"></img></a></center></td>";
         echo "<td>";
         echo "test";
         echo "</td>";
         echo "</tr>";
         //fin TEST

      }
      

      //HELP
      //formatUserName($user->fields["id"], $user->fields["name"], $user->fields["realname"], $user->fields["firstname"]);
      // Toolbox::logInFile($name, $text, $force = false) {


      //on parcourt le tableau pour construire la table à afficher
      // foreach ($ResaByUser as $User => $arrayResa) {
      //    $nbLigne = 1;
      //    $limiteLigneNumber = count($arrayResa);
      //    $flag = 0;
      //    echo "<tr class='tab_bg_2'>";
      //    echo "<td colspan='100%' bgcolor='lightgrey' style='padding:1px;'/>";
      //    echo "</tr>";
      //    echo "<tr class='tab_bg_2'>";
      //    echo "<td rowspan=" . count($arrayResa) . ">" . $User . "</td>";
      //    foreach ($arrayResa as $Num => $resa) {
      //       $color = "";
      //       if ($resa["debut"] > date("Y-m-d H:i:s", time())) { // on colore  en rouge seulement si la date de retour theorique est depassée et si le materiel n'est pas marqué comme rendu (avec une date de retour effectif)
      //          $color = "bgcolor=\"lightgrey\"";
      //       }
      //       $flagSurveille = 0;
      //       // on regarde si la reservation actuelle a été prolongée par le plugin
      //       $query = "SELECT `date_return`, `date_theorique`, `dernierMail` FROM `glpi_plugin_reservation_manageresa` WHERE `resaid` = " . $resa["resaid"];
      //       if ($result = $DB->query($query)) {
      //          $dates = $DB->fetch_row($result);
      //       }

      //       if ($DB->numrows($result)) {
      //          if ($dates[1] < date("Y-m-d H:i:s", time()) && $dates[0] == null) { // on colore  en rouge seulement si la date de retour theorique est depassée et si le materiel n'est pas marqué comme rendu (avec une date de retour effectif)
      //             $color = "bgcolor=\"red\"";
      //             $flagSurveille = 1;
      //          }
      //       }

      //       // le nom du materiel
      //       $items_id = $resa['items_id'];
      //       $itemtype = $resa['itemtype'];
      //       $item = getItemForItemType($itemtype);
      //       $item->getFromDB($resa['items_id']);

      //       echo "<td $color>";
      //       getLinkforItem($item);
      //       echo "</td>";

      //       echo "<td $color>";
      //       getToolTipforItem($item);
      //       echo "</td>";

      //       if (!$flag) {
      //          $i = $Num;

      //          while ($i < count($arrayResa) - 1) {
      //             if ($arrayResa[$i + 1]['debut'] == $resa['debut'] && $arrayResa[$Num + 1]['fin'] == $resa['fin']) {
      //                $nbLigne++;
      //             } else {
      //                break;
      //             }

      //             $i++;
      //          }
      //          $limiteLigneNumber = $Num + $nbLigne - 1;

      //       }

      //       //date de debut de la resa
      //       if (!$flag) {
      //          echo "<td rowspan=" . $nbLigne . " $color>" . date("d-m-Y \à H:i:s", strtotime($resa["debut"])) . "</td>";

      //          // si c'est une reservation prolongée, on affiche la date theorique plutot que la date reelle (qui est prolongée jusqu'au retour du materiel)
      //          if ($DB->numrows($result) && $dates[0] == null) {
      //             echo "<td rowspan=" . $nbLigne . " $color>" . date("d-m-Y \à H:i:s", strtotime($dates[1])) . "</td>";
      //          } else {
      //             echo "<td rowspan=" . $nbLigne . " $color>" . date("d-m-Y \à H:i:s", strtotime($resa["fin"])) . "</td>";
      //          }

      //          //le commentaire
      //          echo "<td rowspan=" . $nbLigne . " $color>" . $resa["comment"] . "</td>";

      //          // les fleches de mouvements
      //          echo "<td rowspan=" . $nbLigne . " ><center>";
      //          if (date("Y-m-d", strtotime($resa["debut"])) == date("Y-m-d", strtotime($begin))) {
      //             echo "<img title=\"\" alt=\"\" src=\"../pics/up-icon.png\"></img>";
      //          }

      //          if (date("Y-m-d", strtotime($resa["fin"])) == date("Y-m-d", strtotime($end))) {
      //             echo "<img title=\"\" alt=\"\" src=\"../pics/down-icon.png\"></img>";
      //          }

      //          echo "</center></td>";

      //       }
      //       if ($nbLigne > 1) {
      //          $flag = 1;
      //       }

      //       if ($Num == $limiteLigneNumber) {
      //          $flag = 0;
      //          $nbLigne = 1;
      //       }

      //       // si la reservation est rendue, on affiche la date du retour, sinon le bouton pour acquitter le retour
      //       if ($dates[0] != null) {
      //          echo "<td>" . date("d-m-Y \à H:i:s", strtotime($dates[0])) . "</td>";
      //       } else {
      //          echo "<td><center><a href=\"reservation.php?resareturn=" . $resa['resaid'] . "\"><img title=\"" . _x('tooltip', 'Set As Returned') . "\" alt=\"\" src=\"../pics/greenbutton.png\"></img></a></center></td>";
      //       }

      //       // boutons action
      //       $matDispo = getMatDispo();
      //       echo "<td>";
      //       echo "<ul>";
      //       echo "<li><span class=\"bouton\" id=\"bouton_add" . $resa['resaid'] . "\" onclick=\"javascript:afficher_cacher('add" . $resa['resaid'] . "');\">" . _sx('button', 'Add an item') . "</span>
      //     <div id=\"add" . $resa['resaid'] . "\" style=\"display:none;\">
      //     <form method='POST' name='form' action='" . Toolbox::getItemTypeSearchURL(__CLASS__) . "'>";
      //       echo '<select name="matDispoAdd">';
      //       foreach ($matDispo as $mat) {
      //          echo "\t", '<option value="', key($mat), '">', current($mat), '</option>';
      //       }
      //       echo "<input type='hidden' name='AjouterMatToResa' value='" . $resa['resaid'] . "'>";
      //       echo "<input type='submit' class='submit' name='submit' value=" . _sx('button', 'Add') . ">";
      //       Html::closeForm();
      //       echo "</div></li>";

      //       echo "<li><span class=\"bouton\" id=\"bouton_replace" . $resa['resaid'] . "\" onclick=\"javascript:afficher_cacher('replace" . $resa['resaid'] . "');\">" . _sx('button', 'Replace an item') . "</span>
      //     <div id=\"replace" . $resa['resaid'] . "\" style=\"display:none;\">
      //     <form method='post' name='form' action='" . Toolbox::getItemTypeSearchURL(__CLASS__) . "'>";
      //       echo '<select name="matDispoReplace">';
      //       foreach ($matDispo as $mat) {
      //          echo "\t", '<option value="', key($mat), '">', current($mat), '</option>';
      //       }
      //       echo "<input type='hidden' name='ReplaceMatToResa' value='" . $resa['resaid'] . "'>";
      //       echo "<input type='submit' class='submit' name='submit' value=" . _sx('button', 'Save') . ">";
      //       Html::closeForm();
      //       echo "</div></li>";
      //       echo "</ul>";
      //       echo "</td>";

      //       echo "<td>";
      //       echo "<ul>";
      //       echo "<li><a class=\"bouton\" title=\"Editer la reservation\" href='../../../front/reservation.form.php?id=" . $resa['resaid'] . "'>" . _sx('button', 'Edit') . "</a></li>";
      //       echo "</ul>";
      //       echo "</td>";

      //       if ($methode == "manual") {
      //          echo "<td>";
      //          echo "<ul>";
      //          if ($flagSurveille) {
      //             echo "<li><a class=\"bouton\" title=\"" . _sx('tooltip', 'Send an e-mail for the late reservation') . "\" href=\"reservation.php?mailuser=" . $resa['resaid'] . "\">" . _sx('button', 'Send an e-mail') . "</a></li>";

      //             if (isset($dates[2])) {
      //                echo "<li>" . __('Last e-mail sent on') . " </li>";
      //                echo "<li>" . date("d-m-Y  H:i:s", strtotime($dates[2])) . "</li>";
      //             }
      //          }
      //          echo "</ul>";
      //          echo "</td>";
      //       }

      //       echo "</tr>";
      //       echo "<tr class='tab_bg_2'>";

      //    }
      //    echo "</tr>\n";
      // }

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
