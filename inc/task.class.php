<?php


$DEBUG = true;

/**
 *
 */
class PluginReservationTask extends CommonDBTM
{
   public static function addEvents(NotificationTargetReservation $target) {
      $target->events['plugin_reservation_conflict_new_user'] = __("Reservation Conflict When Extended, new user (plugin)", "reservation");
      $target->events['plugin_reservation_conflict_previous_user'] = __("Reservation Conflict When Extended, previous user (plugin)", "reservation");
      $target->events['plugin_reservation_expiration'] = __("User Reservation Expired (plugin)", "reservation");
   }

   public static function addData(NotificationTargetReservation $target) {
      $target->data['##reservation.otheruser##']   = "";
      if (isset($target->options['other_user_id']))
      {
         $user_tmp = new User();
         if ($user_tmp->getFromDB($target->options['other_user_id'])) {
            $target->data['##reservation.otheruser##'] = $user_tmp->getName();
         }
      }
   }

   public static function addTarget(NotificationTargetReservation $target) {
      $target->addTagToList(['tag' => 'reservation.otheruser',
      'label' => __('Writer'),
      'value'  => true,
      'events' => ['plugin_reservation_conflict_new_user','plugin_reservation_conflict_previous_user']]);
   }

   public static function cronInfo($name) {
      global $LANG;

      switch ($name) {
         case "checkReservations":
            return ['description' => __('Watch Reservations', 'reservation') . " (" . __('plugin') . ")"];
         case "sendMailLateReservations":
            return ['description' => __('Send an e-mail to users with late reservations', 'reservation') . " (" . __('plugin') . ")"];
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
      $res = self::checkReservations($task);
      $task->setVolume($res);
      return $res;
   }


   public static function checkReservations($task) {
      global $DB, $CFG_GLPI;
      $return = 0;

      $time = time();
      $time -= ($time % MINUTE_TIMESTAMP);
      $begin = date("Y-m-d H:i:s", $time);
      $delay = $CFG_GLPI['time_step'] * MINUTE_TIMESTAMP;
      $end = date("Y-m-d H:i:s", $time + $delay);
      $task->log("Until : " . $end);

      $reservations_list = PluginReservationReservation::getAllReservations(["`end` <= '".$end."'", 'effectivedate is null']);
      //Toolbox::logInFile('reservations_plugin', "reservations_list : ".json_encode($reservations_list)."\n", $force = false);

      foreach ($reservations_list as $res) {
         $task->log(__('Extending reservation', 'reservation') . " : " . $res['reservations_id']);

         $reservation = new Reservation();
         $reservation->getFromDB($res['reservations_id']);
         $reservationitems = $reservation->getConnexityItem('reservationitem', 'reservationitems_id');
         $item = $reservationitems->getConnexityItem($reservationitems->fields['itemtype'], 'items_id');

         $conflict_reservations = PluginReservationReservation::getAllReservations(["`begin` >= '".$reservation->fields['end']."'",
                                                                           "`begin` <= '".$end."'",
                                                                           "reservationitems_id = ".$res['reservationitems_id']]);

         $query = "UPDATE `glpi_reservations` 
                        SET `end` = '".$end."' 
                        WHERE `id`='" . $reservation->fields["id"] . "'";
         $DB->query($query) or die("error on 'update' into checkReservations : " . $DB->error());

         foreach ($conflict_reservations as $conflict) {
            $conflict_reservation = new Reservation();
            $conflict_reservation->getFromDB($conflict['reservations_id']);
            $conflict_user = new User();
            $conflict_user->getFromDB($conflict['users_id']);
            $formatName = formatUserName($conflict_user->fields["id"], $conflict_user->fields["name"], $conflict_user->fields["realname"], $conflict_user->fields["firstname"]);

            // same user ?
            if ($conflict['users_id'] == $res['users_id']) {
               $task->log("$formatName a créé une nouvelle reservation pour le meme materiel : " . $item->fields['name']);
               $new_comment = "(".$reservation->fields['comment'].")";
               $new_comment .= " ==(" .date("d-m-Y", $time). ")==> ".$conflict_reservation->fields['comment'];

               $query = "UPDATE `glpi_reservations` 
                        SET `end` = '".$conflict_reservation->fields["end"]."',
                           `comment` = '".$DB->escape($new_comment)."'
                        WHERE `id`='" . $reservation->fields["id"] . "'";
               $DB->query($query) or die("error on 'update' into checkReservations conflict : " . $DB->error());

               $query = "UPDATE `glpi_plugin_reservation_reservations`
                        SET `baselinedate` = '".$conflict_reservation->fields["end"]."'
                        WHERE `reservations_id`='" . $reservation->fields["id"] . "'";
               $DB->query($query) or die("error on 'update' into checkReservations conflict : " . $DB->error());
            } else {
               $task->log("conflit avec la reservation " . $conflict_reservation->fields['id'] . " du materiel " . $item->fields['name'] . " par " . $formatName . " (du " . date("d-m-Y \à H:i:s", strtotime($conflict['begin'])) . " au " . date("d-m-Y \à H:i:s", strtotime($conflict['end']).")"));
               NotificationEvent::raiseEvent('plugin_reservation_conflict_new_user', $conflict_reservation, ['other_user_id' => $res['users_id']]);
               NotificationEvent::raiseEvent('plugin_reservation_conflict_previous_user', $res, ['other_user_id' => $conflict['users_id']]);
            }
            $PluginReservationConfig = new PluginReservationConfig();
            $conflict_action = $PluginReservationConfig->getConfigurationValue("conflict_action");
            switch ($conflict_action) {
               case "delete":
                  $task->log("Suppression de la reservation  " . $conflict_reservation->fields['id'] . " du materiel ". $item->fields['name']);
                  $conflict_reservation->delete(['id' => $conflict_reservation->fields['id']]);
                  break;
               case "delay":
                  if ($conflict_reservation->fields["end"] <= $end) {
                     $task->log("Impossible de retarder le debut de la reservation " . $conflict_reservation->fields['id'] . " du materiel ". $item->fields['name']);
                     $conflict_reservation->delete(['id' => $conflict_reservation->fields['id']]);
                     break;
                  }
                  $query = "UPDATE `glpi_reservations`
                        SET `begin` = '".$end."'
                        WHERE `id`='" . $conflict_reservation->fields["id"] . "'";
                  $DB->query($query) or die("error on 'update' into checkReservations conflict to delay start of a reservation : " . $DB->error());
                  $task->log("Retardement du debut de la reservation  " . $conflict_reservation->fields['id'] . " du materiel ". $item->fields['name']);
                  break;
            }
         }

         $task->setVolume($return++);
      }
      return $return;
   }

   public static function cronSendMailLateReservations($task) {
      $res = self::sendMailLateReservations($task);
      $task->setVolume($res);
      return $res;
   }

   public static function sendMailLateReservations($task) {
      global $DB, $CFG_GLPI;
      $result = 0;

      $time = time();
      $time -= ($time % MINUTE_TIMESTAMP);
      $now = date("Y-m-d H:i:s", $time);

      $config = new PluginReservationConfig();
      $week = $config->getConfigurationWeek();
      $errlocale = setlocale(LC_TIME, 'fr_FR.utf8', 'fra');
      if (!$errlocale) {
         $task->log("setlocale failed");
      }

      $reservations_list = PluginReservationReservation::getAllReservations(["`baselinedate` < '".$now."'", 'effectivedate is null']);

      foreach ($reservations_list as $reservation) {
         $res = new Reservation();
         $res->getFromDB($reservation['reservations_id']);
         if (NotificationEvent::raiseEvent('plugin_reservation_expiration', $reservation)) {
            $task->setVolume($result++);
            $logtext = sprintf(__('Sending e-mail for reservation %1$s'), $reservation['reservations_id']);
            $logtext = $logtext . sprintf(__('Expected return time was : %1$s'), $reservation['baselinedate']);
            $task->log($logtext);
            Event::log($reservation['reservations_id'], "Reservation", 4, "inventory", __('Sending an e-mail'));
         } else {
            $task->log(__('Could not send notification'));
         }
      }
      return $result;
   }
}
