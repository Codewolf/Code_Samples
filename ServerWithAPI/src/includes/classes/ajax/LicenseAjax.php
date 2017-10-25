<?php

namespace Licencing\ajax;

use Swift_Attachment;
use Licencing\AjaxBase;
use Licencing\core\api\Exceptions\InvalidLicenseException;
use Licencing\core\DBPDO;
use Licencing\core\Exceptions\InvalidAjaxEndpointException;
use Licencing\GlobalFunction;

/**
 * Class LoginAjax
 *
 * @package includes\classes\ajax
 */
class LicenseAjax extends AjaxBase
{

    /**
     * LoginAjax constructor.
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
     * Fetch Any Existing Licenses and if they exist return the basic data.
     *
     * @return void
     */
    public function fetchExistingLicenses()
    {
        $clientLicense = $this->db->fetchQuery(
            "SELECT
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
                    WHERE client_id = :clientId
                    GROUP BY l.id
                    ORDER BY creation_date DESC
                    LIMIT 1;",
            [':clientId' => $_POST['clientId']]
        );
        if ($clientLicense) {
            $clientLicense['modules_installed'] = json_decode($clientLicense['modules_installed'], TRUE);
            $clientLicense['modules_names']     = json_decode($clientLicense['modules_names'], TRUE);
            $clientLicense['ip_addr']           = json_decode($clientLicense['ip_addr'], TRUE);
            $clientLicense['domains']           = json_decode($clientLicense['domains'], TRUE);
        }
        $this->response = ["license" => $clientLicense];
    }

    /**
     * Process the Domain and IP patterns (one per line) into a postgres array.
     *
     * @param string $details The IP addresses or domains, one per line (split by \n).
     *
     * @return string
     */
    private function _processDomainIps(string $details)
    {
        $details = preg_split("/\n/", $details);
        array_map("trim", $details);
        return GlobalFunction::pgImplode($details);
    }

    /**
     * Replace the Public key headers and footers with our own headers and footers.
     *
     * @param string $key RSA Public Key.
     *
     * @return string Custom Public Key.
     */
    private function _keyHeaderFooter(string $key): string
    {
        return str_replace('-----BEGIN PUBLIC KEY-----', '-----BEGIN LICENCE KEY-----', str_replace('-----END PUBLIC KEY-----', '-----END LICENCE KEY-----', $key));
    }

    /**
     * Generate The Private and Public RSA Keys and save it to the database, returning the public key to the client.
     *
     * @return void
     */
    public function generateLicenseKeys()
    {
        $rsaConfig = [
            "digest_alg"       => "sha512",
            "private_key_bits" => 4096,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ];

        // Generate the RSA Key.
        $res = openssl_pkey_new($rsaConfig);
        openssl_pkey_export($res, $privateKey);
        $publicKey = openssl_pkey_get_details($res)['key'];

        // Now Save it to the database.
        $this->db->beginTransaction();
        $modules = array_merge([1, 12], $_POST['modules']);
        sort($modules, SORT_NUMERIC);
        $this->db->executeQuery(
            "INSERT INTO license (client_id, ip_addr, domains, activation_date,expiry_date, modules_installed, license_key) 
                      VALUES (:clientId,:ip,:domains,:activation,:expiry,:modules,:key)",
            [
                ":clientId"   => $_POST['clientId'],
                ":ip"         => $this->_processDomainIps($_POST['ips']),
                ":domains"    => $this->_processDomainIps($_POST['domains']),
                ":activation" => $_POST['activation'],
                ":expiry"     => $_POST['expiry'],
                ":modules"    => GlobalFunction::pgImplode($modules),
                ":key"        => $privateKey,
            ]
        );

        $this->db->commit();

        $this->response = ["license" => $this->_keyHeaderFooter($publicKey)];
    }

    /**
     * Process the License Private Key into a License key.
     *
     * @param string $privateKey RSA Private Key.
     *
     * @return string License Key
     */
    private function _fetchLicensePublicKey(string $privateKey): string
    {
        $privateKey = openssl_pkey_get_private($privateKey);
        return $this->_keyHeaderFooter(openssl_pkey_get_details($privateKey)['key']);
    }

