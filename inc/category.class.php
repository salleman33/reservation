<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

include GLPI_ROOT . "/plugins/reservation/inc/includes.php";

class PluginReservationCategory extends CommonDBTM
{
   /**
    * @param $nb  integer  for singular or plural
    **/
    public static function getTypeName($nb = 0) {
      return _n('Category', 'Categories', $nb, 'reservation');
   }
   

   /**
    * @return array categories of items
    */
   public static function getCategories($filters = [])
   {
      global $DB;

      $res = [];
      // $categories_table = PluginReservationCategory::getTable();
      $categories_table = getTableForItemType(__CLASS__);

      // $where = "WHERE " . $categories_table . ".reservations_id = " . $reservation_table . ".id";
      $where = '';
      // foreach ($filters as $filter) {
      //    $where .= " AND " . $filter;
      // }

      $query = "SELECT *
             FROM $categories_table
             $where";

      // Toolbox::logInFile('reservations_plugin', "QUERY  : ".$query."\n", $force = false);

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) > 0) {
            while ($row = $DB->fetch_assoc($result)) {
               $res[] = $row;
            }
         }
      }
      return $res;
   }
}
