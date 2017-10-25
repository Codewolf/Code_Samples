<?php

namespace LicencingTests;

use PHPUnit\Framework\TestCase;
use Licencing\core\Controller;
use Licencing\core\UUID;

/**
 * Class BaseTest
 *
 */
class ConfigTest extends TestCase
{

    protected static $twig;

    protected static $ini;

    /**
     * Set Up Before Test
     *
     * @return void
     */
    public static function setupBeforeClass()
    {
        self::$ini                            = parse_ini_file("resources/config.ini", TRUE);
        self::$ini['config']['document_root'] = dirname(__FILE__);
        $loader                               = new \Twig_Loader_Filesystem(dirname(__FILE__) . '/templates');
        self::$twig                           = new \Twig_Environment($loader, ["debug" => TRUE]);
        self::$twig->addExtension(new \Twig_Extension_Debug());
        $_SERVER = "[redacted]";
    }

    public function testController()
    {
        $controller     = new Controller(self::$twig, self::$ini);
        $basePathObject = $this->_getPrivateProperty($controller, "_basePath");
        $twigObject     = $this->_getPrivateProperty($controller, "_twig");
        $iniObject      = $this->_getPrivateProperty($controller, "_ini");
        self::assertNotEmpty($twigObject->getValue($controller));
        self::assertNotEmpty($iniObject->getValue($controller));
        return $controller;
    }

    public function testControllerWithEndpoint()
    {
        $_SERVER['REQUEST_URI'] = '/subTemplate/login/test';
        $controller             = new Controller(self::$twig, self::$ini);
        $controllerObject       = $this->_getPrivateProperty($controller, "_controller");
        self::assertEquals("login", $controllerObject->getValue($controller));
        $_SERVER['REQUEST_URI'] = 'login';
        $controller             = new Controller(self::$twig, self::$ini);
        $controllerObject       = $this->_getPrivateProperty($controller, "_controller");
        self::assertEquals("login", $controllerObject->getValue($controller));
        return $controller;
    }

    /**
     * @depends testControllerWithEndpoint
     */
    public function testRender($controller)
    {
        $this->expectOutputString("It Works!");
        $controller->render();
    }

    /**
     * @depends testController
     */
    public function testSetBasePath($controller)
    {
        $baseObject = $this->_getPrivateProperty($controller, "_basePath");
        self::assertEmpty($baseObject->getValue($controller));
        $controller->setBasePath("TEST");
        self::assertEquals("TEST", $baseObject->getValue($controller));
    }

    public function testNonEmptyBasePath()
    {
        $_SERVER['REQUEST_URI']           = '/subTemplate/login/test';
        self::$ini['config']['base_path'] = "subTemplate";
        $controller                       = new Controller(self::$twig, self::$ini);
        $cObject                          = $this->_getPrivateProperty($controller, "_controller");
        self::assertEquals("login", $cObject->getValue($controller));
        self::$ini['config']['basepath'] = "";
    }

    /**
     * @expectedException        \Exception
     * @expectedExceptionMessage Page Does Not Exist: noexist
     */
    public function testRenderNotExist()
    {
        $_SERVER['REQUEST_URI'] = 'noexist';
        $controller             = new Controller(self::$twig, self::$ini);
        $controller->render();
    }

    public function testRenderMissingFile()
    {
        // Set the output to hidden.

        $this->setOutputCallback(function () {
        });
        $_SERVER['REQUEST_URI'] = 'login';
        $controller             = new Controller(self::$twig, self::$ini);
        $a                      = $this->_getPrivateProperty($controller, "_controller");
        $a->setValue($controller, 'noexist');
        $controller->render();
        self::assertEquals('login', $this->_getPrivateProperty($controller, "_controller")->getValue($controller));
    }

    /**
     * @expectedException        \Exception
     * @expectedExceptionMessage Unable to render Page
     */
    public function testRenderFailure()
    {
        $_SERVER['REQUEST_URI'] = 'invalid';
        $controller             = new Controller(self::$twig, self::$ini);
        $controller->render();
    }

    public function testUUID()
    {
        $v4Regex = "/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/";
        $toRegex = "/^[0-9A-F]{8}-[0-9]{2}-[0-9]{2}-[0-9]{2}/";
        self::assertTrue((bool) preg_match($v4Regex, UUID::generate(UUID::VERSION_4)));
        self::assertTrue((bool) preg_match($toRegex, UUID::generate(UUID::CUSTOM)));
    }

    /**
     * @expectedException        \Exception
     * @expectedExceptionMessage Current Versions supported:4,Licencing
     */
    public function testUUIDFail()
    {
        UUID::generate("BOB");
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