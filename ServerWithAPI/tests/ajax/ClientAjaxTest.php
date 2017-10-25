<?php

namespace LicencingTests;

use PHPUnit\Framework\TestCase;
use Licencing\ajax\ClientAjax;
use Licencing\core\DBPDO;
use Licencing\Menu;
use Licencing\core\api\Exceptions\InvalidLicenseException;

class ClientAjaxTest extends TestCase
{

    /**
     * @var DBPDO
     */
    private static $_db;

    /**
     * @var DBPDO
     */
    private static $_oauthDB;

    public static $ini;

    /**
     * Setup the DB Connection before the test
     *
     * @return void
     */
    public static function setupBeforeClass()
    {
        self::$ini      = parse_ini_file(dirname(__FILE__) . "/../resources/config.ini", TRUE);
        self::$_db      = new DBPDO(
            "pgsql:host=" . self::$ini['database']['fqdn'] . ";dbname=" . self::$ini['database']['dbname'],
            self::$ini['database']['user'],
            self::$ini['database']['pass'],
            [
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES   => FALSE,
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            ]
        );
        self::$_oauthDB = new DBPDO(
            "pgsql:host=" . self::$ini['oauth']['fqdn'] . ";dbname=" . self::$ini['oauth']['dbname'],
            self::$ini['oauth']['user'],
            self::$ini['oauth']['pass'],
            [
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES   => FALSE,
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            ]
        );
        $db             = self::$_db;
        $GLOBALS['db']  = self::$_db;
        $GLOBALS['ini'] = self::$ini;
        define('KEY', self::$ini['database']['key']);
        self::$_db->executeQuery("TRUNCATE clients CASCADE;");
        self::$_oauthDB->executeQuery("DELETE FROM oauth_clients WHERE client_id <> 'sandbox'");
    }

    /**
     * @expectedException        \Licencing\core\Exceptions\InvalidAjaxEndpointException
     * @expectedExceptionMessage Endpoint: test does not exist
     * @expectedExceptionCode    404
     */
    public function testInvalidEndpoint()
    {
        $_POST['type'] = "test";
        new ClientAjax(self::$_db);
    }

    public function testNewClient()
    {

        $_POST = [
            'type' => "newClient",
            "form" => [
                "client-name"          => "New Client 2",
                "client-email"         => "client@email.com",
                "client-address"       => "asd",
                "client-address2"      => "asd",
                "client-address3"      => "",
                "client-town"          => "asd",
                "client-postcode"      => "SG12DX",
                "client-country"       => "230",
                "client-manager"       => "1",
                "contact-name"         => ["Contact 1"],
                "contact-relation"     => ["Contact"],
                "contact-details"      => ["con@tact.com"],
                "contact-details-type" => ["3"],
            ],
        ];

        $client = new ClientAjax(self::$_db);

        // Lets see what was input.
        $newClient = self::_fetchClientDetailsFromDatabase();

        // Save the ID for later use, then remove it as this is not needed in an assertion.
        $generatedID = [
            $newClient['id'],
            $newClient['contacts'][0]['id'],
            $newClient['contacts'][0]['contact_details'][0]['id'],
        ];
        unset($newClient['id']);
        unset($newClient['contacts'][0]['id']);
        unset($newClient['contacts'][0]['contact_details'][0]['id']);

        $expected = [
            'client_name' => 'New Client 2',
            'email'       => 'client@email.com',
            'address1'    => 'asd',
            'address2'    => 'asd',
            'address3'    => '',
            'town'        => 'asd',
            'postcode'    => 'SG12DX',
            'country'     => 230,
            'manager'     => 1,
            'contacts'    =>
                [
                    0 =>
                        [
                            'contact_name'     => 'Contact 1',
                            'contact_relation' => 'Contact',
                            'contact_details'  =>
                                [
                                    0 =>
                                        [
                                            'type'  => 3,
                                            'value' => 'con@tact.com',
                                        ],
                                ],
                        ],
                ],
        ];

        self::assertEquals($expected, $newClient);
        return $generatedID;
    }

    /**
     * @expectedException        \Exception
     * @expectedExceptionMessage Unable To Create Client
     * @expectedExceptionCode    500
     */
    public function testNewClientError()
    {

        // This will throw an Exception as theres no user ID 0.
        $_POST = [
            'type' => "newClient",
            "form" => [
                "client-name"          => "New Client 2",
                "client-email"         => "client@email.com",
                "client-address"       => "asd",
                "client-address2"      => "asd",
                "client-address3"      => "",
                "client-town"          => "asd",
                "client-postcode"      => "SG12DX",
                "client-country"       => "230",
                "client-manager"       => "0",
                "contact-name"         => ["Contact 1"],
                "contact-relation"     => ["Contact"],
                "contact-details"      => ["con@tact.com"],
                "contact-details-type" => ["3"],
            ],
        ];

        $client = new ClientAjax(self::$_db);
    }

