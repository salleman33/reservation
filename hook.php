<?php

/**
 * Install hook
 *
 * @return boolean
 */
function plugin_reservation_install() {
   global $DB, $CFG_GLPI;
   if ($DB->tableExists("glpi_plugin_reservation_manageresa")) { //UPDATE plugin < 2.0.0
      $query = "ALTER TABLE `glpi_plugin_reservation_manageresa`
                CHANGE `resaid` `reservations_id` int(11) NOT NULL,
                DROP COLUMN `matid`,
                CHANGE `date_return` `effectivedate` datetime,
                CHANGE `date_theorique` `baselinedate` datetime NOT NULL,
                DROP COLUMN `itemtype`,
                CHANGE `dernierMail` `mailingdate` datetime";

      $DB->queryOrDie($query, $DB->error());
      $query = "RENAME TABLE `glpi_plugin_reservation_manageresa` TO `glpi_plugin_reservation_reservations`";
      $DB->queryOrDie($query, $DB->error());
   }

   if (!$DB->tableExists("glpi_plugin_reservation_reservations")) { //INSTALL >= 2.0.0
      $query = "CREATE TABLE `glpi_plugin_reservation_reservations` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `reservations_id` int(11) NOT NULL,
                `baselinedate` datetime NOT NULL,
                `effectivedate`  datetime,
                `mailingdate` datetime,
                PRIMARY KEY (`id`),
                KEY `reservations_id` (`reservations_id`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->queryOrDie($query, $DB->error());
   }

   // add existing reservations if necessary
   $query = "SELECT *
            FROM glpi_reservations
            WHERE `end` >= NOW()
            AND glpi_reservations.id NOT IN
               (
                  SELECT reservations_id
                  FROM glpi_plugin_reservation_reservations
               )";
   $reservation = new Reservation();
   foreach ($DB->request($query) as $data) {
      $reservation->getFromDb($data['id']);
      plugin_item_add_reservation($reservation);
   }

   if (!$DB->tableExists("glpi_plugin_reservation_configs")) { //INSTALL >= 2.0.0
      $query = "CREATE TABLE `glpi_plugin_reservation_configs` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(20) NOT NULL,
                `value` VARCHAR(20) NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE (`name`)
                )ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, $DB->error());

      $query = "INSERT INTO `glpi_plugin_reservation_configs` (`name` , `value`)
                VALUES  (\"mode_auto\",0),
                        (\"conflict_action\",\"delete\")";

      $DB->queryOrDie($query, $DB->error());
   }

   if ($DB->tableExists("glpi_plugin_reservation_config")) { //UPDATE plugin < 2.0.0
      $query = "ALTER TABLE `glpi_plugin_reservation_config`
                MODIFY `name` VARCHAR(20) NOT NULL,
                MODIFY `value` VARCHAR(20) NOT NULL";

      $DB->queryOrDie($query, $DB->error());

      $query = "UPDATE `glpi_plugin_reservation_configs`
                SET `value` = (
                    SELECT `value`
                    FROM `glpi_plugin_reservation_config`
                    WHERE `name` = \"methode\"
                )
                WHERE `name` = \"mode_auto\"";

      $query = "INSERT INTO `glpi_plugin_reservation_configs` (`name` , `value`)
                VALUES  (\"mode_auto\",0),
                        (\"conflict_action\",\"delete\")";

      $DB->queryOrDie($query, $DB->error());

      $query = "DROP TABLE `glpi_plugin_reservation_config`";
      $DB->queryOrDie($query, $DB->error());

      $query = "DROP TABLE `glpi_plugin_reservation_configdayforauto`";
      $DB->queryOrDie($query, $DB->error());
   }

   $cron = new CronTask;

   if ($cron->getFromDBbyName('PluginReservationTask', 'SurveilleResa')) { // plugin < 2.0.0
      CronTask::unregister("Reservation");
   }

   if (!$cron->getFromDBbyName('PluginReservationTask', 'checkReservations')) {
      CronTask::Register('PluginReservationTask',
                        'checkReservations',
                        $CFG_GLPI['time_step'] * MINUTE_TIMESTAMP,
                        ['param' => 24, 'mode' => 2, 'logs_lifetime' => 10]);
   }

   if (!$cron->getFromDBbyName('PluginReservationTask', 'sendMailLateReservations')) {
      CronTask::Register('PluginReservationTask',
                        'sendMailLateReservations',
                        DAY_TIMESTAMP,
                        ['hourmin' => 23, 'hourmax' => 24, 'mode' => 2, 'logs_lifetime' => 30, 'state' => 0]);
   }

   return true;
}

/**
 * Uninstall hook
 *
 * @return boolean
 */
function plugin_reservation_uninstall() {
   global $DB;
   $tables = ["glpi_plugin_reservation_reservations", "glpi_plugin_reservation_configs"];
   foreach ($tables as $table) {
      $DB->query("DROP TABLE IF EXISTS `$table`");
   }
   CronTask::unregister("Reservation");
   return true;
}

/**
 * hook : add Plugin reservation when a GLPI reservation is added
 *
 * @return void
 */
function plugin_item_add_reservation($reservation) {
   global $DB;

   $DB->insertOrDie('glpi_plugin_reservation_reservations', [
      'reservations_id' => $reservation->fields['id'],
      'baselinedate' => $reservation->fields['end']
   ]
   );
   Toolbox::logInFile('reservations_plugin', "plugin_item_add_reservation : ".json_encode($reservation)."\n", $force = false);
}

/**
 * hook : update plugin reservation when a GLPI reservation is updated
 *
 * @return void
 */
function plugin_item_update_reservation($reservation) {
   global $DB;

   $end = $reservation->fields['end'];

   $query = 'SELECT `effectivedate`
            FROM glpi_plugin_reservation_reservations
            WHERE `reservations_id` = '.$reservation->fields['id'];
   // maybe the reservation is over
   $resume = false;
   foreach ($DB->request($query) as $data) {
      if ($end >= $data['effectivedate']) {
         $resume = true;
      }
   }

   if ($resume) {
      $DB->updateOrDie(
         'glpi_plugin_reservation_reservations', [
            'baselinedate' => $end,
            'effectivedate' => 'NULL'
         ], [
            'reservations_id' => $reservation->fields["id"]
         ]
      );
   } else {
      $DB->updateOrDie(
         'glpi_plugin_reservation_reservations', [
            'baselinedate' => $end
         ], [
            'reservations_id' => $reservation->fields["id"]
         ]
      );
   }
   Toolbox::logInFile('reservations_plugin', "plugin_item_update_reservation : ".json_encode($reservation)."\n", $force = false);
}

/**
 * hook : delete Plugin reservation when a GLPI reservation is delete
 *
 * @return void
 */
function plugin_item_purge_reservation($reservation) {
   global $DB;
   Toolbox::logInFile('reservations_plugin', "plugin_item_purge_reservation : ".json_encode($reservation)."\n", $force = false);
   $DB->delete(
    'glpi_plugin_reservation_reservations', [
       'reservations_id' => $reservation->fields["id"]
        ]
    );
}
