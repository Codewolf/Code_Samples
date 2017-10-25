<?php

namespace Licencing\core\api\Exceptions;

/**
 * Class UnsecuredConnectionException
 */
class UnsecuredConnectionException extends \Exception
{

    /**
     * Additional Data.
     *
     * @var array Additional Data.
     */
    private $_additionalData;

    /**
     * @param string          $message  Error Message.
     * @param integer         $code     Error Code (optional).
     * @param \Exception|NULL $previous Previous Exceptions (optional).
     */
    public function __construct($message = "", $code = 0, \Exception $previous = NULL)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get Additional Data.
     *
     * @return array
     */
    public function getAdditionalData()
    {
        return $this->_additionalData;
    }

    /**
     * Set Additional Data.
     *
     * @param array $additionalData Additional Data.
     *
     * @return void
     */
    public function setAdditionalData(array $additionalData)
    {
        $this->_additionalData = $additionalData;
    }

}