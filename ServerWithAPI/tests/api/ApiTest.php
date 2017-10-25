<?php

namespace LicencingTests;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Licencing\core\DBPDO;
use Licencing\core\api\APIServer;
use Licencing\core\SVG;

class ApiTest extends TestCase
{

    private static $_db;

    private static $_oauthDB;

    private static $_key;

    public static function setupBeforeClass()
    {
        self::$_db      = new DBPDO(
            "pgsql:host=[redacted];dbname=[redacted]",
            "[redacted]",
            "[redacted]",
            [
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES   => FALSE,
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            ]
        );
        self::$_oauthDB = new DBPDO(
            "pgsql:host=localhost;dbname=oauth_test",
            "[redacted]",
            "[redacted]",
            [
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES   => FALSE,
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            ]
        );
        self::$_key     = [
            'valid'   => '-----BEGIN LICENCE KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEA8BlUFoEjmmdgAipIF1wD
umP254t4kSOvI8Om9aboj4/ZhmM7IlTMID2zQ3St1MKeurepx7sFWmMzceX7KtCJ
zySck4RRgeYEkAOPxlIsG4/s2sKi4X+lufO1D39rxaiKfI1hOjHKplDgsuIVaFJw
ZdKQr7V1D8EPVJPSx3EVuNVf3/4ScYbcfiqAKiOD1NbQ57NoqNe4AzKzPzJ0wlRx
TtVzL1cQqOC22h9UBfdSjfWYfKIg3t9X9ZN4eFy7qQdXEwHr4joqVW+f6MbML6HG
iW5cn/g0leLFkBWzNh4P2tj2cuY/oKc5QYqSyxiWkvB7+LdqubpaWLJPpuOWdRX/
gDlGl7J7VRELGS2QusMVB+hmql0si4juA41gA9wcWIPzZkpQeo/TkiI17k0Y3u1V
WXRDzR7IEPPNKSk2h2+YN8lerd3UDO2Z3lYoZ4udHq/H5CM/3af9V9PfLp/sdel8
tQh3Ywpk9cPjou9wWyU5fp690f+CuaDuW4DYXNBDTw8aOUiS8yfvfwTxf2xYhL3W
R7BLWMjpHIviUMXeRasElLM4gwnnO6JUDVZt0+qHHa7Y1W4Duo5CZ0xLG+TjJNWP
6ThIUvToGdhu/6GmHL8jNjtVzeSXZisHF901EZ/WkNsr5PEOLAsQejw1cycE2jbX
5a2ism0K9Ch+Xj+eiKCxH48CAwEAAQ==
-----END LICENCE KEY-----',
            'invalid' => '-----BEGIN LICENCE KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEA17KIqhHgBXSm/YGJo9J/
pLNRXuj1m7/dRli9N+a+RbtN1mRY5I2v7zivvWc5iy/HyvjmCl1kATiyIEkx3Sio
QQ5tFBraZTK732CtLtApTBBuLYvYSJq+z8hPlRV3aTq2ylzPPblG5ddKu9PVjNrL
8aA4+oKt/cCeR9Diccm2PUrPGjy/Jaa6hdSmvBsYQlNP5XUQTceYOCk9qwFdaPZh
8gprMcnW8XxibZbcHzFR8PVIndvrMEWQyEAZFzs6++mgJW8IswAYBxjHDrrTmufV
g1GVdb/DbVfGbry8fXHy8gvn1g4PF2DxOuG5Dm9GtkiKttRj2gdpqLX8ySaQ8XgC
ZTRUiRMUucUbNud0arWJ8QPU9mxdhWRNr8fsR1Lm8HWu7PZdOeks1dN2HGIdmgyZ
hHfUKFa2ewdhzYmhi7G+kkFm7NCw/Qwwe+PDdRIVOhv7bheqz14qLxfsU5DMV0J4
t01fkJAmu+Guk408+M/8dCRcHcxj8vR3CTkvvqavFPEPL7+Dw8/cl9l7/kfh3RYM
tdKQk9fam7LxXpKGI70ECSCj5lVGgFDadExLxa1/6dy63zWh6DNMLGDdta2xUQPu
zQT1ibv/O+NQkIsnCIBxLHISL+K38K6pVYpH/hXiwWAyOuDFdIbxEPe19Z1DegYd
u0MFuFuNkjAW1tdgfVx8jsECAwEAAQ==
-----END LICENCE KEY-----'
        ];
    }

    public function setUp()
    {
        unset($_SERVER['HTTP_X_HTTP_METHOD']);
        unset($_SERVER['HTTP_AUTHORIZATION']);
        $_POST                     = [];
        $_SERVER['HTTPS']          = TRUE;
        $_SERVER['REQUEST_METHOD'] = "POST";
    }

    /**
     * @expectedException        Licencing\core\api\Exceptions\UnsecuredConnectionException
     * @expectedExceptionMessage Connection MUST be made over HTTPS
     * @expectedExceptionCode    505
     */
    public function testAPIServerHTTP()
    {
        unset($_SERVER['HTTPS']);
        new APIServer("Authorisation/getToken/sandbox", self::$_db, self::$_oauthDB);
    }

