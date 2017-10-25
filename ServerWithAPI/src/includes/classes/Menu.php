<?php

namespace Licencing;

/**
 * Class Menu
 * This class creates the menu to be used in the left sidebar.
 *
 * @package Licencing
 */
class Menu
{

    /**
     * @var \Licencing\core\DBPDO Database Resource.
     */
    private $_db;

    /**
     * @var array Main Menu Items.
     */
    private $_roots;

    /**
     * @var array Menu Branches.
     */
    private $_branches;

    /**
     * @var array Menu Badges.
     */
    private $_badges = [];

    /**
     * Menu constructor.
     */
    public function __construct()
    {
        $this->_db       = $GLOBALS['db'];
        $this->_roots    = $this->_fetchRoots();
        $this->_branches = $this->_fetchBranches();
    }

    /**
     * Fetch the Root menu items from the database.
     *
     * @return array
     */
    private function _fetchRoots(): array
    {
        $roots = [];
        $query = $this->_db->executeQuery("SELECT *,array_to_json(visible_to_roles) AS visible_to_roles FROM menu_roots ORDER BY menu_order ASC");
        while (($row = $query->fetch()) !== FALSE) {
            $row['visible_to_roles'] = json_decode($row['visible_to_roles']);
            $roots[$row['id']]       = $row;
        }
        return $roots;
    }

    /**
     * Fetch the Branch/Leaf menu items from the database.
     *
     * @return array
     */
    private function _fetchBranches(): array
    {
        $branches = [];
        $query    = $this->_db->executeQuery(
            "SELECT 
                    *,
                    coalesce(parent_branch,0) AS parent_branch,
                    array_to_json(visible_to_roles) AS visible_to_roles
                    FROM menu_branches ORDER BY menu_order ASC"
        );
        while (($row = $query->fetch()) !== FALSE) {
            $row['visible_to_roles'] = json_decode($row['visible_to_roles']);
            $branches[]              = $row;
        }
        return $branches;
    }

    /**
     * Build the Menu Tree
     *
     * @param array   $branches Array of Branches.
     * @param integer $root     The Root Menu Items.
     *
     * @return array
     */
    private function _buildTree(array &$branches, int $root = 0): array
    {
        $branch = [];
        foreach ($branches as $element) {
            if ($element['parent_branch'] === $root) {
                $children = $this->_buildTree($branches, $element['id']);
                if ($children) {
                    $element['children']  = $children;
                    $element['linkarray'] = array_map(
                        function ($str) {
                            return trim($str, '/');
                        },
                        array_filter(array_column($children, 'link'))
                    );
                }
                $branch[] = $element;
            }
        }
        unset($branches);
        return $branch;
    }

    /**
     * Generate Array to be sent to twig to render the menu.
     *
     * @param array $branches Tree Branches.
     *
     * @return void
     */
    private function _processTree(array $branches)
    {
        foreach ($branches as $branch) {
            if (!isset($this->_roots[$branch["root"]]["linkarray"])) {
                $this->_roots[$branch["root"]]["linkarray"] = [];
            }
            if (($lnk = trim($branch['link'], '/')) != '') {
                $this->_roots[$branch["root"]]["linkarray"][] = $lnk;
            }
            if (isset($branch['linkarray'])) {
                $this->_roots[$branch["root"]]["linkarray"] = array_merge($this->_roots[$branch["root"]]["linkarray"], $branch["linkarray"]);
            }
            $this->_roots[$branch["root"]]['children'][] = $branch;
        }
    }

    /**
     * Create the menu and sub-menus from the data provided.
     *
     * @return array Menu and any associated badges.
     */
    public function createMenu()
    {
        $this->_processTree($this->_buildTree($this->_branches));
        return [
            "menu"   => $this->_roots,
            "badges" => $this->_badges,
        ];
    }

    /**
     * Add a badge to the menu.
     *
     * @param integer        $id    Root Id.
     * @param integer|string $value value to put into the badge.
     *
     * @return Menu This method can be chained to add multiple badges.
     */
    public function addBadge(int $id, $value): Menu
    {
        $this->_badges[$id] = $value;
        return $this;
    }

}