    /**
     * @expectedException        \Exception
     * @expectedExceptionMessage Unable To Create Client OAuth Login Details
     * @expectedExceptionCode    500
     */
    public function testNewClientOauthError()
    {
        // This will throw an Exception as theres already a client with this name, stripping out extraneous Characters which will cause the OAUTH to fail.
        $_POST = [
            'type' => "newClient",
            "form" => [
                "client-name"          => "New Client 2-",
                "client-email"         => "clientOauthError@email.com",
                "client-address"       => "asd",
                "client-address2"      => "asd",
                "client-address3"      => "",
                "client-town"          => "asd",
                "client-postcode"      => "SG12DX",
                "client-country"       => "230",
                "client-manager"       => "1",
                "contact-name"         => ["Contact 1"],
                "contact-relation"     => ["Contact"],
                "contact-details"      => ["con@tact.com"],
                "contact-details-type" => ["3"],
            ],
        ];

        $client = new ClientAjax(self::$_db);
    }

    /**
     * @depends testNewClient
     */
    public function testEditClient($dbID)
    {

        $_POST = [
            'type' => "editClient",
            "form" => [
                "client-id"            => $dbID[0],
                "client-name"          => "New Client 2 - Edit",
                "client-email"         => "client@email.com",
                "client-address"       => "asd",
                "client-address2"      => "asd",
                "client-address3"      => "",
                "client-town"          => "asd",
                "client-postcode"      => "SG12DX",
                "client-country"       => "231",
                "client-manager"       => "1",
                "contact-id"           => [$dbID[1]],
                "contact-name"         => ["Contact 1"],
                "contact-relation"     => ["Contact"],
                "contact-details"      => ["con@tact.com"],
                "contact-details-type" => ["3"],
                "contact-details-id"   => [$dbID[2]],
            ],
        ];

        $client = new ClientAjax(self::$_db);

        // Lets see what was input.
        $newClient = $this->_fetchClientDetailsFromDatabase();

        unset($newClient['id']);
        $expected = [
            'client_name' => 'New Client 2 - Edit',
            'email'       => 'client@email.com',
            'address1'    => 'asd',
            'address2'    => 'asd',
            'address3'    => '',
            'town'        => 'asd',
            'postcode'    => 'SG12DX',
            'country'     => 231,
            'manager'     => 1,
            'contacts'    =>
                [
                    0 =>
                        [
                            'contact_name'     => 'Contact 1',
                            'contact_relation' => 'Contact',
                            'contact_details'  =>
                                [
                                    0 =>
                                        [
                                            'type'  => 3,
                                            'value' => 'con@tact.com',
                                            'id'    => $dbID[2]
                                        ],
                                ],
                            'id'               => $dbID[1]
                        ],
                ],
        ];

        self::assertEquals($expected, $newClient);
    }

    /**
     * @depends testNewClient
     */
    public function testEditClientNewContact($dbID)
    {

        $_POST = [
            'type' => "editClient",
            "form" => [
                "client-id"            => $dbID[0],
                "client-name"          => "New Client 2 - Edit",
                "client-email"         => "client@email.com",
                "client-address"       => "asd",
                "client-address2"      => "asd",
                "client-address3"      => "",
                "client-town"          => "asd",
                "client-postcode"      => "SG12DX",
                "client-country"       => "231",
                "client-manager"       => "1",
                "contact-id"           => [$dbID[1]],
                "contact-name"         => ["Contact 1", "Contact 2"],
                "contact-relation"     => ["Contact", "Contact"],
                "contact-details"      => ["con@tact.com", "con@tact2.com"],
                "contact-details-type" => ["3", "3"],
                "contact-details-id"   => [$dbID[2], ''],
            ],
        ];

        $client = new ClientAjax(self::$_db);

        // Lets see what was input.
        $newClient = $this->_fetchClientDetailsFromDatabase();

        unset($newClient['id']);
        unset($newClient['contacts'][1]['id']);
        unset($newClient['contacts'][1]['contact_details'][0]['id']);

        $expected = [
            'client_name' => 'New Client 2 - Edit',
            'email'       => 'client@email.com',
            'address1'    => 'asd',
            'address2'    => 'asd',
            'address3'    => '',
            'town'        => 'asd',
            'postcode'    => 'SG12DX',
            'country'     => 231,
            'manager'     => 1,
            'contacts'    =>
                [
                    0 =>
                        [
                            'contact_name'     => 'Contact 1',
                            'contact_relation' => 'Contact',
                            'contact_details'  =>
                                [
                                    0 =>
                                        [
                                            'type'  => 3,
                                            'value' => 'con@tact.com',
                                            'id'    => $dbID[2]
                                        ],
                                ],
                            'id'               => $dbID[1]
                        ],
                    1 =>
                        [
                            'contact_name'     => 'Contact 2',
                            'contact_relation' => 'Contact',
                            'contact_details'  =>
                                [
                                    0 =>
                                        [
                                            'type'  => 3,
                                            'value' => 'con@tact2.com',
                                        ],
                                ],
                        ],
                ],

        ];

        self::assertEquals($expected, $newClient);
    }

