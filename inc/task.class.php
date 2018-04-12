<?php

/**
 *
 */
class PluginReservationTask extends CommonDBTM
{
   public static function addEvents(NotificationTargetReservation $target) {
      $target->events['plugin_reservation_conflit'] = "Reservation Conflict When Extended (plugin)";
      $target->events['plugin_reservation_expiration'] = "User Reservation Expired (plugin)";
   }

   public static function cronInfo($name) {
      global $LANG;

      switch ($name) {
         case "checkReservations":
            return ['description' => __('Watch Reservations') . " (" . __('plugin') . ")",];
         case "sendMailLateReservations":
            return ['description' => __('Send an e-mail to users with late reservations') . " (" . __('plugin') . ")",];
      }
   }

   /**
    * Execute 1 task manage by the plugin
    *
    * @param $task Object of CronTask class for log / stat
    *
    * @return interger
    *    >0 : done
    *    <0 : to be run again (not finished)
    *     0 : nothing to do
    */
   public static function cronCheckReservations($task) {
      $res = self::surveilleResa($task);
      $task->setVolume($res);
      return $res;
   }

   public static function cronSendMailLateReservations($task) {
      $res = self::mailUserDelayedResa($task);
      $task->setVolume($res);
      return $res;
   }

   public static function mailUserDelayedResa($task) {
      global $DB, $CFG_GLPI;
      $res = 0;

      $temps = time();
      $temps -= ($temps % MINUTE_TIMESTAMP);
      $now = date("Y-m-d H:i:s", $temps);

      $config = new PluginReservationConfig();
      $week = $config->getConfigurationWeek();
      $errlocale = setlocale(LC_TIME, 'fr_FR.utf8', 'fra');
      if (!$errlocale) {
         $task->log("setlocale failed");
      }
      $jour = strftime("%u");
      $dayOk = false;
      if (($jour == 1 && $week['lundi']) ||
         ($jour == 2 && $week['mardi']) ||
         ($jour == 3 && $week['mercredi']) ||
         ($jour == 4 && $week['jeudi']) ||
         ($jour == 5 && $week['vendredi']) ||
         ($jour == 6 && $week['samedi']) ||
         ($jour == 7 && $week['dimanche'])) {
         $dayOk = true;
      }

      if ($dayOk) {

         $query = "SELECT * FROM `glpi_plugin_reservation_manageresa` WHERE `date_return` is NULL";

         if ($result = $DB->query($query)) {
            while ($row = $DB->fetch_assoc($result)) {
               if ($now > $row['date_theorique']) {

                  $reservation = new Reservation();
                  $reservation->getFromDB($row['resaid']);
                  if (NotificationEvent::raiseEvent('plugin_reservation_expiration', $reservation)) {
                     $task->setVolume($res++);
                     $logtext = sprintf(__('Sending e-mail for reservation %1$s'), $row['resaid']);
                     $logtext = $logtext . sprintf(__('Expected return time was : %1$s'), $row['date_theorique']);
                     $task->log($logtext);
                     Event::log($row['resaid'], "Reservation", 4, "inventory", __('Sending an e-mail'));
                  } else {
                     $task->log(__('Could not send notification'));
                  }
               }

            }
         }
         return $res;
      } else {
         $task->log("e-mails not enabled for this week day");
      }
   }

