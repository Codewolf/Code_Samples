<?php

namespace Licencing\core;

use ImagickPixel;

/**
 * Class SVG
 *
 * Converting JSON to SVG and SVG to JSON
 */
abstract class SVG
{

    /**
     * Get the Bounds of the svg image.
     *
     * @param array $json JSON string.
     *
     * @return array Max and Min x and y of the svg.
     */
    private static function _getSVGBounds(array $json)
    {
        $min = [
            "x" => 100000,
            "y" => 100000
        ];
        $max = [
            "x" => 0,
            "y" => 0
        ];
        foreach ($json as $point) {
            if ($point->mx < $min["x"]) {
                $min['x'] = $point->mx;
            }
            if ($point->my < $min["y"]) {
                $min['y'] = $point->my;
            }
            if (isset($point->c)) {
                self::_pointMin($point, $min);
            }
            if ($point->mx > $max["x"]) {
                $max['x'] = $point->mx;
            }
            if ($point->my > $max["y"]) {
                $max['y'] = $point->my;
            }
            if (isset($point->c)) {
                self::_pointMax($point, $max);
            }
        }

        return [
            $min,
            $max,
        ];
    }

    /**
     * Calculate Minimum coordinates Wrapper.
     *
     * @param object $point Points Object.
     * @param array  $min   Minimum Coordinate Array.
     *
     * @return void
     */
    private static function _pointMin($point, array &$min)
    {
        foreach ($point->c as $coords) {
            self::_pointCXMin($coords, $min);
            self::_pointCYMin($coords, $min);
        }
    }

    /**
     * Calculate Maximum coordinates Wrapper.
     *
     * @param object $point Points Object.
     * @param array  $max   Minimum Coordinate Array.
     *
     * @return void
     */
    private static function _pointMax($point, array &$max)
    {
        foreach ($point->c as $coords) {
            self::_pointCXMax($coords, $max);
            self::_pointCYMax($coords, $max);
        }
    }

    /**
     * Calculate Maximum X coordinates.
     *
     * @param object $coords Points Object.
     * @param array  $max    Maximum Coordinate Array.
     *
     * @return void
     */
    private static function _pointCXMax($coords, array &$max)
    {
        $max['x'] = ($coords->cx1 > $max["x"]) ? $coords->cx1 : $max['x'];
        $max['x'] = ($coords->cx2 > $max["x"]) ? $coords->cx2 : $max['x'];
        $max['x'] = ($coords->cx > $max["x"]) ? $coords->cx : $max['x'];
    }

    /**
     * Calculate Maximum Y coordinates.
     *
     * @param object $coords Points Object.
     * @param array  $max    Maximum Coordinate Array.
     *
     * @return void
     */
    private static function _pointCYMax($coords, array &$max)
    {
        $max['y'] = ($coords->cy > $max["y"]) ? $coords->cy : $max['y'];
        $max['y'] = ($coords->cy > $max["y"]) ? $coords->cy : $max['y'];
        $max['y'] = ($coords->cy > $max["y"]) ? $coords->cy : $max['y'];
    }

    /**
     * Calculate Minimum X coordinates.
     *
     * @param object $coords Points Object.
     * @param array  $min    Maximum Coordinate Array.
     *
     * @return void
     */
    private static function _pointCXMin($coords, array &$min)
    {
        $min['x'] = ($coords->cx1 < $min["x"]) ? $coords->cx1 : $min['x'];
        $min['x'] = ($coords->cx2 < $min["x"]) ? $coords->cx2 : $min['x'];
        $min['x'] = ($coords->cx < $min["x"]) ? $coords->cx : $min['x'];
    }

    /**
     * Calculate Minimum Y coordinates.
     *
     * @param object $coords Points Object.
     * @param array  $min    Maximum Coordinate Array.
     *
     * @return void
     */
    private static function _pointCYMin($coords, array &$min)
    {
        $min['y'] = ($coords->cy < $min["y"]) ? $coords->cy : $min['y'];
        $min['y'] = ($coords->cy < $min["y"]) ? $coords->cy : $min['y'];
        $min['y'] = ($coords->cy < $min["y"]) ? $coords->cy : $min['y'];
    }

