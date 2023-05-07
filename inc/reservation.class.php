<?php

use Glpi\Event;

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
    public static function getTypeName($nb = 0)
    {
        return _n('Reservation', 'Reservations', $nb, 'reservation');
    }

    public static function getMenuName()
    {
        return PluginReservationReservation::getTypeName(2);
    }

    public static function canCreate()
    {
        return Reservation::canCreate();
    }

    public static function canDelete()
    {
        return Reservation::canDelete();
    }

    public static function canUpdate()
    {
        return Reservation::canUpdate();
    }

    /**
     * get all reservations info merged with plugin_reservation info
     * @param string[] $filters [optional] filters to apply to DB request
     * @return array GLPI reservations mixed with plugin reservations
     */
    public static function getAllReservations($filters = [], $options = [])
    {
        global $DB;

        $res = [];
        $reservation_table = getTableForItemType('reservation');
        $plugin_table = getTableForItemType(__CLASS__);

        $where = "WHERE " . $plugin_table . ".reservations_id = " . $reservation_table . ".id";
        foreach ($filters as $filter) {
            $where .= " AND " . $filter;
        }

        $extra = '';
        foreach ($options as $option) {
            $extra .= " " . $option;
        }

        $query = "SELECT *
               FROM $reservation_table
                  , $plugin_table
               $where
               $extra";

        if ($result = $DB->query($query)) {
            if ($DB->numrows($result) > 0) {
                while ($row = $DB->fetchAssoc($result)) {
                    $res[] = $row;
                }
            }
        }
        return $res;
    }

    /**
     * get all available  reservation items for this date
     * @param string $begin the begin date with format "Y-m-d H:i:s"
     * @param string $end the end date with "Y-m-d H:i:s"
     * @return array array of reservations items
     */
    public static function getAvailablesItems($begin, $end)
    {
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
                            AND '" . $begin . "' < `glpi_reservations`.`end`
                            AND '" . $end . "' > `glpi_reservations`.`begin`)";

            $where = " AND `glpi_reservations`.`id` IS NULL ";

            $query = "SELECT `glpi_reservationitems`.`id`,
                          `glpi_reservationitems`.`comment`,
                          `$itemtable`.`name` AS name,
                          `$itemtable`.`entities_id` AS entities_id,
                          `glpi_reservationitems`.`items_id` AS items_id
                   FROM `glpi_reservationitems`
                   INNER JOIN `$itemtable`
                        ON (`glpi_reservationitems`.`itemtype` = '$itemtype'
                            AND `glpi_reservationitems`.`items_id` = `$itemtable`.`id`)
                   $left
                   WHERE `glpi_reservationitems`.`is_active` = '1'
                        AND `$itemtable`.`is_deleted` = '0'
                        $where " .
            getEntitiesRestrictRequest(
                " AND",
                $itemtable,
                '',
                $_SESSION['glpiactiveentities'],
                $item->maybeRecursive()
            )
                . "
                   ORDER BY `$itemtable`.`entities_id`,
                            `$itemtable`.`name` ASC";

            if ($res = $DB->query($query)) {
                while ($row = $DB->fetchAssoc($res)) {
                    $result[] = array_merge($row, ['itemtype' => $itemtype]);
                }
            }
        }
        return $result;
    }

    /**
     * Send an email for this reservation
     */
    public static function sendMail($reservation_id)
    {
        global $DB, $CFG_GLPI;
        $reservation = new Reservation();
        $reservation->getFromDB($reservation_id);
        NotificationEvent::raiseEvent('plugin_reservation_expiration', $reservation);

        $tablename = getTableForItemType(__CLASS__);
        $query = "UPDATE `" . $tablename . "` SET `mailingdate`= '" . date("Y-m-d H:i:s", time()) . "' WHERE `reservations_id` = " . $reservation_id;
        $DB->query($query) or die("error on 'update' in sendMail: " . $DB->error());

        Event::log(
            $reservation_id,
            "reservation",
            4,
            "inventory",
            sprintf(
                __('%1$s sends email for the reservation %2$s'),
                $_SESSION["glpiname"],
                $reservation_id
            )
        );
        Toolbox::logInFile('reservations_plugin', "sendMail : " . $reservation_id . "\n", $force = false);
        return true;
    }

    /**
     * checkout a reservation
     * @param integer $reservation_id the id of reservation
     */
    public static function checkoutReservation($reservation_id)
    {
        global $DB, $CFG_GLPI;

        $tablename = getTableForItemType(__CLASS__);
        $query = "UPDATE `" . $tablename . "`
               SET `effectivedate` = '" . date("Y-m-d H:i:s", time()) . "'
               WHERE `reservations_id` = '" . $reservation_id . "';";
        $DB->query($query) or die("error on checkoutReservation 1 : " . $DB->error());

        $query = "UPDATE `glpi_reservations` SET `end`='" . date("Y-m-d H:i:s", time()) . "' WHERE `id`='" . $reservation_id . "';";
        $DB->query($query) or die("error on checkoutReservation 2 : " . $DB->error());

        Event::log(
            $reservation_id,
            "reservation",
            4,
            "inventory",
            sprintf(
                __('%1$s marks the reservation %2$s as returned'),
                $_SESSION["glpiname"],
                $reservation_id
            )
        );
        Toolbox::logInFile('reservations_plugin', "checkoutReservation : " . $reservation_id . "\n", $force = false);
        return true;
    }

    /**
     * checkin a reservation
     * @since 2.2.0
     * @param integer $reservation_id the id of reservation
     */
    public static function checkinReservation($reservation_id)
    {
        global $DB;

        $resa = new Reservation();
        $resa->getFromDb($reservation_id);

        $time = time();
        $time -= ($time % MINUTE_TIMESTAMP);
        $now = date("Y-m-d H:i:s", $time);

        $input = $resa->fields;
        $input['begin'] = $now;
        $input = Toolbox::addslashes_deep($input);
        
        if ($resa->update($input)) {
            $tablename = getTableForItemType(__CLASS__);
            $query = "UPDATE `" . $tablename . "`
                  SET `checkindate` = '" . date("Y-m-d H:i:s", time()) . "'
                  WHERE `reservations_id` = '" . $reservation_id . "';";
            $DB->query($query) or die("error on checkinReservation  : " . $DB->error());

            Event::log(
                $reservation_id,
                "reservation",
                4,
                "inventory",
                sprintf(
                    __('%1$s marks the reservation %2$s as gone'),
                    $_SESSION["glpiname"],
                    $reservation_id
                )
            );
            Toolbox::logInFile('reservations_plugin', "checkinReservation : " . $reservation_id . "\n", $force = false);
            NotificationEvent::raiseEvent('plugin_reservation_checkin', $resa);
            return true;
        }
        return false;
    }

    /**
     * Add an item to an existing reservation
     * @param integer $item_id id of the item to add
     * @param integer $reservation_id id of the concerned reservation
     */
    public static function addItemToResa($item_id, $reservation_id)
    {
        $resa = new Reservation();
        $resa->getFromDb($reservation_id);

        $rr = new Reservation();
        $input = [];
        $input['reservationitems_id'] = $item_id;
        $input['comment'] = $resa->fields['comment'];
        $input['group'] = $rr->getUniqueGroupFor($item_id);
        $input['begin'] = $resa->fields['begin'];
        $input['end'] = $resa->fields['end'];
        $input['users_id'] = $resa->fields['users_id'];
        $input = Toolbox::addslashes_deep($input);
        unset($rr->fields["id"]);
        if ($newID = $rr->add($input)) {
            Event::log(
                $newID,
                "reservation",
                4,
                "inventory",
                sprintf(
                    __('%1$s adds the reservation %2$s for item %3$s'),
                    $_SESSION["glpiname"],
                    $newID,
                    $item_id
                )
            );
            Toolbox::logInFile('reservations_plugin', "addItemToResa : " . $item_id . " => " . $reservation_id . "\n", $force = false);
            return true;
        } else {
            Toolbox::logInFile('reservations_plugin', "Error in addItemToResa : " . $item_id . " <=> " . $reservation_id . "\n", $force = false);
        }
        return false;
    }

    /**
     * Replace an item to an existing reservation
     * @param integer $item_id id of the item to replace
     * @param integer $reservation_id id of the concerned reservation
     */
    public static function switchItemToResa($item_id, $reservation_id)
    {
        $resa = new Reservation();
        $resa->getFromDb($reservation_id);

        $input = $resa->fields;
        $input['reservationitems_id'] = $item_id;
        $input = Toolbox::addslashes_deep($input);

        if ($resa->update($input)) {
            Event::log(
                $reservation_id,
                "reservation",
                4,
                "inventory",
                sprintf(
                    __('%1$s switchs the reservation %2$s with item %3$s'),
                    $_SESSION["glpiname"],
                    $reservation_id,
                    $item_id
                )
            );
            Toolbox::logInFile('reservations_plugin', "switchItemToResa : " . $item_id . " <=> " . $reservation_id . "\n", $force = false);
            return true;
        } else {
            Toolbox::logInFile('reservations_plugin', "Error in switchItemToResa : " . $item_id . " <=> " . $reservation_id . "\n", $force = false);
        }
        return false;
    }
}