    /**
     * Email the custom Public key to the client in a custom extension file (dlf).
     *
     * This license file (dlf) is a text based file containing the public key.
     *
     * @codeCoverageIgnore Ignoring coverage due to emailing..
     *
     * @return void
     */
    public function emailLicenseToClient()
    {
        $details   = $this->db->fetchQuery(
            "SELECT
                      convert_from(decrypt(cl.client_email, '" . KEY . "', 'aes'), 'SQL_ASCII') AS client_email,
                      convert_from(decrypt(cl.client_name, '" . KEY . "', 'aes'), 'SQL_ASCII') AS client_name,
                      li.license_key
                    FROM clients cl
                      LEFT JOIN license li ON (
                        cl.client_id = li.client_id AND li.id =
                                                        (
                                                          SELECT max(id)
                                                          FROM license l
                                                          WHERE cl.client_id = l.client_id
                                                        )
                        )
                    WHERE cl.id = :cid;",
            [":cid" => intval($_POST['clientId'])]
        );
        $publicKey = $this->_fetchLicensePublicKey($details['license_key']);
        GlobalFunction::mailSend(
            "license",
            [],
            [$details['client_email'] => $details['client_name']],
            NULL,
            [],
            function (\Swift_Message $message) use ($publicKey) {
                $message->attach(new Swift_Attachment($publicKey, 'license.dlf', 'text/plain'));
            }
        );
    }

    /**
     * Fetch the license History for the client
     *
     * @return void
     */
    public function fetchLicenseHistory()
    {
        $history = [];
        $query   = $this->db->executeQuery(
            "SELECT
                      l.id,
                      TO_CHAR(l.creation_date, 'Dy DD Mon YYYY, HH24:MI')   AS creation_date,
                      TO_CHAR(l.creation_date, 'YYYY-MM-DD HH24:MI:SS')     AS creation_date_iso,
                      TO_CHAR(l.activation_date, 'Dy DD Mon YYYY, HH24:MI') AS activation_date,
                      TO_CHAR(l.activation_date, 'YYYY-MM-DD HH24:MI:SS')   AS activation_date_iso,
                      TO_CHAR(l.expiry_date, 'Dy DD Mon YYYY, HH24:MI')     AS expiry_date,
                      TO_CHAR(l.expiry_date, 'YYYY-MM-DD HH24:MI:SS')       AS expiry_date_iso,
                      array_to_json(l.ip_addr)                              AS ip_addr,
                      array_to_json(l.domains)                              AS domains,
                      array_to_json(array_agg(mi.description
                                    ORDER BY mi.id ASC))                    AS modules_names,
                      now() BETWEEN activation_date AND expiry_date         AS active
                    FROM license l
                      LEFT JOIN modules_available mi ON mi.id = ANY (l.modules_installed) AND mi.enabled_by_default=0
                    WHERE client_id = :clientId
                    GROUP BY l.id
                    ORDER BY l.creation_date DESC",
            [':clientId' => $_POST['clientId']]
        );
        while (($row = $query->fetch()) !== FALSE) {
            $row['modules_names'] = json_decode($row['modules_names'], TRUE);
            $row['ip_addr']       = json_decode($row['ip_addr'], TRUE);
            $row['domains']       = json_decode($row['domains'], TRUE);
            $history[]            = $row;
        }
        if (!empty($history)) {
            $history[0]['isLatest'] = TRUE;
        }
        $this->response = ["licenses" => $history];
    }

    /**
     * Fetch the License Ley (public) for the client.
     *
     * @return void
     * @throws InvalidLicenseException On invalid or no license key.
     */
    public function fetchLicenseKey()
    {
        try {
            $license = $this->db->fetchQuery("SELECT license_key FROM license WHERE id=:keyId", [":keyId" => intval($_POST['kid'])]);
            if (!$license) {
                throw new InvalidLicenseException("No License Key Available.", 401);
            }
            $this->response = ["license" => $this->_fetchLicensePublicKey($license['license_key'])];
        } catch (InvalidLicenseException $e) {
            throw new InvalidLicenseException($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            throw new InvalidLicenseException("Unable to Fetch License Key.", 500);
        }
    }

}