    /**
     * @expectedException        Licencing\core\api\Exceptions\UnsecuredConnectionException
     * @expectedExceptionMessage use of GET is not allowed
     * @expectedExceptionCode    405
     */
    public function testAPIServerGet()
    {
        $_SERVER['REQUEST_METHOD'] = "GET";
        $api                       = new APIServer("Authorisation/getToken/sandbox", self::$_db, self::$_oauthDB);
        $response                  = $api->processAPI();
    }

    /**
     * @expectedException        Licencing\core\api\Exceptions\UnsecuredConnectionException
     * @expectedExceptionMessage Connection Not Secured: no valid OAuth Token
     * @expectedExceptionCode    401
     */
    public function testAPINoOauth()
    {
        $api      = new APIServer("test", self::$_db, self::$_oauthDB);
        $response = $api->processAPI();
    }

    /**
     * @expectedException        Licencing\core\api\Exceptions\UnexpectedHeaderException
     * @expectedExceptionMessage Unexpected Header
     * @expectedExceptionCode    417
     */
    public function testAPIServerHTTPSStrangeHeader()
    {
        $_SERVER['HTTP_X_HTTP_METHOD'] = "TEST";
        new APIServer("Authorisation/getToken/sandbox", self::$_db, self::$_oauthDB);
    }

    /**
     * @expectedException        Licencing\core\api\Exceptions\UnexpectedHeaderException
     * @expectedExceptionMessage Invalid Method
     * @expectedExceptionCode    405
     */
    public function testAPIServerHTTPSStrangeMethodType()
    {
        $_SERVER['REQUEST_METHOD'] = "TEST";
        new APIServer("Authorisation/getToken/sandbox", self::$_db, self::$_oauthDB);
    }

    public function testAPIServerHTTPSNoGrantType()
    {
        $error    = '{"error":"invalid_request","error_description":"The grant type was not specified in the request"}';
        $api      = new APIServer("Authorisation/getToken/sandbox", self::$_db, self::$_oauthDB);
        $response = $api->processAPI();
        self::assertEquals($error, $response);
    }

    public function testAPIServerHTTPSNoClientCreds()
    {
        $error    = '{"error":"invalid_client","error_description":"Client credentials were not found in the headers or body"}';
        $_POST    = ["grant_type" => "client_credentials"];
        $api      = new APIServer("Authorisation/getToken/sandbox", self::$_db, self::$_oauthDB);
        $response = $api->processAPI();
        self::assertEquals($error, $response);
    }

    public function testAPIServerHTTPSInvalidClient()
    {
        $_POST                         = ["grant_type" => "client_credentials"];
        $_SERVER['HTTP_AUTHORIZATION'] = "Basic " . base64_encode("sandbox:sandbox");
        $error                         = '{"error":"invalid_client","error_description":"The client credentials are invalid"}';
        $api                           = new APIServer("Authorisation/getToken/sandbox", self::$_db, self::$_oauthDB);
        $response                      = $api->processAPI();
        self::assertEquals($error, $response);
    }

    public function testAPIServerHTTPS()
    {
        $_POST                         = ["grant_type" => "client_credentials"];
        $_SERVER['HTTP_AUTHORIZATION'] = "Basic " . base64_encode("sandbox:0A1FD305-96D1-4F24-BB24-68F9F854D5A6");
        $api                           = new APIServer("Authorisation/getToken/sandbox", self::$_db, self::$_oauthDB);
        $response                      = $api->processAPI();
        $response                      = json_decode($response);
        self::assertNotNull($response->access_token);
        self::assertEquals(3600, $response->expires_in);
        self::assertEquals("Bearer", $response->token_type);
        return $response->access_token;
    }

    public function testAPIServerHTTPSBadEndpoint()
    {
        $_POST                         = ["grant_type" => "client_credentials"];
        $_SERVER['HTTP_AUTHORIZATION'] = "Basic " . base64_encode("sandbox:0A1FD305-96D1-4F24-BB24-68F9F854D5A6");
        $api                           = new APIServer("Authorisation/test", self::$_db, self::$_oauthDB);
        $response                      = $api->processAPI();
        $response                      = json_decode($response, TRUE);
        self::assertEquals(
            [
                "error"             => "API Error",
                "error_description" => "Endpoint test not recognised, valid enpoints are: [getToken]"
            ],
            $response
        );
    }

    /**
     * @depends testAPIServerHTTPS
     */
    public function testAPIInvalidEndpoint($token)
    {
        $error                 = '"No Endpoint: test"';
        $_POST['access_token'] = $token;
        $api                   = new APIServer("test", self::$_db, self::$_oauthDB);
        $response              = $api->processAPI();
        self::assertEquals($error, $response);
    }

