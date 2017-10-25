<?php

namespace LicencingTests;

use PHPUnit\Framework\TestCase;
use Licencing\ajax\LicenseAjax;
use Licencing\core\DBPDO;
use Licencing\Menu;
use Licencing\core\api\Exceptions\InvalidLicenseException;

class LicenseAjaxTest extends TestCase
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

        self::$_db     = new DBPDO(
            "pgsql:host=[redacted];dbname=[redacted]",
            "[redacted]",
            "[redacted]",
            [
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES   => FALSE,
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            ]
        );
        $db            = self::$_db;
        $GLOBALS['db'] = self::$_db;
    }

    /**
     * @expectedException        \Licencing\core\Exceptions\InvalidAjaxEndpointException
     * @expectedExceptionMessage Endpoint: test does not exist
     * @expectedExceptionCode    404
     */
    public function testInvalidEndpoint()
    {
        $_POST['type'] = "test";
        new LicenseAjax(self::$_db);
    }

    public function testExistingLicense()
    {

        $expected                      = self::$_db->fetchQuery("SELECT
                      TO_CHAR(l.creation_date, 'Dy DD Mon YYYY, HH24:MI')   AS creation_date,
                      TO_CHAR(l.activation_date, 'Dy DD Mon YYYY, HH24:MI') AS activation_date,
                      TO_CHAR(l.expiry_date, 'Dy DD Mon YYYY, HH24:MI')     AS expiry_date,
                      array_to_json(array_agg(mi.id
                                    ORDER BY mi.id ASC))                    AS modules_installed,
                      array_to_json(l.ip_addr)                              AS ip_addr,
                      array_to_json(l.domains)                              AS domains,
                      array_to_json(array_agg(mi.description
                                    ORDER BY mi.id ASC))                    AS modules_names,
                      now() BETWEEN activation_date AND expiry_date         AS active
                    FROM license l
                      LEFT JOIN modules_available mi ON mi.id = ANY (l.modules_installed) AND mi.enabled_by_default=0
                    WHERE client_id = 'CA92BFD2-1390-47C4-903D-E69C5457240B'
                    GROUP BY l.id
                    ORDER BY creation_date DESC
                    LIMIT 1;");
        $expected['modules_installed'] = json_decode($expected['modules_installed'], TRUE);
        $expected['modules_names']     = json_decode($expected['modules_names'], TRUE);
        $expected['ip_addr']           = json_decode($expected['ip_addr'], TRUE);
        $expected['domains']           = json_decode($expected['domains'], TRUE);

        $_POST    = [
            'type'     => "fetchExistingLicenses",
            "clientId" => "CA92BFD2-1390-47C4-903D-E69C5457240B",
        ];
        $license  = new LicenseAjax(self::$_db);
        $expected = json_encode(["license" => $expected]);
        self::assertEquals($expected, $license->getResponse());
    }

    public function testExistingLicenseNoLicense()
    {
        self::$_db->executeQuery("DELETE FROM license WHERE client_id='CA92BFD2-1390-47C4-903D-E69C5457240X'");
        $_POST    = [
            'type'     => "fetchExistingLicenses",
            "clientId" => "CA92BFD2-1390-47C4-903D-E69C5457240X",
        ];
        $license  = new LicenseAjax(self::$_db);
        $expected = json_encode(["license" => FALSE]);
        self::assertEquals($expected, $license->getResponse());
    }

    public function testGenerateLicenseKeys()
    {
        self::$_db->executeQuery("DELETE FROM license WHERE client_id='CA92BFD2-1390-47C4-903D-E69C5457240X'");
        $_POST                         = [
            'type'       => "generateLicenseKeys",
            "clientId"   => "CA92BFD2-1390-47C4-903D-E69C5457240X",
            "ips"        => "127.0.0.1\n192.168.1.1",
            "domains"    => "domain1.com\nlocalhost",
            "modules"    => [2, 3, 4, 5, 6],
            "activation" => "2017-01-01",
            "expiry"     => "2027-01-01",
        ];
        $license                       = new LicenseAjax(self::$_db);
        $expected                      = self::$_db->fetchQuery("
                                SELECT
                                *,
                                 array_to_json(l.ip_addr)                              AS ip_addr,
                                  array_to_json(l.domains)                              AS domains,
                                  array_to_json(l.modules_installed) AS modules_installed
                                FROM license l WHERE client_id='CA92BFD2-1390-47C4-903D-E69C5457240X'"
        );
        $expected['modules_installed'] = json_decode($expected['modules_installed'], TRUE);
        $expected['ip_addr']           = json_decode($expected['ip_addr'], TRUE);
        $expected['domains']           = json_decode($expected['domains'], TRUE);

        $privateKey = openssl_pkey_get_private($expected['license_key']);
        $publicKey  = $this->_keyHeaderFooter(openssl_pkey_get_details($privateKey)['key']);
        self::assertEquals(json_encode(["license" => $publicKey]), $license->getResponse());
        self::assertEquals([1, 2, 3, 4, 5, 6, 12], $expected['modules_installed']);
        self::assertEquals(["127.0.0.1", "192.168.1.1"], $expected['ip_addr']);
        self::assertEquals(["domain1.com", "localhost"], $expected['domains']);
    }

    public function testGetLicenseHistory()
    {
        $_POST    = [
            'type'     => "fetchLicenseHistory",
            "clientId" => 'CA92BFD2-1390-47C4-903D-E69C5457240B',
        ];
        $license  = new LicenseAjax(self::$_db);
        $expected = '{"licenses":[{"id":2,"creation_date":"Wed 03 May 2017, 10:17","creation_date_iso":"2017-05-03 10:17:04","activation_date":"Fri 17 Feb 2017, 13:40","activation_date_iso":"2017-02-17 13:40:26","expiry_date":"Wed 17 Feb 2027, 13:40","expiry_date_iso":"2027-02-17 13:40:26","ip_addr":["127.0.0.1"],"domains":["127.0.0.1"],"modules_names":["Dashboard","Business Development","Customer Service","Finance \\/ Invoicing","Progress Tracking","Transport Operation","Warehouse Operation","Stock Inventory","Purchasing","Human Resources","Task Management","System Administration"],"active":true,"isLatest":true}]}';
        self::assertEquals($expected, $license->getResponse());
    }

    public function testFetchLicenseKey()
    {
        $_POST    = [
            'type' => "fetchLicenseKey",
            "kid"  => 2,
        ];
        $expected = "-----BEGIN LICENCE KEY-----\nMIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEA8BlUFoEjmmdgAipIF1wD\numP254t4kSOvI8Om9aboj4/ZhmM7IlTMID2zQ3St1MKeurepx7sFWmMzceX7KtCJ\nzySck4RRgeYEkAOPxlIsG4/s2sKi4X+lufO1D39rxaiKfI1hOjHKplDgsuIVaFJw\nZdKQr7V1D8EPVJPSx3EVuNVf3/4ScYbcfiqAKiOD1NbQ57NoqNe4AzKzPzJ0wlRx\nTtVzL1cQqOC22h9UBfdSjfWYfKIg3t9X9ZN4eFy7qQdXEwHr4joqVW+f6MbML6HG\niW5cn/g0leLFkBWzNh4P2tj2cuY/oKc5QYqSyxiWkvB7+LdqubpaWLJPpuOWdRX/\ngDlGl7J7VRELGS2QusMVB+hmql0si4juA41gA9wcWIPzZkpQeo/TkiI17k0Y3u1V\nWXRDzR7IEPPNKSk2h2+YN8lerd3UDO2Z3lYoZ4udHq/H5CM/3af9V9PfLp/sdel8\ntQh3Ywpk9cPjou9wWyU5fp690f+CuaDuW4DYXNBDTw8aOUiS8yfvfwTxf2xYhL3W\nR7BLWMjpHIviUMXeRasElLM4gwnnO6JUDVZt0+qHHa7Y1W4Duo5CZ0xLG+TjJNWP\n6ThIUvToGdhu/6GmHL8jNjtVzeSXZisHF901EZ/WkNsr5PEOLAsQejw1cycE2jbX\n5a2ism0K9Ch+Xj+eiKCxH48CAwEAAQ==\n-----END LICENCE KEY-----\n";
        $license  = new LicenseAjax(self::$_db);
        self::assertEquals(json_encode(["license" => $expected]), $license->getResponse());
    }

    /**
     * @expectedException        \Licencing\core\api\Exceptions\InvalidLicenseException
     * @expectedExceptionMessage No License Key Available.
     * @expectedExceptionCode    401
     */
    public function testFetchLicenseKeyInvalidId()
    {
        $_POST   = [
            'type' => "fetchLicenseKey",
            "kid"  => 999,
        ];
        $license = new LicenseAjax(self::$_db);
    }

    /**
     * @expectedException        \Licencing\core\api\Exceptions\InvalidLicenseException
     * @expectedExceptionMessage Unable to Fetch License Key.
     * @expectedExceptionCode    500
     */
    public function testFetchLicenseKeyNoId()
    {
        $_POST   = [
            'type' => "fetchLicenseKey",
        ];
        $license = new LicenseAjax(self::$_db);
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

    private function _keyHeaderFooter($key)
    {
        return str_replace('-----BEGIN PUBLIC KEY-----', '-----BEGIN LICENCE KEY-----', str_replace('-----END PUBLIC KEY-----', '-----END LICENCE KEY-----', $key));
    }

    private function _log($obj)
    {
        fwrite(STDERR, print_r($obj, TRUE));
    }
}
