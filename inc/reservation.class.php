<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

// function getGLPIUrl()
// {
//    return str_replace("plugins/reservation/front/reservation.php", "", $_SERVER['SCRIPT_NAME']);
// }

class PluginReservationReservation extends CommonDBTM
{
   // From CommonDBChild
   public static $reservations_id = 'reservations_id';

   /**
    * @param $nb  integer  for singular or plural
    **/
   public static function getTypeName($nb = 0) {
      return _n('Reservation', 'Reservations', $nb, 'reservation');
   }

   public static function getMenuName() {
      return PluginReservationReservation::getTypeName(2);
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


   /**
    * @return array GLPI reservations mixed with plugin reservations
    */
   public static function getAllReservations($filters = []) {
      global $DB;

      $res = [];
      $reservation_table = getTableForItemType('reservation');
      $plugin_table = getTableForItemType(__CLASS__);

      $where = "WHERE ".$plugin_table.".reservations_id = ".$reservation_table.".id";

      foreach ($filters as $filter) {
         $where .= " AND ".$filter;
      }

      $query = "SELECT *
               FROM $reservation_table
                  , $plugin_table
               $where";
      Toolbox::logInFile('sylvain', "QUERY : ".$query."\n", $force = false);

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) > 0) {
            while ($row = $DB->fetch_assoc($result)) {
               $res[] = $row;
            }
         }
      }
      // Toolbox::logInFile('sylvain', "getAllReservations RETURN : ".json_encode($res)."\n", $force = false);
      return $res;
   }


   /**
    *
    */
   public static function getAvailablesItems($begin, $end) {
      global $DB, $CFG_GLPI;

      $result = [];

      foreach ($CFG_GLPI["reservation_types"] as $itemtype) {
         if (!($item = getItemForItemtype($itemtype))) {
            continue;
         }
         $itemtable = getTableForItemType($itemtype);
         $left = "";
         $where = "";
         $left = "LEFT JOIN `glpi_reservations`
                        ON (`glpi_reservationitems`.`id` = `glpi_reservations`.`reservationitems_id`
                            AND '". $begin."' < `glpi_reservations`.`end`
                            AND '". $end."' > `glpi_reservations`.`begin`)";

         $where = " AND `glpi_reservations`.`id` IS NULL ";

         $query = "SELECT `glpi_reservationitems`.`id`,
                          `glpi_reservationitems`.`comment`,
                          `$itemtable`.`name` AS name,
                          `glpi_reservationitems`.`items_id` AS items_id
                   FROM `glpi_reservationitems`
                   INNER JOIN `$itemtable`
                        ON (`glpi_reservationitems`.`itemtype` = '$itemtype'
                            AND `glpi_reservationitems`.`items_id` = `$itemtable`.`id`)
                   $left
                   WHERE `glpi_reservationitems`.`is_active` = '1'
                         AND `glpi_reservationitems`.`is_deleted` = '0'
                         AND `$itemtable`.`is_deleted` = '0'
                         $where ".
                         getEntitiesRestrictRequest(" AND", $itemtable, '',
                                                    $_SESSION['glpiactiveentities'],
                                                    $item->maybeRecursive())."
                   ORDER BY `$itemtable`.`entities_id`,
                            `$itemtable`.`name`+0";

         if ($res = $DB->query($query)) {
            while ($row = $DB->fetch_assoc($res)) {
               $result[] = array_merge($row, ['itemtype'=>$itemtype]);
            }
         }
      }
      return $result;
   }




   public static function sendMail($reservation_id) {
      global $DB, $CFG_GLPI;
      $reservation = new Reservation();
      $reservation->getFromDB($reservation_id);
      NotificationEvent::raiseEvent('plugin_reservation_expiration', $reservation);

      $tablename = getTableForItemType(__CLASS__);
      $query = "UPDATE `".$tablename."` SET `mailingdate`= '" . date("Y-m-d H:i:s", time()) . "' WHERE `reservations_id` = " . $reservation_id;
      $DB->query($query) or die("error on 'update' in sendMail: " . $DB->error());

      Event::log($reservation_id, "reservation", 4, "inventory",
                  sprintf(__('%1$s sends email for the reservation %2$s'),
                           $_SESSION["glpiname"], $reservation_id));
   }

   public static function checkoutReservation($reservation_id) {
      global $DB, $CFG_GLPI;

      $tablename = getTableForItemType(__CLASS__);
      // Toolbox::logInFile('sylvain', "checkoutReservation RETURN : ".$tablename."\n", $force = false);
      $query = "UPDATE `".$tablename."` 
               SET `effectivedate` = '" . date("Y-m-d H:i:s", time()) . "' 
               WHERE `reservations_id` = '" . $reservation_id . "';";
      $DB->query($query) or die("error on checkoutReservation 1 : " . $DB->error());

      $query = "UPDATE `glpi_reservations` SET `end`='" . date("Y-m-d H:i:s", time()) . "' WHERE `id`='" . $reservation_id . "';";
      $DB->query($query) or die("error on checkoutReservation 2 : " . $DB->error());

      Event::log($reservation_id, "reservation", 4, "inventory",
                  sprintf(__('%1$s marks the reservation %2$s as returned'),
                           $_SESSION["glpiname"], $reservation_id));
   }


   public static function addItemToResa($item_id, $reservation_id) {
      $resa = new Reservation();
      $resa->getFromDb($reservation_id);

      $rr = new Reservation();
      $input                        = [];
      $input['reservationitems_id'] = $item_id;
      $input['comment']             = $resa->fields['comment'];
      $input['group']               = $rr->getUniqueGroupFor($item_id);
      $input['begin']               = $resa->fields['begin'];
      $input['end']                 = $resa->fields['end'];
      $input['users_id']            = $resa->fields['users_id'];
      unset($rr->fields["id"]);
      Toolbox::logInFile('sylvain', "addItemToResa INPUT : ".json_encode($input)."\n", $force = false);
      if ($newID = $rr->add($input)) {
         Event::log($newID, "reservation", 4, "inventory",
                  sprintf(__('%1$s adds the reservation %2$s for item %3$s'),
                           $_SESSION["glpiname"], $newID, $item_id));
      }
      // Toolbox::logInFile('sylvain', "addItemToResa RETURN : ".json_encode($newID)."\n", $force = false);
   }

   public static function switchItemToResa($item_id, $reservation_id) {
      global $DB;

      $query = "UPDATE `glpi_reservations` SET `reservationitems_id`='" . $item_id . "' WHERE `id`='" . $reservation_id . "';";
      $DB->query($query) or die("error on 'update' in replaceResa / hash: " . $DB->error());
      Event::log($reservation_id, "reservation", 4, "inventory",
                  sprintf(__('%1$s switchs the reservation %2$s with item %3$s'),
                          $_SESSION["glpiname"], $reservation_id, $item_id));
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













































































// function getMatDispo()
// {

//    global $DB, $CFG_GLPI, $datesresa;

//    $showentity = Session::isMultiEntitiesMode();

//    $begin = $datesresa["begin"];
//    $end = $datesresa["end"];
//    $left = "";
//    $where = "";
//    $myArray = [];

//    foreach ($CFG_GLPI["reservation_types"] as $itemtype) {
//       if (!($item = getItemForItemtype($itemtype))) {
//          continue;
//       }

//       $itemtable = getTableForItemType($itemtype);
//       $otherserial = "'' AS otherserial";

//       if ($item->isField('otherserial')) {
//          $otherserial = "`$itemtable`.`otherserial`";
//       }

//       if (isset($begin) && isset($end)) {
//          $left = "LEFT JOIN `glpi_reservations`
//     ON (`glpi_reservationitems`.`id` = `glpi_reservations`.`reservationitems_id`
//         AND '" . $begin . "' < `glpi_reservations`.`end`
//         AND '" . $end . "' > `glpi_reservations`.`begin`)";
//          $where = " AND `glpi_reservations`.`id` IS NULL ";
//       }

//       $query = "SELECT `glpi_reservationitems`.`id`,
//   `glpi_reservationitems`.`comment`,
//   `$itemtable`.`id` AS materielid,
//   `$itemtable`.`name` AS name,
//   `$itemtable`.`entities_id` AS entities_id,
//   $otherserial,
//   `glpi_locations`.`completename` AS location,
//   `glpi_reservationitems`.`items_id` AS items_id
//     FROM `glpi_reservationitems`
//     $left
//     INNER JOIN `$itemtable`
//     ON (`glpi_reservationitems`.`itemtype` = '$itemtype'
//         AND `glpi_reservationitems`.`items_id` = `$itemtable`.`id`)
//     LEFT JOIN `glpi_locations`
//     ON (`$itemtable`.`locations_id` = `glpi_locations`.`id`)
//     WHERE `glpi_reservationitems`.`is_active` = '1'
//     AND `glpi_reservationitems`.`is_deleted` = '0'
//     AND `$itemtable`.`is_deleted` = '0'
//     $where " .
//       getEntitiesRestrictRequest(" AND", $itemtable, '',
//          $_SESSION['glpiactiveentities'],
//          $item->maybeRecursive()) . "
//     ORDER BY `$itemtable`.`entities_id`,
//   `$itemtable`.`name`";

//       if ($result = $DB->query($query)) {

//          while ($row = $DB->fetch_assoc($result)) {
//             array_push($myArray, [$row["id"] => $row["name"]]);
//          }
//       }
//    }
//    return $myArray;
// }


/*


    public function showCurrentResa($includeFuture = 0)
    {
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

/*
function compare_date_by_user($a, $b)
{
   return strnatcmp($a['debut'], $b['debut']);
}

function compare_date_by_alluser($a, $b)
{
   return strnatcmp($a[0]['debut'], $b[0]['debut']);
}
*/