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
   public static function getCategoriesNames($filters = [])
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

      $query = "SELECT name
             FROM $categories_table
             $where";

      // Toolbox::logInFile('reservations_plugin', "QUERY  : ".$query."\n", $force = false);

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) > 0) {
            while ($row = $DB->fetch_assoc($result)) {
               $res[] = $row['name'];
            }
         }
      }
      return $res;
   }

   /**
    * add a category in database
    */
   public static function addCategory($name) {
      global $DB;
      $categories_table = getTableForItemType(__CLASS__);

      $DB->insertOrDie($categories_table, [
         'name' => $name,
      ]
      );
      $_SESSION['glpi_use_mode'] && Toolbox::logInFile('reservations_plugin', "DEBUG : ".json_encode($categories_table)."\n", $force = false);

      // Toolbox::logInFile('reservations_plugin', "addCategory : ".json_encode($name)."\n", $force = false);
   }
   
   /**
    * delete a category in database
    */
   public static function deleteCategory($name) {
      global $DB;
      $categories_table = getTableForItemType(__CLASS__);

      $DB->deleteOrDie($categories_table, [
         'name' => $name,
      ]
      );
      // Toolbox::logInFile('reservations_plugin', "deleteCategory : ".json_encode($name)."\n", $force = false);
   }

   /**
    * update categories in database
    */
   public static function updateCategories($newList) {
      $currentList = PluginReservationCategory::getCategoriesNames();

      foreach (array_diff($newList, $currentList) as $toAdd) {
         // Toolbox::logInFile('reservations_plugin', "category to Add : ".json_encode($toAdd)."\n", $force = false);
         PluginReservationCategory::addCategory($toAdd);
      }
      foreach (array_diff($currentList, $newList) as $toDelete) {
         // Toolbox::logInFile('reservations_plugin', "category to Delete : ".json_encode($toDelete)."\n", $force = false);
         PluginReservationCategory::deleteCategory($toDelete);
      }
   }

   /**
    * update categories in database
    */
   public static function updateCategoriesItems($list_items_by_categories) {
      global $DB;

      foreach ($list_items_by_categories as $category_name => $category_items) {
         $category = new PluginReservationCategory();
         $category->getFromDBByCrit(['name' => $category_name]);

         foreach ($category_items as $item_id) {
            $items = new PluginReservationCategory_Item();
            $items_table = $items->getTable();

            if(!$items->getFromDBByCrit(['reservationitems_id' => $item_id])) {
               $DB->insertOrDie($items_table, [
                  'categories_id' => $category->getId(),
                  'reservationitems_id' => $item_id,
                  'priority' => 0,
               ]
               );
            } else {
               $DB->updateOrDie($items_table, [
                  'categories_id' => $category->getId(),
                  'priority' => 0,
               ],
               [
                  'reservationitems_id' => $item_id,
               ]);
            }
         }
      }
   }

  
   
}