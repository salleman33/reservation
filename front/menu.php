<?php

use Glpi\Application\View\TemplateRenderer;

// Check if the user is allowed to go here
$config = new PluginReservationConfig();
$read_make_access = $config->getConfigurationValue("read_make_access");
$access = [CREATE, UPDATE, DELETE];

if ($read_make_access) {
    $access = [READ, ReservationItem::RESERVEANITEM, CREATE, UPDATE, DELETE];
}

Session::checkRightsOr("reservation", $access);

if (Session::getCurrentInterface() == "helpdesk") {
    Html::helpHeader(__('Simplified interface'), $_SERVER['PHP_SELF'], $_SESSION["glpiname"]);
} else {
    Html::header(PluginReservationMenu::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "plugins", "pluginreservationmenu", "reservation");
}

// Check if plugin is activated...
$plugin = new Plugin();
if (!$plugin->isInstalled('reservation') || !$plugin->isActivated('reservation')) {
    return Html::displayNotFoundError();
}

global $CFG_GLPI;
$form_dates = [];

$day = date("d", time());
$month = date("m", time());
$year = date("Y", time());

$begin_time = time();

$planning_begin = $CFG_GLPI['planning_begin'];
$planning_end = $CFG_GLPI['planning_end'];
$planning_begin_time = explode(":", $planning_begin);
$planning_end_time = explode(":", $planning_end);
$planning_begin_date = date("Y-m-d H:i:s", mktime($planning_begin_time[0], $planning_begin_time[1], 00, $month, $day, $year));
$planning_end_date = date("Y-m-d H:i:s", mktime($planning_end_time[0], $planning_end_time[1], 00, $month, $day, $year));

$form_dates["begin"] = date("Y-m-d H:i:s", $begin_time);
if ($planning_end_date > date("Y-m-d H:i:s", time())) {
    $form_dates['end'] = $planning_end_date;
} else {
    $form_dates['end'] = date("Y-m-d H:i:s", mktime(23, 59, 00, $month, $day, $year));
}

if (isset($_POST['date_begin'])) {
    $form_dates["begin"] = $_POST['date_begin'];
}
if (isset($_GET['date_begin'])) {
    $form_dates["begin"] = $_GET['date_begin'];
}
if (isset($_POST['date_end'])) {
    $form_dates["end"] = $_POST['date_end'];
}
if (isset($_GET['date_end'])) {
    $form_dates["end"] = $_GET['date_end'];
}
if (isset($_POST['nextday']) || isset($_GET['nextday'])) {
    $form_dates = $_SESSION['glpi_plugin_reservation_form_dates'];
    $day = date("d", strtotime($form_dates["begin"]) + DAY_TIMESTAMP);
    $month = date("m", strtotime($form_dates["begin"]) + DAY_TIMESTAMP);
    $year = date("Y", strtotime($form_dates["begin"]) + DAY_TIMESTAMP);

    $form_dates["begin"] = date("Y-m-d H:i:s", mktime($planning_begin_time[0], $planning_begin_time[1], 00, $month, $day, $year));
    $form_dates["end"] = date("Y-m-d H:i:s", mktime($planning_end_time[0], $planning_end_time[1], 00, $month, $day, $year));
}
if (isset($_POST['previousday']) || isset($_GET['previousday'])) {
    $form_dates = $_SESSION['glpi_plugin_reservation_form_dates'];
    $day = date("d", strtotime($form_dates["begin"]) - DAY_TIMESTAMP);
    $month = date("m", strtotime($form_dates["begin"]) - DAY_TIMESTAMP);
    $year = date("Y", strtotime($form_dates["begin"]) - DAY_TIMESTAMP);

    $form_dates["begin"] = date("Y-m-d H:i:s", mktime($planning_begin_time[0], $planning_begin_time[1], 00, $month, $day, $year));
    $form_dates["end"] = date("Y-m-d H:i:s", mktime($planning_end_time[0], $planning_end_time[1], 00, $month, $day, $year));
}
if (isset($_GET['reset'])) {
    unset($_SESSION['glpi_plugin_reservation_form_dates']);
}
if (isset($_POST['add_item_to_reservation'])) {
    $form_dates = $_SESSION['glpi_plugin_reservation_form_dates'];
    $current_reservation = $_POST['add_item_to_reservation'];
    $item_to_add = $_POST['add_item'];
    PluginReservationReservation::addItemToResa($item_to_add, $current_reservation);
}
if (isset($_POST['switch_item_to_reservation'])) {
    $form_dates = $_SESSION['glpi_plugin_reservation_form_dates'];
    $current_reservation = $_POST['switch_item_to_reservation'];
    $item_to_switch = $_POST['switch_item'];
    PluginReservationReservation::switchItemToResa($item_to_switch, $current_reservation);
}
if (isset($_SESSION['glpi_plugin_reservation_change_in_progress'])) {
    $form_dates = $_SESSION['glpi_plugin_reservation_form_dates'];
    unset($_SESSION['glpi_plugin_reservation_change_in_progress']);
}

$_SESSION['glpi_plugin_reservation_form_dates'] = $form_dates;

$reservation_types = PluginReservationMenu::getReservationTypes();

TemplateRenderer::getInstance()->display('@reservation/dates_forms.html.twig', [ 
    'reservation_types' => $reservation_types,
    'default_location' => (int) ($_POST['locations_id'] ?? User::getById(Session::getLoginUserID())->fields['locations_id'] ?? 0),
    'form_dates' => $form_dates
]);

$menu = new PluginReservationMenu();
// $menu->showFormDate();
$menu->display();

if (Session::getCurrentInterface() == "helpdesk") {
    Html::helpFooter();
} else {
    Html::footer();
}