   public static function surveilleResa($task) {
      global $DB, $CFG_GLPI;
      $valreturn = 0;

      $temps = time();
      $temps -= ($temps % MINUTE_TIMESTAMP);
      $begin = date("Y-m-d H:i:s", $temps);
      $end = date("Y-m-d H:i:s", $temps + 5 * MINUTE_TIMESTAMP);
      $task->log("Temps : " . $temps . " Begin : " . $begin . " End : " . $end);
      $left = "";
      $where = "";

      $reservationsIds = [];

      $queryUsedTypes = "SELECT itemtype from glpi_reservationitems GROUP BY itemtype";

      if ($resultUsedTypes = $DB->query($queryUsedTypes)) {
         while ($row = $DB->fetch_assoc($resultUsedTypes)) {
            $itemtype = $row["itemtype"];

            // this only returns original GLPI types in CLI mode. Not compatible with genericobject plugin
            //        foreach ($CFG_GLPI["reservation_types"] as $itemtype) {
            //            $task->log("Checking Type : " .$itemtype);

            if (!($item = getItemForItemtype($itemtype))) {
               continue;
            }

            $itemtable = getTableForItemType($itemtype);

            $otherserial = "'' AS otherserial";
            if ($item->isField('otherserial')) {
               $otherserial = "`$itemtable`.`otherserial`";
            }
            // reservation qui vont finir
            //       AND '" . $end . "' >= `glpi_reservations`.`end`)";

            // on enlève que�"qui ne sont pas finies" car on en loupe du coup si la tache n'est pas lancée toutes les 5 minutes en permanence
            //           AND '" . $begin . "' <= `glpi_reservations`.`end`

            // on sélectionne les réservations qui ne sont pas finies et qui ont déjà démarré
            if (isset($begin) && isset($end)) {
               $left = "LEFT JOIN `glpi_reservations`
                     ON (`glpi_reservationitems`.`id` = `glpi_reservations`.`reservationitems_id`
                     AND '" . $begin . "' >= `glpi_reservations`.`begin`)";

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
                        `glpi_reservationitems`.`items_id` AS items_id
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
                        $where " . "ORDER BY username,
                        `$itemtable`.`entities_id`,
                        `$itemtable`.`name`";

            //error_log($query);

            // ajout des nouvelles reservations
            if ($resultReservations = $DB->query($query)) {
               while ($row = $DB->fetch_assoc($resultReservations)) {
                  // store it for future use
                  $task->log("Checking reservation " . $row['resaid']);
                  $reservationsIds[] = $row["resaid"];
                  $query = "SELECT * FROM `glpi_plugin_reservation_manageresa` WHERE `resaid` = " . $row["resaid"];
                  //on insere la reservation seulement si elle n'est pas deja presente dans la table
                  if ($res = $DB->query($query)) {
                     if (!$DB->numrows($res)) {
                        $task->setVolume($valreturn++);
                        $task->log("Adding reservation " . $row['resaid'] . " to the watch table");
                        $query = "INSERT INTO  `glpi_plugin_reservation_manageresa` (`resaid`, `matid`, `date_theorique`, `itemtype`) VALUES ('" . $row["resaid"] . "','" . $row["items_id"] . "','" . $row['end'] . "','" . $itemtype . "');";
                        $DB->query($query) or die("error on 'insert' into glpi_plugin_reservation_manageresa  lors du cron/ hash: " . $DB->error());
                     }
                  }
               }
            }
         }
      }
      //on va prolonger toutes les resa managées qui n'ont pas de date de retour et qui sont en retard
      $query = "SELECT * FROM `glpi_plugin_reservation_manageresa` WHERE date_return is NULL;";
      if ($resultWatchedReservations = $DB->query($query)) {
         while ($row = $DB->fetch_assoc($resultWatchedReservations)) {
            // is the reservation still exists
            if (in_array($row['resaid'], $reservationsIds)) {
               if ($end >= $row['date_theorique']) {
                  $newEnd = $temps + 5 * MINUTE_TIMESTAMP;
                  $task->setVolume($valreturn++);

                  $task->log(__('Extending reservation') . " : " . $row['resaid']);

                  // prolongation de la vrai resa
                  $current_user_id = self::find_user_from_resa($row['resaid']);
                  $res = 0;
                  $res = self::verifDisponibiliteAndMailIGS($task, $row['itemtype'], $row['matid'], $row['resaid'], $begin, date("Y-m-d H:i:s", $newEnd), $current_user_id);
                  if ($res == 0) {
                     $query = "UPDATE `glpi_reservations` SET `end`='" . date("Y-m-d H:i:s", $newEnd) . "' WHERE `id`='" . $row["resaid"] . "';";
                     $DB->query($query) or die("error on 'update' into glpi_reservations while cron : " . $DB->error());

                  }
               }
            } else // if the reservation is not here anymore, delete it
            {
               $task->setVolume($valreturn++);
               $task->log("Removing reservation " . $row['resaid'] . " from watch table as it was deleted");
               $query = "DELETE FROM `glpi_plugin_reservation_manageresa` WHERE `resaid` = " . $row["resaid"];
               $DB->query($query) or die("error on 'delete' into glpi_plugin_reservation_manageresa  while cron/ hash: " . $DB->error());
            }

         }
      }

      return $valreturn;
   }

   public static function verifDisponibiliteAndMailIGS($task, $itemtype, $idMat, $currentResa, $datedebut, $datefin, $current_user_id) {
      global $DB, $CFG_GLPI;

      $begin = $datedebut;
      $end = $datefin;

      $left = "";
      $where = "";
      $itemtable = getTableForItemType($itemtype);

      if (isset($begin) && isset($end)) {
         $left = "LEFT JOIN `glpi_reservations`
ON (`glpi_reservationitems`.`id` = `glpi_reservations`.`reservationitems_id`
AND '" . $begin . "' < `glpi_reservations`.`end`
AND '" . $end . "' > `glpi_reservations`.`begin`)";
         $where = " AND `glpi_reservations`.`id` IS NOT NULL
AND `glpi_reservations`.`id` != '" . $currentResa . "'
AND `glpi_reservationitems`.`items_id` = '" . $idMat . "'";
      }
      $query = "SELECT `glpi_reservationitems`.`id`,
`glpi_reservationitems`.`comment`,
`$itemtable`.`name` AS name,
`$itemtable`.`entities_id` AS entities_id,
`glpi_reservations`.`id` AS resaid,
`glpi_reservations`.`comment`,
`glpi_reservations`.`begin`,
`glpi_reservations`.`end`,
`glpi_reservations`.`comment`,
`glpi_users`.`name` AS username,
`glpi_reservationitems`.`items_id` AS items_id
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
$where";

      if ($result = $DB->query($query)) {
         while ($row = $DB->fetch_assoc($result)) {

            $user_id = self::find_user_from_resa($row['resaid']);
            if ($user_id == $current_user_id) {
               $task->log(" l'utilisateur " . $user_id . " (current : " . $current_user_id . ") a reservé de nouveau le matos " . $row['id'] . ", on supprime la nouvelle reservation numero " . $row['resaid']);
               $query = "UPDATE `glpi_reservations` SET `end` = '" . $row['end'] . "' WHERE `id`='" . $currentResa . "';";
               $DB->query($query) or die("error on 'update date end' into glpi_reservations lors du cron : " . $DB->error());
               $query = "UPDATE `glpi_reservations` SET `comment` = concat(comment,' //// " . $row['comment'] . "') WHERE `id`='" . $currentResa . "';";
               $DB->query($query) or die("error on 'update comment' into glpi_reservations lors du cron : " . $DB->error());
               $query = "DELETE FROM `glpi_reservations` WHERE `id`='" . $row['resaid'] . "';";
               $DB->query($query) or die("error on 'delete' into glpi_reservations lors du cron : " . $DB->error());
               $query = "DELETE FROM `glpi_plugin_reservation_manageresa` WHERE `resaid`='" . $currentResa . "';";
               $DB->query($query) or die("error on 'delete' into glpi_plugin_reservation_manageresa lors du cron : " . $DB->error());
               return 1;
            }

            $task->log("CONFLIT avec la reservation du materiel " . $row['name'] . " par " . $row['username'] . " (du " . date("\L\e d-m-Y \à H:i:s", strtotime($row['begin'])) . " au " . date("\L\e d-m-Y \à H:i:s", strtotime($row['end'])));
            $task->log("on supprime la resa numero : " . $row['resaid']);

            $reservation = new Reservation();
            $reservation->getFromDB($row['resaid']);
            NotificationEvent::raiseEvent('plugin_reservation_conflit', $reservation);

            $query = "DELETE FROM `glpi_reservations` WHERE `id`='" . $row["resaid"] . "';";
            $DB->query($query) or die("error on 'delete' into glpi_reservations lors du cron : " . $DB->error());

         }
      }
   }

   public static function find_user_from_resa($resaid) {
      global $DB, $CFG_GLPI;

      $query = "SELECT `glpi_reservations`.`users_id`
FROM `glpi_reservations`, `glpi_plugin_reservation_manageresa`
WHERE `glpi_reservations`.`id` = `glpi_plugin_reservation_manageresa`.`resaid`
AND glpi_reservations.`id` = " . $resaid;

      if ($result = $DB->query($query)) {
         while ($row = $DB->fetch_assoc($result)) {
            return $row["users_id"];
         }
      }
   }
}
