<?php

namespace Licencing\core\api;

use OAuth2\GrantType\AuthorizationCode;
use OAuth2\GrantType\ClientCredentials;
use OAuth2\GrantType\RefreshToken;
use OAuth2\GrantType\UserCredentials;
use OAuth2\Request;
use OAuth2\Storage\Pdo;
use Licencing\core\api\Exceptions\UnsecuredConnectionException;
use Licencing\core\DBPDO;

/**
 *
 * This Class is the base class for the API which handles setting up the OAuth Server, the Authorisation endpoint, and setting the global class-level variables
 * as well as logging the connection.
 */
class RestServer extends API
{

    /**
     * Database
     *
     * @var DBPDO DB resource.
     */
    protected $db;

    /**
     * OAuth Database
     *
     * @var DBPDO
     */
    private $_oauthDB;

    /** OAuth
     *
     * @var ServerOverridden Server resource.
     */
    protected $server;

    /**
     * Construct API Server.
     *
     * @param string $apiRequest API Request.
     *
     * @param DBPDO  $db         Database Connection Resource for data.
     * @param DBPDO  $oauthDb    Database Connection Resource for OAuth.
     *
     * @throws UnsecuredConnectionException If not using HTTPS or not authorised by API Key.
     */
    public function __construct($apiRequest, DBPDO $db, DBPDO $oauthDb)
    {
        $this->db       = $db;
        $this->_oauthDB = $oauthDb;
        parent::__construct($apiRequest);

        $this->_setupOAuthServer();
        $response = new ResponseOverridden();
        if (!$this->server->verifyResourceRequest(Request::createFromGlobals(), $response) && $this->endpoint != "Authorisation") {
            $exception = new UnsecuredConnectionException("Connection Not Secured: no valid OAuth Token", 401);
            $exception->setAdditionalData($response->getParameters());
            throw $exception;
        }
    }

    /**
     * Setup The OAuth Server and variables.
     *
     * @return void
     */
    private function _setupOAuthServer()
    {
        $storage      = new Pdo($this->_oauthDB);
        $this->server = new ServerOverridden($storage);
        $this->server->addGrantType(new ClientCredentials($storage));
        $this->server->addGrantType(new AuthorizationCode($storage));
        $this->server->addGrantType(new UserCredentials($storage));
        $this->server->addGrantType(new RefreshToken($storage, ['always_issue_new_refresh_token' => TRUE]));
    }

    /**
     * Connection Logger
     *
     * @codeCoverageIgnore Ignoring coverage as this is a logging function.
     *
     * @param string $type    Type of log.
     * @param string $message Message to log.
     *
     * @return void
     */
    protected function log($type = "access", $message = '')
    {
        if (!isset($_POST['ignoreLog'])) {
            $logTime = date("Y-m-d H:i:s", time());
            $dtMsg   = "\n{$logTime} - ";
            switch ($type) {
                case "licence":
                    $message = "{$dtMsg} [LICENSING]: " . $message;
                    $file    = "licence.log";
                    break;

                case "error":
                    $message = "{$dtMsg} [ERROR]: " . $message;
                    $file    = "error.log";
                    break;

                case "access":
                default:
                    $message = "{$dtMsg} [INFO]: Connection from {$_SERVER['REMOTE_ADDR']} Requesting Endpoint: {$_SERVER['REQUEST_URI']}";
                    $file    = "access.log";
                    break;
            }
            file_put_contents("../../logs" . DIRECTORY_SEPARATOR . $file, $message, FILE_APPEND);
        }
    }

    /**
     * Authorisation endpoint.
     *
     * @return array|boolean
     * @throws UnsecuredConnectionException If unable to auth.
     */
    protected function Authorisation()
    {
        switch ($this->verb) {
            case "getToken":
                return $this->server->handleTokenRequest(Request::createFromGlobals())->send();

            default:
                $error = [
                    "error"             => "API Error",
                    "error_description" => "Endpoint {$this->verb} not recognised, valid enpoints are: [getToken]"
                ];
                return $error;
        }
    }

}