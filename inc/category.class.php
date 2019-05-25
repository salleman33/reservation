<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

include_once GLPI_ROOT . "/plugins/reservation/inc/includes.php";

class PluginReservationCategory extends CommonDBTM
{
   /**
    * @param $nb  integer  for singular or plural
    **/
   public static function getTypeName($nb = 0)
   {
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
             $where
             ORDER BY lower(name)";

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
   private function addCategory($name)
   {
      global $DB;
      $categories_table = getTableForItemType(__CLASS__);

      $DB->insertOrDie(
         $categories_table,
         [
            'name' => $name,
         ]
      );
   }

   /**
    * delete a category in database
    */
   private function deleteCategory($name)
   {
      global $DB;
      $categories_table = getTableForItemType(__CLASS__);

      $DB->deleteOrDie(
         $categories_table,
         [
            'name' => $name,
         ]
      );
   }

   /**
    * Get current categories sorted and items config
    * @return array array of items by categories like ["cat1" => [1,2,3,4], "cat2" => [5,6] ]
    */
   public static function getCategoriesConfig()
   { 
      $result = [];
      $list_reservationitems = self::getReservationItems();
      foreach ($list_reservationitems as $item) {
         $category_name =$item['category_name']; 
         if ($category_name === null) {
            $category_name = 'pluginnotcategorized';
         }

         if (array_key_exists($category_name, $result)) {
            array_push($result[$category_name], $item["items_id"]);
         } else {
            $result[$category_name] = [];
            array_push($result[$category_name], $item["items_id"]);
         }
      }
      ksort($result);
      return $result;
   }

   /**
    * get reservation items merged with their category configs (id, name, priority)
    * @return array list of reservation items like [{"id":"1","comment":"Windows 10","name":"computer 1","entities_id":"0","category_name":"Windows","category_id":"11","items_priority":"1","items_id":"1","itemtype":"Computer"}{...}{...}],
    */
   public static function getReservationItems($begin = '', $end = '', $available = false)
   {
      global $DB, $CFG_GLPI;
      $result = [];

      foreach ($CFG_GLPI["reservation_types"] as $itemtype) {
         if (!($item = getItemForItemtype($itemtype))) {
            continue;
         }
         $itemtable = getTableForItemType($itemtype);
         $categories_table = getTableForItemType(__CLASS__);
         $category_items_table = getTableForItemType("PluginReservationCategory_Item");

         $left = "LEFT JOIN `glpi_reservations`
                        ON (`glpi_reservationitems`.`id` = `glpi_reservations`.`reservationitems_id`
                            AND '". $begin."' < `glpi_reservations`.`end`
                            AND '". $end."' > `glpi_reservations`.`begin`)";
         
         $where = $available ? " AND `glpi_reservations`.`id` IS NULL " : '' ;


         $query = "SELECT `glpi_reservationitems`.`id`,
                           `glpi_reservationitems`.`comment`,
                           `$itemtable`.`name` AS name,
                           `$itemtable`.`entities_id` AS entities_id,
                           `$categories_table`.`name` AS category_name,
                           `$categories_table`.`id` AS category_id,
                           `$category_items_table`.`priority` AS items_priority,
                           `glpi_reservationitems`.`items_id` AS items_id
                             
                  FROM `glpi_reservationitems`
                  INNER JOIN `$itemtable`
                     ON (`glpi_reservationitems`.`itemtype` = '$itemtype'
                           AND `glpi_reservationitems`.`items_id` = `$itemtable`.`id`)
                  LEFT OUTER JOIN `$category_items_table`
                     ON `glpi_reservationitems`.`id` = `$category_items_table`.`reservationitems_id`
                  LEFT OUTER JOIN `$categories_table`
                     ON `$category_items_table`.`categories_id` = `$categories_table`.`id`
                  $left
                  WHERE `glpi_reservationitems`.`is_active` = '1'
                     AND `glpi_reservationitems`.`is_deleted` = '0'
                     AND `$itemtable`.`is_deleted` = '0'
                     $where ".
                     getEntitiesRestrictRequest(
                        " AND",
                        $itemtable,
                        '',
                        $_SESSION['glpiactiveentities'],
                        $item->maybeRecursive()
                     ) . "
                  ORDER BY `$itemtable`.`entities_id`,
                     `$category_items_table`.`priority`,
                     `$itemtable`.`name` ASC";

         if ($res = $DB->query($query)) {
            while ($row = $DB->fetch_assoc($res)) {
               $result[] = array_merge($row, ['itemtype' => $itemtype]);
            }
         }
      }
      return $result;
   }



   /**
    * Apply config defined in $_POST 
    */
   public function applyCategoriesConfig($POST)
   {
      $categories = [];
      $items = [];
      foreach ($POST as $key => $val) {
         if (preg_match('/^item_([0-9]+)$/', $key, $match)) {
            if (array_key_exists($val, $items)) {
               array_push($items[$val], $match[1]);
            } else {
               $items[$val] = [];
               array_push($items[$val], $match[1]);
            }
         }
         if (preg_match('/^category_([a-zA-Z0-9]+)$/', $key, $match)) {
            array_push($categories, $val);
         }
      }

      $this->updateCategories($categories);
      $this->updateCategoryItems($items);
   }

   /**
    * update categories in database
    * @param string[] $next_config [optiona] array of categories like ["cat1", "cat2" ]
    */
   private function updateCategories($next_config = [])
   {
      $previous_config = $this->getCategoriesNames();

      foreach (array_diff($next_config, $previous_config) as $to_add) {
         // Toolbox::logInFile('reservations_plugin', "category to Add : ".json_encode($toAdd)."\n", $force = false);
         $this->addCategory($to_add);
      }
      foreach (array_diff($previous_config, $next_config) as $to_delete) {
         // Toolbox::logInFile('reservations_plugin', "category to Delete : ".json_encode($toDelete)."\n", $force = false);
         $this->deleteCategory($to_delete);
      }
   }

   /**
    * update category items in database
    * @param $list_items_by_categories  array of items by categories like ["cat1" => [1,2,3,4], "cat2" => [5,6] ]
    */
   private function updateCategoryItems($list_items_by_categories = [])
   {
      global $DB;

      foreach ($list_items_by_categories as $category_name => $category_items) {
         $this->getFromDBByCrit(['name' => $category_name]);

         for($i = 0; $i < count($category_items); ++$i) {
            $items = new PluginReservationCategory_Item();
            $items_table = $items->getTable();

            if ($items->getFromDBByCrit(['reservationitems_id' => $category_items[$i]] )) {
               $DB->updateOrDie(
                  $items_table,
                  [
                     'categories_id' => $this->getId(),
                     'priority' => $i+1,
                  ],
                  [
                     'reservationitems_id' => $category_items[$i],
                  ]
               );
            } else {
               $DB->insertOrDie(
                  $items_table,
                  [
                     'categories_id' => $this->getId(),
                     'reservationitems_id' => $category_items[$i],
                     'priority' => $i+1,
                  ]
               );
            }
         }
      }
   }
}
