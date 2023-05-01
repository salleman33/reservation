<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

include_once GLPI_ROOT . "/plugins/reservation/inc/includes.php";

class PluginReservationCategory_Item extends CommonDBChild
{
    /**
     * @param $nb  integer  for singular or plural
     **/
    public static function getTypeName($nb = 0)
    {
        return _n('CategoryItem', 'CategoriesItems', $nb, 'reservation');
    }

    // /**
    //  * Retrieve an item from the database for a specific item
    //  *
    //  * @param $category   category of the item
    //  * @param $ID         ID of the item
    //  *
    //  * @return true if succeed else false
    //  **/
    // function getFromDBbyItem($category_id, $ID)
    // {

    //    return $this->getFromDBByCrit([
    //       $this->getTable() . '.categories_id'  => $category_id,
    //       $this->getTable() . '.reservationitems_id'  => $ID
    //    ]);
    // }

    /**
     * @return array items for a category
     */
    public static function getReservationItemsForCategory($name = '')
    {
        global $DB;

        $res = [];
        $table = getTableForItemType(__CLASS__);
        $category = new PluginReservationCategory();
        $category->getFromDBByCrit(['name' => $name]);
        $category_id = $category->getId();

        $query = "SELECT `glpi_reservationitems`.`id`
              FROM `glpi_reservationitems`, `$table`
              WHERE `glpi_reservationitems`.`id` = `$table`.reservationitems_id
              AND `$table`.`categories_id` = $category_id";

        if ($result = $DB->query($query)) {
            if ($DB->numrows($result) > 0) {
                while ($row = $DB->fetchAssoc($result)) {
                    $res[] = $row;
                }
            }
        }
        return $res;
    }

    /**
     * Get the category id of a reservationitem
     * @param $id integer the reservationitem id
     * @return integer the category id
     */
    public static function getCategoryId($item_id)
    {
        global $DB;
        $cat_id = '';

        $query = "SELECT `glpi_plugin_reservation_categories_items`.`categories_id`
               FROM `glpi_plugin_reservation_categories_items`
               WHERE `reservationitems_id` = $item_id
               ";
        if ($result = $DB->query($query)) {
            if ($DB->numrows($result) == 1) {
                $cat_id = $DB->result($result, 0, "categories_id");
            }
        }

        return $cat_id;
    }

    /**
     * Get the itemtype of the reservationitem
     * @param $id integer the reservationitem id
     */
    public static function getItemTypeFromId($id)
    {
        global $DB;
        $itemtype = '';

        $query = "SELECT `glpi_reservationitems`.`itemtype`
                FROM `glpi_reservationitems`
                WHERE `id` = $id
                ";
        if ($result = $DB->query($query)) {
            if ($DB->numrows($result) == 1) {
                $itemtype = $DB->result($result, 0, "itemtype");
            }
        }

        return $itemtype;
    }

    /**
     * Get the items_id of the reservationitem
     * @param $id integer the reservationitem id
     */
    public static function getItemIdFromId($id)
    {
        global $DB;
        $items_id = '';

        $query = "SELECT `glpi_reservationitems`.`items_id`
                FROM `glpi_reservationitems`
                WHERE `id` = $id
                ";
        if ($result = $DB->query($query)) {
            if ($DB->numrows($result) == 1) {
                $items_id = $DB->result($result, 0, "items_id");
            }
        }

        return $items_id;
    }

    /**
     * Get the name of the reservationitem
     * @param integer $id the reservationitem id
     * @return string name of the reservation item
     */
    public static function getItemNameFromId($id)
    {
        global $DB;
        $name = '';
        $itemtype = '';
        $items_id = '';

        $query = "SELECT `glpi_reservationitems`.`itemtype`,
                        `glpi_reservationitems`.`items_id`
               FROM `glpi_reservationitems`
               WHERE `id` = $id
               ";
        if ($result = $DB->query($query)) {
            if ($DB->numrows($result) == 1) {
                $itemtype = $DB->result($result, 0, "itemtype");
                $items_id = $DB->result($result, 0, "items_id");
            }
        }

        $itemtable = getTableForItemType($itemtype);
        $query = "SELECT `$itemtable`.`name`
               FROM `$itemtable`
               WHERE `id` = $items_id
               ";
        if ($result = $DB->query($query)) {
            if ($DB->numrows($result) == 1) {
                $name = $DB->result($result, 0, "name");
            }
        }
        return $name;
    }

    /**
     * Get the comment of the reservationitem
     * @param $id integer the reservationitem id
     */
    public static function getItemCommentFromId($id)
    {
        $item = new ReservationItem();
        $item->getFromDB($id);
        return $item->fields['comment'];
    }
}
