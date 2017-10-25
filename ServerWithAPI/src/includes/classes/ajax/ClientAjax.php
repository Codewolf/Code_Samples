<?php

namespace Licencing\ajax;

use Swift_Attachment;
use Licencing\AjaxBase;
use Licencing\core\api\Exceptions\InvalidLicenseException;
use Licencing\core\DBPDO;
use Licencing\core\Exceptions\InvalidAjaxEndpointException;
use Licencing\core\UUID;
use Licencing\GlobalFunction;

/**
 * Class ClientAjax
 *
 * @package includes\classes\ajax
 */
class ClientAjax extends AjaxBase
{

    /**
     * ClientAjax constructor.
     *
     * @param DBPDO $db (optional) Database Resource.
     *
     * @throws InvalidAjaxEndpointException On unknown endpoint.
     */
    public function __construct(DBPDO $db = NULL)
    {
        parent::__construct($db);
        if (method_exists($this, $_POST['type'])) {
            $this->{$_POST['type']}();
        } else {
            throw new InvalidAjaxEndpointException("Endpoint: {$_POST['type']} does not exist", 404);
        }
    }

    /**
     * Save a new Client to the Database.
     *
     * @return void
     * @throws \Exception On database Error.
     */
    public function newClient()
    {
        try {
            $this->db->beginTransaction();
            $form     = $_POST['form'];
            $clientId = $this->db->fetchQuery(
                "INSERT INTO clients (
                     client_id,
                     client_name, 
                     client_email, 
                     address1, 
                     address2, 
                     address3, 
                     town, 
                     country, 
                     postcode, 
                     created_by, 
                     managed_by,
                     joined_date
                     ) VALUES (
                      :clientId,
                      encrypt(:clientName,'" . KEY . "','aes'),
                      encrypt(:clientEmail,'" . KEY . "','aes'),
                      encrypt(:address1,'" . KEY . "','aes'),
                      encrypt(:address2,'" . KEY . "','aes'),
                      encrypt(:address3,'" . KEY . "','aes'),
                      encrypt(:town,'" . KEY . "','aes'),
                      :country,
                      encrypt(:postcode,'" . KEY . "','aes'),
                      :createdBy,
                      :managedBy,
                      now()
                     ) RETURNING id",
                [
                    ":clientId"    => UUID::generate(UUID::VERSION_4),
                    ":clientName"  => $form['client-name'],
                    ":clientEmail" => $form['client-email'],
                    ":address1"    => $form['client-address'],
                    ":address2"    => $form['client-address2'],
                    ":address3"    => $form['client-address3'],
                    ":town"        => $form['client-town'],
                    ":country"     => $form['client-country'],
                    ":postcode"    => strtoupper(preg_replace("/[^A-Z0-9]+/", '', $form['client-postcode'])),
                    ":createdBy"   => intval(($_SESSION['user']['id'] ?? 1)),
                    ":managedBy"   => intval($form['client-manager'])
                ]
            )['id'];
            foreach ($form['contact-name'] as $key => $contact) {
                $contactId = $this->db->fetchQuery(
                    "INSERT INTO client_contacts (client_id, contact_name, contact_relation) VALUES (:cid,encrypt(:cname,'" . KEY . "','aes'),encrypt(:crelation,'" . KEY . "','aes')) RETURNING id",
                    [
                        ":cid"       => $clientId,
                        ":cname"     => $contact,
                        ":crelation" => $form['contact-relation'][$key],
                    ]
                )['id'];
                if (!is_array($form['contact-details'][$key])) {
                    $form['contact-details'][$key]      = [$form['contact-details'][$key]];
                    $form['contact-details-type'][$key] = [$form['contact-details-type'][$key]];
                }
                foreach ($form['contact-details'][$key] as $cKey => $details) {
                    $this->db->executeQuery(
                        "INSERT INTO client_contact_details (contact_id, contact_type, contact_details) VALUES (:cid,:ctype,encrypt(:cdetails,'" . KEY . "','aes'))",
                        [
                            ":cid"      => $contactId,
                            ":ctype"    => intval($form['contact-details-type'][$key][$cKey]),
                            ":cdetails" => $details,
                        ]
                    );
                }
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            GlobalFunction::logError($e);
            throw new \Exception("Unable To Create Client", 500);
        }

        try {
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
            $_oAuthDb->beginTransaction();
            $_oAuthDb->executeQuery(
                "INSERT INTO oauth_clients (client_id, client_secret, user_id,redirect_uri) VALUES (:clientPublic,:clientSecret,:clientId,'')",
                [
                    ":clientPublic" => preg_replace("/[\W]+/i", '', $form['client-name']),
                    ":clientSecret" => UUID::generate(UUID::VERSION_4),
                    ":clientId"     => $clientId
                ]
            );
            $_oAuthDb->commit();
        } catch (\Exception $e) {
            $_oAuthDb->rollBack();
            GlobalFunction::logError($e);
            throw new \Exception("Unable To Create Client OAuth Login Details", 500);
        }

    }

