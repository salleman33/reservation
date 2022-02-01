<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

include_once GLPI_ROOT . "/plugins/reservation/inc/includes.php";

class PluginReservationMultiEdit extends CommonDBTM
{
    public function showForm($ID)
    {
        global $CFG_GLPI;

        if (!Session::haveRight("reservation", ReservationItem::RESERVEANITEM)) {
            return false;
        }

        if (!isset($ID["ids"]) || empty($ID["ids"]) || !is_array($ID["ids"])) {
            return false;
        }

        if (count($ID["ids"]) <= 1) {
            // redirect to normal way
            Html::redirect(Toolbox::getItemTypeFormURL('Reservation') . "?id=" . array_shift($ID["ids"]));
        }

        echo "<div class='center'>";
        // TODO change the action path to deal with multiple items
        echo "<form method='post' name=form action='" . Reservation::getFormURL() . "'>";

        echo "<table class='tab_cadre' width='700px'>";
        echo "<tr><th colspan='2'>" . __('Edit multiple items') . "</th></tr>\n";

        $options = [];
        foreach ($ID["ids"] as $resa_id) {
            $resa = new Reservation();
            if (!$resa->getFromDB($resa_id)) {
                return false;
            }

            if (!$resa->can($resa_id, UPDATE)) {
                return false;
            }

            $itemid = $resa->getField('reservationitems_id');

            $options['item'][$itemid] = $itemid;
        }
        // TODO every time $resa is used, there is a possible problem.
        // TODO ensure that every resa have been made by the same user and got the same begin and end date

        // Add Hardware name
        $r = new ReservationItem();

        echo "<tr class='tab_bg_1'><td>" . _n('Item', 'Items', 1) . "</td>";
        echo "<td>";
        foreach ($options['item'] as $itemID) {
            $r->getFromDB($itemID);
            $type = $r->fields["itemtype"];
            $name = NOT_AVAILABLE;
            $item = null;

            if ($item = getItemForItemtype($r->fields["itemtype"])) {
                $type = $item->getTypeName();

                if ($item->getFromDB($r->fields["items_id"])) {
                    $name = $item->getName();
                } else {
                    $item = null;
                }
            }

            echo "<span class='b'>" . sprintf(__('%1$s - %2$s'), $type, $name) . "</span><br>";
            echo "<input type='hidden' name='items[$itemID]' value='$itemID'>";
        }

        echo "</td></tr>\n";

        // TODO ensure same user for each resa
        $uid = $resa->fields['users_id'];

        echo "<tr class='tab_bg_2'><td>" . __('By') . "</td>";
        echo "<td>";
        if (
            !Session::haveRight("reservation", UPDATE)
            || is_null($item)
            || !Session::haveAccessToEntity($item->fields["entities_id"])
        ) {

            echo "<input type='hidden' name='users_id' value='" . $uid . "'>";
            echo Dropdown::getDropdownName(
                User::getTable(),
                $uid
            );
        } else {
            User::dropdown([
                'value'        => $uid,
                'entity'       => $item->getEntityID(),
                'entity_sons'  => $item->isRecursive(),
                'right'        => 'all'
            ]);
        }
        echo "</td></tr>\n";
        echo "<tr class='tab_bg_2'><td>" . __('Start date') . "</td><td>";
        $rand_begin = Html::showDateTimeField(
            "resa[begin]",
            [
                'value'      => $resa->fields["begin"],
                'maybeempty' => false
            ]
        );
        echo "</td></tr>\n";
        $default_delay = floor((strtotime($resa->fields["end"]) - strtotime($resa->fields["begin"]))
            / $CFG_GLPI['time_step'] / MINUTE_TIMESTAMP)
            * $CFG_GLPI['time_step'] * MINUTE_TIMESTAMP;

        // FIX
        $default_delay = 0;
        //

        echo "<tr class='tab_bg_2'><td>" . __('Duration') . "</td><td>";
        $rand = Dropdown::showTimeStamp(
            "resa[_duration]",
            [
                'min'        => 0,
                'max'        => 24 * HOUR_TIMESTAMP,
                'value'      => $default_delay,
                'emptylabel' => __('Specify an end date')
            ]
        );
        echo "<br><div id='date_end$rand'></div>";
        $params = [
            'duration'     => '__VALUE__',
            'end'          => $resa->fields["end"],
            'name'         => "resa[end]"
        ];

        Ajax::updateItemOnSelectEvent(
            "dropdown_resa[_duration]$rand",
            "date_end$rand",
            $CFG_GLPI["root_doc"] . "/ajax/planningend.php",
            $params
        );

        if ($default_delay == 0) {
            $params['duration'] = 0;
            Ajax::updateItem("date_end$rand", $CFG_GLPI["root_doc"] . "/ajax/planningend.php", $params);
        }
        // todo deal with multiple items
        Alert::displayLastAlert('Reservation', array_shift($ID["ids"]));
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_2'><td>" . __('Comments') . "</td>";
        echo "<td><textarea name='comment' rows='8' cols='60'>" . $resa->fields["comment"] . "</textarea>";
        echo "</td></tr>\n";


        if (($resa->fields["users_id"] == Session::getLoginUserID())
            || Session::haveRightsOr(static::$rightname, [PURGE, UPDATE])
        ) {
            echo "<tr class='tab_bg_2'>";
            if (($resa->fields["users_id"] == Session::getLoginUserID())
                || Session::haveRight(static::$rightname, PURGE)
            ) {
                echo "<td class='top center'>";
                echo "<input type='submit' name='purge' value=\"" . _sx('button', 'Delete permanently') . "\"
                        class='submit'>";
                if ($resa->fields["group"] > 0) {
                    echo "<br><input type='checkbox' name='_delete_group'>&nbsp;" .
                        __s('Delete all repetition');
                }
                echo "</td>";
            }
            if (($resa->fields["users_id"] == Session::getLoginUserID())
                || Session::haveRight(static::$rightname, UPDATE)
            ) {
                echo "<td class='top center'>";
                echo "<input type='submit' name='update' value=\"" . _sx('button', 'Save') . "\"
                       class='submit'>";
                echo "</td>";
            }
            echo "</tr>\n";
        }

        echo "</table>";
        Html::closeForm();
        echo "</div>\n";
    }
}
