<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

include_once GLPI_ROOT . "/plugins/reservation/inc/includes.php";

class PluginReservationConfig extends CommonDBTM
{
    public function getConfigurationValue($name, $defaultValue = 0)
    {
        global $DB;
        $query = "SELECT * FROM glpi_plugin_reservation_configs WHERE `name`='" . $name . "'";
        $value = $defaultValue;
        if ($result = $DB->query($query)) {
            if ($DB->numrows($result) > 0) {
                while ($row = $DB->fetchAssoc($result)) {
                    $value = $row['value'];
                }
            }
        }
        return $value;
    }

    public function setConfigurationValue($name, $value = '')
    {
        global $DB;

        if ($value != '') {
            $query = "INSERT INTO glpi_plugin_reservation_configs (name,value) VALUES('" . $name . "','" . $value . "') ON DUPLICATE KEY UPDATE value=Values(value)";
            $DB->query($query) or die($DB->error());
        }
    }

    public function setMailAutomaticAction($value = 1)
    {
        global $DB;

        $query = "UPDATE `glpi_crontasks` SET state='" . $value . "' WHERE name = 'sendMailLateReservations'";
        $DB->query($query) or die($DB->error());
    }

    public function showForm($ID, array $option = [])
    {
        echo "<form id=\"formPluginReservationConfigs\" method='post' action='" . $this->getFormURL() . "'>";
        if ($ID === 1) {
            $this->mainConfigView();
        }
        if ($ID === 2) {
            $this->categoryConfigView($option[0]);
        }
        Html::closeForm();
        return true;
    }

