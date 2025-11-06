<?php

// Check if plugin is activated...
$plugin = new Plugin();
if (!$plugin->isInstalled('reservation') || !$plugin->isActivated('reservation')) {
    return http_response_code(401);
}


// Check if the user is allowed to go here
$config = new PluginReservationConfig();
$read_make_access = $config->getConfigurationValue("read_make_access");
$access = [CREATE, UPDATE, DELETE];

if ($read_make_access) {
    $access = [READ, ReservationItem::RESERVEANITEM, CREATE, UPDATE, DELETE, PURGE];
}

if (!Session::haveRightsOr("reservation", $access)) {
    return http_response_code(401);
}

if (isset($_GET['mailuser'])) {
    if (PluginReservationReservation::sendMail($_GET['mailuser'])) {
        return http_response_code(200);
    } else {
        return http_response_code(500);
    }
}
if (isset($_GET['checkout'])) {
    if (PluginReservationReservation::checkoutReservation($_GET['checkout'])) {
        return http_response_code(200);
    } else {
        return http_response_code(500);
    }
}
if (isset($_GET['checkin'])) {
    if (PluginReservationReservation::checkinReservation($_GET['checkin'])) {
        return http_response_code(200);
    } else {
        return http_response_code(500);
    }
}

if (isset($_GET['change_in_progress'])) {
    $_SESSION['glpi_plugin_reservation_change_in_progress'] = true;
    return http_response_code(200);
}

return http_response_code(400);
