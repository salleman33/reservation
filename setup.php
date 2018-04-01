<?php

/**
 * Init the hooks of the plugins - Needed
 *
 * @return void
 */
function plugin_init_reservation() {
  global $PLUGIN_HOOKS;

  $PLUGIN_HOOKS['csrf_compliant']['reservation'] = true;
  $PLUGIN_HOOKS['add_css']['reservation'][]="css/views.css";
  $PLUGIN_HOOKS['add_javascript']['reservation']= array('scripts/tri.js');
  $PLUGIN_HOOKS['config_page']['reservation'] = 'front/config.form.php';
  $PLUGIN_HOOKS['item_update']['reservation'] = array('Reservation' => 'plugin_item_update_reservation');
  $PLUGIN_HOOKS['item_delete']['reservation'] = array('Reservation' => 'plugin_item_update_reservation');
  $PLUGIN_HOOKS['menu_toadd']['reservation'] = array('plugins' => 'PluginReservationReservation');

  Plugin::registerClass('PluginReservationConfig');
  Plugin::registerClass('PluginReservationReservation');
  Plugin::registerClass('PluginReservationTask');

   // Notifications
   $PLUGIN_HOOKS['item_get_events']['reservation'] =
         array('NotificationTargetReservation' => array('PluginReservationTask', 'addEvents'));


  if (Session::getLoginUserID()) {
    $PLUGIN_HOOKS['menu_entry']['reservation']              = 'front/reservation.php';
  }
}

/**
 * Get the name and the version of the plugin - Needed
 *
 * @return array
 */
function plugin_version_reservation() {
  return [
    'name'           => 'Reservation',
    'version'        => '1.5.0',
    'author'         => 'Sylvain Allemand',
    'license'        => 'GLPv3',
    'homepage'       => 'https://plmlab.math.cnrs.fr/sylvain.allemand/reservations',
    'requirements'   => [
       'glpi'   => [
          'min' => '9.2'
       ]
    ]
 ];
}

/**
 * Optional : check prerequisites before install : may print errors or add to message after redirect
 *
 * @return boolean
 */
function plugin_reservation_check_prerequisites() {
  if (version_compare(GLPI_VERSION, '9.2', 'lt')) {
    if (method_exists('Plugin', 'messageIncompatible')) {
       //since GLPI 9.2
       Plugin::messageIncompatible('core', 9.2);
    } else {
       echo "This plugin requires GLPI >= 9.2";
    }
    return false;
 }
}

/**
 * Check configuration process for plugin : need to return true if succeeded
 * Can display a message only if failure and $verbose is true
 *
 * @param boolean $verbose Enable verbosity. Default to false
 *
 * @return boolean
 */
function plugin_reservation_check_config($verbose=false) {
  if (true) { // Your configuration check
    return true;
  }

  if ($verbose) {
    echo 'Installed / not configured';
  }
  return false;
}

?>
