<?php

define('PLUGIN_RESERVATION_GLPI_MIN_VERSION', '10');
define('PLUGIN_VERSION', '2.4.4');

/**
 * Init the hooks of the plugins - Needed
 *
 * @return void
 */
function plugin_init_reservation()
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['reservation'] = true;
    $PLUGIN_HOOKS['add_css']['reservation'][] = "css/views.css";
    $PLUGIN_HOOKS['add_javascript']['reservation'] = ['js/tools.js'];
    $PLUGIN_HOOKS['config_page']['reservation'] = 'front/config.form.php';
    $PLUGIN_HOOKS['item_add']['reservation'] = ['Reservation' => 'plugin_item_add_reservation'];
    $PLUGIN_HOOKS['item_update']['reservation'] = ['Reservation' => 'plugin_item_update_reservation'];
    $PLUGIN_HOOKS['item_purge']['reservation'] = ['Reservation' => 'plugin_item_purge_reservation'];
    $PLUGIN_HOOKS['menu_toadd']['reservation'] = ['plugins' => 'PluginReservationMenu'];
    $PLUGIN_HOOKS['multi_edit']['reservation'] = 'front/multiedit.form.php';

    Plugin::registerClass('PluginReservationMenu');
    Plugin::registerClass('PluginReservationConfig');
    Plugin::registerClass('PluginReservationReservation');
    Plugin::registerClass('PluginReservationCategory');
    Plugin::registerClass('PluginReservationCategory_Item');
    Plugin::registerClass('PluginReservationTask');
    Plugin::registerClass('PluginReservationApi');
    Plugin::registerClass('PluginReservationMultiEdit');

    // Notifications
    $PLUGIN_HOOKS['item_get_events']['reservation'] =
        ['NotificationTargetReservation' => ['PluginReservationTask', 'addEvents']];
    $PLUGIN_HOOKS['item_get_datas']['reservation'] =
        ['NotificationTargetReservation' => ['PluginReservationTask', 'addData']];
    $PLUGIN_HOOKS['item_add_targets']['reservation'] =
        ['NotificationTargetReservation' => ['PluginReservationTask', 'addTarget']];

    if (Session::getLoginUserID()) {
        $PLUGIN_HOOKS['menu_entry']['reservation'] = 'front/reservation.php';
    }
}

/**
 * Get the name and the version of the plugin - Needed
 *
 * @return array
 */
function plugin_version_reservation()
{
    return [
        'name' => 'Reservation',
        'version' => PLUGIN_VERSION,
        'author' => 'Sylvain Allemand',
        'license' => 'GLPv3',
        'homepage' => 'https://github.com/salleman33/reservation',
        'requirements' => [
            'glpi' => [
                'min' => PLUGIN_RESERVATION_GLPI_MIN_VERSION,
            ],
        ],
    ];
}

/**
 * Optional : check prerequisites before install : may print errors or add to message after redirect
 *
 * @return boolean
 */
function plugin_reservation_check_prerequisites()
{
    return true;
}

/**
 * Check configuration process for plugin : need to return true if succeeded
 * Can display a message only if failure and $verbose is true
 *
 * @param boolean $verbose Enable verbosity. Default to false
 *
 * @return boolean
 */
function plugin_reservation_check_config($verbose = false)
{
    if (true) { // Your configuration check
        return true;
    }

    if ($verbose) {
        echo 'Installed / not configured';
    }
    return false;
}
