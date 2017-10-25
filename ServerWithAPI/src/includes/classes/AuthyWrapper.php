<?php

namespace Licencing;

use Authy\AuthyApi;

/**
 * Class AuthyWrapper
 *
 * @codeCoverageIgnore Ignoring coverage due to using external library.
 *
 * @package            Licencing
 */
class AuthyWrapper extends AuthyApi
{

    /**
     * AuthyWrapper constructor.
     *
     * This Method Wraps the AuthyApi Class to disable SSL verification, this fixes the CURL error regarding certificates.
     *
     * @param string $api_key Authy API Key.
     * @param string $api_url Authy URI.
     */
    public function __construct($api_key, $api_url = "https://api.authy.com")
    {
        parent::__construct($api_key, $api_url);
        $this->rest->setDefaultOption('verify', FALSE);
    }
}