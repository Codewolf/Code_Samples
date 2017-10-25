<?php

namespace Licencing\core\api;

use Licencing\core\api\Exceptions\UnexpectedHeaderException;
use Licencing\core\api\Exceptions\UnsecuredConnectionException;

/**
 * API
 *
 */
abstract class API
{

    /**
     * Property: method
     * The HTTP method this request was made in, either GET, POST, PUT or DELETE
     *
     * @var string
     */
    protected $method = '';

    /**
     * Property: endpoint
     * The Model requested in the URI.
     *
     * @var mixed|string
     */
    protected $endpoint = '';

    /**
     * Property: verb
     * An optional additional descriptor about the endpoint, used for things that can
     * not be handled by the basic methods.
     *
     * @var mixed|string
     */
    protected $verb = '';

    /**
     * Property: args
     * Any additional URI components after the endpoint and verb have been removed
     * eg: /<endpoint>/<verb>/<arg0>/<arg1>
     * or /<endpoint>/<arg0>.
     *
     * @var array
     */
    protected $args = [];

    /**
     * Property: file
     * Stores the input of the PUT request
     *
     * @var null|string
     */
    protected $file = NULL;

    /**
     * Create the API, with the requirement of the connection being made over HTTPS.
     *
     * @param string $request The URI requested. eg. /<endpoint>/<verb>/.
     *
     * @throws UnsecuredConnectionException If the request is done over POST or not over HTTPS.
     */
    public function __construct($request)
    {
        if (!isset($_SERVER['HTTPS'])) {
            throw new UnsecuredConnectionException("Connection MUST be made over HTTPS", 505);
        }
        header("Access-Control-Allow-Orgin: *");
        header("Access-Control-Allow-Methods: POST, DELETE, PUT");
        header("Content-Type: application/json");
        $this->args     = explode('/', rtrim($request, '/'));
        $this->endpoint = array_shift($this->args);
        if (array_key_exists(0, $this->args) && !is_numeric($this->args[0])) {
            $this->verb = array_shift($this->args);
        }
        $this->_processMethod();
    }

    /**
     * Process the request method.
     *
     * @codeCoverageIgnore Ignoring coverage as this is just processing Requests.
     * @return void
     * @throws UnexpectedHeaderException If an unexpected Header type is used.
     */
    private function _processMethod()
    {
        $this->method = $_SERVER['REQUEST_METHOD'];
        if ($this->method == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER)) {
            if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE') {
                $this->method = 'DELETE';
            } else if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT') {
                $this->method = 'PUT';
            } else {
                throw new UnexpectedHeaderException("Unexpected Header", 417);
            }
        }
        switch ($this->method) {
            case 'DELETE':
            case 'POST':
                $this->request = $this->_cleanInputs($_POST);
                break;

            case 'GET':
                $this->request = $this->_cleanInputs($_GET);
                break;

            case 'PUT':
                $this->request = $this->_cleanInputs($_GET);
                $this->file    = file_get_contents("php://input");
                break;

            default:
                throw new UnexpectedHeaderException("Invalid Method", 405);
        }
    }

    /**
     * Recursive cleaning of inputs from the request.
     *
     * @param string $data The inputs.
     *
     * @return array|string The cleaned input.
     */
    private function _cleanInputs($data)
    {
        $clean_input = [];
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $clean_input[$k] = $this->_cleanInputs($v);
            }
        } else {
            $clean_input = trim(strip_tags($data));
        }

        return $clean_input;
    }

    /**
     * Show a response header with the selected values.
     *
     * @param string  $data   Error message.
     * @param integer $status HTTP response status.
     *
     * @return string
     */
    private function _response($data, $status = 200)
    {
        header("HTTP/1.1 " . $status . " " . $this->_requestStatus($status));

        return json_encode($data);
    }

    /**
     * Get the HTTP status.
     *
     * @param integer $code HTTP error code.
     *
     * @return string HTTP response string.
     */
    private function _requestStatus($code)
    {
        $status = [
            200 => 'OK',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            500 => 'Internal Server Error',
        ];

        return ($status[$code]) ? $status[$code] : $status[500];
    }

    /**
     * Process the API request.
     *
     * @return string JSON encoded string to return.
     * @throws UnsecuredConnectionException If the request is GET.
     */
    public function processAPI()
    {
        if ($this->method !== "GET") {
            if (method_exists($this, $this->endpoint)) {
                return $this->_response($this->{$this->endpoint}($this->args));
            }

            return $this->_response("No Endpoint: $this->endpoint", 404);
        } else {
            throw new UnsecuredConnectionException("use of GET is not allowed", 405);
        }
    }

}