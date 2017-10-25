<?php

namespace LicencingTests;

use PHPUnit\Framework\TestCase;
use Licencing\ajax\LoginAjax;
use Licencing\core\DBPDO;
use Licencing\Menu;

class LoginAjaxTest extends TestCase
{

    /**
     * @var DBPDO
     */
    private static $_db;

    /**
     * Setup the DB Connection before the test
     *
     * @return void
     */
    public static function setupBeforeClass()
    {

        self::$_db              = new DBPDO(
            "pgsql:host=[redacted];dbname=[redacted]",
            "[redacted]",
            "[redacted]",
            [
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES   => FALSE,
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            ]
        );
        $GLOBALS['db']          = self::$_db;
        $_SERVER['REMOTE_ADDR'] = 'test';
        define("DEBUG", TRUE);
        define("MAX_ATTEMPTS", 5);
    }

    public function testLogin()
    {
        $_POST    = [
            'email'    => "phpunit@test",
            'password' => "password",
            'key'      => "123456789",
        ];
        $login    = new LoginAjax(self::$_db);
        $dbObject = $this->_getPrivateProperty($login, 'db');
        self::assertEquals(self::$_db, $dbObject->getValue($login));
        self::assertNotNull($_SESSION);
        self::assertEquals(json_encode(["success" => TRUE]), $login->getResponse());
    }

    /**
     * @expectedException        \Licencing\core\Exceptions\InvalidLoginException
     * @expectedExceptionMessage User: nouser@test does not exist.
     */
    public function testInvalidLoginUser()
    {
        $_POST = [
            'email'    => "nouser@test",
            'password' => "password",
            'key'      => "123456",
        ];
        $login = new LoginAjax(self::$_db);
    }

    /**
     * @expectedException        \Licencing\core\Exceptions\InvalidLoginException
     * @expectedExceptionMessage Token format is invalid
     */
    public function testAuthyFail()
    {
        $_POST = [
            'email'    => "phpunit@test",
            'password' => "password",
            'key'      => "1234",
        ];
        $login = new LoginAjax(self::$_db);
    }

    /**
     * @expectedException        \Exception
     * @expectedExceptionMessage Database Error, Please see error log for details.
     */
    public function testFail()
    {
        $_POST = [
            'email'    => "phpunit@test",
            'password' => "password"
        ];
        $login = new LoginAjax(self::$_db);
    }

    /**
     * @expectedException        \Licencing\core\Exceptions\InvalidLoginException
     * @expectedExceptionMessage User Account Is Locked. Please inform the Administrator
     * @expectedExceptionCode    403
     */
    public function testLocked()
    {
        $_POST = [
            'email'    => "locked@test",
            'password' => "password",
            'key'      => "123456789",
        ];
        $login = new LoginAjax(self::$_db);
    }

    /**
     * @expectedException        \Licencing\core\Exceptions\InvalidLoginException
     * @expectedExceptionMessageRegExp  /Incorrect Username or Password; Attempts remaining before your account is locked: [0-9]+/
     * @expectedExceptionCode           401
     */
    public function testInvalidPassword()
    {
        self::$_db->executeQuery("UPDATE users SET is_locked=0,failed_attempts=0 WHERE convert_from(decrypt(email, '61A14481-9201-4EAA-AFED-70EB8A1A4763','aes'),'SQL_ASCII')='incorrect@test'");
        $_POST = [
            'email'    => "incorrect@test",
            'password' => "invalidpassword",
            'key'      => "123456789",
        ];
        $login = new LoginAjax(self::$_db);
    }

    /**
     * @expectedException        \Licencing\core\Exceptions\InvalidLoginException
     * @expectedExceptionMessage        Incorrect Username or Password, your account has been locked
     * @expectedExceptionCode           401
     */
    public function testLockUser()
    {
        self::$_db->executeQuery("UPDATE users SET is_locked=0,failed_attempts=10 WHERE convert_from(decrypt(email, '61A14481-9201-4EAA-AFED-70EB8A1A4763','aes'),'SQL_ASCII')='incorrect@test'");
        $_POST = [
            'email'    => "incorrect@test",
            'password' => "invalidpassword",
            'key'      => "123456789",
        ];
        $login = new LoginAjax(self::$_db);
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
