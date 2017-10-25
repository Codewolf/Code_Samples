<?php

namespace Licencing\core;

/**
 * Abstract Class CustomException
 *
 * @package Licencing\core
 */
abstract class CustomException extends \Exception
{

    /**
     * Additional Data.
     *
     * @var array Additional Data.
     */
    protected $additionalData;

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
     * Return string representation of class.
     *
     * @return string
     */
    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }

    /**
     * Get Additional Data.
     *
     * @return array
     */
    public function getAdditionalData()
    {
        return $this->additionalData;
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
        $this->additionalData = $additionalData;
    }

}