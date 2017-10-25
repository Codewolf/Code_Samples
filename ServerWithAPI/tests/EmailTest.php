<?php

namespace LicencingTests;

use PHPUnit\Framework\TestCase;
use Licencing\core\Controller;
use Licencing\core\TwigMailer;
use Licencing\core\UUID;

/**
 * Class BaseTest
 *
 */
class EmailTest extends TestCase
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
        self::$ini = parse_ini_file("resources/config.ini", TRUE);
        $loader     = new \Twig_Loader_Filesystem(dirname(__FILE__) . '/templates');
        self::$twig = new \Twig_Environment($loader, ["debug" => TRUE]);
        self::$twig->addExtension(new \Twig_Extension_Debug());
        $_SERVER = "[redacted]";
    }

    public function testGetMessage()
    {
        $mailer  = new TwigMailer(self::$twig);
        $message = $mailer->getMessage("test");
        self::assertInstanceOf(\Swift_Message::class, $message);
        self::assertEquals("<p>TEST</p>", $message->getBody());
        self::assertEquals("TEST", $message->getSubject());
    }

    /**
     * @expectedException        \Licencing\core\Exceptions\MailException
     * @expectedExceptionMessage Error Creating Email
     */
    public function testGetMessageFailure()
    {
        $mailer  = new TwigMailer(self::$twig);
        $message = $mailer->getMessage("NonExistingFile");
    }

    private function _log($obj)
    {
        fwrite(STDERR, print_r($obj, TRUE));
    }
}