    /**
     * @expectedException        \Exception
     * @expectedExceptionMessage Unable To Edit Client
     * @expectedExceptionCode    500
     */
    public function testEditClientFail()
    {
        // Invalid Post Input, no client ID.
        $_POST  = [
            'type' => "editClient",
            "form" => [
                "client-name"          => "New Client 2 - Edit",
                "client-email"         => "client@email.com",
                "client-address"       => "asd",
                "client-address2"      => "asd",
                "client-address3"      => "",
                "client-town"          => "asd",
                "client-postcode"      => "SG12DX",
                "client-country"       => "231",
                "client-manager"       => "1",
                "contact-name"         => ["Contact 1", "Contact 2"],
                "contact-relation"     => ["Contact", "Contact"],
                "contact-details"      => ["con@tact.com", "con@tact2.com"],
                "contact-details-type" => ["3", "3"],
            ],
        ];
        $client = new ClientAjax(self::$_db);
    }

    /**
     * @depends testNewClient
     */
    public function testRemoveContactDetails($dbID)
    {

        $_POST = [
            'type' => "deleteClientContactDetails",
            "id"   => $dbID[2],
        ];

        new ClientAjax(self::$_db);
        $contact = self::$_db->fetchQuery("SELECT 1 FROM client_contact_details WHERE id=:id", [':id' => $dbID[2]]);

        self::assertFalse($contact);
    }

    /**
     * @depends testNewClient
     */
    public function testRemoveContact($dbID)
    {

        $_POST = [
            'type' => "deleteClientContact",
            "id"   => $dbID[1],
        ];

        new ClientAjax(self::$_db);
        $contact = self::$_db->fetchQuery("SELECT 1 FROM client_contacts WHERE id=:id", [':id' => $dbID[1]]);

        self::assertFalse($contact);
    }

    public function testCheckClientEmail()
    {
        $_POST  = [
            'type'  => "checkClientEmail",
            "email" => 'client@email.com',
        ];
        $client = new ClientAjax(self::$_db);

        self::assertEquals(json_encode(["free" => FALSE]), $client->getResponse());

        $_POST  = [
            'type'  => "checkClientEmail",
            "email" => 'client@email2.com',
        ];
        $client = new ClientAjax(self::$_db);

        self::assertEquals(json_encode(["free" => TRUE]), $client->getResponse());

    }

    public function testFetchClientList()
    {
        // Clear the database down so there are no clients, this clears the mess from the other tests.
        self::$_db->executeQuery("TRUNCATE clients CASCADE;");
        self::$_oauthDB->executeQuery("DELETE FROM oauth_clients WHERE client_id <> 'sandbox'");

        //Set up a single client.
        $_POST = [
            'type' => "newClient",
            "form" => [
                "client-name"          => "New Client 2",
                "client-email"         => "client@email.com",
                "client-address"       => "asd",
                "client-address2"      => "asd",
                "client-address3"      => "",
                "client-town"          => "asd",
                "client-postcode"      => "SG12DX",
                "client-country"       => "230",
                "client-manager"       => "1",
                "contact-name"         => ["Contact 1"],
                "contact-relation"     => ["Contact"],
                "contact-details"      => ["con@tact.com"],
                "contact-details-type" => ["3"],
            ],
        ];
        new ClientAjax(self::$_db);
        $dates = self::$_db->fetchQuery(
            "SELECT 
                    TO_CHAR(cl.joined_date, 'Dy DD Mon YYYY, HH24:MI')                                                         AS creation_date,
                    TO_CHAR(cl.joined_date, 'YYYY-MM-DD HH24:MI:SS')                                                           AS creation_date_iso
                    FROM clients cl LIMIT 1"
        );

        // Fetch the client list.
        $_POST  = ['type' => "fetchClientList"];
        $client = new ClientAjax(self::$_db);
        $actual = json_decode($client->getResponse(), TRUE);

        // Clear the id as this changes on a per-test basis.
        unset($actual["clients"][0]['id']);

        $expected = json_decode('{"clients":[{"client_name":"New Client 2","client_email":"client@email.com","client_address1":"asd","client_address2":"asd","client_address3":null,"client_town":"asd","client_postcode":"SG1 2DX","client_country":"United Kingdom","creation_date":"' . $dates['creation_date'] . '","creation_date_iso":"' . $dates['creation_date_iso'] . '","account_manager":"Matt Nunn","license_state":null,"expiry_date":null,"client_address":["asd","asd","asd","SG1 2DX","United Kingdom"],"contacts_count":1,"contacts":[{"contact_name":"Contact 1","contact_relation":"Contact","contact_details":[{"type":3,"value":"con@tact.com"}]}]}]}', TRUE);
        self::assertEquals($expected, $actual);
    }