    /**
     * @depends                  testAPIServerHTTPS
     * @expectedException        Licencing\core\api\Exceptions\InvalidLicenseException
     * @expectedExceptionMessage Invalid Client Identification
     * @expectedExceptionCode    403
     */
    public function testAPIAuthenticateInvalidClient($token)
    {
        $_SERVER['HTTP_ORIGIN'] = '127.0.0.1';
        $_POST                  = [
            'access_token' => $token,
            'cid'          => "",
            "ignoreLog"    => TRUE,
        ];
        $api                    = new APIServer("AuthenticateLicense", self::$_db, self::$_oauthDB);
        $response               = $api->processAPI();
    }

    /**
     * @depends                  testAPIServerHTTPS
     * @expectedException        Licencing\core\api\Exceptions\InvalidLicenseException
     * @expectedExceptionMessage This License has expired.
     * @expectedExceptionCode    402
     */
    public function testAPIAuthenticateExpiredLicense($token)
    {
        $_SERVER['HTTP_ORIGIN'] = '127.0.0.1';
        $_POST                  = [
            'access_token' => $token,
            'cid'          => "CA92BFD2-1390-47C4-903D-E69C5457240C",
            "ignoreLog"    => TRUE,
        ];
        $api                    = new APIServer("AuthenticateLicense", self::$_db, self::$_oauthDB);
        $response               = $api->processAPI();
    }

    /**
     * @depends                  testAPIServerHTTPS
     * @expectedException        Licencing\core\api\Exceptions\InvalidLicenseException
     * @expectedExceptionMessage This License has yet to be activated.
     * @expectedExceptionCode    401
     */
    public function testAPIAuthenticateInactiveLicense($token)
    {
        $_SERVER['HTTP_ORIGIN'] = '127.0.0.1';
        $_POST                  = [
            'access_token' => $token,
            'cid'          => "CA92BFD2-1390-47C4-903D-E69C5457240D",
            "ignoreLog"    => TRUE,
        ];
        $api                    = new APIServer("AuthenticateLicense", self::$_db, self::$_oauthDB);
        $response               = $api->processAPI();
    }

    /**
     * @depends                  testAPIServerHTTPS
     * @expectedException        Licencing\core\api\Exceptions\InvalidOriginException
     * @expectedExceptionMessage Access to License Server from: baddomain.com is not allowed
     * @expectedExceptionCode    403
     */
    public function testAPIAuthenticateInvalidOrigin($token)
    {
        $_SERVER['HTTP_ORIGIN'] = 'baddomain.com';
        $_POST                  = [
            'access_token' => $token,
            'cid'          => "CA92BFD2-1390-47C4-903D-E69C5457240B",
            "ignoreLog"    => TRUE,
        ];
        $api                    = new APIServer("AuthenticateLicense", self::$_db, self::$_oauthDB);
        $response               = $api->processAPI();
    }

    /**
     * @depends                  testAPIServerHTTPS
     * @expectedException        Licencing\core\api\Exceptions\InvalidLicenseException
     * @expectedExceptionMessage Invalid Licence Key Provided.
     * @expectedExceptionCode    401
     */
    public function testAPIAuthenticateInvalidLicense($token)
    {
        $key = $this->_keyHeaderFooter(self::$_key['invalid']);
        openssl_public_encrypt("CA92BFD2-1390-47C4-903D-E69C5457240B", $encrypted, $key);
        $_SERVER['HTTP_ORIGIN'] = '127.0.0.1';
        $_POST                  = [
            'access_token' => $token,
            'cid'          => "CA92BFD2-1390-47C4-903D-E69C5457240B",
            'key'          => $encrypted,
            "ignoreLog"    => TRUE,
        ];
        $api                    = new APIServer("AuthenticateLicense", self::$_db, self::$_oauthDB);
        $response               = $api->processAPI();
    }

    /**
     * @depends                  testAPIServerHTTPS
     */
    public function testAPIAuthenticateValidLicense($token)
    {
        $key = $this->_keyHeaderFooter(self::$_key['valid']);
        openssl_public_encrypt("CA92BFD2-1390-47C4-903D-E69C5457240B", $encrypted, $key);
        $_SERVER['HTTP_ORIGIN'] = '127.0.0.1';
        $_POST                  = [
            'access_token' => $token,
            'cid'          => "CA92BFD2-1390-47C4-903D-E69C5457240B",
            'key'          => $encrypted,
            "ignoreLog"    => TRUE,
        ];
        $api                    = new APIServer("AuthenticateLicense", self::$_db, self::$_oauthDB);
        $response               = $api->processAPI();
        $expected               = '{"mi":[1,2,3,4,5,6,7,8,9,10,11,12],"ad":"1487338826.14851","ed":"1802871626.14851"}';
        self::assertEquals($expected, $response);
    }

    private function _log($obj)
    {
        fwrite(STDERR, print_r($obj, TRUE));
    }

    /**
     * Replace the custom key headers/footers with public key headers/footers.
     *
     * @param string $key Licence Key.
     *
     * @return string RSA key.
     */
    private function _keyHeaderFooter($key)
    {
        return str_replace('-----BEGIN LICENCE KEY-----', '-----BEGIN PUBLIC KEY-----', str_replace('-----END LICENCE KEY-----', '-----END PUBLIC KEY-----', $key));
    }
}
