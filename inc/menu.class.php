<?php

use Glpi\Application\View\TemplateRenderer;
use Glpi\RichText\RichText;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

function getToolTipforItem($item)
{
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
    if ($show_group && $item->isField('groups_id')) {
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
    if ($show_status && $item->isField("states_id")) {
        $status = getStatusFromItem($item);
        $toolTip .= "<br><b>" . __('Status') . " : </b>" . $status;
    }
    // $tooltip = nl2br($toolTip);
    // Html::showToolTip($tooltip, null);
    $res = Html::showToolTip(
                    RichText::getEnhancedHtml($toolTip),
                    ['display' => false]
    );
    return $res;
}

function getGroupFromItem($item)
{
    $group_id = $item->fields["groups_id"];
    $group_tmp = new Group();
    $group_tmp->getFromDB($group_id);
    return $group_tmp->getName();
}

function getLocationFromItem($item)
{
    $location_id = $item->fields["locations_id"];
    $location_tmp = new Location();
    $location_tmp->getFromDB($location_id);
    return $location_tmp->getName();
}

function getStatusFromItem($item)
{
    $states_id = $item->fields["states_id"];
    $states_tmp = new State();
    $states_tmp->getFromDB($states_id);
    return $states_tmp->getName();
}

function getManufacturerFromItem($item)
{
    $manufacturer_id = $item->fields["manufacturers_id"];
    $manufacturer_tmp = new Manufacturer();
    $manufacturer_tmp->getFromDB($manufacturer_id);
    return $manufacturer_tmp->getName();
}

function getModelFromItem($item)
{
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
                    FROM `$modeltable`
                    WHERE `$modeltable`.`id` = " . $item->fields[$modelfield];

                    if ($resmodel = $DB->doQuery($query)) {
                        while ($rowModel = $DB->fetchAssoc($resmodel)) {
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
    public static function getTypeName($nb = 0)
    {
        return PluginReservationReservation::getTypeName($nb);
    }

    public static function getMenuName()
    {
        return PluginReservationMenu::getTypeName(2);
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

    public static function canView(): bool
    {
        return Reservation::canView();
    }

    public static function getForbiddenActionsForMenu()
    {
        return ['add', 'template'];
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addStandardTab(__CLASS__, $ong, $options);
        return $ong;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        $ong = [];
        $config = new PluginReservationConfig();
        $i = 1;
        if ($config->getConfigurationValue("tabmine", 0)) {
            $ong[$i] = __('My Reservations', "reservation");
            $i++;
        }
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

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        $config = new PluginReservationConfig();
        $tabmine = $config->getConfigurationValue("tabmine", 0);
        $tabcurrent = $config->getConfigurationValue("tabcurrent", 1);
        $tabcoming = $config->getConfigurationValue("tabcoming", 0);

        $tabs = [];
        if ($tabmine) {
            array_push($tabs, 'PluginReservationMenu::displayTabContentForMyReservations');
        }

        if ($tabcurrent) {
            array_push($tabs, 'PluginReservationMenu::displayTabContentForCurrentReservations');
        }

        if ($tabcoming) {
            array_push($tabs, 'PluginReservationMenu::displayTabContentForAllReservations');
        }

        array_push($tabs, 'PluginReservationMenu::displayTabContentForAvailableHardware');
        // array_push($tabs, 'ReservationItem::showListSimple');

        if (array_key_exists($tabnum - 1, $tabs)) {
            $tabs[$tabnum - 1]($item);
        }
        return true;
    }

    public static function getDateFormat()
    {
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
    public static function arrayGroupBy($array, $element)
    {
        $result = [];
        foreach ($array as $one) {
            $result[$one[$element]][] = $one;
        }
        return $result;
    }

    public static function displayTabContentForMyReservations()
    {
        $form_dates = $_SESSION['glpi_plugin_reservation_form_dates'];
        $begin = $form_dates["begin"];
        $end = $form_dates["end"];
        $user_id = $_SESSION['glpiID'];

        $filters = ["'" . $begin . "' < `end`", "'" . $end . "' > `begin`", "'" . $user_id . "' = `users_id`"];
        $list = PluginReservationReservation::getAllReservations($filters);
        $ReservationsOfUser = self::arrayGroupBy($list, 'users_id');
        ksort($ReservationsOfUser);

        self::displayTabReservations($begin, $end, $ReservationsOfUser, true);
    }

    /**
     *
     */
    public static function displayTabContentForCurrentReservations()
    {
        
        global $CFG_GLPI, $DB;
        
        if (!Session::haveRightsOr(ReservationItem::$rightname, [READ, ReservationItem::RESERVEANITEM])) {
            return false;
        }

        $form_dates = $_SESSION['glpi_plugin_reservation_form_dates'];
        $begin = $form_dates["begin"];
        $end = $form_dates["end"];

        $ok         = false;
        $showentity = Session::isMultiEntitiesMode();
        
        $reservations = PluginReservationReservation::getReservations(['start' => $begin, 'end' => $end]);
        $entries = [];
        $entity_cache = [];

        $user_id = $_SESSION['glpiID'];
        $config = new PluginReservationConfig();
        $mode_auto = $config->getConfigurationValue("mode_auto");
        $checkin_enable = $config->getConfigurationValue("checkin", 0);
        $only_ckeckin_own = $config->getConfigurationValue("only_ckeckin_own", 1);

        foreach ($reservations as $res) {
            $can_be_edited = (!$only_ckeckin_own || $user_id == $res['users_id']) || Session::haveRight("reservation", DELETE);

            $entry = [
                'checkbox' => Html::getCheckbox([
                    'name'  => "item[" . $res["id"] . "]",
                    'value' => $res["id"],
                    'zero_on_empty' => false,
                ]),
                'user'     => $res['user'],
                'title'    => $res['title'],
                'start'    => $res['start'],
                'end'      => $res['end'],
                'comment'  => RichText::getSafeHtml($res["comment"]),
                'itemtype' => $res['itemtype'],
                'id'       => $res['id'],
                'entity'   => '',
            ];
            if ($res['start'] > $end) {
                $entry['row_class'] = 'futur';
            }

            if ($res['baselinedate'] < $end
                && $res['baselinedate'] < date("Y-m-d H:i:s", time())
                && $res['effectivedate'] == null) {
                $entry['row_class'] = 'expired';
            }

            $entry['moves'] = '';
            if (date("Y-m-d", strtotime($res['start'])) == date("Y-m-d", strtotime($begin))) {
                $entry['moves'] .= "<img title=\"\" alt=\"\" src=\"../pics/up-icon.png\"></img>";
            }
            if (date("Y-m-d", strtotime($res['baselinedate'])) == date("Y-m-d", strtotime($end))) {
                $entry['moves'] .= "<img title=\"\" alt=\"\" src=\"../pics/down-icon.png\"></img>";
            }

            if ($checkin_enable) {
                $entry['checkin'] = '';
                if ($res['checkindate'] != null) {
                    $entry['checkin'] = date(Toolbox::getDateFormat('php')  . " H:i", strtotime($res['checkindate']));
                } else {
                    if ($can_be_edited) {
                        // $entry['checkin'] .= '<td id="checkin' . $res['id'] . '">';
                        $entry['checkin'] .= '<center>';
                        $entry['checkin'] .= '<a class="bouton" href="javascript:void(0);" onclick="checkin(' . $res['id'] . ')">';
                        $entry['checkin'] .= '<img title="' . _sx('tooltip', 'Set As Gone', "reservation") . '" alt="" src="../pics/redbutton.png"></img>';
                        $entry['checkin'] .= '</a></center>';
                        // </td>';
                    }
                }
            }

            $entry['checkout'] = '';
            if ($res['effectivedate'] != null) {
                $entry['checkout'] = date(Toolbox::getDateFormat('php') . " \à H:i:s", strtotime($res['effectivedate']));
            } else {
                if ($can_be_edited) {
                    // $entry['checkout'] .= '<td id="checkout' . $res['id'] . '">';
                    $entry['checkout'] .= '<center>';
                    $entry['checkout'] .= '<a class="bouton" href="javascript:void(0);" onclick="checkout(' . $res['id'] . ')">';
                    $entry['checkout'] .= '<img title="' . _sx('tooltip', 'Set As Returned', "reservation") . '" alt="" src="../pics/greenbutton.png"></img>';
                    $entry['checkout'] .= '</a></center>';
                    // </td>';
                } 
            }
            
            $entry['action'] = '';
            if ($can_be_edited) {
                // action
                $available_reservationsitem = PluginReservationReservation::getAvailablesItems($res['start'], $res['end']);
                // $entry['action'] .= "<td>";
                $entry['action'] .= '<ul style="list-style: none";>';

                // add item ti ti-cube-plus
                $entry['action'] .= "<li><span class=\"bouton\" id=\"bouton_add" . $res['id'] . "\" onclick=\"javascript:afficher_cacher('add" . $res['id'] . "');\">" . _sx('button', 'Add an item') . "</span>
                    <div id=\"add" . $res['id'] . "\" style=\"display:none;\">
                    <form method='POST' name='form' action='" . Toolbox::getItemTypeSearchURL(__CLASS__) . "'>";
                $entry['action'] .= '<select name="add_item">';
                foreach ($available_reservationsitem as $item) {
                    $entry['action'] .= "\t";
                    $entry['action'] .= '<option value="';
                    $entry['action'] .= $item['id'];
                    $entry['action'] .= '">';
                    $entry['action'] .= getItemForItemtype($item['itemtype'])->getTypeName() . ' - ' . $item["name"];
                    $entry['action'] .= '</option>';
                }

                $entry['action'] .= "<input type='hidden' name='add_item_to_reservation' value='" . $res['id'] . "'>";
                $entry['action'] .= "<input type='submit' class='submit' name='add' value=" . _sx('button', 'Add') . ">";
                $entry['action'] .= "<i class='ti repeat fa-2x cursor-pointer' title=\"" . __s("Reserve this item") . "\"></i>";

                $entry['action'] .= Html::closeForm(false);
                $entry['action'] .= "</div></li>";

                // switch item ti ti-replace
                $entry['action'] .= "<li><span class=\"bouton\" id=\"bouton_replace" . $res['id'] . "\" onclick=\"javascript:afficher_cacher('replace" . $res['id'] . "');\">" . _sx('button', 'Replace an item', 'reservation') . "</span>
                    <div id=\"replace" . $res['id'] . "\" style=\"display:none;\">
                <form method='post' name='form' action='" . Toolbox::getItemTypeSearchURL(__CLASS__) . "'>";
                $entry['action'] .= '<select name="switch_item">';
                foreach ($available_reservationsitem as $item) {
                    $entry['action'] .= "\t";
                    $entry['action'] .= '<option value="';
                    $entry['action'] .= $item['id'];
                    $entry['action'] .= '">';
                    $entry['action'] .= getItemForItemtype($item['itemtype'])->getTypeName() . ' - ' . $item["name"];
                    $entry['action'] .= '</option>';
                }
                $entry['action'] .= "<input type='hidden' name='switch_item_to_reservation' value='" . $res['id'] . "'>";
                $entry['action'] .= "<input type='submit' class='submit' name='submit' value=" . _sx('button', 'Save') . ">";
                $entry['action'] .= Html::closeForm(false);
                $entry['action'] .= "</div></li>";
                $entry['action'] .= "</ul>";
                $entry['action'] .= "</td>";

                // // Edit ti ti-edit
                // $rowspan_line = 1;
                // $multiEditParams = [];
                // $multiEditParams[$res['id']] = $res['id'];
                // if ($count == $rowspan_end_bis) {
                //     $i = $count;
                //     while ($i < count($reservations_user_list)) {
                //         if (
                //             $reservations_user_list[$i]['begin'] == $res['begin']
                //             && $reservations_user_list[$i]['end'] == $reservation_user_info['end']
                //         ) {
                //             $rowspan_line++;
                //             $multiEditParams[$reservations_user_list[$i]['reservations_id']] = $reservations_user_list[$i]['reservations_id'];
                //         } else {
                //             break;
                //         }
                //         $i++;
                //     }
                //     if ($rowspan_line > 1) {
                //         $rowspan_end_bis = $count + $rowspan_line;
                //     } else {
                //         $rowspan_end_bis++;
                //     }

                //     if ($rowspan_line > 1) {
                //         $str_multiEditParams = "?";
                //         foreach ($multiEditParams as $key => $value) {
                //             $str_multiEditParams = $str_multiEditParams . "&ids[$key]=$value";
                //         }

                //         // case if multi edit enabled for first item
                //         $entry['action'] .= "<td class='showIfMultiEditEnabled' rowspan='" . $rowspan_line . "'>";
                //         $entry['action'] .= "<a class='bouton' title='" . __('Edit multiple', 'reservation') . "' onclick=\"makeAChange('" . 'multiedit.form.php' . $str_multiEditParams . "');\"   href=\"javascript:void(0);\">" . __('Edit multiple', 'reservation') . "</a>";
                //         $entry['action'] .= "</td>";

                //         // case if multi edit disable for first item
                //         $entry['action'] .= "<td class='hideIfMultiEditEnabled' style='display: none;'>";
                //         $entry['action'] .= '<ul style="list-style: none;">';
                //         $entry['action'] .= "<li><a class=\"bouton\" title=\"" . __('Edit') . "\" onclick=\"makeAChange('" . Toolbox::getItemTypeFormURL('Reservation') . "?id=" . $reservation_user_info['reservations_id'] . "');\" href=\"javascript:void(0);\">" . _sx('button', 'Edit') . "</a></li>";
                //         $entry['action'] .= "</ul>";
                //         $entry['action'] .= "</td>";
                //     } else {
                //         // normal case (no group)
                //         $entry['action'] .= "<td>";
                //         $entry['action'] .= '<ul style="list-style: none;">';
                //         $entry['action'] .= "<li><a class=\"bouton\" title=\"" . __('Edit') . "\" onclick=\"makeAChange('" . Toolbox::getItemTypeFormURL('Reservation') . "?id=" . $reservation_user_info['reservations_id'] . "');\" href=\"javascript:void(0);\">" . _sx('button', 'Edit') . "</a></li>";
                //         $entry['action'] .= "</ul>";
                //         $entry['action'] .= "</td>";
                //     }
                // } else {
                //     // case if multi edit enabled for other items
                //     $entry['action'] .= "<td class='hideIfMultiEditEnabled' style='display: none;'>";
                //     $entry['action'] .= '<ul style="list-style: none;">';
                //     $entry['action'] .= "<li><a class=\"bouton\" title=\"" . __('Edit') . "\" onclick=\"makeAChange('" . Toolbox::getItemTypeFormURL('Reservation') . "?id=" . $reservation_user_info['reservations_id'] . "');\"  href=\"javascript:void(0);\">" . _sx('button', 'Edit') . "</a></li>";
                //     $entry['action'] .= "</ul>";
                //     $entry['action'] .= "</td>";
                // }
            } else {
                // $entry['action'] .= '<td>';
                // $entry['action'] .= '</td>';
                // $entry['action'] .= '<td>';
                // $entry['action'] .= '</td>';
            }

            // if (!$mode_auto) {
            //     $entry['action'] .= "<td>";
            //     $entry['action'] .= '<ul style="list-style: none;">';
            //     if ($reservation_user_info['baselinedate'] < date("Y-m-d H:i", time()) && $reservation_user_info['effectivedate'] == null) {
            //         $entry['action'] .= '<li id="mailed' . $reservation_user_info['reservations_id'] . '">';
            //         $entry['action'] .= '<a class="bouton" href="javascript:void(0);" onclick="mailuser(' . $reservation_user_info['reservations_id'] . ')" title="' . _sx('tooltip', 'Send an e-mail for the late reservation', "reservation") . '">';
            //         $entry['action'] .= _sx('button', 'Send an e-mail', "reservation");
            //         $entry['action'] .= '</a></li>';
            //         if (isset($reservation_user_info['mailingdate'])) {
            //             echo "<li>" . __('Last e-mail sent on', "reservation") . " </li>";
            //             echo "<li>" . date(self::getDateFormat() . " H:i", strtotime($reservation_user_info['mailingdate'])) . "</li>";
            //         }
            //     }
            //     echo "</ul>";
            //     echo "</td>";
            // }

            if ($showentity) {
                if (!isset($entity_cache[$row["entities_id"]])) {
                    $entity_cache[$row["entities_id"]] = Dropdown::getDropdownName("glpi_entities", $row["entities_id"]);
                }
                $entry['entity'] = $entity_cache[$row["entities_id"]];
            }



            $ok = true;
            $entries[] = $entry;
        }
        
        

        $columns = [
            'checkbox' => [
                'label' => Html::getCheckAllAsCheckbox('nosearch'),
                'raw_header' => true,
            ],
            'user' => __('User'),
            'title' => ReservationItem::getTypeName(1),
            'start' => __('Begin'),
            'end' => __('End'),
            'comment' => _n('Comment', 'Comments', 1),
            'moves' => __('Moves', 'reservation'),
        ];
        if ($checkin_enable) {
            $columns['checkin'] = Entity::getTypeName(1);
        }
        $columns['checkout'] = __('Checkout', 'reservation');
        $columns['action'] = __('Action');
        if ($showentity) {
            $columns['entity'] = Entity::getTypeName(1);
        }
        

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => $columns,
            'formatters' => [
                'checkbox' => 'raw_html',
                'title' => 'raw_html',
                'comment' => 'raw_html',
                'start' => 'datatime',
                'end' => 'datetime',
                'moves' => 'raw_html',
                'checkin' => 'raw_html',
                'checkout' => 'raw_html',
                'action' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => false,
        ]);

        // $filters = ["'" . $begin . "' < `end`", "'" . $end . "' > `begin`"];
        // $list = PluginReservationReservation::getAllReservations($filters);
        // $ReservationsByUser = self::arrayGroupBy($list, 'users_id');
        // ksort($ReservationsByUser);
        // //Toolbox::logInFile('reservations_plugin', "reservations_list : ".json_encode($list)."\n", $force = false);
        // self::displayTabReservations($begin, $end, $ReservationsByUser, false);
    }



    /**
     *
     */
    public static function displayTabContentForAllReservations()
    {
        $form_dates = $_SESSION['glpi_plugin_reservation_form_dates'];
        $begin = $form_dates["begin"];
        $end = $form_dates["end"];

        $filters = ["'" . $begin . "' < `end`"];
        $list = PluginReservationReservation::getAllReservations($filters);
        $ReservationsByUser = self::arrayGroupBy($list, 'users_id');
        ksort($ReservationsByUser);

        self::displayTabReservations($begin, $end, $ReservationsByUser, true);
    }

    private static function filterEntitiesItems($ReservationsByUser)
    {
        global $DB;
        $filteredArray = [];

        foreach ($ReservationsByUser as $reservation_user => $reservations_user_list) {
            $itemsForUser = [];

            foreach ($reservations_user_list as $reservation_user_info) {
                $reservation = new Reservation();
                $reservation->getFromDB($reservation_user_info['reservations_id']);
                $reservationitems = $reservation->getConnexityItem('reservationitem', 'reservationitems_id');
                $item = $reservationitems->getConnexityItem($reservationitems->fields['itemtype'], 'items_id');

                if (Session::haveAccessToEntity($item->fields["entities_id"])) {
                    array_push($itemsForUser, $reservation_user_info);
                }
            }

            if (count($itemsForUser)) {
                $filteredArray[$reservation_user] = $itemsForUser;
            }
        }
        return $filteredArray;
    }

    /**
     *
     */
    private static function displayTabReservations($begin, $end, $listResaByUser, $includeFuture)
    {
        global $DB;

        $user_id = $_SESSION['glpiID'];
        $ReservationsByUser = self::filterEntitiesItems($listResaByUser);

        $showentity = Session::isMultiEntitiesMode();
        $config = new PluginReservationConfig();
        $mode_auto = $config->getConfigurationValue("mode_auto");
        $checkin_enable = $config->getConfigurationValue("checkin", 0);
        $only_ckeckin_own = $config->getConfigurationValue("only_ckeckin_own", 1);

        echo "<div class='center'>";
        echo "<table class='tab_cadre'>";
        echo "<thead>";
        $colums = 11;
        $colums_action = 3;
        if ($mode_auto) {
            $colums--;
            $colums_action--;
        }
        if ($checkin_enable) {
            $colums++;
        }

        if ($includeFuture) {
            echo "<tr><th colspan='" . $colums . "'>" . __('Current and future reservations', 'reservation') . "</th></tr>\n";
        } else {
            echo "<tr><th colspan='" . $colums . "'>" . __('Reservations in the selected timeline', 'reservation') . "</th></tr>\n";
        }
        echo "<tr class='tab_bg_2'>";
        echo "<th>" . __('User') . "</a></th>";
        echo "<th colspan='2'>" . __('Item') . "</a></th>";
        echo "<th>" . __('Begin') . "</a></th>";
        echo "<th>" . __('End') . "</a></th>";
        echo "<th>" . __('Comment') . "</a></th>";
        echo "<th>" . __('Moves', 'reservation') . "</a></th>";
        if ($checkin_enable) {
            echo "<th>" . __('Checkin', 'reservation') . "</th>";
        }
        echo "<th>" . __('Checkout', 'reservation') . "</th>";
        // Multi edit enabled by default
        echo "<th colspan='" . $colums_action . "'>" . __('Action') . " (<label><input class='allowMultipleEditCheckbox' type='checkbox' onclick='onClickAllowMultipleEditCheckbox(this);' checked /> " . __('Allow multiple edit', 'reservation') . "</label>)</th>";

        echo "</tr></thead>";
        echo "<tbody>";

        foreach ($ReservationsByUser as $reservation_user => $reservations_user_list) {
            usort($reservations_user_list, function ($a, $b) {
                return strnatcmp($a['begin'], $b['begin']);
            });
            $user = new User();
            $user->getFromDB($reservation_user);
            $reservation_user_id = $user->fields["id"];

            $can_be_edited = (!$only_ckeckin_own || $user_id == $reservation_user_id) || Session::haveRight("reservation", DELETE);

            echo "<tr class='tab_bg_2'>";
            echo "<td colspan='100%' bgcolor='lightgrey' style='padding:1px;'/>";
            echo "</tr>";

            echo "<tr class='tab_bg_2'>";
            // user name
            $formatName = formatUserName($reservation_user_id, $user->fields["name"], $user->fields["realname"], $user->fields["firstname"]);
            echo "<td rowspan=" . count($reservations_user_list) . ">" . $formatName . "</td>";

            $count = 0;
            $rowspan_end = 1;
            $rowspan_end_bis = 1;
            $multiEditParams = [];
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
                        if (
                            $reservations_user_list[$i]['begin'] == $reservation_user_info['begin']
                            && $reservations_user_list[$i]['end'] == $reservation_user_info['end']
                        ) {
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
                    echo "<td rowspan=" . $rowspan_line . " $color>" . date(self::getDateFormat() . " H:i", strtotime($reservation->fields['begin'])) . "</td>";
                    // date end
                    echo "<td rowspan=" . $rowspan_line . " $color>" . date(self::getDateFormat() . " H:i", strtotime($reservation_user_info['baselinedate'])) . "</td>";

                    // comment
                    echo "<td rowspan=" . $rowspan_line . " $color>" . $reservation->fields['comment'] . "</td>";

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

                // checkin buttons or date checkin
                if ($checkin_enable) {
                    if ($reservation_user_info['checkindate'] != null) {
                        echo "<td>" . date(self::getDateFormat() . " H:i", strtotime($reservation_user_info['checkindate'])) . "</td>";
                    } else {
                        if ($can_be_edited) {
                            echo '<td id="checkin' . $reservation_user_info['reservations_id'] . '">';
                            echo '<center>';
                            echo '<a class="bouton" href="javascript:void(0);" onclick="checkin(' . $reservation_user_info['reservations_id'] . ')">';
                            echo '<img title="' . _sx('tooltip', 'Set As Gone', "reservation") . '" alt="" src="../pics/redbutton.png"></img>';
                            echo '</a></center></td>';
                        } else {
                                echo '<td id="checkin' . $reservation_user_info['reservations_id'] . '">';
                                echo '</td>';
                        }
                    }
                }

                // checkout buttons or date checkout
                if ($reservation_user_info['effectivedate'] != null) {
                    echo "<td>" . date(self::getDateFormat() . " \à H:i:s", strtotime($reservation_user_info['effectivedate'])) . "</td>";
                } else {
                    if ($can_be_edited) {
                        echo '<td id="checkout' . $reservation_user_info['reservations_id'] . '">';
                        echo '<center>';
                        echo '<a class="bouton" href="javascript:void(0);" onclick="checkout(' . $reservation_user_info['reservations_id'] . ')">';
                        echo '<img title="' . _sx('tooltip', 'Set As Returned', "reservation") . '" alt="" src="../pics/greenbutton.png"></img>';
                        echo '</a></center></td>';
                    } else {
                        echo '<td id="checkout' . $reservation_user_info['reservations_id'] . '">';
                        echo '</td>';
                    }
                }

                if ($can_be_edited) {
                    // action
                    $available_reservationsitem = PluginReservationReservation::getAvailablesItems($reservation->fields['begin'], $reservation->fields['end']);
                    echo "<td>";
                    echo '<ul style="list-style: none";>';

                    // add item
                    echo "<li><span class=\"bouton\" id=\"bouton_add" . $reservation_user_info['reservations_id'] . "\" onclick=\"javascript:afficher_cacher('add" . $reservation_user_info['reservations_id'] . "');\">" . _sx('button', 'Add an item') . "</span>
                        <div id=\"add" . $reservation_user_info['reservations_id'] . "\" style=\"display:none;\">
                        <form method='POST' name='form' action='" . Toolbox::getItemTypeSearchURL(__CLASS__) . "'>";
                    echo '<select name="add_item">';
                    foreach ($available_reservationsitem as $item) {
                        echo "\t", '<option value="', $item['id'], '">', getItemForItemtype($item['itemtype'])->getTypeName() . ' - ' . $item["name"], '</option>';
                    }

                    echo "<input type='hidden' name='add_item_to_reservation' value='" . $reservation_user_info['reservations_id'] . "'>";
                    echo "<input type='submit' class='submit' name='add' value=" . _sx('button', 'Add') . ">";
                    Html::closeForm();
                    echo "</div></li>";

                    // switch item
                    echo "<li><span class=\"bouton\" id=\"bouton_replace" . $reservation_user_info['reservations_id'] . "\" onclick=\"javascript:afficher_cacher('replace" . $reservation_user_info['reservations_id'] . "');\">" . _sx('button', 'Replace an item', 'reservation') . "</span>
                        <div id=\"replace" . $reservation_user_info['reservations_id'] . "\" style=\"display:none;\">
                    <form method='post' name='form' action='" . Toolbox::getItemTypeSearchURL(__CLASS__) . "'>";
                    echo '<select name="switch_item">';
                    foreach ($available_reservationsitem as $item) {
                        echo "\t", '<option value="', $item['id'], '">', getItemForItemtype($item['itemtype'])->getTypeName() . ' - ' . $item["name"], '</option>';
                    }
                    echo "<input type='hidden' name='switch_item_to_reservation' value='" . $reservation_user_info['reservations_id'] . "'>";
                    echo "<input type='submit' class='submit' name='submit' value=" . _sx('button', 'Save') . ">";
                    Html::closeForm();
                    echo "</div></li>";
                    echo "</ul>";
                    echo "</td>";

                    // Edit
                    $rowspan_line = 1;
                    $multiEditParams = [];
                    $multiEditParams[$reservation_user_info['reservations_id']] = $reservation_user_info['reservations_id'];
                    if ($count == $rowspan_end_bis) {
                        $i = $count;
                        while ($i < count($reservations_user_list)) {
                            if (
                                $reservations_user_list[$i]['begin'] == $reservation_user_info['begin']
                                && $reservations_user_list[$i]['end'] == $reservation_user_info['end']
                            ) {
                                $rowspan_line++;
                                $multiEditParams[$reservations_user_list[$i]['reservations_id']] = $reservations_user_list[$i]['reservations_id'];
                            } else {
                                break;
                            }
                            $i++;
                        }
                        if ($rowspan_line > 1) {
                            $rowspan_end_bis = $count + $rowspan_line;
                        } else {
                            $rowspan_end_bis++;
                        }

                        if ($rowspan_line > 1) {
                            $str_multiEditParams = "?";
                            foreach ($multiEditParams as $key => $value) {
                                $str_multiEditParams = $str_multiEditParams . "&ids[$key]=$value";
                            }

                            // case if multi edit enabled for first item
                            echo "<td class='showIfMultiEditEnabled' rowspan='" . $rowspan_line . "'>";
                            echo "<a class='bouton' title='" . __('Edit multiple', 'reservation') . "' onclick=\"makeAChange('" . 'multiedit.form.php' . $str_multiEditParams . "');\"   href=\"javascript:void(0);\">" . __('Edit multiple', 'reservation') . "</a>";
                            echo "</td>";

                            // case if multi edit disable for first item
                            echo "<td class='hideIfMultiEditEnabled' style='display: none;'>";
                            echo '<ul style="list-style: none;">';
                            echo "<li><a class=\"bouton\" title=\"" . __('Edit') . "\" onclick=\"makeAChange('" . Toolbox::getItemTypeFormURL('Reservation') . "?id=" . $reservation_user_info['reservations_id'] . "');\" href=\"javascript:void(0);\">" . _sx('button', 'Edit') . "</a></li>";
                            echo "</ul>";
                            echo "</td>";
                        } else {
                            // normal case (no group)
                            echo "<td>";
                            echo '<ul style="list-style: none;">';
                            echo "<li><a class=\"bouton\" title=\"" . __('Edit') . "\" onclick=\"makeAChange('" . Toolbox::getItemTypeFormURL('Reservation') . "?id=" . $reservation_user_info['reservations_id'] . "');\" href=\"javascript:void(0);\">" . _sx('button', 'Edit') . "</a></li>";
                            echo "</ul>";
                            echo "</td>";
                        }
                    } else {
                        // case if multi edit enabled for other items
                        echo "<td class='hideIfMultiEditEnabled' style='display: none;'>";
                        echo '<ul style="list-style: none;">';
                        echo "<li><a class=\"bouton\" title=\"" . __('Edit') . "\" onclick=\"makeAChange('" . Toolbox::getItemTypeFormURL('Reservation') . "?id=" . $reservation_user_info['reservations_id'] . "');\"  href=\"javascript:void(0);\">" . _sx('button', 'Edit') . "</a></li>";
                        echo "</ul>";
                        echo "</td>";
                    }
                } else {
                    echo '<td>';
                    echo '</td>';
                    echo '<td>';
                    echo '</td>';
                }

                if (!$mode_auto) {
                    echo "<td>";
                    echo '<ul style="list-style: none;">';
                    if ($reservation_user_info['baselinedate'] < date("Y-m-d H:i", time()) && $reservation_user_info['effectivedate'] == null) {
                        echo '<li id="mailed' . $reservation_user_info['reservations_id'] . '">';
                        echo '<a class="bouton" href="javascript:void(0);" onclick="mailuser(' . $reservation_user_info['reservations_id'] . ')" title="' . _sx('tooltip', 'Send an e-mail for the late reservation', "reservation") . '">';
                        echo _sx('button', 'Send an e-mail', "reservation");
                        echo '</a></li>';
                        if (isset($reservation_user_info['mailingdate'])) {
                            echo "<li>" . __('Last e-mail sent on', "reservation") . " </li>";
                            echo "<li>" . date(self::getDateFormat() . " H:i", strtotime($reservation_user_info['mailingdate'])) . "</li>";
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
     *  largement inspiré de la methode ReservationItem::showListSimple
     */
    static function getReservationTypes() 
    {
        global $CFG_GLPI, $DB;

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

        $iterator = $DB->request([
            'SELECT'    => [
                'glpi_peripheraltypes.name',
                'glpi_peripheraltypes.id',
            ],
            'FROM'      => 'glpi_peripheraltypes',
            'LEFT JOIN' => [
                'glpi_peripherals'      => [
                    'ON' => [
                        'glpi_peripheraltypes'  => 'id',
                        'glpi_peripherals'      => 'peripheraltypes_id',
                    ],
                ],
                'glpi_reservationitems' => [
                    'ON' => [
                        'glpi_reservationitems' => 'items_id',
                        'glpi_peripherals'      => 'id',
                    ],
                ],
            ],
            'WHERE'     => [
                'itemtype'           => 'Peripheral',
                'is_active'          => 1,
                'peripheraltypes_id' => ['>', 0],
            ] + getEntitiesRestrictCriteria('glpi_reservationitems', 'entities_id', $_SESSION['glpiactiveentities'], true),
            'ORDERBY'   => 'glpi_peripheraltypes.name',
        ]);

        foreach ($iterator as $ptype) {
            $id = $ptype['id'];
            $reservation_types["Peripheral#$id"] = $ptype['name'];
        }

        return $reservation_types;
    }

    /**
     *  largement inspiré de la methode ReservationItem::showListSimple
     */
    public static function displayTabContentForAvailableHardware()
    {
        global $CFG_GLPI, $DB;
        
        if (!Session::haveRightsOr(ReservationItem::$rightname, [READ, ReservationItem::RESERVEANITEM])) {
            return false;
        }

        $ok         = false;
        $showentity = Session::isMultiEntitiesMode();

        if (isset($_SESSION['glpi_saved']['PluginReservationMenu'])) {
            $_POST = $_SESSION['glpi_saved']['PluginReservationMenu'];
        }

        // GET method passed to form creation
        echo "<div id='nosearch' class='card'>";
        echo "<form name='form' method='GET' action='" . htmlescape(Reservation::getFormURL()) . "'>";

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
                'FROM'   => ReservationItem::getTable(),
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
            $begin = $_SESSION['glpi_plugin_reservation_form_dates']["begin"];
            $end   = $_SESSION['glpi_plugin_reservation_form_dates']["end"];

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
                $entry = [
                    'itemtype' => $itemtype,
                    'id'       => $row['id'],
                    'checkbox' => Html::getCheckbox([
                        'name'  => "item[" . $row["id"] . "]",
                        'value' => $row["id"],
                        'zero_on_empty' => false,
                    ]),
                    'entity'   => '',
                ];

                $typename = $item::getTypeName();
                if ($itemtype === Peripheral::class) {
                    $item->getFromDB($row['items_id']);
                    if (
                        isset($item->fields["peripheraltypes_id"])
                         && ((int) $item->fields["peripheraltypes_id"] !== 0)
                    ) {
                        $typename = Dropdown::getDropdownName(
                            "glpi_peripheraltypes",
                            $item->fields["peripheraltypes_id"]
                        );
                    }
                }
                
                $item_link = htmlescape(sprintf(__('%1$s - %2$s'), $typename, $row["name"]));
                if ($itemtype::canView()) {
                    $item_link = "<a href='" . htmlescape($itemtype::getFormURLWithId($row['items_id'])) . "&forcetab=Reservation$1'>"
                        . $item_link
                        . "</a>";
                    if (PluginReservationConfig::getConfigurationValue("tooltip")) {
                        $item->getFromDB($row['items_id']);
                        $item_link .= getToolTipforItem($item);
                    }
                    
                }
                $entry['item'] = $item_link;

                if (!isset($location_cache[$row["location"]])) {
                    $location_cache[$row["location"]] = Dropdown::getDropdownName("glpi_locations", $row["location"]);
                }
                $entry['location'] = $location_cache[$row["location"]];

                $entry['comment'] = RichText::getSafeHtml($row["comment"]);

                if ($showentity) {
                    if (!isset($entity_cache[$row["entities_id"]])) {
                        $entity_cache[$row["entities_id"]] = Dropdown::getDropdownName("glpi_entities", $row["entities_id"]);
                    }
                    $entry['entity'] = $entity_cache[$row["entities_id"]];
                }
                $cal_href = htmlescape(Reservation::getSearchURL() . "?reservationitems_id=" . $row['id']);
                $entry['calendar'] = "<a href='$cal_href'>";
                $entry['calendar'] .= "<i class='" . htmlescape(Planning::getIcon()) . " fa-2x cursor-pointer' title=\"" . __s("Reserve this item") . "\"></i>";

                $ok = true;
                $entries[] = $entry;
            }
        }

        $columns = [
            'checkbox' => [
                'label' => Html::getCheckAllAsCheckbox('nosearch'),
                'raw_header' => true,
            ],
            'item' => self::getTypeName(1),
            'location' => Location::getTypeName(1),
            'comment' => _n('Comment', 'Comments', 1),
        ];
        if ($showentity) {
            $columns['entity'] = Entity::getTypeName(1);
        }
        $columns['calendar'] = __("Booking calendar");
        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => $columns,
            'formatters' => [
                'checkbox' => 'raw_html',
                'item' => 'raw_html',
                'comment' => 'raw_html',
                'calendar' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => false,
        ]);
        
        if ($ok && Session::haveRight("reservation", ReservationItem::RESERVEANITEM)) {
            echo "<i class='ti ti-corner-left-up mx-3'></i>";
            echo "<th colspan='" . ($showentity ? "5" : "4") . "'>";
            if (isset($_SESSION['glpi_saved']['PluginReservationMenu'])) {
                echo Html::hidden('begin', ['value' => $begin]);
                echo Html::hidden('end', ['value'   => $end]);
            }
            echo Html::submit(_x('button', 'Book'), [
                'class' => 'btn btn-primary mt-2 mb-2',
                'icon'  => 'ti ti-calendar-plus',
            ]);
        }

        echo "<input type='hidden' name='id' value=''>";
        echo "</form>";// No CSRF token needed
        echo "</div>";
    }
    // {
    //     $showentity = Session::isMultiEntitiesMode();
    //     $form_dates = $_SESSION['glpi_plugin_reservation_form_dates'];

    //     $begin = $form_dates["begin"];
    //     $end = $form_dates["end"];

    //     echo "<div class='center'>\n";
    //     echo "<form name='form' method='GET' action='" . Reservation::getFormURL() . "'>\n";
    //     echo "<table class='tab_cadre' style=\"border-spacing:20px;\">\n";
    //     echo "<tr>";

    //     $plugin_config = new PluginReservationConfig();
    //     $custom_categories = $plugin_config->getConfigurationValue("custom_categories", 0);

    //     if ($custom_categories) {
    //         $available_reservationsitem = PluginReservationCategory::getReservationItems($begin, $end, true);
    //         self::displayItemsInCustomCategories($available_reservationsitem);
    //     } else {
    //         $available_reservationsitem = PluginReservationReservation::getAvailablesItems($begin, $end);
    //         self::displayItemsInTypesCategories($available_reservationsitem);
    //     }

    //     echo "</tr>";
    //     echo "<tr class='tab_bg_1 center'><td colspan='" . ($showentity ? "5" : "4") . "'>";
    //     echo "<input type='submit' value='" . __('Create new reservation', "reservation") . "' class='submit'></td></tr>\n";

    //     echo "</table>\n";

    //     echo "<input type='hidden' name='id' value=''>";
    //     echo "<input type='hidden' name='begin' value='" . $begin . "'>";
    //     echo "<input type='hidden' name='end' value='" . $end . "'>";
    //     Html::closeForm();
    //     echo "</div>";
    // }

    private static function displayItemsInCustomCategories($available_reservationsitem = [])
    {
        global $CFG_GLPI;
        $plugin_config = new PluginReservationConfig();
        $use_items_types = $plugin_config->getConfigurationValue("use_items_types", 0);

        $categories_names = PluginReservationCategory::getCategoriesNames();

        // display the custom categories first
        foreach ($categories_names as $category_name) {
            if ($category_name != 'zzpluginnotcategorized') {
                $category_items = array_filter(
                    $available_reservationsitem,
                    function ($element) use ($category_name) {
                        return ($element['category_name'] == $category_name);
                    }
                );

                self::displayCategory($category_name, $category_items);
            }
        }

        // display the remaining items
        $remaining_items = array_filter(
            $available_reservationsitem,
            function ($element) {
                return $element['category_name'] == 'zzpluginnotcategorized' || is_null($element['category_name']);
            }
        );

        if ($use_items_types) {
            self::displayItemsInTypesCategories($remaining_items);
        } else {
            self::displayCategory('', $remaining_items);
        }
    }

    private static function displayItemsInTypesCategories($available_reservationsitem = [])
    {
        global $CFG_GLPI;

        foreach ($CFG_GLPI["reservation_types"] as $itemtype) {
            $type_items = array_filter(
                $available_reservationsitem,
                function ($element) use ($itemtype) {
                    return ($element['itemtype'] == $itemtype);
                }
            );

            $item = getItemForItemtype($itemtype);
            self::displayCategory($item->getTypeName(), $type_items);
        }
    }

    private static function displayCategory($category_name = '', $category_items = [])
    {
        if (empty($category_items)) {
            return;
        }

        $showentity = Session::isMultiEntitiesMode();
        echo "<td style=\"display:inline-block;\" valign=\"top\">";

        echo "\n\t<table class='tab_cadre'>";
        echo "<tr><th colspan='" . ($showentity ? "6" : "5") . "'>" . $category_name . "</th></tr>\n";
        foreach ($category_items as $reservation_item) {
            var_dump("toto");
            var_dump( getItemForItemtype($reservation_item['itemtype']));
            $item = getItemForItemtype($reservation_item['itemtype']);
            $item->getFromDB($reservation_item['items_id']);
            echo "<td>";
            echo Html::getCheckbox([
                'name' => "item[" . $reservation_item["id"] . "]",
                "value" => $reservation_item["id"],
                "zero_on_empty" => false,
            ]);
            echo "</td>";

            echo "<td>";
            echo Html::link($item->fields['name'], $item->getFormURLWithID($item->fields['id']));
            echo "</td>";
            echo "<td>" . nl2br($reservation_item['comment'] ?? '') . "</td>";

            if ($showentity) {
                echo "<td>" . Dropdown::getDropdownName("glpi_entities", $reservation_item["entities_id"]) . "</td>";
            }

            echo "<td>";
            getToolTipforItem($item);
            echo "</td>";

            echo "<td><a title=\"Show Calendar\" href='../../../front/reservation.php?reservationitems_id=" . $reservation_item['id'] . "'><i class=\"far fa-calendar-alt\"></i></a></td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
        echo "</td>";
    }

    /**
     * Display the form with begin and end dates, next day, previous day, etc.
     */
    public function showFormDate()
    {
        global $CFG_GLPI, $DB;
        // $form_dates = $_SESSION['glpi_plugin_reservation_form_dates'];

        // echo "<div id='viewresasearch'  class='center'>";
        // echo "<table class='tab_cadre' style='background-color:transparent;box-shadow:none'>";

        // echo "<tr>";
        // echo "<td>";
        // $this->showCurrentMonthForAllLink();
        // echo "</td>";
        // echo "<td>";

        // echo "<form method='post' name='form' action='" . Toolbox::getItemTypeSearchURL(__CLASS__) . "'>";
        // echo "<table class='tab_cadre'><tr class='tab_bg_2'>";
        // echo "<th colspan='6'>" . __('Date') . "</th>";
        // echo "</tr>";

        // echo "<tr class='tab_bg_2'>";

        // echo "<td rowspan='3'>";
        // echo "<input type='submit' class='submit' name='previousday' value='" . __('Previous') . "'>";
        // echo "</td>";

        // echo "<td>" . __('Start date') . "</td><td>";
        // Html::showDateTimeField('date_begin', ['value' => $form_dates["begin"], 'maybeempty' => false]);
        // echo "</td><td rowspan='3'>";
        // echo "<input type='submit' class='submit' name='submit' value=\"" . _sx('button', 'Search') . "\">";
        // echo "</td>";
        // echo "<td rowspan='3'>";
        // echo "<input type='submit' class='submit' name='nextday' value='" . __('Next') . "'>";
        // echo "</td>";
        // echo "<td rowspan='3'>";
        // echo '<a class="fa fa-undo reset-search" href="' . Toolbox::getItemTypeSearchURL(__CLASS__) . '?reset=reset" title="' . __('Reset') . '"><span class="sr-only">' . __('Reset') . '</span></a>';
        // echo "</td>";
        // echo "</tr>";

        // echo "<tr class='tab_bg_2'><td>" . __('End date') . "</td><td>";
        // Html::showDateTimeField('date_end', ['value' => $form_dates["end"], 'maybeempty' => false]);
        // echo "</td></tr>";
        // echo "</td></tr>";
        // echo "</table>";

        // Html::closeForm();

        // echo "</td>";
        // echo "</tr>";
        // echo "</table>";

        // echo "</div>";
      
        
    }

    /**
     * Link with current month reservations
     */
    // public function showCurrentMonthForAllLink()
    // {
    //     global $CFG_GLPI;
    //     if (!Session::haveRight("reservation", "1")) {
    //         return false;
    //     }
    //     $mois_courant = intval(date('m'));
    //     $annee_courante = date('Y');

    //     $mois_courant = intval($mois_courant);

    //     $all = "<a class='vsubmit' href='../../../front/reservation.php?reservationitems_id=&amp;mois_courant=" . "$mois_courant&amp;annee_courante=$annee_courante'>" . __('Show all') . "</a>";

    //     echo "<div class='center'>";
    //     echo "<table class='tab_cadre'>";
    //     echo "<tr><th colspan='2'>" . __('Reservations This Month', "reservation") . "</th></tr>\n";
    //     echo "<td>";
    //     echo "<img src='" . $CFG_GLPI["root_doc"] . "/pics/reservation.png' alt=''>";
    //     echo "</td>";
    //     echo "<td >$all</td>\n";
    //     echo "</table>";
    //     echo "</div>";
    // }
}
