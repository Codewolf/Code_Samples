<?php

namespace LicencingTests;

use PHPUnit\Framework\TestCase;
use Licencing\core\User;

class UserTest extends TestCase
{

    public static function setupBeforeClass()
    {
        $_SESSION      = [
            'user'          => [
                'id'        => 1,
                'shortname' => 'Matt',
                'name'      => 'Matt Nunn',
                'email'     => 'MH.Nunn@gmail.com',
                'ssid'      => 'D0B4E0D2-40B1-4ACE-9A15-53ADC6E34736',
                'roles'     => [0 => 1],
            ],
            'LAST_ACTIVITY' => 1494411234,
        ];
        $GLOBALS['db'] = [];
    }

    public function testUserClass()
    {
        $user  = new User($_SESSION['user']);
        $_user = $this->_getPrivateProperty($user, "_user")->getValue($user);
        self::assertEquals($_SESSION['user'], $_user);
        return $user;
    }

    /**
     * @depends testUserClass
     */
    public function testGetId($user)
    {
        self::assertEquals($_SESSION['user']['id'], $user->getId());
    }

    /**
     * @depends testUserClass
     */
    public function testGetEmail($user)
    {
        self::assertEquals($_SESSION['user']['email'], $user->getEmail());
    }

    /**
     * @depends testUserClass
     */
    public function testGetNameFull($user)
    {
        self::assertEquals($_SESSION['user']['name'], $user->getName());
        self::assertEquals($_SESSION['user']['name'], $user->getName('full'));
    }

    /**
     * @depends testUserClass
     */
    public function testGetNameShort($user)
    {
        self::assertEquals($_SESSION['user']['shortname'], $user->getName('first'));
        self::assertEquals($_SESSION['user']['shortname'], $user->getName('firstname'));
        self::assertEquals($_SESSION['user']['shortname'], $user->getName('short'));
    }

    /**
     * @depends testUserClass
     */
    public function testHasRole($user)
    {
        self::assertTrue($user->hasRole(1));
    }

    /**
     * @depends testUserClass
     */
    public function testHasRoleFalse($user)
    {
        self::assertFalse($user->hasRole(2));
    }

    /**
     * @depends testUserClass
     */
    public function testHasRoleArray($user)
    {
        self::assertTrue($user->hasRole([1]));
    }

    /**
     * @depends testUserClass
     */
    public function testHasRoleFalseArray($user)
    {
        self::assertFalse($user->hasRole([2]));
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
