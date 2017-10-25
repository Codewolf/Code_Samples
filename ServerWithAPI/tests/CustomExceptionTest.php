<?php

namespace LicencingTests;

use PHPUnit\Framework\TestCase;
use Licencing\core\api\Exceptions\InvalidLicenseException;
use Licencing\core\api\Exceptions\InvalidOriginException;
use Licencing\core\api\Exceptions\UnexpectedHeaderException;
use Licencing\core\api\Exceptions\UnsecuredConnectionException;
use Licencing\core\Exceptions\InvalidHexColorException;
use Licencing\core\Exceptions\UnauthorisedException;

class CustomExceptionTest extends TestCase
{

    /**
     * @expectedException        \Licencing\core\Exceptions\InvalidHexColorException
     * @expectedExceptionMessage
     */
    public function testInvalid()
    {
        $invalid   = new InvalidHexColorException("TEST", 1);
        $testArray = ["test" => "test"];

        $o = $this->_getPrivateProperty($invalid, "additionalData");
        self::assertNull($o->getValue($invalid));
        $invalid->setAdditionalData($testArray);
        self::assertEquals("Licencing\core\CustomException: [1]: TEST\n", $invalid->__toString());
        self::assertEquals($testArray, $o->getValue($invalid));
        self::assertEquals($testArray, $invalid->getAdditionaldata());
        throw new $invalid;
    }

    /**
     * @expectedException        \Licencing\core\Exceptions\UnauthorisedException
     * @expectedExceptionMessage Unauthorised Access Attempt To File test.php from IP test
     */
    public function testUnauthorised()
    {
        $_SERVER['REMOTE_ADDR'] = "test";
        throw new UnauthorisedException("test.php");
    }

    /**
     * @expectedException        \Licencing\core\api\Exceptions\InvalidLicenseException
     * @expectedExceptionMessage Invalid License
     */
    public function testInvalidLicense()
    {
        $ad = ["test" => "test"];
        $e  = new InvalidLicenseException("Invalid License");
        $e->setAdditionalData($ad);
        self::assertEquals($ad, $e->getAdditionalData());
        throw $e;
    }

    /**
     * @expectedException        \Licencing\core\api\Exceptions\InvalidOriginException
     * @expectedExceptionMessage Invalid Origin
     */
    public function testInvalidOrigin()
    {
        $ad = ["test" => "test"];
        $e  = new InvalidOriginException("Invalid Origin");
        $e->setAdditionalData($ad);
        self::assertEquals($ad, $e->getAdditionalData());
        throw $e;
    }

    /**
     * @expectedException        \Licencing\core\api\Exceptions\UnexpectedHeaderException
     * @expectedExceptionMessage Unexpected Header
     */
    public function testUnexpectedHeader()
    {
        $ad = ["test" => "test"];
        $e  = new UnexpectedHeaderException("Unexpected Header");
        $e->setAdditionalData($ad);
        self::assertEquals($ad, $e->getAdditionalData());
        throw $e;
    }

    /**
     * @expectedException        \Licencing\core\api\Exceptions\UnsecuredConnectionException
     * @expectedExceptionMessage Unsecured Connection
     */
    public function testUnsecuredConnection()
    {
        $ad = ["test" => "test"];
        $e  = new UnsecuredConnectionException("Unsecured Connection");
        $e->setAdditionalData($ad);
        self::assertEquals($ad, $e->getAdditionalData());
        throw $e;
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

    /**
     * Get a Private Property
     *
     * @param object|string $class  Class Object Or Name.
     * @param string        $method Method Name.
     *
     * @return \ReflectionMethod
     */
    private function _getPrivateMethod($class, $method)
    {
        $reflector = new \ReflectionClass($class);
        $method    = $reflector->getMethod($method);
        $method->setAccessible(TRUE);

        return $method;
    }

    private function _log($obj)
    {
        fwrite(STDERR, print_r($obj, TRUE));
    }
}