    private function categoryConfigView($category)
    {
        $all_reservation_items = PluginReservationCategory::getReservationItems('', '', false, ["filter_is_active" => false]);
        echo "<div class='center'>";
        echo "<script>$('#formPluginReservationConfigs').submit( function(eventObj) {
            var items = document.getElementById('select_selectedItems');
            for (let i = 0; i < items.options.length; i++) {
                var item = items.options[i];
                $('<input />').attr('type', 'hidden')
                    .attr('name', 'option_selectedItems_'+item.value)
                    .attr('value',item.value)
                    .appendTo('#formPluginReservationConfigs');
            }
            return true;
        });</script>";
        echo '<h1>' . $category . '</h1>';
        echo "<table class='tab_cadre_fixe'  cellpadding='2'>";
        echo '<input type="hidden" name="configCategoryItems" value="' . $category . '">';
        echo '<tr>';
        echo '<th style="text-align: right;">' . __('Available Items', "reservation") . '</th>';
        echo '<th></th>';
        echo '<th style="text-align: left;">' . __('Selected Items', "reservation") . '</th>';
        echo '</tr>';

        echo '<tr>';
        echo '<td style="text-align: right;">';
        $availableItems_array = array_filter(
            $all_reservation_items,
            function ($element) {
                return ($element['category_name'] === 'zzpluginnotcategorized' || is_null($element['category_name']));
            }
        );
        $selectedItems_array = array_filter(
            $all_reservation_items,
            function ($element) use ($category) {
                return ($element['category_name'] === $category);
            }
        );
        echo '<div id=div_availableItems>';
        echo '<select id="select_availableItems" size="' . count($availableItems_array) + count($selectedItems_array) . '">';
        foreach ($availableItems_array as $item) {
            echo '<option value="' . $item['id'] . '">' . $item['name'] . '</option>';
        }
        echo '</select>';
        echo '</div>';

        echo '</td>';
        echo '<td style="text-align: center;">';
        echo '<div style="text-align: center;">';
        echo '<div><button class="submit"  type="button" onclick="upItemInCategory()" >' . _sx('button', '↑') . '</button></div>';
        echo '<div>';
        echo '<button style="margin-right:10px;" type="button" onclick="removeItemFromCategory()"  >' . _sx('button', '←') . '</button>';
        echo '<button style="margin-left:10px;" type="button" onclick="addItemToCategory()" >' . _sx('button', '→') . '</button>';
        echo '</div>';
        echo '<div><button class="submit"  type="button" onclick="downItemInCategory()" >' . _sx('button', '↓') . '</button></div>';
        echo '</div>';
        echo '</td>';
        echo '<td style="text-align: left;">';

        echo '<div id=div_selectedItems>';
        echo '<select id="select_selectedItems" size="' . count($availableItems_array) + count($selectedItems_array) . '">';
        foreach ($selectedItems_array as $item) {
            echo '<option value="' . $item['id'] . '">' . $item['name'] . '</option>';
        }
        echo '</select>';
        echo '</div>';

        echo '</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<td colspan="3">';
        echo '<input class="submit" type="submit" value="' . _sx('button', 'Save') . '">';
        echo '</td>';
        echo '</tr>';
        echo '</table>';
    }

    private function mainConfigView()
    {
        echo "<div class='center'>";
        echo "<table class='tab_cadre_fixe'  cellpadding='2'>";
        echo "<th>" . __('Configuration') . "</th>";

        // extension time
        $extension_time = $this->getConfigurationValue("extension_time", 'default');
        echo '<tr class="tab_bg_2">';
        echo "<td>";
        echo __('Duration added (in hour) to reservations expiring and not checkout', "reservation");
        echo "<br>";
        echo __('By defaut, use value of <b>step for the hours</b> defined in <I>General Setup > Assistance</I>', "reservation") . " : ";
        echo "<select name=\"extension_time\">";
        echo "<option value=\"default\" " . ($extension_time == 'default' ? 'selected="selected"' : '') . ">" . __('Default', "reservation") . "</option>";
        for ($h = 1; $h <= 24; $h++) {
            echo "<option value=\"" . $h . "\" " . ($extension_time == $h ? 'selected="selected"' : '') . ">" . $h . " </option>";
        }
        echo "</select>";
        echo "</td>";
        echo "</tr>";

        // mode_auto for mailing
        $mode_auto = $this->getConfigurationValue("mode_auto");
        echo '<tr class="tab_bg_2">';
        echo "<td>";
        echo __('Method used to send e-mails to users with late reservations', "reservation") . " : ";
        echo "<select name=\"mode_auto\">";
        echo "<option value=\"1\" " . ($mode_auto ? 'selected="selected"' : '') . ">" . __('Automatic', "reservation") . "</option>";
        echo "<option value=\"0\" " . ($mode_auto ? '' : 'selected="selected"') . ">" . __('Manual', "reservation") . "</option>";
        echo "</select>";
        echo "</td>";
        echo "</tr>";

        // conflicted reservation
        $conflict_action = $this->getConfigurationValue("conflict_action");
        echo '<tr class="tab_bg_2">';
        echo "<td>";
        echo __('Method used when there is a conflicted reservation', "reservation") . " : ";
        echo "<select name=\"conflict_action\">";
        echo "<option value=\"delete\" " . ($conflict_action == 'delete' ? 'selected="selected"' : '') . ">" . __('Delete the conflicted reservation', "reservation") . "</option>";
        echo "<option value=\"delay\" " . ($conflict_action == 'delay' ? 'selected="selected"' : '') . ">" . __('Delay the start of the conflicted reservation', "reservation") . "</option>";
        echo "</select>";
        echo "</td>";
        echo "</tr>";

        // checkin
        $checkin = $this->getConfigurationValue("checkin", 0);
        $checkin_timeout = $this->getConfigurationValue("checkin_timeout", 1);
        $checkin_action = $this->getConfigurationValue("checkin_action", '2');
        $auto_checkin = $this->getConfigurationValue("auto_checkin", '0');
        $auto_checkin_time = $this->getConfigurationValue("auto_checkin_time", '1');
        echo '<tr class="tab_bg_2">';
        echo "<td style=\"padding-left:20px;\">";
        echo "<input type=\"hidden\" name=\"checkin\" value=\"0\">";
        echo "<input onclick=\"javascript:afficher_cacher_simple('checkin_config');\" type=\"checkbox\" name=\"checkin\" value=\"1\" " . ($checkin ? 'checked' : '') . "> ";
        echo __('Enable check in', 'reservation') . "</td>";
        echo '</tr>';

        // checkin action
        echo '<tr class="tab_bg_2">';
        echo "<td>";
        if ($checkin) {
            echo '<table id="checkin_config">';
        } else {
            echo '<table id="checkin_config" style="display:none;" >';
        }
        echo '<tr>';
        echo "<td>";
        echo __('Action when reservation are not checkin', "reservation") . " : ";
        echo "<select name=\"checkin_action\">";
        echo "<option value=\"0\" " . ($checkin_action == '0' ? 'selected="selected"' : '') . ">" . __('Do Nothing', "reservation") . "</option>";
        echo "<option value=\"1\" " . ($checkin_action == '1' ? 'selected="selected"' : '') . ">" . __('Warn', "reservation") . "</option>";
        echo "<option value=\"2\" " . ($checkin_action == '2' ? 'selected="selected"' : '') . ">" . __('Warn and delete unclaimed reservation', "reservation") . "</option>";
        echo "</select>";
        echo "</td>";
        echo "</tr>";

        // checkin timeout
        echo '<tr>';
        echo "<td>";
        echo __('Waiting time (in hour) to cancel the unclaimed reservation', "reservation") . " : ";
        echo "<select name=\"checkin_timeout\">";
        for ($h = 1; $h <= 24; $h++) {
            echo "<option value=\"" . $h . "\" " . ($checkin_timeout == $h ? 'selected="selected"' : '') . ">" . $h . " </option>";
        }
        echo "</select>";
        echo '</td>';
        echo "</tr>";

        // auto checkin
        echo '<tr>';
        echo "<td>";
        echo "<input type=\"hidden\" name=\"auto_checkin\" value=\"0\">";
        echo "<input onclick=\"javascript:afficher_cacher_simple('auto_checkin_config');\" type=\"checkbox\" name=\"auto_checkin\" value=\"1\" " . ($auto_checkin ? 'checked' : '') . "> ";
        echo __('Enable auto check in', 'reservation') . "</td>";
        echo '<tr class="tab_bg_2">';
        echo "<td>";
        if ($auto_checkin) {
            echo '<table id="auto_checkin_config">';
        } else {
            echo '<table id="auto_checkin_config" style="display:none;" >';
        }
        echo '<tr>';
        echo "<td>";
        echo __('for reservations made until (in minutes) ', "reservation") . " : ";
        echo "<select name=\"auto_checkin_time\">";
        echo "<option value=\"" . $h . "\" " . ($auto_checkin_time == '1' ? 'selected="selected"' : '') . ">" . '1' . " </option>";
        for ($h = 5; $h <= 60; $h += 5) {
            echo "<option value=\"" . $h . "\" " . ($auto_checkin_time == $h ? 'selected="selected"' : '') . ">" . $h . " </option>";
        }
        echo "</select>";
        echo '</td>';
        echo "</tr>";
        echo '</table>';
        echo '</td>';
        echo "</tr>";
        echo '</td>';
        echo "</tr>";

        echo '</table>';
        echo "</table>";

        // define tabs
        echo "<table class='tab_cadre_fixe'  cellpadding='2'>";
        echo "<th>" . __('Tab Configuration', "reservation") . "</th>";
        // my reservation tab
        $tabmine = $this->getConfigurationValue("tabmine", 0);
        echo '<tr class="tab_bg_2">';
        echo "<input type=\"hidden\" name=\"tabmine\" value=\"0\">";
        echo "<td style=\"padding-left:20px;\">";
        echo "<input type=\"checkbox\" name=\"tabmine\" value=\"1\" " . ($tabmine ? 'checked' : '') . "> ";
        echo __('My Reservation tab', "reservation") . "</td>";
        echo "</tr>";
        // current reservation tab
        $tabcurrent = $this->getConfigurationValue("tabcurrent", 1);
        echo '<tr class="tab_bg_2">';
        echo "<input type=\"hidden\" name=\"tabcurrent\" value=\"0\">";
        echo "<td style=\"padding-left:20px;\">";
        echo "<input type=\"checkbox\" name=\"tabcurrent\" value=\"1\" " . ($tabcurrent ? 'checked' : '') . "> ";
        echo __('Current Reservation tab', "reservation") . "</td>";
        echo "</tr>";
        // incoming reservation tab
        $tabcoming = $this->getConfigurationValue("tabcoming", 0);
        echo '<tr class="tab_bg_2">';
        echo "<input type=\"hidden\" name=\"tabcoming\" value=\"0\">";
        echo "<td style=\"padding-left:20px;\">";
        echo "<input type=\"checkbox\" name=\"tabcoming\" value=\"1\" " . ($tabcoming ? 'checked' : '') . "> ";
        echo __('Incoming Reservation tab', "reservation") . "</td>";
        echo "</tr>";
        echo "</table>";

        // custom categories
        echo "<table class='tab_cadre_fixe'  cellpadding='2'>";
        echo "<th>" . __('Categories Configuration', "reservation") . "</th>";
        $custom_categories = $this->getConfigurationValue("custom_categories", 0);
        echo '<tr class="tab_bg_2">';
        echo "<input type=\"hidden\" name=\"custom_categories\" value=\"0\">";
        echo "<td style=\"padding-left:20px;\">";
        echo "<input onclick=\"javascript:afficher_cacher_simple('custom_categories_view');\" type=\"checkbox\" name=\"custom_categories\" value=\"1\" " . ($custom_categories ? 'checked' : '') . "> ";
        echo __('Use custom categories', "reservation") . "</td>";
        echo "</tr>";
        if ($custom_categories) {
            $use_items_types = $this->getConfigurationValue("use_items_types", 0);
            echo '<tr class="tab_bg_2">';
            echo "<input type=\"hidden\" name=\"use_items_types\" value=\"0\">";
            echo "<td style=\"padding-left:20px;\">";
            echo "<input onclick=\"javascript:afficher_cacher_simple('use_items_types_view');\" type=\"checkbox\" name=\"use_items_types\" value=\"1\" " . ($use_items_types ? 'checked' : '') . "> ";
            echo __('Use items types when not in a custom category', "reservation") . "</td>";
            echo "</tr>";
            echo '<tr class="tab_bg_2" id="use_items_types">';
            echo $this->showConfigCategoriesForm();
            echo "</tr>";
        }
        echo "</table>";

        // tooltip
        echo "<table class='tab_cadre_fixe'  cellpadding='2'>";
        echo "<th>" . __('ToolTip Configuration', "reservation") . "</th>";
        $tooltip = $this->getConfigurationValue("tooltip");
        echo '<tr class="tab_bg_2">';
        echo "<input type=\"hidden\" name=\"tooltip\" value=\"0\">";
        echo "<td> ";
        echo "<input type=\"checkbox\" name=\"tooltip\" value=\"1\" " . ($tooltip ? 'checked' : '') . "> ";
        echo __('ToolTip', "reservation") . "</td>";
        echo "</tr>";

        if ($tooltip) {
            $comment = $this->getConfigurationValue("comment");
            echo "<tr>";
            echo "<input type=\"hidden\" name=\"comment\" value=\"0\">";
            echo "<td style=\"padding-left:20px;\">";
            echo "<input type=\"checkbox\" name=\"comment\" value=\"1\" " . ($comment ? 'checked' : '') . "> " . __('Comment') . "</td>";
            echo "</tr>";

            $location = $this->getConfigurationValue("location");
            echo "<tr>";
            echo "<input type=\"hidden\" name=\"location\" value=\"0\">";
            echo "<td style=\"padding-left:20px;\">";
            echo "<input type=\"checkbox\" name=\"location\" value=\"1\" " . ($location ? 'checked' : '') . "> " . __('Location') . "</td>";
            echo "</tr>";

            $serial = $this->getConfigurationValue("serial");
            echo "<tr>";
            echo "<input type=\"hidden\" name=\"serial\" value=\"0\">";
            echo "<td style=\"padding-left:20px;\">";
            echo "<input type=\"checkbox\" name=\"serial\" value=\"1\" " . ($serial ? 'checked' : '') . "> " . __('Serial number') . "</td>";
            echo "</tr>";

            $inventory = $this->getConfigurationValue("inventory");
            echo "<tr>";
            echo "<input type=\"hidden\" name=\"inventory\" value=\"0\">";
            echo "<td style=\"padding-left:20px;\">";
            echo "<input type=\"checkbox\" name=\"inventory\" value=\"1\" " . ($inventory ? 'checked' : '') . "> " . __('Inventory number') . "</td>";
            echo "</tr>";

            $group = $this->getConfigurationValue("group");
            echo "<tr>";
            echo "<input type=\"hidden\" name=\"group\" value=\"0\">";
            echo "<td style=\"padding-left:20px;\">";
            echo "<input type=\"checkbox\" name=\"group\" value=\"1\" " . ($group ? 'checked' : '') . "> " . __('Group') . "</td>";
            echo "</tr>";

            $man_model = $this->getConfigurationValue("man_model");
            echo "<tr>";
            echo "<input type=\"hidden\" name=\"man_model\" value=\"0\">";
            echo "<td style=\"padding-left:20px;\">";
            echo "<input type=\"checkbox\" name=\"man_model\" value=\"1\" " . ($man_model ? 'checked' : '') . "> " . __('Manufacturer') . " & " . __('Model') . "</td>";
            echo "</tr>";

            $status = $this->getConfigurationValue("status");
            echo "<tr>";
            echo "<input type=\"hidden\" name=\"status\" value=\"0\">";
            echo "<td style=\"padding-left:20px;\">";
            echo "<input type=\"checkbox\" name=\"status\" value=\"1\" " . ($status ? 'checked' : '') . "> " . __('Status') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<input class=\"submit\" type=\"submit\" value='" . _sx('button', 'Save') . "'>";
        echo "</div>";
    }

    private function showConfigCategoriesForm()
    {
        $menu = "<table class='tab_cadre_fixe'  cellpadding='2'>";
        $menu .= "<input type=\"hidden\" name=\"configCategoriesForm\">";
        $menu .= "<th>" . __('Categories customisation', "reservation") . "</th>";
        $menu .= '<tr>';

        $menu .= '<td>';
        $menu .= '<input class="noEnterSubmit" onkeydown="createCategoryEnter()" type="text" id="newCategoryTitle" size="15"  title="Please enter a type">';
        $menu .= '<button type="button" onclick="createCategory()">' . _sx('button', 'Add') . '</button>';
        $menu .= '<table class="listCustomCategories" id="categoriesContainer">';
        $menu .= '<tbody>';

        $categories_names = PluginReservationCategory::getCategoriesNames();
        foreach ($categories_names as $category_name) {
            $it = 0;
            if ($category_name === "zzpluginnotcategorized") {
                continue;
            }
            $menu .= '<tr style="min-width: 200px;" id="trConfigCategory_' . $category_name . '" class="listCustomCategories" >';
            $menu .= '<td>' . $category_name . '</td>';
            $menu .= '<td><button type="button" onclick="configCategory(\'' . $category_name . '\')" class="categoryConfig" >config</td>';
            $menu .= '<td><button type="button" onclick="deleteCategory(\'' . $category_name . '\')" class="categoryClose" >X</td>';
            $menu .= '<input type="hidden" name="category_' . $category_name . '" value="' . $category_name . '">';
            $menu .= '</tr>';
        }
        $menu .= '</tbody>';
        $menu .= '</table>';
        $menu .= '</td>';
        $menu .= "</tr>";
        $menu .= "</table>";

        return $menu;
    }
}
