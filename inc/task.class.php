<?php

use Glpi\Event;

/**
 *
 */
class PluginReservationTask extends CommonDBTM
{
    public static function addEvents(NotificationTargetReservation $target)
    {
        $target->events['plugin_reservation_conflict_new_user'] = __("Reservation Conflict When Extended, new user (plugin)", "reservation");
        $target->events['plugin_reservation_conflict_previous_user'] = __("Reservation Conflict When Extended, previous user (plugin)", "reservation");
        $target->events['plugin_reservation_expiration'] = __("User Reservation Expired (plugin)", "reservation");
        $target->events['plugin_reservation_not_checkin'] = __("User Reservation Not Checkin (plugin)", "reservation");
        $target->events['plugin_reservation_checkin'] = __("Reservation Checkin (plugin)", "reservation");
    }

    public static function addData(NotificationTargetReservation $target)
    {
        $target->data['##reservation.otheruser##'] = "";
        if (isset($target->options['other_user_id'])) {
            $user_tmp = new User();
            if ($user_tmp->getFromDB($target->options['other_user_id'])) {
                $target->data['##reservation.otheruser##'] = $user_tmp->getName();
            }
        }
    }

    public static function addTarget(NotificationTargetReservation $target)
    {
        $target->addTagToList(['tag' => 'reservation.otheruser',
            'label' => __('Writer'),
            'value' => true,
        ]);
        // can't be done in GLPI 9.4
        //'events' => ['plugin_reservation_conflict_new_user','plugin_reservation_conflict_previous_user']]);
    }

