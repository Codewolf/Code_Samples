<?php

namespace Licencing\core\api;

use OAuth2\Controller\ResourceControllerInterface;
use OAuth2\RequestInterface;
use OAuth2\ResponseInterface;
use OAuth2\Server;

/**
 * Class ServerOverridden
 *
 * @package Licencing
 */
class ServerOverridden extends Server implements ResourceControllerInterface
{

    /** Handle the Token Request.
     *
     * @param \OAuth2\RequestInterface       $request  The Request.
     * @param \OAuth2\ResponseInterface|NULL $response The Response.
     *
     * @return \OAuth2\ResponseInterface|ResponseOverridden
     */
    public function handleTokenRequest(RequestInterface $request, ResponseInterface $response = NULL)
    {
        $this->response = ($response === NULL) ? new ResponseOverridden() : $response;
        $this->getTokenController()->handleTokenRequest($request, $this->response);

        return $this->response;
    }
}