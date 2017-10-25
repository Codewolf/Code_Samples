<?php

namespace Licencing\core\Exceptions;

use Licencing\core\CustomException;

/**
 * Class UnauthorisedException
 *
 * @package Licencing\core
 */
class UnauthorisedException extends CustomException
{

    /**
     * UnauthorisedException constructor.
     *
     * @param string          $file     File being accessed.
     * @param integer         $code     Error Code (optional).
     * @param \Exception|NULL $previous Previous Exceptions (optional).
     */
    public function __construct($file, $code = 401, \Exception $previous = NULL)
    {
        $file    = basename($file);
        $message = "Unauthorised Access Attempt To File {$file} from IP {$_SERVER['REMOTE_ADDR']}";
        parent::__construct($message, $code, $previous);
    }

}