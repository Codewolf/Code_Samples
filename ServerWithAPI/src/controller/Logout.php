<?php

namespace LicencingController;

use Licencing\ControllerBase;
use Licencing\GlobalFunction;

/**
 * Class Logout
 *
 * @package LicencingController
 */
class Logout extends ControllerBase
{

    /**
     * Login constructor.
     */
    public function __construct()
    {
        session_unset();
        session_destroy();
        header("Location: login");
        $this->options['no-sidebar'] = TRUE;
    }
}