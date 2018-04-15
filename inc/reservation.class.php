<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

function getGLPIUrl() {
   return str_replace("plugins/reservation/front/reservation.php", "", $_SERVER['SCRIPT_NAME']);
}

class PluginReservationReservation extends CommonDBTM
{
   // From CommonDBChild
   static public $reservations_id = 'reservations_id';

   protected $tabs = [];
   protected $tabsNames = [];

   /*
   public function getAbsolutePath() {
      return str_replace("plugins/reservation/inc/reservation.class.php", "", $_SERVER['SCRIPT_FILENAME']);
   } */

   /**
   * @param $nb  integer  for singular or plural
   **/
   static function getTypeName($nb = 0) {
      return _n('Reservation', 'Reservations', $nb, 'Reservation');
   }

   public static function getMenuName() {
      return PluginReservationReservation::getTypeName(2);
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      $ong = [];
      $config = new PluginReservationConfig();
      $i = 1;
      if ($config->getConfigurationValue("tabcurrent", 1)) {
         $ong[$i] = __('Current Reservations');
         $i++;
      }
      if ($config->getConfigurationValue("tabcoming")) {
         $ong[$i] = __('Current and Incoming Reservations');
         $i++;
      }
      $ong[$i] = __('Available Hardware');
      return $ong;
   }

