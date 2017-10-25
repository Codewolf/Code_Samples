<?php

namespace LicencingController;

use Licencing\ControllerBase;
use Licencing\core\DBPDO;
use Licencing\GlobalFunction;

/**
 * Class Client
 *
 * This class handles the Clients Page.
 *
 * @package LicencingController
 */
class Client extends ControllerBase
{

    private $_action;

    /**
     * @var \Licencing\core\DBPDO Database Resource.
     */
    private $_db;

    /**
     * License Page constructor.
     */
    public function __construct()
    {
        $this->_db                    = $GLOBALS['db'];
        $this->_action                = ($_GET[0] ?? 'New');
        $this->options['active_page'] = 'Client/' . $this->_action;
        $this->options['page_action'] = strtolower($this->_action);
        $this->_processAction();
    }

    /**
     * Process the page action.
     *
     * @return void
     */
    private function _processAction()
    {
        switch ($this->options['page_action']) {
            case "edit":
                try {
                    if (isset($_GET[1]) && is_numeric($_GET[1])) {
                        $this->_fetchClientDetails();
                    }
                } catch (\Exception $e) {
                    GlobalFunction::logError($e);
                    $this->options['error'] = $e->getMessage();
                }
            case "new":
                $this->_fetchCountries();
                $this->_fetchAccountManagers();
                break;

            case "list":
            default:
                $this->_fetchClientList();
                break;
        }
    }

    /**
     * Fetch The countries from the database and populate the countries twig variable.
     *
     * @return void
     */
    private function _fetchCountries()
    {
        $this->options['countries'] = $this->_db->fetchAllKeyQuery("SELECT * FROM countries");
    }

    /**
     * Fetch the available account managers.
     *
     * @return void
     */
    private function _fetchAccountManagers()
    {
        $this->options['employees'] = $this->_db->fetchAllQuery(
            "SELECT
                  id,
                  concat_ws(
                      ' ',
                      convert_from(decrypt(firstname, '" . KEY . "', 'aes'), 'SQL_ASCII'),
                      convert_from(decrypt(lastname, '" . KEY . "', 'aes'), 'SQL_ASCII')
                  ) AS employee_name
                FROM users
                WHERE 2 = ANY (groups);"
        );
    }

    /**
     * Fetch the list of clients and their managers.
     *
     * @return void
     */
    private function _fetchClientList()
    {
        $this->options['clients'] = $this->_db->fetchAllQuery(
            "SELECT
                      cl.id,
                      cl.client_id,
                      convert_from(decrypt(cl.client_name, '" . KEY . "', 'aes'), 'SQL_ASCII') AS client_name,
                      concat_ws(
                          ' ',
                          convert_from(decrypt(mb.firstname, '" . KEY . "', 'aes'), 'SQL_ASCII'),
                          convert_from(decrypt(mb.lastname, '" . KEY . "', 'aes'), 'SQL_ASCII')
                      )                                                                                                 AS managed_by
                    FROM clients cl
                      LEFT JOIN users mb ON cl.managed_by = mb.id"
        );
    }

    /**
     * Fetch the clients Details from the DB, this is used for editing the client.
     *
     * @return void
     * @throws \Exception On non-existing Client.
     */
    private function _fetchClientDetails()
    {
        $_oAuthDb = new DBPDO(
            "pgsql:host={$GLOBALS['ini']['oauth']['fqdn']};dbname={$GLOBALS['ini']['oauth']['dbname']}",
            $GLOBALS['ini']['oauth']['user'],
            $GLOBALS['ini']['oauth']['pass'],
            [
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES   => FALSE,
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            ]
        );
        $client   = $this->_db->fetchQuery(
            "SELECT
              cl.id,
              cl.client_id,
              convert_from(decrypt(cl.client_name, '" . KEY . "', 'aes'), 'SQL_ASCII')  AS client_name,
              convert_from(decrypt(cl.client_email, '" . KEY . "', 'aes'), 'SQL_ASCII') AS email,
              convert_from(decrypt(cl.address1, '" . KEY . "', 'aes'), 'SQL_ASCII')     AS address1,
              convert_from(decrypt(cl.address2, '" . KEY . "', 'aes'), 'SQL_ASCII')     AS address2,
              convert_from(decrypt(cl.address3, '" . KEY . "', 'aes'), 'SQL_ASCII')     AS address3,
              convert_from(decrypt(cl.town, '" . KEY . "', 'aes'), 'SQL_ASCII')         AS town,
              convert_from(decrypt(cl.postcode, '" . KEY . "', 'aes'), 'SQL_ASCII')     AS postcode,
              cn.id                                                                                              AS country,
              cl.managed_by                                                                                      AS manager
            FROM clients cl
              LEFT JOIN countries cn ON cl.country = cn.id
              WHERE cl.id=:cid;",
            [":cid" => intval($_GET[1])]
        );
        if ($client !== FALSE) {
            $oauth                   = $_oAuthDb->fetchQuery("SELECT client_id AS client_public,client_secret FROM oauth_clients WHERE user_id=:uid", [":uid" => $client['id']]);
            $client['client_public'] = $oauth['client_public'];
            $client['client_secret'] = $oauth['client_secret'];
            $client['postcode']      = GlobalFunction::formatPostcode($client['postcode']);
            $contacts                = [];
            $query                   = $this->_db->executeQuery(
                "SELECT
                      ct.id,
                      convert_from(decrypt(ct.contact_name, '" . KEY . "', 'aes'), 'SQL_ASCII')     AS contact_name,
                      convert_from(decrypt(ct.contact_relation, '" . KEY . "', 'aes'), 'SQL_ASCII') AS contact_relation,
                      json_agg(
                          json_build_object(
                              'id',ccd.id,
                              'type', ccd.contact_type,
                              'value', convert_from(decrypt(ccd.contact_details, '" . KEY . "', 'aes'), 'SQL_ASCII')
                          )
                      )                                                                                                      AS contact_details
                    FROM client_contacts ct
                      LEFT JOIN client_contact_details ccd ON ct.id = ccd.contact_id
                    WHERE ct.client_id = :cid
                    GROUP BY ct.id
                    ORDER BY convert_from(decrypt(ct.contact_name, '" . KEY . "', 'aes'), 'SQL_ASCII') ASC;",
                [":cid" => intval($_GET[1])]
            );
            while (($row = $query->fetch()) !== FALSE) {
                $row['contact_details'] = json_decode($row['contact_details'], TRUE);
                $contacts[]             = $row;
            }
            $client['contacts'] = $contacts;

            $this->options['client'] = $client;
        } else {
            throw new \Exception("Client with id:{$_GET[1]} does not exist", 400);
        }
    }
}