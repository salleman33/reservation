<?php

/**
 * Install hook
 *
 * @return boolean
 */
function plugin_reservation_install() {
   global $DB, $CFG_GLPI;
   if (TableExists("glpi_plugin_reservation_manageresa")) { //UPDATE plugin < 1.5.0
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

   if (!TableExists("glpi_plugin_reservation_reservations")) { //INSTALL >= 1.5.0
      $query = "CREATE TABLE `glpi_plugin_reservation_reservations` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `reservations_id` int(11) NOT NULL,
                `effectivedate`  datetime,
                `baselinedate` datetime NOT NULL,
                `mailingdate` datetime,
                PRIMARY KEY (`id`),
                KEY `reservations_id` (`reservations_id`)
                ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

      $DB->queryOrDie($query, $DB->error());
   }

   if (!TableExists("glpi_plugin_reservation_configs")) { //INSTALL >= 1.5.0
      $query = "CREATE TABLE `glpi_plugin_reservation_configs` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(10) NOT NULL UNIQUE,
                `value` VARCHAR(10) NOT NULL,
                PRIMARY KEY (`id`),
                )ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
      $DB->queryOrDie($query, $DB->error());

      $query = "INSERT INTO `glpi_plugin_reservation_configs` (`name` , `value`)
                VALUES (\"mode_auto\",1)";

      $DB->queryOrDie($query, $DB->error());

      $query = "INSERT INTO `glpi_plugin_reservation_configs` (`name` , `value`)
                VALUES  (\"lundi\",1),
                        (\"mardi\",1),
                        (\"mercredi\",1),
                        (\"jeudi\",1),
                        (\"vendredi\",1),
                        (\"samedi\",0),
                        (\"dimanche\",0)";

      $DB->queryOrDie($query, $DB->error());

   }

   if (TableExists("glpi_plugin_reservation_config")) { //UPDATE plugin < 1.5.0
      $query = "UPDATE `glpi_plugin_reservation_configs`
                SET `value` = (
                    SELECT `value`
                    FROM `glpi_plugin_reservation_config`
                    WHERE `name` = \"methode\"
                )
                WHERE `name` = \"mode_auto\"";

      $DB->queryOrDie($query, $DB->error());

      $query = "DROP TABLE `glpi_plugin_reservation_config`";
      $DB->queryOrDie($query, $DB->error());

      $query = "INSERT INTO `glpi_plugin_reservation_configs` (`name` , `value`)
                VALUES  (\"lundi\", CAST((
                        SELECT `actif`
                        FROM `glpi_plugin_reservation_configdayforauto`
                        WHERE `jour` = \"lundi\") as CHAR(10))),
                    (\"mardi\", CAST((
                        SELECT `actif`
                        FROM `glpi_plugin_reservation_configdayforauto`
                        WHERE `jour` = \"mardi\") as CHAR(10))),
                    (\"mercredi\", CAST((
                        SELECT `actif`
                        FROM `glpi_plugin_reservation_configdayforauto`
                        WHERE `jour` = \"mercredi\") as CHAR(10))),
                    (\"jeudi\", CAST((
                        SELECT `actif`
                        FROM `glpi_plugin_reservation_configdayforauto`
                        WHERE `jour` = \"jeudi\") as CHAR(10))),
                    (\"vendredi\", CAST((
                        SELECT `actif`
                        FROM `glpi_plugin_reservation_configdayforauto`
                        WHERE `jour` = \"vendredi\") as CHAR(10))),
                    (\"samedi\", CAST((
                        SELECT `actif`
                        FROM `glpi_plugin_reservation_configdayforauto`
                        WHERE `jour` = \"samedi\") as CHAR(10))),
                    (\"dimanche\", CAST((
                        SELECT `actif`
                        FROM `glpi_plugin_reservation_configdayforauto`
                        WHERE `jour` = \"dimanche\") as CHAR(10)))";

      $DB->queryOrDie($query, $DB->error());
      $query = "DROP TABLE `glpi_plugin_reservation_configdayforauto`";
      $DB->queryOrDie($query, $DB->error());

   }

   $cron = new CronTask;

   if ($cron->getFromDBbyName('PluginReservationTask', 'SurveilleResa')) { // plugin < 1.5.0
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
      $DB->query("DROP TABLE IF EXISTS `$table`;");
   }
   CronTask::unregister("Reservation");
   return true;
}

/**
 * hook : deladdete Plugin reservation when a GLPI reservation is added
 *
 * @return void
 */
function plugin_item_add_reservation($item) {
   global $DB;
}

/**
 * hook : update plugin reservation when a GLPI reservation is updated
 *
 * @return void
 */
function plugin_item_update_reservation($item) {
   global $DB;

   $end = $item->fields['end'];

   $req = $DB->request('glpi_plugin_reservation_reservations', [
      'FIELDS' => 'effectivedate',
      'WHERE' => ['reservations_id' => $items->fields['id']]
   ]);
   // maybe the reservation is over
   $resume = false;
   if ($row = $req->next()) {
      if ($end >= $row['effectivedate']) {
         $resume = true;
      }
   }

   if ($resume) {
      $DB->updateOrDie(
         'glpi_plugin_reservation_reservations', [
            'baselinedate' => $end,
            'effectivedate' => 'NULL'
         ], [
            'reservations_id' => $item->fields["id"]
         ]
      );
   } else {
      $DB->updateOrDie(
         'glpi_plugin_reservation_reservations', [
            'baselinedate' => $end
         ], [
            'reservations_id' => $item->fields["id"]
         ]
      );
   }
}

/**
 * hook : delete Plugin reservation when a GLPI reservation is delete
 *
 * @return void
 */
function plugin_item_delete_reservation($item) {
   global $DB;
   $DB->delete(
    'glpi_plugin_reservation_reservations', [
       'reservations_id' => $item->fields["id"]
        ]
    );
}