    public static function cronInfo($name)
    {
        global $LANG;

        switch ($name) {
            case "checkReservations":
                return ['description' => __('Watch Reservations', 'reservation') . " (" . __('plugin') . ")"];
            case "sendMailLateReservations":
                return ['description' => __('Send an e-mail to users with expired reservations', 'reservation') . " (" . __('plugin') . ")"];
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
    public static function cronCheckReservations($task)
    {
        $count = 0;
        $count += self::checkExpiration($task);
        $count += self::checkGone($task);
        $task->setVolume($count);
        return $count;
    }

    public static function checkGone($task)
    {
        $return = 0;

        $config = new PluginReservationConfig();
        if ($config->getConfigurationValue("checkin", 0) == 0) {
            return $return;
        }
        if ($config->getConfigurationValue("checkin_action", 2) == 0) {
            return $return;
        }

        $time = time();
        $time -= ($time % MINUTE_TIMESTAMP);
        $timeout = $config->getConfigurationValue("checkin_timeout") * HOUR_TIMESTAMP;
        $since = date("Y-m-d H:i:s", $time - $timeout);
        $task->log("Since : " . $since);

        $reservations_list = PluginReservationReservation::getAllReservations(["`begin` <= '" . $since . "'", 'effectivedate is null', 'checkindate is null']);
        foreach ($reservations_list as $res) {
            $reservation = new Reservation();
            $reservation->getFromDB($res['reservations_id']);
            $reservationitems = $reservation->getConnexityItem('reservationitem', 'reservationitems_id');
            $item = $reservationitems->getConnexityItem($reservationitems->fields['itemtype'], 'items_id');

            if ($config->getConfigurationValue("checkin_action", 2) == 2) {
                $task->log(
                    sprintf(
                        __('Deleting reservation (check in) : %1$s on item %2$s', 'reservation'),
                        $reservation->fields['id'],
                        $item->fields['name']
                    )
                );
                $reservation->delete(['id' => $reservation->fields['id']]);
            }
            NotificationEvent::raiseEvent('plugin_reservation_not_checkin', $reservation);
            $return++;
        }
        return $return;
    }

    public static function checkExpiration($task)
    {
        global $DB, $CFG_GLPI;
        $return = 0;

        $time = time();
        $time -= ($time % MINUTE_TIMESTAMP);
        $config = new PluginReservationConfig();
        $extension_time = $config->getConfigurationValue("extension_time", 'default');
        if ($extension_time === 'default') {
            $delay = $CFG_GLPI['time_step'] * MINUTE_TIMESTAMP;
        } else {
            $delay = $extension_time * HOUR_TIMESTAMP;
        }
        $end = date("Y-m-d H:i:s", $time + $delay);
        $task->log(sprintf(__('Until : %1$s', 'reservation'), $end));

        $reservations_list = PluginReservationReservation::getAllReservations(["`end` <= '" . $end . "'", 'effectivedate is null']);

        foreach ($reservations_list as $res) {
            // bug with GLPI 9.4 ?
            // $task->log(__('Extending reservation', 'reservation') . " : " . $res['reservations_id']);
            $task->log(sprintf(__('Extending reservation : %1$s', 'reservation'), $res['reservations_id']));

            $reservation = new Reservation();
            $reservation->getFromDB($res['reservations_id']);
            $reservationitems = $reservation->getConnexityItem('reservationitem', 'reservationitems_id');
            $item = $reservationitems->getConnexityItem($reservationitems->fields['itemtype'], 'items_id');

            $conflict_reservations = PluginReservationReservation::getAllReservations(["`begin` >= '" . $reservation->fields['end'] . "'",
                "`begin` <= '" . $end . "'",
                "effectivedate is null",
                "reservationitems_id = " . $res['reservationitems_id'],
            ]);

            $query = "UPDATE `glpi_reservations`
                        SET `end` = '" . $end . "'
                        WHERE `id`='" . $reservation->fields["id"] . "'";
            $DB->query($query) or die("error on 'update' into checkReservations : " . $DB->error());
            if (count($conflict_reservations) == 0) {
                $task->log(sprintf(__('no conflict reservation !', 'reservation')));
            }

            foreach ($conflict_reservations as $conflict) {
                $conflict_reservation = new Reservation();
                $conflict_reservation->getFromDB($conflict['reservations_id']);
                $conflict_user = new User();
                $conflict_user->getFromDB($conflict['users_id']);
                $formatName = formatUserName($conflict_user->fields["id"], $conflict_user->fields["name"], $conflict_user->fields["realname"], $conflict_user->fields["firstname"]);

                // same user ?
                if ($conflict['users_id'] == $res['users_id']) {
                    $task->log(sprintf(__('%1$s created a new reservation for the same item : %2$s', 'reservation'), $formatName, $item->fields['name']));
                    $extension_counter = 0;
                    $new_comment = $reservation->fields['comment'];
                    $current_counter = [];
                    if (preg_match('/.*\(\(extension : ([0-9]+)\)\)$/', $new_comment, $current_counter)) {
                        $extension_counter = $current_counter[1];
                        $new_comment = str_replace("((extension : $extension_counter))", '', $new_comment);
                    }

                    $new_comment .= " ((extension : " . ++$extension_counter . "))";
                    $new_comment .= $conflict_reservation->fields['comment'];

                    $query = "UPDATE `glpi_reservations`
                        SET `end` = '" . $conflict_reservation->fields["end"] . "',
                           `comment` = '" . $DB->escape($new_comment) . "'
                        WHERE `id`='" . $reservation->fields["id"] . "'";
                    $DB->query($query) or die("error on 'update' into checkReservations conflict : " . $DB->error());

                    $query = "UPDATE `glpi_plugin_reservation_reservations`
                        SET `baselinedate` = '" . $conflict_reservation->fields["end"] . "'
                        WHERE `reservations_id`='" . $reservation->fields["id"] . "'";
                    $DB->query($query) or die("error on 'update' into checkReservations conflict : " . $DB->error());
                    $task->log(sprintf(__('Deleting reservation %1$s on item %2$s because a new reservation is made by same user'), $conflict_reservation->fields['id'], $item->fields['name']));
                    $conflict_reservation->delete(['id' => $conflict_reservation->fields['id']]);
                    continue;
                } else {
                    $task->log(
                        sprintf(
                            __('conflit for reservation %1$s on item %2$s used by %3$s (from %4$s to %5$s)', 'reservation'),
                            $conflict_reservation->fields['id'],
                            $item->fields['name'],
                            $formatName,
                            date("d-m-Y \à H:i:s", strtotime($conflict['begin'])),
                            date("d-m-Y \à H:i:s", strtotime($conflict['end']))
                        )
                    );
                }
                $PluginReservationConfig = new PluginReservationConfig();
                $conflict_action = $PluginReservationConfig->getConfigurationValue("conflict_action");
                switch ($conflict_action) {
                    case "delete":
                        $task->log(sprintf(__('Deleting reservation %1$s on item %2$s', 'reservation'), $conflict_reservation->fields['id'], $item->fields['name']));
                        $conflict_reservation->delete(['id' => $conflict_reservation->fields['id']]);
                        NotificationEvent::raiseEvent('plugin_reservation_conflict_new_user', $conflict_reservation, ['other_user_id' => $res['users_id']]);
                        NotificationEvent::raiseEvent('plugin_reservation_conflict_previous_user', $reservation, ['other_user_id' => $conflict['users_id']]);
                        break;
                    case "delay":
                        $end_plus_epsilon = date("Y-m-d H:i:s", $time + $delay + ($delay / 2));
                        if ($conflict_reservation->fields["end"] <= $end_plus_epsilon) {
                            $task->log(sprintf(__('Could not delay reservation %1$s on item %2$s', 'reservation'), $conflict_reservation->fields['id'], $item->fields['name']));
                            $conflict_reservation->delete(['id' => $conflict_reservation->fields['id']]);
                            NotificationEvent::raiseEvent('plugin_reservation_conflict_new_user', $conflict_reservation, ['other_user_id' => $res['users_id']]);
                            NotificationEvent::raiseEvent('plugin_reservation_conflict_previous_user', $reservation, ['other_user_id' => $conflict['users_id']]);
                            break;
                        }
                        $query = "UPDATE `glpi_reservations`
                        SET `begin` = '" . $end . "'
                        WHERE `id`='" . $conflict_reservation->fields["id"] . "'";
                        $DB->query($query) or die("error on 'update' into checkReservations conflict to delay start of a reservation : " . $DB->error());
                        $task->log(sprintf(__('Delaying reservation %1$s on item %2$s', 'reservation'), $conflict_reservation->fields['id'], $item->fields['name']));
                        break;
                }
            }
            $return++;
        }
        return $return;
    }

    public static function cronSendMailLateReservations($task)
    {
        $res = self::sendMailLateReservations($task);
        $task->setVolume($res);
        return $res;
    }

    public static function sendMailLateReservations($task)
    {
        global $DB, $CFG_GLPI;
        $result = 0;

        $time = time();
        $time -= ($time % MINUTE_TIMESTAMP);
        $now = date("Y-m-d H:i:s", $time);

        $errlocale = setlocale(LC_TIME, 'fr_FR.utf8', 'fra');
        if (!$errlocale) {
            $task->log("setlocale failed");
        }

        $reservations_list = PluginReservationReservation::getAllReservations(["`baselinedate` < '" . $now . "'", 'effectivedate is null']);

        foreach ($reservations_list as $reservation) {
            $resObj = new Reservation();
            $resObj->getFromDB($reservation['reservations_id']);
            if (NotificationEvent::raiseEvent('plugin_reservation_expiration', $resObj)) {
                $task->setVolume($result++);
                $logtext = sprintf(__('Sending e-mail for reservation %1$s', 'reservation'), $reservation['reservations_id']);
                $logtext = $logtext . sprintf(__('Expected return time was : %1$s'), $reservation['baselinedate']);
                $task->log($logtext);
                Event::log($reservation['reservations_id'], "Reservation", 4, "inventory", __('Sending an e-mail', 'reservation'));
            } else {
                $task->log(__('Could not send notification', 'reservation'));
            }
        }
        return $result;
    }
}
