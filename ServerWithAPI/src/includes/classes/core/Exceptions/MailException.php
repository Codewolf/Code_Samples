<?php

namespace Licencing\core\Exceptions;

/**
 * Class MailException
 *
 */
class MailException extends \Exception
{

    /**
     * Create the Mail Exception.
     *
     * @param string     $message  Error Message.
     * @param integer    $code     Error Code.
     * @param \Exception $previous Previously Thrown Exception.
     */
    public function __construct($message, $code = 0, \Exception $previous = NULL)
    {
        parent::__construct($message, $code, $previous);
    }

}