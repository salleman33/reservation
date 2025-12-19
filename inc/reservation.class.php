<?php

use Glpi\Event;
use Glpi\RichText\RichText;

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

    public static function canCreate(): bool
    {
        return Reservation::canCreate();
    }

    public static function canDelete(): bool
    {
        return Reservation::canDelete();
    }

    public static function canUpdate(): bool
    {
        return Reservation::canUpdate();
    }

    /*
    * largement inspiré de Reservation::getEvents
    */
    public static function getReservations(array $params): array
    {
        global $DB, $CFG_GLPI;

        $defaults = [
            'start'               => '',
            'end'                 => '',
            'reservationitems_id' => 0,
        ];
        $params = array_merge($defaults, $params);

        $start = date("Y-m-d H:i:s", strtotime($params['start']));
        $end   = date("Y-m-d H:i:s", strtotime($params['end']));

        $res_table   = Reservation::getTable();
        $res_i_table = ReservationItem::getTable();
        $plugin_table = static::getTable();

        $can_read    = Session::haveRight("reservation", READ);
        $can_edit    = Session::getCurrentInterface() === "central" && Session::haveRight("reservation", UPDATE);
        $can_reserve = Session::haveRight("reservation", ReservationItem::RESERVEANITEM);

        $user = new User();

        $where = [];
        if ($params['reservationitems_id'] > 0) {
            $where = [
                "$res_table.reservationitems_id" => $params['reservationitems_id'],
            ];
        }
        
        $iterator = $DB->request([
            'SELECT'     => [
                "$res_table.id",
                "$res_table.begin",
                "$res_table.end",
                "$res_table.comment",
                "$res_table.users_id",
                "$res_i_table.items_id",
                "$res_i_table.itemtype",
                "$plugin_table.baselinedate",
                "$plugin_table.effectivedate",
                "$plugin_table.checkindate",
                "$plugin_table.mailingdate"
            ],
            'FROM'       => $res_table,
            'INNER JOIN' => [
                $res_i_table => [
                    'ON' => [
                        $res_i_table => 'id',
                        $res_table   => 'reservationitems_id',
                    ],
                ],
                $plugin_table => [
                    'ON' => [
                        $plugin_table => 'reservations_id',
                        $res_table => 'id',
                    ]
                ]
            ],
            'WHERE' => [
                'end'   => ['>', $start],
                'begin' => ['<', $end],
            ] + $where,
        ]);


        $reservations = [];
        if (!count($iterator)) {
            return [];
        }
        foreach ($iterator as $data) {
            $item = getItemForItemtype($data['itemtype']);
            if (!$item->getFromDB($data['items_id'])) {
                continue;
            }
            if (!Session::haveAccessToEntity($item->getEntityID(), $item->isRecursive())) {
                continue;
            }

            $my_item = $data['users_id'] === Session::getLoginUserID();

            $data['comment'] = RichText::getSafeHtml($data['comment']);
            if ($can_read || $my_item) {
                $user->getFromDB($data['users_id']);
                // $data['comment'] .= '<br />' . htmlescape(sprintf(__("Reserved by %s"), $user->getFriendlyName()));
                $data['user'] = $user->getFriendlyName();
            }            

            $name = $item->getName([
                'complete' => true,
            ]);

            $editable = $can_edit || ($can_reserve && $my_item);

            $reservations[] = [
                'id'          => $data['id'],
                'resourceId'  => $data['itemtype'] . "-" . $data['items_id'],
                'start'       => $data['begin'],
                'end'         => $data['end'],
                'users_id'        => $data['users_id'],
                'user'        => $data['user'],
                'baselinedate'=> $data['baselinedate'],
                'effectivedate' => $data['effectivedate'],
                'checkindate' => $data['checkindate'],
                'mailingdate' => $data['mailingdate'],
                'comment'     => $can_read || $my_item ? $data['comment'] : '',
                'title'       => $params['reservationitems_id'] ? "" : $name,
                'icon'        => $item->getIcon(),
                'description' => $item->getTypeName(),
                'itemtype'    => $data['itemtype'],
                'items_id'    => $data['items_id'],
                'color'       => Toolbox::getColorForString($name),
                'ajaxurl'     => $CFG_GLPI['root_doc'] . '/ajax/reservations.php?action=add_edit_reservation_fromselect&id=' . $data['id'],
                'editable'    => $editable, // "editable" is used by fullcalendar, but is not accessible
                '_editable'   => $editable, // "_editable" will be used by custom event handlers
            ];
        }

        return $reservations;
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

        if ($result = $DB->doQuery($query)) {
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
        $ok         = false;
        $showentity = Session::isMultiEntitiesMode();
        $reservation_types     = [];

        $iterator = $DB->request([
            'SELECT'          => 'itemtype',
            'DISTINCT'        => true,
            'FROM'            => 'glpi_reservationitems',
            'WHERE'           => [
                'is_active' => 1,
            ] + getEntitiesRestrictCriteria('glpi_reservationitems', 'entities_id', $_SESSION['glpiactiveentities'], true),
        ]);

        foreach ($iterator as $data) {
            /** @var array{itemtype: string} $data */
            if (is_a($data['itemtype'], CommonDBTM::class, true)) {
                $reservation_types[$data['itemtype']] = $data['itemtype']::getTypeName();
            }
        }

        $entries = [];
        $location_cache = [];
        $entity_cache = [];
        foreach ($CFG_GLPI["reservation_types"] as $itemtype) {
            if (!($item = getItemForItemtype($itemtype))) {
                continue;
            }
            $itemtable = getTableForItemType($itemtype);
            $itemname  = $item::getNameField();

            $otherserial = new QueryExpression($DB->quote('') . ' AS ' . $DB::quoteName('otherserial'));
            if ($item->isField('otherserial')) {
                $otherserial = "$itemtable.otherserial AS otherserial";
            }
            $criteria = [
                'SELECT' => [
                    'glpi_reservationitems.id',
                    'glpi_reservationitems.comment',
                    "$itemtable.$itemname AS name",
                    "$itemtable.entities_id AS entities_id",
                    $otherserial,
                    'glpi_locations.id AS location',
                    'glpi_reservationitems.items_id AS items_id',
                ],
                'FROM'   => 'glpi_reservationitems',
                'INNER JOIN'   => [
                    $itemtable  => [
                        'ON'  => [
                            'glpi_reservationitems' => 'items_id',
                            $itemtable              => 'id', [
                                'AND' => [
                                    'glpi_reservationitems.itemtype' => $itemtype,
                                ],
                            ],
                        ],
                    ],
                ],
                'LEFT JOIN'    =>  [
                    'glpi_locations'  => [
                        'ON'  => [
                            $itemtable        => 'locations_id',
                            'glpi_locations'  => 'id',
                        ],
                    ],
                ],
                'WHERE'        => [
                    'glpi_reservationitems.is_active'   => 1,
                    "$itemtable.is_deleted"             => 0,
                ] + getEntitiesRestrictCriteria($itemtable, '', $_SESSION['glpiactiveentities'], $item->maybeRecursive()),
                'ORDERBY'      => [
                    "$itemtable.entities_id",
                    "$itemtable.$itemname",
                ],
            ];

            if (isset($begin, $end)) {
                $criteria['LEFT JOIN']['glpi_reservations'] = [
                    'ON'  => [
                        'glpi_reservationitems' => 'id',
                        'glpi_reservations'     => 'reservationitems_id', [
                            'AND' => [
                                'glpi_reservations.end'    => ['>', $begin],
                                'glpi_reservations.begin'  => ['<', $end],
                            ],
                        ],
                    ],
                ];
                $criteria['WHERE'][] = ['glpi_reservations.id' => null];
            }
            if (!empty($_POST["reservation_types"])) {
                $tmp = explode('#', $_POST["reservation_types"]);
                $criteria['WHERE'][] = ['glpi_reservationitems.itemtype' => $tmp[0]];
                if (
                    isset($tmp[1]) && ($tmp[0] === Peripheral::class)
                    && ($itemtype === Peripheral::class)
                ) {
                    $criteria['LEFT JOIN']['glpi_peripheraltypes'] = [
                        'ON' => [
                            'glpi_peripherals'      => 'peripheraltypes_id',
                            'glpi_peripheraltypes'  => 'id',
                        ],
                    ];
                    $criteria['WHERE'][] = ["$itemtable.peripheraltypes_id" => $tmp[1]];
                }
            }

            // Filter locations if location was provided/submitted
            if ((int) ($_POST['locations_id'] ?? 0) > 0) {
                $criteria['WHERE'][] = [
                    'glpi_locations.id' => getSonsOf('glpi_locations', (int) $_POST['locations_id']),
                ];
            }

        

            $iterator = $DB->request($criteria);
            foreach ($iterator as $row) {
                // while ($row = $DB->fetchAssoc($iterator)) {
                //     $result[] = array_merge($row, ['itemtype' => $itemtype]);
                // }
                
                $entry = [
                    'itemtype' => $itemtype,
                    'id'       => $row['id'],
                    'items_id' => $row["id"],
                    'name'  => $row["name"],
                    'entity'   => '',
                ];            

                if ($showentity) {
                    if (!isset($entity_cache[$row["entities_id"]])) {
                        $entity_cache[$row["entities_id"]] = Dropdown::getDropdownName("glpi_entities", $row["entities_id"]);
                    }
                    $entry['entity'] = $entity_cache[$row["entities_id"]];
                }
                $cal_href = htmlescape(Reservation::getSearchURL() . "?reservationitems_id=" . $row['id']);
                $ok = true;
                $entries[] = $entry;
            }
        }

        return $entries;
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
        $DB->doQuery($query) or die("error on 'update' in sendMail: " . $DB->error());

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
        $DB->doQuery($query) or die("error on checkoutReservation 1 : " . $DB->error());

        $query = "UPDATE `glpi_reservations` SET `end`='" . date("Y-m-d H:i:s", time()) . "' WHERE `id`='" . $reservation_id . "';";
        $DB->doQuery($query) or die("error on checkoutReservation 2 : " . $DB->error());

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

        if ($resa->update($input)) {
            $tablename = getTableForItemType(__CLASS__);
            $query = "UPDATE `" . $tablename . "`
                  SET `checkindate` = '" . date("Y-m-d H:i:s", time()) . "'
                  WHERE `reservations_id` = '" . $reservation_id . "';";
            $DB->doQuery($query) or die("error on checkinReservation  : " . $DB->error());

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
