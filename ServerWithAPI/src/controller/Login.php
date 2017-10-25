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
class Login extends ControllerBase
{

    /**
     * Login constructor.
     */
    public function __construct()
    {
        $this->_checkLoginStatus();
        $this->options['nosidebar'] = TRUE;
    }

    /**
     * Check To see if the user is already logged in, if they are forward them onto the dashboard.
     *
     * @return void
     */
    private function _checkLoginStatus()
    {
        if (GlobalFunction::isLoggedIn()) {
            header("Location: dashboard");
        }
    }
}