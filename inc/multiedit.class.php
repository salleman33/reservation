<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

use Glpi\Event;

include_once GLPI_ROOT . "/plugins/reservation/inc/includes.php";

class PluginReservationMultiEdit extends CommonDBTM
{
    public function updateMultipleItems($post)
    {
        if (!Session::haveRight("reservation", ReservationItem::RESERVEANITEM)) {
            return false;
        }

        if (!isset($post["resa_ids"])) {
            return false;
        }
        if (!is_array($post["resa_ids"])) {
            return false;
        }
        if (count($post["resa_ids"]) < 2) {
            return false;
        }

        if (!isset($post["begin"])) {
            return false;
        }
        if (!isset($post["end"])) {
            return false;
        }

        $reservations = [];
        $inputForUpdate = [];

        foreach ($post["resa_ids"] as $resa_id) {
            $resa = new Reservation();
            if (!$resa->getFromDB($resa_id)) {
                return false;
            }

            if (!$resa->can($resa_id, UPDATE)) {
                return false;
            }

            $inputForUpdate = $resa->prepareInputForUpdate([
                'begin' => $post["begin"],
                'end'   => $post["end"],
                'comment'   => $post["comment"]
            ]);

            $reservations[$resa_id] = $resa;
        }

        $updateSuccessful = true;
        foreach ($reservations as $resa_id => $resa_instance) {
            $updateSuccessful &= $resa_instance->update(
                [
                    'id'        => (int) $resa_id,
                    'begin'     => $inputForUpdate["begin"],
                    'end'       => $inputForUpdate["end"],
                    'comment'   => $inputForUpdate["comment"]
                ]
            );
        }

        if ($updateSuccessful) {
            foreach ($reservations as $resa_id => $resa_instace) {
                Event::log(
                    $resa_id,
                    "reservation",
                    4,
                    "inventory",
                    sprintf(__('%1$s updated the reservation %2$s with new dates', 'reservation'), $_SESSION["glpiname"], $resa_id)
                );
                Toolbox::logInFile('reservations_plugin', "multiedit_update : " . $resa_id . "\n", $force = false);
            }

            return true;
        } else {
            // fail to update
            return false;
        }
    }

    public function purgeMultipleItems($post)
    {
        if (!Session::haveRight("reservation", ReservationItem::RESERVEANITEM)) {
            return false;
        }

        if (!isset($post["resa_ids"])) {
            return false;
        }
        if (!is_array($post["resa_ids"])) {
            return false;
        }
        if (count($post["resa_ids"]) < 2) {
            return false;
        }

        $reservations = [];

        foreach ($post["resa_ids"] as $resa_id) {
            $resa = new Reservation();
            if (!$resa->getFromDB($resa_id)) {
                return false;
            }

            $reservations[$resa_id] = $resa;
        }

        $purgeSuccessful = true;
        foreach ($reservations as $resa_id => $resa_instance) {
            $purgeSuccessful &= $resa_instance->delete([
                'id'    => (int) $resa_id,
                'purge' => 'purge'
            ], 1);
        }

        if ($purgeSuccessful) {
            foreach ($reservations as $resa_id => $resa_instance) {
                Event::log(
                    $resa_id,
                    "reservation",
                    4,
                    "inventory",
                    sprintf(__('%1$s purges the reservation for item %2$s', 'reservation'), $_SESSION["glpiname"], $resa_id)
                );
            }

            return true;
        } else {
            // fail to purge
            return false;
        }
    }

