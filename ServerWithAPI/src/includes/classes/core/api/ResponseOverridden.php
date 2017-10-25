<?php

namespace Licencing\core\api;

use OAuth2\Response;
use OAuth2\ResponseInterface;

/**
 * Class ResponseOverride
 *
 * This class overrides \OAuth2\Response to allow for inclusion in an API rather than being a standalone item.
 *
 * @codeCoverageIgnore Ignoring coverage due to Response Being Tested as part of the third party suite.
 *
 * @package            Licencing
 */
class ResponseOverridden extends Response implements ResponseInterface
{

    /**
     * Create the Response to be sent back to the API. This method overrides the native implementation.
     *
     * @param string $format Format to return.
     *
     * @return mixed
     */
    public function send($format = 'json')
    {
        // Headers have already been sent by the developer.
        if (headers_sent()) {
            return NULL;
        }

        switch ($format) {
            case 'json':
            default:
                $this->setHttpHeader('Content-Type', 'application/json');
                break;

            case 'xml':
                $this->setHttpHeader('Content-Type', 'text/xml');
                break;
        }
        // Status.
        header(sprintf('HTTP/%s %s %s', $this->version, $this->statusCode, $this->statusText));

        foreach ($this->getHttpHeaders() as $name => $header) {
            if ($name === "Location" && strpos($header, "code=") != -1) {
                parse_str(parse_url($header, PHP_URL_QUERY), $header_array);
                $this->parameters += $header_array;
            } else {
                header(sprintf('%s: %s', $name, $header));
            }
        }

        return $this->getResponseBody($format);
    }

    /**
     * Create the Response Body to be sent back to the send method. This method overrides the native implementation.
     *
     * @param string $format Format to return.
     *
     * @return array|mixed
     * @throws \InvalidArgumentException If format not supported.
     */
    public function getResponseBody($format = 'json')
    {
        switch ($format) {
            case 'json':
                return $this->parameters;

            case 'xml':
                // This only works for single-level arrays.
                $xml = new \SimpleXMLElement('<response/>');
                foreach ($this->parameters as $key => $param) {
                    $xml->addChild($key, $param);
                }
                return $xml->asXML();

            default:
                throw new \InvalidArgumentException(sprintf('The format %s is not supported', $format));
        }
    }
}