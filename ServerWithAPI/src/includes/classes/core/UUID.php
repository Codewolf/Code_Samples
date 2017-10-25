<?php

namespace Licencing\core;

/**
 * Class UUID
 * Create the UUID.
 *
 * @package Licencing\core
 */
abstract class UUID
{

    const VERSION_4 = 4;

    const CUSTOM = 9;

    /**
     * Hexidecimal Character List.
     *
     * @var string Characters.
     */
    private static $_hexDec = "0123456789ABCDEF";

    /**
     * UUID V4 char List.
     *
     * @var string V4 Characters
     */
    private static $_VChar = "89AB";

    /**
     * Generate The UUID string.
     *
     * @param integer $version Version of UUID.
     *
     * @return string UUID.
     * @throws \Exception If unsupported version.
     */
    public static function generate($version)
    {
        $uuid = "";
        switch ($version) {
            case self::VERSION_4:
                for ($i = 0; $i < 36; $i++) {
                    if (in_array($i, [8, 13, 18, 23])) {
                        $uuid .= "-";
                    } else if ($i == 14) {
                        $uuid .= "4";
                    } else if ($i == 19) {
                        $char = mt_rand(0, (strlen(self::$_VChar) - 1));
                        // RANDOM v4 Char.
                        $uuid .= substr(self::$_VChar, $char, 1);
                    } else {
                        $char = mt_rand(0, (strlen(self::$_hexDec) - 1));
                        // RANDOM v4 Char.
                        $uuid .= substr(self::$_hexDec, $char, 1);
                    }
                }
                break;

            case self::CUSTOM:
                for ($i = 0; $i < 8; $i++) {
                    $char = mt_rand(0, (strlen(self::$_hexDec) - 1));
                    // RANDOM v4 Char.
                    $uuid .= substr(self::$_hexDec, $char, 1);
                }
                $uuid .= "-" . date("y-m-d");
                break;

            default:
                throw new \Exception("Current Versions supported:4,Licencing");
        }

        return $uuid;
    }

}

?>