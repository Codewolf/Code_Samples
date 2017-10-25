<?php

namespace LicencingTests;

use PHPUnit\Framework\TestCase;
use Licencing\core\SVG;

class SvgTest extends TestCase
{

    public function testJsonSVG()
    {
        $_json = [
            '[{"lx":128,"ly":68,"mx":128,"my":67},{"lx":128,"ly":67,"mx":128,"my":68}]',
            '[{"mx":"10","my":"10","c":[{"cx1":"20","cy1":"20","cx2":"40","cy2":"20","cx":"50","cy":"10"}]}]',
            '[{"typedString": "TEST"}]',
        ];
        $_xml  = [
            "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>\n<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 5 6\" width=\"5px\" height=\"6px\" version=\"1.0\"><g stroke=\"black\" stroke-width=\"2\"><path d=\"M2 2 L 2 3 Z\"/><path d=\"M2 3 L 2 2 Z\"/></g></svg>\n",
            "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>\n<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 45 5\" width=\"45px\" height=\"5px\" version=\"1.0\"><g stroke=\"black\" stroke-width=\"2\"><path d=\"M2 2C 12,12 32,12 42,2\"/></g></svg>\n",
            "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>\n<svg xmlns=\"http://www.w3.org/2000/svg\" version=\"1.0\"><text x=\"0\" y=\"30px\" style=\"font-family:'Journal', sans-serif\" font-size=\"30px\">TEST</text></svg>\n"
        ];

        foreach ($_json as $k => $json) {
            self::assertEquals($_xml[$k], SVG::JSON_SVG($json));
        }
    }

    public function testSVGJson()
    {
        $_json = [
            '[{"mx":"2","my":"2","lx":"2","ly":"3"},{"mx":"2","my":"3","lx":"2","ly":"2"}]',
            '[{"mx":"2","my":"2","c":[{"cx1":"12","cy1":"12","cx2":"32","cy2":"12","cx":"42","cy":"2"}]}]',
        ];
        $_xml  = [
            "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>\n<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 5 6\" width=\"5px\" height=\"6px\" version=\"1.0\"><g stroke=\"black\" stroke-width=\"2\"><path d=\"M2 2 L 2 3 Z\"/><path d=\"M2 3 L 2 2 Z\"/></g></svg>\n",
            "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>\n<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 45 5\" width=\"45px\" height=\"5px\" version=\"1.0\"><g stroke=\"black\" stroke-width=\"2\"><path d=\"M2 2C 12,12 32,12 42,2\"/></g></svg>\n",
        ];
        foreach ($_json as $k => $json) {
            self::assertEquals($json, SVG::SVG_JSON($_xml[$k]));
        }
    }

    public function testResponsive()
    {
        $_xmlIn  = [
            "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>\n<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 5 6\" width=\"5px\" height=\"6px\" version=\"1.0\"><g stroke=\"black\" stroke-width=\"2\"><path d=\"M2 2 L 2 3 Z\"/><path d=\"M2 3 L 2 2 Z\"/></g></svg>\n",
            "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>\n<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 5 6\" width=\"6px\" height=\"5px\" version=\"1.0\"><g stroke=\"black\" stroke-width=\"2\"><path d=\"M2 2 L 2 3 Z\"/><path d=\"M2 3 L 2 2 Z\"/></g></svg>\n",
        ];
        $_xmlOut = [
            "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>\n<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 5 6\" height=\"100%\" version=\"1.0\"><g stroke=\"black\" stroke-width=\"2\"><path d=\"M2 2 L 2 3 Z\"/><path d=\"M2 3 L 2 2 Z\"/></g></svg>\n",
            "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>\n<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 5 6\" width=\"100%\" version=\"1.0\"><g stroke=\"black\" stroke-width=\"2\"><path d=\"M2 2 L 2 3 Z\"/><path d=\"M2 3 L 2 2 Z\"/></g></svg>\n",
            "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>\n<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 5 6\" height=\"100px\" version=\"1.0\"><g stroke=\"black\" stroke-width=\"2\"><path d=\"M2 2 L 2 3 Z\"/><path d=\"M2 3 L 2 2 Z\"/></g></svg>\n",
            "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>\n<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 5 6\" width=\"100px\" version=\"1.0\"><g stroke=\"black\" stroke-width=\"2\"><path d=\"M2 2 L 2 3 Z\"/><path d=\"M2 3 L 2 2 Z\"/></g></svg>\n",
            "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>\n<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 5 6\" width=\"83.33%\" height=\"100%\" version=\"1.0\"><g stroke=\"black\" stroke-width=\"2\"><path d=\"M2 2 L 2 3 Z\"/><path d=\"M2 3 L 2 2 Z\"/></g></svg>\n",
            "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>\n<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 5 6\" width=\"100%\" height=\"83.33%\" version=\"1.0\"><g stroke=\"black\" stroke-width=\"2\"><path d=\"M2 2 L 2 3 Z\"/><path d=\"M2 3 L 2 2 Z\"/></g></svg>\n",
        ];
        // No Size.
        self::assertEquals($_xmlOut[0], SVG::RESPONSIVE($_xmlIn[0]));
        self::assertEquals($_xmlOut[1], SVG::RESPONSIVE($_xmlIn[1]));
        // No Width.
        self::assertEquals($_xmlOut[2], SVG::RESPONSIVE($_xmlIn[0], [NULL, "100px"]));
        // No Height.
        self::assertEquals($_xmlOut[3], SVG::RESPONSIVE($_xmlIn[0], ["100px", NULL]));
        // Automatic Ratio.
        self::assertEquals($_xmlOut[4], SVG::RESPONSIVE($_xmlIn[0], "ratio"));
        self::assertEquals($_xmlOut[5], SVG::RESPONSIVE($_xmlIn[1], "ratio"));
    }

    private function _log($obj)
    {
        fwrite(STDERR, print_r($obj, TRUE));
    }
}
