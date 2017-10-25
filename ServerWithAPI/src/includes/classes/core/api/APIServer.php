<?php

namespace Licencing\core\api;

use Licencing\core\api\Exceptions\InvalidLicenseException;
use Licencing\core\api\Exceptions\InvalidOriginException;

/**
 * Class APIServer
 *
 * This class contains all the Endpoints for the Restful Server and any required private functions for them to work.
 *
 * @package Licencing
 */
class APIServer extends RestServer
{

    /**
     * Validate The Clients Credentials From the database.
     *
     * @param boolean|array $client Client details from database or false if no client details.
     *
     * @return void
     * @throws InvalidLicenseException On any invalid Information.
     */
    private function _validateClientCredentials($client)
    {
        // Database has returned no client.
        if (!$client) {
            $this->log("error", "Invalid Client Identification. origin: {$_SERVER['HTTP_ORIGIN']}");
            throw new InvalidLicenseException("Invalid Client Identification.", 403);
        }

        // Licence has expired.
        if ($client['expired']) {
            $this->log("licence", "Expired Licence used for client: {$_POST['cid']}");
            throw new InvalidLicenseException("This License has expired.", 402);
        }

        // Licence has yet to be activated, or the activation date has not been reached.
        if (!$client['activated']) {
            $this->log("licence", "Unactivated Licence used for client: {$_POST['cid']}");
            throw new InvalidLicenseException("This License has yet to be activated.", 401);
        }
    }

    /**
     * Validate the request has come from a valid IP or domain.
     *
     * @param array $client Client details from database.
     *
     * @return void
     * @throws InvalidOriginException On non-matched origin.
     */
    private function _validateAccess(array $client)
    {
        $access = json_decode($client['valid_access'], TRUE);
        if (!in_array(trim($_SERVER['HTTP_ORIGIN']), $access)) {
            $this->log("error", "Access to License Server for client {$client['client_id']} Denied from origin: {$_SERVER['HTTP_ORIGIN']}");
            throw new InvalidOriginException("Access to License Server from: {$_SERVER['HTTP_ORIGIN']} is not allowed", 403);
        }
    }

    /**
     * Authenticate the license sent through to the API.
     *
     * @return array Licence details to be put into the clients database.
     *
     * @throws InvalidLicenseException On invalid Licence Key.
     */
    public function AuthenticateLicense()
    {
        // Go to the database and fetch the information.
        try {
            $client = $this->db->fetchQuery(
                "SELECT
              client_id,
              license_key                      AS private_key,
              CASE WHEN now() > activation_date 
                THEN TRUE
              ELSE FALSE END                   AS activated,
             CASE WHEN now() > expiry_date
                THEN TRUE
              ELSE FALSE END                   AS expired,
              array_to_json(modules_installed) AS modules,
              array_to_json(
                  ARRAY(
                      SELECT unnest(ip_addr)
                      UNION
                      SELECT unnest(domains)
                  )
              )                                AS valid_access,
              EXTRACT(EPOCH FROM activation_date) AS activation_date,
              EXTRACT(EPOCH FROM expiry_date) AS expiry_date              
          FROM license WHERE client_id=:cid ORDER BY id DESC LIMIT 1",
                [":cid" => $_POST['cid']]
            );
            // @codeCoverageIgnoreStart
        } catch (\Exception $e) {
            throw new InvalidLicenseException("License Not found.", 401);
        }

        // @codeCoverageIgnoreEnd
        // Validate the access credentials.
        $this->_validateClientCredentials($client);
        $this->_validateAccess($client);

        // Decrypt Sent key.
        openssl_private_decrypt($_POST['key'], $decrypted, $client['private_key']);
        if ($decrypted !== $client['client_id']) {
            $this->log("error", "Invalid License Key for client {$client['client_id']} origin: {$_SERVER['HTTP_ORIGIN']}");
            throw new InvalidLicenseException("Invalid Licence Key Provided.", 401);
        }

        // Return the valid information.
        return [
            "mi" => json_decode($client['modules'], TRUE),
            "ad" => $client['activation_date'],
            "ed" => $client['expiry_date'],
        ];
    }

    /**
     * Destroy the Class on exit.
     */
    public function __destruct()
    {
        $this->db = NULL;
        $_POST    = NULL;
    }
}