   public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      switch ($tabnum) {
         case 1 : //"My first tab"
            self::displayTabContentForCurrentReservations();
            break;
         case 2 : //"My second tab""
            self::displayTabContentForAvailableHardware();
            break;
      }
      return true;
   }


   /**
    * Définition du contenu de l'onglet
    **/
   /*
   public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      $item->getDatesResa();
      call_user_func($item->tabs[$tabnum]);
      return true;
   } */


   /*
   public static function canView() {
      global $CFG_GLPI;
      return true;
      return Session::haveRightsOr(self::$rightname, [READ, self::RESERVEANITEM]);
   } */

   public static function canCreate() {
      return Reservation::canCreate();
   }

   /*
   public static function canViewItem() {
      return true;
   } */

   public static function canDelete() {
      return Reservation::canDelete();
   }

   public static function canUpdate() {
      return Reservation::canUpdate();
   }

   /*
   public function isNewItem() {
      return false;
   } */



   public function showForm($id, $options = []) {
      global $CFG_GLPI, $form_dates;

      $this->getFormDates();
      $this->showFormDate();

   }

   public function getFormDates() {
      global $form_dates;

      if (!isset($form_dates)) {
         $day = date("d", time());
         $month = date("m", time());
         $year = date("Y", time());
         $begin_time = time();

         $form_dates["begin"] = date("Y-m-d H:i:s", $begin_time);
         $form_dates['end'] =  date("Y-m-d H:i:s", mktime(23, 59, 59, $mois, $jour, $annee));
      }
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
      if (isset($_POST['next_day']) || isset($_GET['next_day'])) {
         $form_dates["begin"] = date("Y-m-d H:i:s", strtotime($form_dates["begin"]) + DAY_TIMESTAMP);
         $form_dates["end"] = date("Y-m-d H:i:s", strtotime($form_dates["end"]) + DAY_TIMESTAMP);
      }
      if (isset($_POST['previous_day']) || isset($_GET['previous_day'])) {
         $form_dates["begin"] = date("Y-m-d H:i:s", strtotime($form_dates["begin"]) - DAY_TIMESTAMP);
         $form_dates["end"] = date("Y-m-d H:i:s", strtotime($form_dates["end"]) - DAY_TIMESTAMP);
      }
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

   /**
    * Display the form with begin and end dates, next day, previous day, etc.
    **/
   public function showFormDate() {
      global $form_dates;

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
    * Fonction permettant d'afficher les materiels disponibles et de faire une nouvelle reservation
    * C'est juste une interface differente de celle de GLPI. Pour les nouvelles reservations, on utilise les fonctions du coeur de GLPI
    **/
   public function showDispoAndFormResa() {
      global $DB, $CFG_GLPI, $datesresa;
      $showentity = Session::isMultiEntitiesMode();
      $config = new PluginReservationConfig();

      $begin = $datesresa["begin"];
      $end = $datesresa["end"];
      $left = "";
      $where = "";

      echo "<div class='center'>\n";
      echo "<form name='form' method='GET' action='../../../front/reservation.form.php'>\n";
      echo "<table class='tab_cadre' style=\"border-spacing:20px;\">\n";
      echo "<tr>";

      //get max number of item in a category
      $query = "SELECT itemtype, count(itemtype) as count FROM glpi_reservationitems WHERE glpi_reservationitems.is_active=1 AND glpi_reservationitems.is_deleted=0 GROUP BY itemtype";
      if ($result = $DB->query($query)) {
         while ($row = $DB->fetch_assoc($result)) {
            $typesNb_array[$row["itemtype"]] = $row["count"];
         }
         $maxNbItems = max(array_values($typesNb_array));
      }
      $currentItemRowSize = 0;
      $changeRow = false;

      $reservations = $CFG_GLPI["reservation_types"];
      echo "<td>";

      foreach ($CFG_GLPI["reservation_types"] as $itemtype) {
         if (!($item = getItemForItemtype($itemtype))) {
            continue;
         }

         $itemtable = getTableForItemType($itemtype);

         $otherserial = "'' AS otherserial";

         if ($item->isField('otherserial')) {
            $otherserial = "`$itemtable`.`otherserial`";
         }

         if (isset($begin) && isset($end)) {
            $left = "LEFT JOIN `glpi_reservations`
	        ON (`glpi_reservationitems`.`id` = `glpi_reservations`.`reservationitems_id`
	        AND '" . $begin . "' < `glpi_reservations`.`end`
	        AND '" . $end . "' > `glpi_reservations`.`begin`)";
            $where = " AND `glpi_reservations`.`id` IS NULL ";
         }

         $query = "SELECT `glpi_reservationitems`.`id`,
	      `glpi_reservationitems`.`comment`,
	      `$itemtable`.`id` AS materielid,
	      `$itemtable`.`name` AS name,
	      `$itemtable`.`entities_id` AS entities_id,
	      $otherserial,
	      `glpi_locations`.`completename` AS location,
	      `glpi_reservationitems`.`items_id` AS items_id,
	      `glpi_manufacturers`.`name` AS manufacturer
	      FROM `glpi_reservationitems`
	        $left
	        INNER JOIN `$itemtable`
	        ON (`glpi_reservationitems`.`itemtype` = '$itemtype'
	        AND `glpi_reservationitems`.`items_id` = `$itemtable`.`id`)
	        LEFT JOIN `glpi_locations`
	        ON (`$itemtable`.`locations_id` = `glpi_locations`.`id`)
		#Left join produce null value when value does not exist
		LEFT JOIN `glpi_manufacturers`
		ON (`$itemtable`.`manufacturers_id` = `glpi_manufacturers`.`id`)
	        WHERE `glpi_reservationitems`.`is_active` = '1'
	          AND `glpi_reservationitems`.`is_deleted` = '0'
	          AND `$itemtable`.`is_deleted` = '0'
	        $where " .
         getEntitiesRestrictRequest(" AND", $itemtable, '',
            $_SESSION['glpiactiveentities'],
            $item->maybeRecursive()) . "
	      ORDER BY `$itemtable`.`entities_id`,
        `$itemtable`.`name`";

         if ($result = $DB->query($query)) {

            // If there is at least one item, prepare the table header for the type
            if ($DB->numrows($result)) {

               $currentItemRowSize += $typesNb_array[$itemtype];
               if ($currentItemRowSize < $maxNbItems) {
                  $changeRow = false;
               } else {

                  $changeRow = true;
                  $currentItemRowSize = $typesNb_array[$itemtype];
               }
               if ($changeRow) {
                  echo "</td><td>";
               } else {

                  echo "<div style='padding: 10px;'></div>";
               }
               echo "\n\t<table class='tab_cadre'>";
               echo "<tr><th colspan='" . ($showentity ? "6" : "5") . "'>" . $item->getTypeName() . "</th></tr>\n";
            }

            // Display all items entries for the current type
            while ($row = $DB->fetch_assoc($result)) {
               $item->getFromDB($row['items_id']);
               echo "<tr class='tab_bg_2'><td>";
               echo "<input type='checkbox' name='item[" . $row["id"] . "]' value='" . $row["id"] . "'>" . "</td>";
               $typename = $item->getTypeName();
               if ($itemtype == 'Peripheral') {
                  // TODO isn't that already done ?
                  $item->getFromDB($row['items_id']);
                  if (isset($item->fields["peripheraltypes_id"]) && ($item->fields["peripheraltypes_id"] != 0)) {
                     $typename = Dropdown::getDropdownName("glpi_peripheraltypes",
                        $item->fields["peripheraltypes_id"]);
                  }
               }

               echo "<td>";
               getLinkforItem($item);
               echo "</td>";

               echo "<td>" . nl2br($row["comment"]) . "</td>";
               if ($showentity) {
                  echo "<td>" . Dropdown::getDropdownName("glpi_entities", $row["entities_id"]) . "</td>";
               }

               echo "<td>";
               getToolTipforItem($item);
               echo "</td>";

               echo "<td><a title=\"Show Calendar\" href='../../../front/reservation.php?reservationitems_id=" . $row['id'] . "'>" . "<img title=\"\" alt=\"\" src=\"" . getGLPIUrl() . "pics/reservation-3.png\"></a></td>";
               echo "</tr>\n";
            }
            // if there is at least one entry for the current type
            if ($DB->numrows($result)) {
               echo "</table>";

            }

         }

      }
      echo "</td>";

      echo "</tr>";
      echo "<tr class='tab_bg_1 center'><td colspan='" . ($showentity ? "5" : "4") . "'>";
      echo "<input type='submit' value='" . __('Create new reservation') . "' class='submit'></td></tr>\n";

      echo "</table>\n";

      echo "<input type='hidden' name='id' value=''>";
      echo "<input type='hidden' name='begin' value='" . $begin . "'>";
      echo "<input type='hidden' name='end' value='" . $end . "'>";
      Html::closeForm();
      echo "</div>\n";
   }

   public function mailUser($resaid) {
      global $DB, $CFG_GLPI;
      $reservation = new Reservation();
      $reservation->getFromDB($resaid);
      NotificationEvent::raiseEvent('plugin_reservation_expiration', $reservation);
      $config = new PluginReservationConfig();

      $query = "UPDATE `glpi_plugin_reservation_manageresa` SET `dernierMail`= '" . date("Y-m-d H:i:s", time()) . "' WHERE `resaid` = " . $resaid;
      $DB->query($query) or die("error on 'update' in mailUser: " . $DB->error());

   }

   /**
    * Fonction permettant de marquer une reservation comme rendue
    * Si elle etait dans la table glpi_plugin_reservation_manageresa (c'etait donc une reservation prolongée), on insert la date de retour à l'heure actuelle ET on met à jour la date de fin de la vraie reservation.
    * Sinon, on insert une nouvelle entree dans la table pour avoir un historique du retour de la reservation ET on met à jour la date de fin de la vraie reservation
    **/
   public function resaReturn($resaid) {
      global $DB, $CFG_GLPI;
      // on cherche dans la table de gestion des resa du plugin
      $query = "SELECT * FROM `glpi_plugin_reservation_manageresa` WHERE `resaid` = " . $resaid;
      $trouve = 0;
      $matId;
      if ($result = $DB->query($query)) {
         // $matId =
         if ($DB->numrows($result)) {
            $trouve = 1;
         }

      }

      $ok = 0;
      if ($trouve) {
         // maj de la date de retour dans la table manageresa du plugin
         $query = "UPDATE `glpi_plugin_reservation_manageresa` SET `date_return` = '" . date("Y-m-d H:i:s", time()) . "' WHERE `resaid` = '" . $resaid . "';";
         $DB->query($query) or die("error on 'update' into glpi_plugin_reservation_manageresa / hash: " . $DB->error());
         $ok = 1;
      } else {
         $temps = time();
         // insertion de la reservation dans la table manageresa
         $query = "INSERT INTO  `glpi_plugin_reservation_manageresa` (`resaid`, `matid`, `date_return`, `date_theorique`, `itemtype`) VALUES ('" . $resaid . "', '0',  '" . date("Y-m-d H:i:s", $temps) . "', '" . date("Y-m-d H:i:s", $temps) . "','null');";
         $DB->query($query) or die("error on 'insert' into glpi_plugin_reservation_manageresa / hash: " . $DB->error());
         $ok = 1;
      }

      //update de la vrai reservation
      if ($ok) {
         $query = "UPDATE `glpi_reservations` SET `end`='" . date("Y-m-d H:i:s", time()) . "' WHERE `id`='" . $resaid . "';";
         $DB->query($query) or die("error on 'update' into glpi_reservations / hash: " . $DB->error());
      }
   }

   /**
    * Fonction permettant d'afficher les reservations actuelles
    *
    **/
   public function showCurrentResa($includeFuture = 0) {
      global $DB, $CFG_GLPI, $datesresa;
      $showentity = Session::isMultiEntitiesMode();
      $config = new PluginReservationConfig();
      $methode = $config->getConfigurationValue("late_mail");

      $begin = $datesresa["begin"];
      $end = $datesresa["end"];
      $left = "";
      $where = "";

      //tableau contenant un tableau des reservations par utilisateur
      // exemple : (salleman => ( 0=> (resaid => 1, debut => '12/12/2054', fin => '12/12/5464', comment => 'tralala', name => 'hobbit16'
      $ResaByUser = [];

      foreach ($CFG_GLPI["reservation_types"] as $itemtype) {
         if (!($item = getItemForItemtype($itemtype))) {
            continue;
         }

         $itemtable = getTableForItemType($itemtype);

         $otherserial = "'' AS otherserial";
         if ($item->isField('otherserial')) {
            $otherserial = "`$itemtable`.`otherserial`";
         }
         if (isset($begin)) {
            if ($includeFuture) {
               $left = "LEFT JOIN `glpi_reservations`
	  ON (`glpi_reservationitems`.`id` = `glpi_reservations`.`reservationitems_id`
	      AND '" . $begin . "' < `glpi_reservations`.`end`)";
            } else {
               if (isset($end)) {
                  $left = "LEFT JOIN `glpi_reservations`
  	    ON (`glpi_reservationitems`.`id` = `glpi_reservations`.`reservationitems_id`
	      AND '" . $begin . "' < `glpi_reservations`.`end`
	      AND '" . $end . "' > `glpi_reservations`.`begin`)";
               }
            }
            $where = " AND `glpi_reservations`.`id` IS NOT NULL ";
         }

         $query = "SELECT `glpi_reservationitems`.`id`,
	`glpi_reservationitems`.`comment`,
	`$itemtable`.`name` AS name,
	`$itemtable`.`entities_id` AS entities_id,
	$otherserial,
	`glpi_reservations`.`id` AS resaid,
	`glpi_reservations`.`comment`,
	`glpi_reservations`.`begin`,
	`glpi_reservations`.`end`,
	`glpi_users`.`name` AS username,
	`glpi_reservationitems`.`items_id` AS items_id,
	`glpi_reservationitems`.`itemtype` AS itemtype
	  FROM `glpi_reservationitems`
	  $left
	  INNER JOIN `$itemtable`
	  ON (`glpi_reservationitems`.`itemtype` = '$itemtype'
	      AND `glpi_reservationitems`.`items_id` = `$itemtable`.`id`)
	  LEFT JOIN `glpi_users`
	  ON (`glpi_reservations`.`users_id` = `glpi_users`.`id`)
	  WHERE `glpi_reservationitems`.`is_active` = '1'
	  AND `glpi_reservationitems`.`is_deleted` = '0'
	  AND `$itemtable`.`is_deleted` = '0'
	  $where " .
         getEntitiesRestrictRequest(" AND", $itemtable, '',
            $_SESSION['glpiactiveentities'],
            $item->maybeRecursive()) . "
	  ORDER BY username,
	`$itemtable`.`entities_id`,
	`$itemtable`.`name`";
         if ($result = $DB->query($query)) {
            // on regroupe toutes les reservations d'un meme user dans un tableau.
            while ($row = $DB->fetch_assoc($result)) {
               if (!array_key_exists($row["username"], $ResaByUser)) {
                  $ResaByUser[$row["username"]] = [];
               }
               $tmp = ["resaid" => $row["resaid"],
                  "name" => $row['name'],
                  "items_id" => $row['items_id'],
                  "itemtype" => $row['itemtype'],
                  "debut" => $row["begin"],
                  "fin" => $row["end"],
                  "comment" => nl2br($row["comment"])];
               $ResaByUser[$row["username"]][] = $tmp;
               //on trie par date
               uasort($ResaByUser[$row["username"]], 'compare_date_by_user');
            }
            ksort($ResaByUser);
         }
      }

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
      echo "<th>" . __('Move') . "</a></th>";
      echo "<th>" . __('Checkout') . "</th>";
      echo "<th colspan='" . ($methode == "manual" ? 3 : 2) . "'>" . __('Action') . "</th>";

      echo "</tr></thead>";
      echo "<tbody>";

      //on parcourt le tableau pour construire la table à afficher
      foreach ($ResaByUser as $User => $arrayResa) {
         $nbLigne = 1;
         $limiteLigneNumber = count($arrayResa);
         $flag = 0;
         echo "<tr class='tab_bg_2'>";
         echo "<td colspan='100%' bgcolor='lightgrey' style='padding:1px;'/>";
         echo "</tr>";
         echo "<tr class='tab_bg_2'>";
         echo "<td rowspan=" . count($arrayResa) . ">" . $User . "</td>";
         foreach ($arrayResa as $Num => $resa) {
            $color = "";
            if ($resa["debut"] > date("Y-m-d H:i:s", time())) { // on colore  en rouge seulement si la date de retour theorique est depassée et si le materiel n'est pas marqué comme rendu (avec une date de retour effectif)
               $color = "bgcolor=\"lightgrey\"";
            }
            $flagSurveille = 0;
            // on regarde si la reservation actuelle a été prolongée par le plugin
            $query = "SELECT `date_return`, `date_theorique`, `dernierMail` FROM `glpi_plugin_reservation_manageresa` WHERE `resaid` = " . $resa["resaid"];
            if ($result = $DB->query($query)) {
               $dates = $DB->fetch_row($result);
            }

            if ($DB->numrows($result)) {
               if ($dates[1] < date("Y-m-d H:i:s", time()) && $dates[0] == null) { // on colore  en rouge seulement si la date de retour theorique est depassée et si le materiel n'est pas marqué comme rendu (avec une date de retour effectif)
                  $color = "bgcolor=\"red\"";
                  $flagSurveille = 1;
               }
            }

            // le nom du materiel
            $items_id = $resa['items_id'];
            $itemtype = $resa['itemtype'];
            $item = getItemForItemType($itemtype);
            $item->getFromDB($resa['items_id']);

            echo "<td $color>";
            getLinkforItem($item);
            echo "</td>";

            echo "<td $color>";
            getToolTipforItem($item);
            echo "</td>";

            if (!$flag) {
               $i = $Num;

               while ($i < count($arrayResa) - 1) {
                  if ($arrayResa[$i + 1]['debut'] == $resa['debut'] && $arrayResa[$Num + 1]['fin'] == $resa['fin']) {
                     $nbLigne++;
                  } else {
                     break;
                  }

                  $i++;
               }
               $limiteLigneNumber = $Num + $nbLigne - 1;

            }

            //date de debut de la resa
            if (!$flag) {
               echo "<td rowspan=" . $nbLigne . " $color>" . date("d-m-Y \à H:i:s", strtotime($resa["debut"])) . "</td>";

               // si c'est une reservation prolongée, on affiche la date theorique plutot que la date reelle (qui est prolongée jusqu'au retour du materiel)
               if ($DB->numrows($result) && $dates[0] == null) {
                  echo "<td rowspan=" . $nbLigne . " $color>" . date("d-m-Y \à H:i:s", strtotime($dates[1])) . "</td>";
               } else {
                  echo "<td rowspan=" . $nbLigne . " $color>" . date("d-m-Y \à H:i:s", strtotime($resa["fin"])) . "</td>";
               }

               //le commentaire
               echo "<td rowspan=" . $nbLigne . " $color>" . $resa["comment"] . "</td>";

               // les fleches de mouvements
               echo "<td rowspan=" . $nbLigne . " ><center>";
               if (date("Y-m-d", strtotime($resa["debut"])) == date("Y-m-d", strtotime($begin))) {
                  echo "<img title=\"\" alt=\"\" src=\"../pics/up-icon.png\"></img>";
               }

               if (date("Y-m-d", strtotime($resa["fin"])) == date("Y-m-d", strtotime($end))) {
                  echo "<img title=\"\" alt=\"\" src=\"../pics/down-icon.png\"></img>";
               }

               echo "</center></td>";

            }
            if ($nbLigne > 1) {
               $flag = 1;
            }

            if ($Num == $limiteLigneNumber) {
               $flag = 0;
               $nbLigne = 1;
            }

            // si la reservation est rendue, on affiche la date du retour, sinon le bouton pour acquitter le retour
            if ($dates[0] != null) {
               echo "<td>" . date("d-m-Y \à H:i:s", strtotime($dates[0])) . "</td>";
            } else {
               echo "<td><center><a href=\"reservation.php?resareturn=" . $resa['resaid'] . "\"><img title=\"" . _x('tooltip', 'Set As Returned') . "\" alt=\"\" src=\"../pics/greenbutton.png\"></img></a></center></td>";
            }

            // boutons action
            $matDispo = getMatDispo();
            echo "<td>";
            echo "<ul>";
            echo "<li><span class=\"bouton\" id=\"bouton_add" . $resa['resaid'] . "\" onclick=\"javascript:afficher_cacher('add" . $resa['resaid'] . "');\">" . _sx('button', 'Add an item') . "</span>
          <div id=\"add" . $resa['resaid'] . "\" style=\"display:none;\">
          <form method='POST' name='form' action='" . Toolbox::getItemTypeSearchURL(__CLASS__) . "'>";
            echo '<select name="matDispoAdd">';
            foreach ($matDispo as $mat) {
               echo "\t", '<option value="', key($mat), '">', current($mat), '</option>';
            }
            echo "<input type='hidden' name='AjouterMatToResa' value='" . $resa['resaid'] . "'>";
            echo "<input type='submit' class='submit' name='submit' value=" . _sx('button', 'Add') . ">";
            Html::closeForm();
            echo "</div></li>";

            echo "<li><span class=\"bouton\" id=\"bouton_replace" . $resa['resaid'] . "\" onclick=\"javascript:afficher_cacher('replace" . $resa['resaid'] . "');\">" . _sx('button', 'Replace an item') . "</span>
          <div id=\"replace" . $resa['resaid'] . "\" style=\"display:none;\">
          <form method='post' name='form' action='" . Toolbox::getItemTypeSearchURL(__CLASS__) . "'>";
            echo '<select name="matDispoReplace">';
            foreach ($matDispo as $mat) {
               echo "\t", '<option value="', key($mat), '">', current($mat), '</option>';
            }
            echo "<input type='hidden' name='ReplaceMatToResa' value='" . $resa['resaid'] . "'>";
            echo "<input type='submit' class='submit' name='submit' value=" . _sx('button', 'Save') . ">";
            Html::closeForm();
            echo "</div></li>";
            echo "</ul>";
            echo "</td>";

            echo "<td>";
            echo "<ul>";
            echo "<li><a class=\"bouton\" title=\"Editer la reservation\" href='../../../front/reservation.form.php?id=" . $resa['resaid'] . "'>" . _sx('button', 'Edit') . "</a></li>";
            echo "</ul>";
            echo "</td>";

            if ($methode == "manual") {
               echo "<td>";
               echo "<ul>";
               if ($flagSurveille) {
                  echo "<li><a class=\"bouton\" title=\"" . _sx('tooltip', 'Send an e-mail for the late reservation') . "\" href=\"reservation.php?mailuser=" . $resa['resaid'] . "\">" . _sx('button', 'Send an e-mail') . "</a></li>";

                  if (isset($dates[2])) {
                     echo "<li>" . __('Last e-mail sent on') . " </li>";
                     echo "<li>" . date("d-m-Y  H:i:s", strtotime($dates[2])) . "</li>";
                  }
               }
               echo "</ul>";
               echo "</td>";
            }

            echo "</tr>";
            echo "<tr class='tab_bg_2'>";

         }
         echo "</tr>\n";
      }
      echo "</tbody>";
      echo "</table>\n";
      echo "</div>\n";

   }

   public function addToResa($idmat, $idresa) {

      global $DB, $CFG_GLPI;

      $query = "SELECT * FROM `glpi_reservations` WHERE `id`='" . $idresa . "';";
      $result = $DB->query($query) or die("error on 'select' dans addToResa / 1: " . $DB->error());

      $matToAdd = $DB->fetch_assoc($result);

      $query = "INSERT INTO  `glpi_reservations` (`begin`, `end`, `reservationitems_id`,`users_id`) VALUES ('" . $matToAdd['begin'] . "', '" . $matToAdd['end'] . "', '" . $idmat . "', '" . $matToAdd['users_id'] . "');";
      $DB->query($query) or die("error on 'insert' dans addToResa / hash: " . $DB->error());

      // pour avoir l'id et l'itemtypede la nouvelle reservation créée
      $query = "SELECT `glpi_reservations`.`id`, `glpi_reservationitems`.`itemtype` FROM `glpi_reservations`, `glpi_reservationitems`  WHERE `begin` = '" . $matToAdd['begin'] . "' AND `end` = '" . $matToAdd['end'] . "' AND `reservationitems_id` = '" . $idmat . "' AND `users_id` ='" . $matToAdd['users_id'] . "' AND `glpi_reservationitems`.`id` = `glpi_reservations`.`reservationitems_id`";
      $result = $DB->query($query) or die("error on 'select' dans addToResa / 2: " . $DB->error());
      $res = $DB->fetch_row($result);
      $idnewreservation = $res[0];
      $itemtypenewresa = $res[1];

      //on regarde si la reservation à laquelle on ajoute le materiel est deja "surveillée", pour  alors surveiller le nouveau mat
      $query = "SELECT * FROM `glpi_plugin_reservation_manageresa` WHERE `resaid` = '" . $idresa . "';";
      $result = $DB->query($query) or die("error on 'select' dans addToResa / manageresa: " . $DB->error());

      if ($DB->numrows($result) > 0) {
         $row = $DB->fetch_assoc($result);
         if ($row['date_return'] == null) {
            $query = "INSERT INTO  `glpi_plugin_reservation_manageresa` (`resaid`, `matid`, `itemtype`,  `date_theorique`) VALUES ('" . $idnewreservation . "', '" . $idmat . "', '" . $itemtypenewresa . "', '" . $row['date_theorique'] . "');";
         } else {
            $query = "INSERT INTO  `glpi_plugin_reservation_manageresa` (`resaid`, `matid`, `itemtype`, `date_return`, `date_theorique`) VALUES ('" . $idnewreservation . "', '" . $idmat . "', '" . $itemtypenewresa . "', " . $row['date_return'] . "', '" . $row['date_theorique'] . "');";
         }

         $DB->query($query) or die("error on 'insert' in addToResa / hash: " . $DB->error());

      }

   }

   public function replaceResa($idmat, $idresa) {
      global $DB, $CFG_GLPI;

      $query = "UPDATE `glpi_reservations` SET `reservationitems_id`='" . $idmat . "' WHERE `id`='" . $idresa . "';";
      $DB->query($query) or die("error on 'update' in replaceResa / hash: " . $DB->error());

   }

}

function getLinkforItem($item) {
   $itemLink = $item->getFormUrl();
   $argConcatenator = "?";
   if (strpos($itemLink, '?') !== false) {
      $argConcatenator = "&amp;";
   }

   echo "<a target='_blank' href='" . $itemLink . $argConcatenator . "id=" . $item->fields["id"] . "'>" . $item->fields["name"] . "</a>";

}

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

function getMatDispo() {

   global $DB, $CFG_GLPI, $datesresa;

   $showentity = Session::isMultiEntitiesMode();

   $begin = $datesresa["begin"];
   $end = $datesresa["end"];
   $left = "";
   $where = "";
   $myArray = [];

   foreach ($CFG_GLPI["reservation_types"] as $itemtype) {
      if (!($item = getItemForItemtype($itemtype))) {
         continue;
      }

      $itemtable = getTableForItemType($itemtype);
      $otherserial = "'' AS otherserial";

      if ($item->isField('otherserial')) {
         $otherserial = "`$itemtable`.`otherserial`";
      }

      if (isset($begin) && isset($end)) {
         $left = "LEFT JOIN `glpi_reservations`
    ON (`glpi_reservationitems`.`id` = `glpi_reservations`.`reservationitems_id`
        AND '" . $begin . "' < `glpi_reservations`.`end`
        AND '" . $end . "' > `glpi_reservations`.`begin`)";
         $where = " AND `glpi_reservations`.`id` IS NULL ";
      }

      $query = "SELECT `glpi_reservationitems`.`id`,
  `glpi_reservationitems`.`comment`,
  `$itemtable`.`id` AS materielid,
  `$itemtable`.`name` AS name,
  `$itemtable`.`entities_id` AS entities_id,
  $otherserial,
  `glpi_locations`.`completename` AS location,
  `glpi_reservationitems`.`items_id` AS items_id
    FROM `glpi_reservationitems`
    $left
    INNER JOIN `$itemtable`
    ON (`glpi_reservationitems`.`itemtype` = '$itemtype'
        AND `glpi_reservationitems`.`items_id` = `$itemtable`.`id`)
    LEFT JOIN `glpi_locations`
    ON (`$itemtable`.`locations_id` = `glpi_locations`.`id`)
    WHERE `glpi_reservationitems`.`is_active` = '1'
    AND `glpi_reservationitems`.`is_deleted` = '0'
    AND `$itemtable`.`is_deleted` = '0'
    $where " .
      getEntitiesRestrictRequest(" AND", $itemtable, '',
         $_SESSION['glpiactiveentities'],
         $item->maybeRecursive()) . "
    ORDER BY `$itemtable`.`entities_id`,
  `$itemtable`.`name`";

      if ($result = $DB->query($query)) {

         while ($row = $DB->fetch_assoc($result)) {
            array_push($myArray, [$row["id"] => $row["name"]]);
         }
      }
   }
   return $myArray;
}

function compare_date_by_user($a, $b) {
   return strnatcmp($a['debut'], $b['debut']);
}

function compare_date_by_alluser($a, $b) {
   return strnatcmp($a[0]['debut'], $b[0]['debut']);
}