    private static function _fetchClientDetailsFromDatabase()
    {
        $newClient = self::$_db->fetchQuery(
            "SELECT
              cl.id,
              convert_from(decrypt(cl.client_name, '61A14481-9201-4EAA-AFED-70EB8A1A4763', 'aes'), 'SQL_ASCII')  AS client_name,
              convert_from(decrypt(cl.client_email, '61A14481-9201-4EAA-AFED-70EB8A1A4763', 'aes'), 'SQL_ASCII') AS email,
              convert_from(decrypt(cl.address1, '61A14481-9201-4EAA-AFED-70EB8A1A4763', 'aes'), 'SQL_ASCII')     AS address1,
              convert_from(decrypt(cl.address2, '61A14481-9201-4EAA-AFED-70EB8A1A4763', 'aes'), 'SQL_ASCII')     AS address2,
              convert_from(decrypt(cl.address3, '61A14481-9201-4EAA-AFED-70EB8A1A4763', 'aes'), 'SQL_ASCII')     AS address3,
              convert_from(decrypt(cl.town, '61A14481-9201-4EAA-AFED-70EB8A1A4763', 'aes'), 'SQL_ASCII')         AS town,
              UPPER(convert_from(decrypt(cl.postcode, '61A14481-9201-4EAA-AFED-70EB8A1A4763', 'aes'), 'SQL_ASCII'))     AS postcode,
              cn.id                                                                                              AS country,
              cl.managed_by                                                                                      AS manager
            FROM clients cl
              LEFT JOIN countries cn ON cl.country = cn.id
              WHERE convert_from(decrypt(cl.client_email, '61A14481-9201-4EAA-AFED-70EB8A1A4763', 'aes'), 'SQL_ASCII')=:cid;",
            [":cid" => 'client@email.com']
        );
        $contacts  = [];
        $query     = self::$_db->executeQuery(
            "SELECT
                      ct.id,
                      convert_from(decrypt(ct.contact_name, '61A14481-9201-4EAA-AFED-70EB8A1A4763', 'aes'), 'SQL_ASCII')     AS contact_name,
                      convert_from(decrypt(ct.contact_relation, '61A14481-9201-4EAA-AFED-70EB8A1A4763', 'aes'), 'SQL_ASCII') AS contact_relation,
                      json_agg(
                          json_build_object(
                              'id',ccd.id,
                              'type', ccd.contact_type,
                              'value', convert_from(decrypt(ccd.contact_details, '61A14481-9201-4EAA-AFED-70EB8A1A4763', 'aes'), 'SQL_ASCII')
                          )
                      )                                                                                                      AS contact_details
                    FROM client_contacts ct
                      LEFT JOIN client_contact_details ccd ON ct.id = ccd.contact_id
                    WHERE ct.client_id = :cid
                    GROUP BY ct.id
                    ORDER BY convert_from(decrypt(ct.contact_name, '61A14481-9201-4EAA-AFED-70EB8A1A4763', 'aes'), 'SQL_ASCII') ASC;",
            [":cid" => $newClient['id']]
        );
        while (($row = $query->fetch()) !== FALSE) {
            $row['contact_details'] = json_decode($row['contact_details'], TRUE);
            $contacts[]             = $row;
        }
        $newClient['contacts'] = $contacts;
        return $newClient;
    }

    private function _log($obj)
    {
        fwrite(STDERR, print_r($obj, TRUE));
    }

    public static function tearDownAfterClass()
    {
        self::$_db->executeQuery("TRUNCATE clients CASCADE;");
        self::$_oauthDB->executeQuery("DELETE FROM oauth_clients WHERE client_id <> 'sandbox'");
    }
}
