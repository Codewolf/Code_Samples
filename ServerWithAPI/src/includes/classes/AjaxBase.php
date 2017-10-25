<?php

namespace Licencing;

use Licencing\core\DBPDO;

/**
 * Class AjaxBase
 *
 * @package Licencing
 */
abstract class AjaxBase
{

    /**
     * @var \Licencing\core\DBPDO Database Resource
     */
    protected $db;

    /**
     * @var array Response Array to be set to json
     */
    protected $response = ["success" => TRUE];

    /**
     * AjaxBase constructor.
     * This Function sets the protected database resource from the global DB variable.
     *
     * @codeCoverageIgnore Ignoring coverage due to setting DB.
     *
     * @param DBPDO $db Database Resource.
     */
    public function __construct(DBPDO $db = NULL)
    {
        if ($db === NULL) {
            $this->db = $GLOBALS['db'];
        } else {
            $this->db = $db;
        }
    }

    /**
     * Return the JSON encoded response.
     *
     * @return string
     */
    public function getResponse(): string
    {
        // Get the response.
        $response = $this->response;
        // Reset the response State to default.
        $this->response = ["success" => TRUE];
        return json_encode($response, JSON_NUMERIC_CHECK);
    }
}