    /**
     * Generate SVG XML from signature JSON
     *
     * @param string $jsonString  JSON string to parse.
     *
     * @param string $strokeColor Stroke color in string or Hexidecimal format.
     *
     * @return string SVG XML string;
     *
     */
    public static function JSON_SVG($jsonString, $strokeColor = "black")
    {
        $jsonString = json_decode($jsonString);

        if (isset($jsonString[0]->typedString)) {
            $xml  = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" standalone="no"?><svg version="1.0" xmlns="http://www.w3.org/2000/svg"></svg>');
            $text = $xml->addChild('text', $jsonString[0]->typedString);
            $text->addAttribute("x", 0);
            $text->addAttribute("y", "30px");
            $text->addAttribute("style", "font-family:'Journal', sans-serif");
            $text->addAttribute("font-size", "30px");
        } else {
            list($min, $max) = self::_getSVGBounds($jsonString);

            $xml   = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" standalone="no"?><svg viewBox="0 0 ' . ($max["x"] - $min["x"] + 5) . ' ' . ($max["y"] - $min["y"] + 5) . '" width="' . ($max["x"] - $min["x"] + 5) . 'px" height="' . ($max["y"] - $min["y"] + 5) . 'px" version="1.0" xmlns="http://www.w3.org/2000/svg"></svg>');
            $group = $xml->addChild("g");
            foreach ($jsonString as $point) {
                if (isset($point->c)) {
                    $pathsvg = 'M' . (($point->mx) - $min['x'] + 2) . ' ' . (($point->my) - $min['y'] + 2);
                    foreach ($point->c as $curve) {
                        $pathsvg .= "C " . (($curve->cx1) - $min['x'] + 2) . ',' . (($curve->cy1) - $min['y'] + 2);
                        $pathsvg .= " " . (($curve->cx2) - $min['x'] + 2) . ',' . (($curve->cy2) - $min['y'] + 2);
                        $pathsvg .= " " . (($curve->cx) - $min['x'] + 2) . ',' . (($curve->cy) - $min['y'] + 2);
                    }
                } else {
                    $pathsvg = 'M' . (($point->mx) - $min['x'] + 2) . ' ' . (($point->my) - $min['y'] + 2) . ' L ' . (($point->lx) - $min['x'] + 2) . ' ' . (($point->ly) - $min['y'] + 2) . ' Z';
                }

                $path = $group->addChild('path');
                $path->addAttribute('d', $pathsvg);
            }
            $group->addAttribute('stroke', $strokeColor);
            $group->addAttribute('stroke-width', "2");
        }

        return $xml->asXML();
    }

    /**
     * Generate JSON string from an SVG image.
     *
     * @param string|\SimpleXMLElement $xml Either an XML string or a SimpleXMLElement.
     *
     * @return string JSON array.
     */
    public static function SVG_JSON($xml)
    {
        if (!$xml instanceof \SimpleXMLElement) {
            $xml = simplexml_load_string($xml);
        }
        $json  = [];
        $paths = ((!isset($xml->g->path)) ? $xml->path : $xml->g->path);
        foreach ($paths as $path) {
            $split = preg_split("/([MCL][0-9\-,\s]+)/i", $path->attributes()->d, 0, (PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE));
            $part  = [];
            foreach ($split as $coords) {
                $type   = strtoupper(substr(trim($coords), 0, 1));
                $coords = (substr(trim($coords), 1));
                switch ($type) {
                    case "M":
                    case "L":
                        $split2                        = preg_split("/[\s,]/", $coords, 0, PREG_SPLIT_NO_EMPTY);
                        $part[strtolower($type) . "x"] = $split2[0];
                        $part[strtolower($type) . "y"] = $split2[1];
                        break;

                    case "C":
                        $split2 = preg_split("/[\s,]/", $coords, 0, PREG_SPLIT_NO_EMPTY);
                        $chunk  = array_chunk($split2, 6);
                        foreach ($chunk as $curve) {
                            $part[strtolower($type)][] = [
                                "cx1" => $curve[0],
                                "cy1" => $curve[1],
                                "cx2" => $curve[2],
                                "cy2" => $curve[3],
                                "cx"  => $curve[4],
                                "cy"  => $curve[5],
                            ];
                        }
                        break;

                    default:
                        // Do Nothing.
                        break;
                }
            }
            $json[] = $part;
        }
        return json_encode($json);
    }