    /**
     * Save Client Changes/Edits to the Database.
     *
     * @return void
     * @throws \Exception On database Error.
     */
    public function editClient()
    {
        $this->db->beginTransaction();
        try {
            $form = $_POST['form'];
            $this->db->executeQuery(
                "UPDATE clients
                        SET
                          client_name = encrypt(:clientName, '" . KEY . "', 'aes'),
                          address1    = encrypt(:address1, '" . KEY . "', 'aes'),
                          address2    = encrypt(:address2, '" . KEY . "', 'aes'),
                          address3    = encrypt(:address3, '" . KEY . "', 'aes'),
                          town        = encrypt(:town, '" . KEY . "', 'aes'),
                          country     = :country,
                          postcode    = encrypt(:postcode, '" . KEY . "', 'aes'),
                          managed_by  = :managedBy
                        WHERE id = :id",
                [
                    ":clientName" => $form['client-name'],
                    ":address1"   => $form['client-address'],
                    ":address2"   => $form['client-address2'],
                    ":address3"   => $form['client-address3'],
                    ":town"       => $form['client-town'],
                    ":country"    => $form['client-country'],
                    ":postcode"   => strtoupper(preg_replace("/[^A-Z0-9]+/", '', $form['client-postcode'])),
                    ":managedBy"  => intval($form['client-manager']),
                    ":id"         => intval($form['client-id']),
                ]
            );
            foreach ($form['contact-name'] as $key => $contact) {
                if (!isset($form['contact-id'][$key])) {
                    $form['contact-id'][$key] = $this->db->fetchQuery(
                        "INSERT INTO client_contacts (client_id, contact_name, contact_relation) VALUES (:cid,encrypt(:cname,'" . KEY . "','aes'),encrypt(:crelation,'" . KEY . "','aes')) RETURNING id",
                        [
                            ":cid"       => intval($form['client-id']),
                            ":cname"     => $contact,
                            ":crelation" => $form['contact-relation'][$key],
                        ]
                    )['id'];
                } else {
                    $this->db->executeQuery(
                        "UPDATE client_contacts SET contact_name=encrypt(:cname,'" . KEY . "','aes'),contact_relation=encrypt(:crelation,'" . KEY . "','aes') WHERE id=:id",
                        [
                            ":id"        => $form['contact-id'][$key],
                            ":cname"     => $contact,
                            ":crelation" => $form['contact-relation'][$key],
                        ]
                    );
                }

                if (!is_array($form['contact-details'][$key])) {
                    $form['contact-details'][$key]      = [$form['contact-details'][$key]];
                    $form['contact-details-type'][$key] = [$form['contact-details-type'][$key]];
                    $form['contact-details-id'][$key]   = [$form['contact-details-id'][$key]];
                }
                foreach ($form['contact-details'][$key] as $cKey => $details) {
                    if ($form['contact-details-id'][$key][$cKey] === NULL || $form['contact-details-id'][$key][$cKey] === '') {
                        $this->db->executeQuery(
                            "INSERT INTO client_contact_details (contact_id, contact_type, contact_details) VALUES (:cid,:ctype,encrypt(:cdetails,'" . KEY . "','aes'))",
                            [
                                ":cid"      => $form['contact-id'][$key],
                                ":ctype"    => intval($form['contact-details-type'][$key][$cKey]),
                                ":cdetails" => $details,
                            ]
                        );
                    } else {
                        $this->db->executeQuery(
                            "UPDATE client_contact_details SET contact_type=:ctype,contact_details=encrypt(:cdetails,'" . KEY . "','aes') WHERE id=:id",
                            [
                                ":id"       => intval($form['contact-details-id'][$key][$cKey]),
                                ":ctype"    => intval($form['contact-details-type'][$key][$cKey]),
                                ":cdetails" => $details,
                            ]
                        );
                    }
                }
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            GlobalFunction::logError($e);
            throw new \Exception("Unable To Edit Client", 500);
        }
    }

    /**
     * Delete a client contact and associated contact details from the database.
     *
     * @return void
     */
    public function deleteClientContact()
    {
        $this->db->executeQuery("DELETE FROM client_contacts WHERE id=:id", [":id" => intval($_POST["id"])]);
    }

    /**
     * Delete client contact details from the database.
     *
     * @return void
     */
    public function deleteClientContactDetails()
    {
        $this->db->executeQuery("DELETE FROM client_contact_details WHERE id=:id", [":id" => intval($_POST["id"])]);
    }

    /**
     * Check the client emails to see if this email is already in use for another client.
     *
     * @return void
     */
    public function checkClientEmail()
    {
        $email          = $this->db->fetchQuery("SELECT id FROM clients WHERE convert_from(decrypt(client_email, '" . KEY . "', 'aes'), 'SQL_ASCII') = :email;", [":email" => $_POST['email']]);
        $this->response = ["free" => ($email === FALSE)];
    }

    /**
     * Process the client address into a human-readable format.
     *
     * @param array $row Row from the database.
     *
     * @return void
     */
    private function _processAddress(array &$row)
    {
        $row['client_postcode'] = GlobalFunction::formatPostcode($row['client_postcode']);
        $row["client_address"]  = array_values(
            array_filter(
                [
                    $row['client_address1'],
                    $row['client_address2'],
                    $row['client_address3'],
                    $row['client_town'],
                    $row['client_postcode'],
                    $row['client_country'],
                ]
            )
        );
    }

    /**
     * Fetch a list of clients, this is used to populate the client list table.
     *
     * @return void
     */
    public function fetchClientList()
    {
        $clients = [];
        $this->db->beginTransaction();
        $query = $this->db->executeQuery(
            "SELECT
                      cl.id,
                      convert_from(decrypt(cl.client_name, '" . KEY . "', 'aes'), 'SQL_ASCII')          AS client_name,
                      convert_from(decrypt(cl.client_email, '" . KEY . "', 'aes'), 'SQL_ASCII')         AS client_email,
                      convert_from(decrypt(cl.address1, '" . KEY . "', 'aes'), 'SQL_ASCII')             AS client_address1,
                      nullif(convert_from(decrypt(cl.address2, '" . KEY . "', 'aes'), 'SQL_ASCII'), '') AS client_address2,
                      nullif(convert_from(decrypt(cl.address3, '" . KEY . "', 'aes'), 'SQL_ASCII'), '') AS client_address3,
                      convert_from(decrypt(cl.town, '" . KEY . "', 'aes'), 'SQL_ASCII')                 AS client_town,
                      convert_from(decrypt(cl.postcode, '" . KEY . "', 'aes'), 'SQL_ASCII')             AS client_postcode,
                      cn.country_name                                                                                            AS client_country,
                      TO_CHAR(cl.joined_date, 'Dy DD Mon YYYY, HH24:MI')                                                         AS creation_date,
                      TO_CHAR(cl.joined_date, 'YYYY-MM-DD HH24:MI:SS')                                                           AS creation_date_iso,
                      concat_ws(
                          ' ',
                          convert_from(decrypt(u.firstname, '" . KEY . "', 'aes'), 'SQL_ASCII'),
                          convert_from(decrypt(u.lastname, '" . KEY . "', 'aes'), 'SQL_ASCII')
                      )                                                                                                          AS account_manager,
                      CASE
                      WHEN li.expiry_date IS NULL
                        THEN NULL
                      WHEN li.expiry_date < now()
                        THEN 0
                      WHEN li.expiry_date < now() + INTERVAL '2 weeks'
                        THEN 1
                      ELSE
                        2
                      END                                                                                                        AS license_state,
                      TO_CHAR(li.expiry_date, 'Dy DD Mon YYYY')                                                                  AS expiry_date
                    FROM clients cl
                      LEFT JOIN countries cn ON cl.country = cn.id
                      LEFT JOIN users u ON cl.created_by = u.id
                      LEFT JOIN license li ON (
                        cl.client_id = li.client_id AND li.id =
                                                        (
                                                          SELECT max(id)
                                                          FROM license l
                                                          WHERE cl.client_id = l.client_id
                                                        )
                        );"
        );
        while (($row = $query->fetch()) !== FALSE) {
            $contacts = [];
            $cQuery   = $this->db->executeQuery(
                "SELECT
                      convert_from(decrypt(ct.contact_name, '" . KEY . "', 'aes'), 'SQL_ASCII')     AS contact_name,
                      convert_from(decrypt(ct.contact_relation, '" . KEY . "', 'aes'), 'SQL_ASCII') AS contact_relation,
                      json_agg(
                          json_build_object(
                              'type', ccd.contact_type,
                              'value', convert_from(decrypt(ccd.contact_details, '" . KEY . "', 'aes'), 'SQL_ASCII')
                          )
                      )                                                                                                      AS contact_details
                    FROM client_contacts ct
                      LEFT JOIN client_contact_details ccd ON ct.id = ccd.contact_id
                    WHERE ct.client_id = :cid
                    GROUP BY ct.id;",
                [':cid' => $row['id']]
            );
            while (($cRow = $cQuery->fetch()) !== FALSE) {
                $cRow['contact_details'] = json_decode($cRow['contact_details'], TRUE);
                $contacts[]              = $cRow;
            }
            $this->_processAddress($row);
            $row['contacts_count'] = count($contacts);
            $row['contacts']       = $contacts;
            $clients[]             = $row;
        }
        $this->db->commit();
        $this->response = ['clients' => $clients];
    }

}