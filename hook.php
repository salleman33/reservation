<?php

/**
 * Install hook
 *
 * @return boolean
 */
function plugin_reservation_install() {
  global $DB;
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



if (!TableExists("glpi_plugin_reservation_configs")) //INSTALL >= 1.5.0
  {
    $query = "CREATE TABLE `glpi_plugin_reservation_configs` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `key` VARCHAR(10) NOT NULL PRIMARY KEY,
      `value` VARCHAR(10) NOT NULL
    )ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
    $DB->queryOrDie($query, $DB->error());

    $query = "INSERT INTO `glpi_plugin_reservation_configs` (`key` , `value`)
      VALUES (\"mode\",\"manual\")";

$DB->queryOrDie($query, $DB->error());
}

  if (TableExists("glpi_plugin_reservation_config")) //UPDATE plugin < 1.5.0
  {
    $query = "UPDATE `glpi_plugin_reservation_configs`
              SET `value` = (
                SELECT `value`
                FROM `glpi_plugin_reservation_config`
                WHERE `name` = \"methode\"
              )
              WHERE `key` = \"mode\"
              ";

$DB->queryOrDie($query, $DB->error());

    // Création de la table config
    $query = "DROP TABLE `glpi_plugin_reservation_config`";
    $DB->queryOrDie($query, $DB->error());


    $query = "INSERT INTO `glpi_plugin_reservation_configs` (`key` , `value`)
              VALUES (\"lundi\", (SELECT `actif` 
                                FROM `glpi_plugin_reservation_configdayforauto`
                                WHERE `jour` = \"lundi\")),
                                (\"mardi\", (SELECT `actif` 
                                FROM `glpi_plugin_reservation_configdayforauto`
                                WHERE `jour` = \"mardi\")),
                                (\"mercredi\", (SELECT `actif` 
                                FROM `glpi_plugin_reservation_configdayforauto`
                                WHERE `jour` = \"mercredi\")),
                                (\"jeudi\", (SELECT `actif` 
                                FROM `glpi_plugin_reservation_configdayforauto`
                                WHERE `jour` = \"jeudi\")),
                                (\"vendredi\", (SELECT `actif` 
                                FROM `glpi_plugin_reservation_configdayforauto`
                                WHERE `jour` = \"vendredi\")),
                                (\"samedi\", (SELECT `actif` 
                                FROM `glpi_plugin_reservation_configdayforauto`
                                WHERE `jour` = \"samedi\")),
                                (\"dimanche\", (SELECT `actif` 
                                FROM `glpi_plugin_reservation_configdayforauto`
                                WHERE `jour` = \"dimanche\"))";

$DB->queryOrDie($query, $DB->error());
$query = "DROP TABLE `glpi_plugin_reservation_configdayforauto`";
    $DB->queryOrDie($query, $DB->error());
		     
  }
 



  if(TableExists("glpi_plugin_reservation_config") && !TableExists("glpi_plugin_reservation_configdayforauto"))
  {
    $query = "RENAME TABLE `glpi_plugin_reservation_config` TO `glpi_plugin_reservation_configdayforauto`";
    $DB->queryOrDie($query, $DB->error());
  }

  if(!TableExists("glpi_plugin_reservation_configdayforauto"))
  {
    // Création de la table config
    $query = "CREATE TABLE `glpi_plugin_reservation_configdayforauto` (
      `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
      `jour` char(32) NOT NULL default '',
      `actif` int(1) NOT NULL default '1'
    )ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
    $DB->queryOrDie($query, $DB->error());

    $query = "INSERT INTO `glpi_plugin_reservation_configdayforauto` (`jour` , `actif`)
      VALUES (\"lundi\",1),
	(\"mardi\",1),
		       (\"mercredi\",1),
		       (\"jeudi\",1),
		       (\"vendredi\",1),
		       (\"samedi\",0),
		       (\"dimanche\",0)";

$DB->queryOrDie($query, $DB->error());
}
  else { // UPDATE
  }




  $cron = new CronTask;
  if (!$cron->getFromDBbyName('PluginReservationTask','SurveilleResa'))
  {
    CronTask::Register('PluginReservationTask', 'SurveilleResa', 5*MINUTE_TIMESTAMP,array('param' => 24, 'mode' => 2, 'logs_lifetime'=> 10));
  }

  if (!$cron->getFromDBbyName('PluginReservationTask','MailUserDelayedResa'))
  {
    CronTask::Register('PluginReservationTask', 'MailUserDelayedResa', DAY_TIMESTAMP,array('hourmin' => 23, 'hourmax' => 24,  'mode' => 2, 'logs_lifetime'=> 30, 'state'=>0));
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
  $tables = array("glpi_plugin_reservation_reservations","glpi_plugin_reservation_config","glpi_plugin_reservation_configdayforauto");
  foreach($tables as $table)
  {
    $DB->query("DROP TABLE IF EXISTS `$table`;");
  }
  return true;
}

/**
 * Update hook when a reservation is updated
 *
 * @return boolean
 */
function plugin_item_update_reservation($item) {
  global $DB;
  $query = "DELETE FROM `glpi_plugin_reservation_reservations` WHERE `reservations_id` = '".$item->fields["id"]."';";
  $DB->query($query) or die("error on 'DELETE' into plugin_item_update_reservation : ". $DB->error());
  return true;
}



?>
