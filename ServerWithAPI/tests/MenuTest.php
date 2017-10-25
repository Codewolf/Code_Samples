<?php

namespace LicencingTests;

use PHPUnit\Framework\TestCase;
use Licencing\core\DBPDO;
use Licencing\Menu;

class MenuTest extends TestCase
{

    private static $_db;

    /**
     * Setup the DB Connection before the test
     *
     * @return void
     */
    public static function setupBeforeClass()
    {
        self::$_db     = new DBPDO(
            "pgsql:host=[redacted];dbname=[redacted]",
            "[redacted]",
            "[redacted]",
            [
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES   => FALSE,
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            ]
        );
        $GLOBALS['db'] = self::$_db;
    }

    public function testMenuSetup()
    {
        $menu        = new Menu();
        $rootsObj    = $this->_getPrivateProperty($menu, '_roots');
        $branchesObj = $this->_getPrivateProperty($menu, '_branches');
        $badgesObj   = $this->_getPrivateProperty($menu, '_badges');
        self::assertNotNull($rootsObj->getValue($menu));
        self::assertNotNull($branchesObj->getValue($menu));
        self::assertEmpty($badgesObj->getValue($menu));
        return $menu;
    }

    /**
     * @depends testMenuSetup
     */
    public function testCreateMenu($menu)
    {
        $rootsObj    = $this->_getPrivateProperty($menu, '_roots');
        $branchesObj = $this->_getPrivateProperty($menu, '_branches');
        $badgesObj   = $this->_getPrivateProperty($menu, '_badges');

        $menuArray = $menu->createMenu();
        self::assertEquals($rootsObj->getValue($menu), $menuArray['menu']);
        self::assertEquals([], $menuArray['badges']);
    }

    /**
     * @depends testMenuSetup
     */
    public function testAddBadges($menu)
    {
        $badgesObj = $this->_getPrivateProperty($menu, '_badges');
        self::assertEquals([], $badgesObj->getValue($menu));
        $menu->addBadge(1, "TEST");
        $menuArray = $menu->createMenu();
        self::assertEquals([1 => "TEST"], $menuArray['badges']);
    }

    /**
     * Get a Private Property
     *
     * @param object|string $class    Class Object Or Name.
     * @param string        $property Property Name.
     *
     * @return \ReflectionProperty
     */
    private function _getPrivateProperty($class, $property)
    {
        $reflector = new \ReflectionClass($class);
        $property  = $reflector->getProperty($property);
        $property->setAccessible(TRUE);

        return $property;
    }

    private function _log($obj)
    {
        fwrite(STDERR, print_r($obj, TRUE));
    }
}