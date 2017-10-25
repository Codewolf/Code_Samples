<?php
/**
 * - Copyright (c) Matt Nunn - All Rights Reserved
 * - Unauthorized copying of this file via any medium is strictly prohibited
 * - Written by Matt Nunn <MH.Nunn@gmail.com> 2016.
 */

namespace Licencing\core;

use Licencing\core\Exceptions\InvalidHexColorException;

/**
 * Class RGBA
 *
 * @package Licencing\core
 */
abstract class RGBA
{

    /**
     * Transform an RGBA value into a packed integer for storage.
     *
     * @param integer $R Red Color Value (0-255).
     * @param integer $G Green Color Value (0-255).
     * @param integer $B Blue Color Value (0-255).
     * @param float   $A Alpha (transparency) Value (0-1) (optional).
     *
     * @return integer Packed integer value.
     */
    public static function Pack($R, $G, $B, $A = 0.00)
    {
        $packed = ($R << 24);

        $packed |= ($G << 16);
        $packed |= ($B << 8);
        $packed |= intval(($A * 255));
        return $packed;
    }

    /**
     * Transform a HEX value into a packed integer for storage.
     *
     * @param string $hex Color value in hex.
     *
     * @return integer Packed integer value.
     * @throws InvalidHexColorException If Color is not a valid Hex Color.
     */
    public static function PackHex($hex)
    {
        if (preg_match("/([A-F0-9]{1,2})([A-F0-9]{1,2})([A-F0-9]{1,2})/i", ltrim($hex, '#'), $rgb)) {
            array_shift($rgb);
            foreach ($rgb as &$byte) {
                if (strlen($byte) < 2) {
                    $byte = str_repeat($byte, 2);
                }
            }
        } else {
            throw new InvalidHexColorException("Invalid Hex Color");
        }
        list($R, $G, $B) = array_map("hexdec", $rgb);

        $packed = ($R << 24);

        $packed |= ($G << 16);
        $packed |= ($B << 8);
        $packed |= (255);
        return $packed;
    }

    /**
     * Transform a packed Integer back into rgba values.
     *
     * @param integer $packed Packed Integer value.
     *
     * @return array Array containing RGBA Values.
     */
    public static function Unpack($packed)
    {
        return [
            "R" => (($packed >> 24) & 255),
            "G" => (($packed >> 16) & 255),
            "B" => (($packed >> 8) & 255),
            "A" => ((($packed) & 255) / 255)
        ];
    }
}