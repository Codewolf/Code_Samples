<?php

namespace LicencingTests;

use PHPUnit\Framework\TestCase;
use Licencing\core\RGBA;
use Licencing\GlobalFunction;

class GlobalTest extends TestCase
{

    public function testFileError()
    {
        $errors = [
            1  => ["message" => "The File is too large, please upload a smaller file.", "code" => 413,],
            3  => ["message" => "The File failed to upload successfully, please try again.", "code" => 400,],
            6  => ["message" => "Unable to write file.", "code" => 507,],
            99 => ["message" => "An unknown error occured.", "code" => 500],
        ];
        foreach ($errors as $code => $error) {
            self::assertEquals($error, GlobalFunction::fileErrors($code));
        }
    }

    public function testInArray()
    {
        $testArray = [1, 2, [45, [95, 99]]];
        self::assertTrue(GlobalFunction::inArrayR(99, $testArray));
        self::assertFalse(GlobalFunction::inArrayR("99", $testArray, TRUE));
        self::assertFalse(GlobalFunction::inArrayR(233, $testArray));
    }

    public function testFormatPostcode()
    {
        $postcode  = "SG12DX";
        $formatted = "SG1 2DX";
        self::assertEquals($formatted, GlobalFunction::formatPostcode($postcode));
    }

    public function testPasswordGenerator()
    {
        $password = GlobalFunction::generatePassword(15);
        self::assertTrue(strlen($password) == 15);
    }

    public function testSortSubkey()
    {
        $unsortedASC  = [
            ["id" => 53],
            ["id" => 15],
            ["id" => 35],
            ["id" => 99],
            ["id" => 25],
        ];
        $unsortedDESC = [
            ["id" => 53],
            ["id" => 15],
            ["id" => 35],
            ["id" => 99],
            ["id" => 25],
        ];
        $sortedASC    = [
            ["id" => 15],
            ["id" => 25],
            ["id" => 35],
            ["id" => 53],
            ["id" => 99],
        ];
        $sortedDESC   = [
            ["id" => 99],
            ["id" => 53],
            ["id" => 35],
            ["id" => 25],
            ["id" => 15],
        ];

        GlobalFunction::uSortSubkey($unsortedASC, 'id', "ASC");
        GlobalFunction::uSortSubkey($unsortedDESC, 'id', "DESC");
        self::assertEquals($sortedASC, $unsortedASC);
        self::assertEquals($sortedDESC, $unsortedDESC);
    }

    public function testEnglishImplode()
    {
        $result = "1,2,3 & 4";
        $array  = [1, 2, 3, 4];
        self::assertEquals($result, GlobalFunction::englishImplode($array));
    }

    public function testPGImplode()
    {
        $expected      = "{1,2,3,4,5}";
        $array         = [1, 2, 3, 4, 5];
        $expectedAssoc = '{"A":1,"B":2,"C":3,"D":4}';
        $arrayAssoc    = ["A" => 1, "B" => 2, "C" => 3, "D" => 4];
        self::assertEquals($expected, GlobalFunction::pgImplode($array));
        self::assertEquals($expectedAssoc, GlobalFunction::pgImplode($arrayAssoc));
    }

    private function _log($obj)
    {
        fwrite(STDERR, print_r($obj, TRUE));
    }
}