    public function showForm($ID, array $option = [])
    {
        global $CFG_GLPI;

        if (!Session::haveRight("reservation", ReservationItem::RESERVEANITEM)) {
            return false;
        }

        if (!isset($ID["ids"]) || !is_array($ID["ids"]) || count($ID["ids"]) < 2) {
            return false;
        }

        echo "<div class='center'>";
        echo "<form method='post' name=form action='" . $this->getFormURL() . "'>";

        echo "<table class='tab_cadre' width='700px'>";
        echo "<tr><th colspan='2'>" . __('Update multiple items', 'reservation') . "</th></tr>\n";

        $confirmedSameUser = '';
        $confirmedSameBegin = '';
        $confirmedSameEnd = '';
        $mixedComment = '';

        $options = [];
        foreach ($ID["ids"] as $resa_id) {
            $resa = new Reservation();
            if (!$resa->getFromDB($resa_id)) {
                return false;
            }

            if (!$resa->can($resa_id, UPDATE)) {
                return false;
            }

            // check that every resa have the same users_id
            if ($confirmedSameUser == '') {
                $confirmedSameUser = $resa->getField('users_id');
            } else {
                if ($confirmedSameUser != $resa->getField('users_id')) {
                    return false;
                }
            }

            // check that every resa have the same begin date
            if ($confirmedSameBegin == '') {
                $confirmedSameBegin = $resa->getField('begin');
            } else {
                if ($confirmedSameBegin != $resa->getField('begin')) {
                    return false;
                }
            }

            // check that every resa have the same end date
            if ($confirmedSameEnd == '') {
                $confirmedSameEnd = $resa->getField('end');
            } else {
                if ($confirmedSameEnd != $resa->getField('end')) {
                    return false;
                }
            }

            // pick one comment
            if ($mixedComment == '' && !empty($resa->getField('comment'))) {
                $mixedComment = $resa->getField('comment');
            }

            $itemid = $resa->getField('reservationitems_id');

            $options['item'][$itemid] = $itemid;

            echo "<input type='hidden' name='resa_ids[$resa_id]' value='$resa_id'>";
        }

        // Add Hardware name
        $r = new ReservationItem();

        echo "<tr class='tab_bg_1'><td>" . _n('Item', 'Items', count($options['item'])) . "</td>";
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
        }

        echo "</td></tr>\n";

        // user
        echo "<tr class='tab_bg_2'><td>" . __('By') . "</td>";
        echo "<td>";
        echo "<input type='hidden' name='users_id' value='" . $confirmedSameUser . "'>";
        echo Dropdown::getDropdownName(User::getTable(), $confirmedSameUser);
        echo "</td>";
        echo "</tr>\n";

        // begin
        echo "<tr class='tab_bg_2'><td>" . __('Start date') . "</td><td>";
        $rand_begin = Html::showDateTimeField(
            "resa[begin]",
            [
                'value'      => $confirmedSameBegin,
                'maybeempty' => false
            ]
        );
        echo "</td></tr>\n";
        $default_delay = floor((strtotime($confirmedSameEnd) - strtotime($confirmedSameBegin))
            / $CFG_GLPI['time_step'] / MINUTE_TIMESTAMP)
            * $CFG_GLPI['time_step'] * MINUTE_TIMESTAMP;

        // duration / end
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
            'end'          => $confirmedSameEnd,
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
        echo "</td></tr>\n";

        // comment
        echo "<tr class='tab_bg_2'><td>" . __('Comments') . "</td>";
        echo "<td><textarea name='comment' rows='8' cols='60'>" . $mixedComment . "</textarea>";
        echo "</td></tr>\n";

        // Actions
        echo "<tr class='tab_bg_2'>";

        // delete button
        if ($confirmedSameUser == Session::getLoginUserID() || Session::haveRight("reservation", PURGE)) {
            echo "<td class='top center'>";
            echo "<input type='submit' name='purge' value=\"" . _sx('button', 'Delete permanently') . "\" class='submit'>";
            echo "</td>";
        }

        // save button
        if ($confirmedSameUser == Session::getLoginUserID() || Session::haveRight("reservation", UPDATE)) {
            echo "<td class='top center'>";
            echo "<input type='submit' name='update' value=\"" . _sx('button', 'Save') . "\" class='submit'>";
            echo "</td>";
        }
        echo "</tr>\n";

        echo "</table>";
        Html::closeForm();
        echo "</div>\n";

        return true;
    }
}
