<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

include_once(__DIR__ . '/includes.php');

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

        if ($result = $DB->doQuery($query)) {
            if ($DB->numrows($result) > 0) {
                while ($row = $DB->fetchAssoc($result)) {
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
        $this->getFromDBByCrit(['name' => $name]);

        $items = new PluginReservationCategory_Item();
        $items_table = $items->getTable();

        $DB->deleteOrDie(
            $items_table,
            [
                'categories_id' => $this->getId(),
            ]
        );

        $DB->deleteOrDie(
            $categories_table,
            [
                'name' => $name,
            ]
        );
    }

    /**
     * get reservation items merged with their category configs (id, name, priority)
     * @return array list of reservation items like [{"id":"1","comment":"Windows 10","name":"computer 1","entities_id":"0","category_name":"Windows","category_id":"11","items_priority":"1","items_id":"1","itemtype":"Computer"}{...}{...}],
     */
    public static function getReservationItems($begin = '', $end = '', $available = false, $optional = [])
    {
        global $DB, $CFG_GLPI;

        if (isset($_SESSION['glpi_saved']['PluginReservationMenu'])) {
            $_POST = $_SESSION['glpi_saved']['PluginReservationMenu'];
        }

        $filter_is_active = true;
        if (isset($optional["filter_is_active"])) {
            $filter_is_active = $optional["filter_is_active"];
        }
        $result = [];

        foreach ($CFG_GLPI["reservation_types"] as $itemtype) {
            if (!($item = getItemForItemtype($itemtype))) {
                continue;
            }
            $itemtable = getTableForItemType($itemtype);
            $itemname  = $item::getNameField();
            $otherserial = new QueryExpression($DB->quote('') . ' AS ' . $DB::quoteName('otherserial'));
            if ($item->isField('otherserial')) {
                $otherserial = "$itemtable.otherserial AS otherserial";
            }
            $categories_table = getTableForItemType(__CLASS__);
            $category_items_table = getTableForItemType("PluginReservationCategory_Item");


            $criteria = [
                'SELECT' => [
                    'glpi_reservationitems.id',
                    'glpi_reservationitems.comment',
                    "$itemtable.$itemname AS name",
                    "$itemtable.entities_id AS entities_id",
                    $otherserial,
                    'glpi_locations.id AS location',
                    "$categories_table.name AS category_name",
                    "$categories_table.id AS category_id",
                    "$category_items_table.priority AS items_priority",
                    'glpi_reservationitems.items_id AS items_id',
                    'glpi_reservationitems.is_active',
                ],
                'FROM'   => ReservationItem::getTable(),
                'INNER JOIN'   => [
                    $itemtable  => [
                        'ON'  => [
                            'glpi_reservationitems' => 'items_id',
                            $itemtable              => 'id',
                            [
                                'AND' => [
                                    'glpi_reservationitems.itemtype' => $itemtype,
                                ],
                            ],
                        ],
                    ],
                ],
                'LEFT JOIN' =>  [
                    'glpi_locations'  => [
                        'ON'  => [
                            $itemtable        => 'locations_id',
                            'glpi_locations'  => 'id',
                        ],
                    ],
                    $category_items_table  => [
                        'ON'  => [
                            'glpi_reservationitems' => 'id',
                            $category_items_table => 'id',
                        ],
                    ],
                    $categories_table => [
                        'ON' => [
                            $category_items_table => 'categories_id',
                            $categories_table => 'id',
                        ],
                    ],
                ],
                'WHERE'        => [
                    "$itemtable.is_deleted" => 0,
                ] + getEntitiesRestrictCriteria($itemtable, '', $_SESSION['glpiactiveentities'], $item->maybeRecursive()),
                'ORDERBY'      => [
                    "$itemtable.entities_id",
                    "$itemtable.$itemname",
                ],
            ];
            if ($begin != '' && $end != '') {
                $criteria['LEFT JOIN']['glpi_reservations'] = [
                    'ON'  => [
                        'glpi_reservationitems'  => 'id',
                        'glpi_reservations'  => 'reservationitems_id', [
                            'AND' => [
                                'glpi_reservations.end'    => ['>', $begin],
                                'glpi_reservations.begin'  => ['<', $end],
                            ],
                        ],
                    ],
                ];
            }
            if ($filter_is_active) {
                $criteria['WHERE'][] = ['glpi_reservationitems.is_active' => '1'];
            }
            if ($available) {
                $criteria['WHERE'][] = ['glpi_reservations.id' => null];
            }

            if (!empty($_POST["reservation_types"])) {
                $tmp = explode('#', $_POST["reservation_types"]);
                $criteria['WHERE'][] = ['glpi_reservationitems.itemtype' => $tmp[0]];
                if (
                    isset($tmp[1]) && ($tmp[0] === Peripheral::class)
                    && ($itemtype === Peripheral::class)
                ) {
                    $criteria['LEFT JOIN']['glpi_peripheraltypes'] = [
                        'ON' => [
                            'glpi_peripherals'      => 'peripheraltypes_id',
                            'glpi_peripheraltypes'  => 'id',
                        ],
                    ];
                    $criteria['WHERE'][] = ["$itemtable.peripheraltypes_id" => $tmp[1]];
                }
            }

            // Filter locations if location was provided/submitted
            if ((int) ($_POST['locations_id'] ?? 0) > 0) {
                $criteria['WHERE'][] = [
                    'glpi_locations.id' => getSonsOf('glpi_locations', (int) $_POST['locations_id']),
                ];
            }

            $iterator = $DB->request($criteria);
            foreach ($iterator as $row) {
                $result[] = array_merge($row, ['itemtype' => $itemtype]);
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
            if (preg_match('/^category_([a-zA-Z0-9]+)$/', $key, $match)) {
                array_push($categories, $val);
            }
        }

        $this->updateCategories($categories);
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
     * Apply config of items in custom categories defined in $_POST
     */
    public function applyCategoryItem($POST)
    {
        global $DB;
        $category = $_POST['configCategoryItems'];
        $items_list = [];
        $available_list = [];

        foreach ($POST as $key => $val) {
            if (preg_match('/^option_selectedItems_([0-9]+)$/', $key, $match)) {
                array_push($items_list, $match[1]);
            }
            if (preg_match('/^option_availableItems_([0-9]+)$/', $key, $match)) {
                array_push($available_list, $match[1]);
            }
        }

        $this->getFromDBByCrit(['name' => $category]);
        $items = new PluginReservationCategory_Item();
        $items_table = $items->getTable();
        for ($i = 0; $i < count($items_list); ++$i) {
            if ($items->getFromDBByCrit(['reservationitems_id' => $items_list[$i]])) {
                $DB->updateOrDie(
                    $items_table,
                    [
                        'categories_id' => $this->getId(),
                        'priority' => $i + 1,
                    ],
                    [
                        'reservationitems_id' => $items_list[$i],
                    ]
                );
            } else {
                $DB->insertOrDie(
                    $items_table,
                    [
                        'categories_id' => $this->getId(),
                        'reservationitems_id' => $items_list[$i],
                        'priority' => $i + 1,
                    ]
                );
            }
        }

        for ($i = 0; $i < count($available_list); ++$i) {
            if ($items->getFromDBByCrit(['reservationitems_id' => $available_list[$i]])) {
                $DB->deleteOrDie(
                    $items_table,
                    [
                        'reservationitems_id' => $available_list[$i],
                    ]
                );
            }
        }
    }
}
