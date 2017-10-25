<?php

namespace LicencingTests;

use PHPUnit\Framework\TestCase;
use Licencing\core\RGBA;

class RGBATest extends TestCase
{

    public function testPack()
    {
        self::assertEquals(4294967295, RGBA::Pack(255, 255, 255, 1));
    }

    public function testPackHex()
    {
        self::assertEquals(4294967295, RGBA::PackHex("#FFFFFF"));
    }

    /**
     * @expectedException        \Licencing\core\Exceptions\InvalidHexColorException
     * @expectedExceptionMessage Invalid Hex Color
     *
     */
    public function testPackInvalidHex()
    {
        self::assertEquals(4294967295, RGBA::PackHex("#GGASGS"));
    }

    public function testUnpack()
    {
        $rgb = ["R" => 255, "G" => 255, "B" => 255, "A" => 1];
        self::assertEquals($rgb, RGBA::Unpack(4294967295));
    }

    private function _log($obj)
    {
        fwrite(STDERR, print_r($obj, TRUE));
    }
}