    /**
     * Replace SVG width and Height to make the SVG responsive.
     *
     * @param \SimpleXMLElement|string $svg  SVG File.
     * @param \null|array|string       $size (optional) Force A width and height of the SVG or "ratio".
     *
     * @return string SVG.
     */
    public static function RESPONSIVE($svg, $size = NULL)
    {
        if (!$svg instanceof \SimpleXMLElement) {
            $svg = simplexml_load_string($svg);
        }
        if ($size === NULL) {
            self::_processSizeDefault($svg);
        } else if (is_array($size)) {
            self::_processSizeArray($size, $svg);
        } else {
            self::_processSizeRatio($svg);
        }
        return $svg->asXML();
    }

    /**
     * Process the Forced Width and Height.
     *
     * @param array             $size Width and Height of the svg [Width,Height].
     * @param \SimpleXMLElement $svg  SVG Object.
     *
     * @return void
     */
    private static function _processSizeArray(array $size, \SimpleXMLElement &$svg)
    {
        if ($size[0] === NULL) {
            unset($svg->attributes()->width);
        } else {
            $svg->attributes()->width = $size[0];
        }
        if ($size[1] === NULL) {
            unset($svg->attributes()->height);
        } else {
            $svg->attributes()->height = $size[1];
        }
    }

    /**
     * Process the default sizing parameters.
     *
     * @param \SimpleXMLElement $svg SVG Object.
     *
     * @return void
     */
    private static function _processSizeDefault(\SimpleXMLElement &$svg)
    {
        if (floatval($svg->attributes()->width) > floatval($svg->attributes()->height)) {
            $svg->attributes()->width = "100%";
            unset($svg->attributes()->height);
        } else {
            $svg->attributes()->height = "100%";
            unset($svg->attributes()->width);
        }
    }

    /**
     * Process the ratio.
     *
     * @param \SimpleXMLElement $svg SVG Object.
     *
     * @return void
     */
    private static function _processSizeRatio(\SimpleXMLElement &$svg)
    {
        if (floatval($svg->attributes()->width) > floatval($svg->attributes()->height)) {
            $ratio                     = (floatval($svg->attributes()->width) / floatval($svg->attributes()->height));
            $svg->attributes()->width  = "100%";
            $svg->attributes()->height = round((100 / $ratio), 2) . "%";
        } else {
            $ratio                     = (floatval($svg->attributes()->height) / floatval($svg->attributes()->width));
            $svg->attributes()->height = "100%";
            $svg->attributes()->width  = round((100 / $ratio), 2) . "%";
        }
    }

    /**
     * Create PNG Image Blob from SVG.
     *
     * @param string $svg        SVG XML.
     *
     * @param array  $resolution Resolution to set.
     *
     * @codeCoverageIgnore Ignoring coverage due to using external library.
     *
     * @return string PNG Image Blob.
     */
    public static function TOPNG($svg, array $resolution = NULL)
    {
        $im = new \Imagick();
        $im->setBackgroundColor(new ImagickPixel('transparent'));
        if ($resolution) {
            $im->setResolution($resolution[0], $resolution[1]);
        }
        $im->readImageBlob($svg);
        $im->setImageFormat("png32");
        $im->trimImage(0);
        $img = $im->getImageBlob();
        $im->clear();
        $im->destroy();

        return $img;
    }

}