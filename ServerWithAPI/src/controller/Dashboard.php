<?php

namespace LicencingController;

use Licencing\ControllerBase;
use Licencing\GlobalFunction;

/**
 * Class Login
 *
 * This class handles the login Page.
 *
 * @package LicencingController
 */
class Dashboard extends ControllerBase
{

    /**
     * Login constructor.
     */
    public function __construct()
    {
        $this->options['active_page'] = 'dashboard';
    }
}