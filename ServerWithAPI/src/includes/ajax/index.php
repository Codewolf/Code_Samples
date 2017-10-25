<?php

namespace LicencingAjax;

use Licencing\core\Exceptions\InvalidAjaxEndpointException;
use Licencing\core\Exceptions\InvalidLoginException;
use Licencing\core\Exceptions\UnauthorisedException;
use Licencing\GlobalFunction;

require_once "../core.php";
header("Content-Type: application/json");
try {
    $endpoint = $_POST['endpoint'];
    if (!preg_match("/(login|logout)/i", $endpoint)) {
        if (!GlobalFunction::isLoggedIn()) {
            throw new UnauthorisedException($endpoint);
        }
    }
    if (!file_exists("../classes/ajax/{$endpoint}Ajax.php")) {
        throw new InvalidAjaxEndpointException("No Ajax Endpoint: {$endpoint}", 404);
    } else {
        $class = 'Licencing\\ajax\\' . $endpoint . 'Ajax';

        /**
         * @var $c \Licencing\AjaxBase Ajax Base.
         */
        $c = new $class;
        echo $c->getResponse();
    }
} catch (InvalidLoginException $e) {
    // We Know what this is, no need to log it.
    http_response_code($e->getCode());
    echo json_encode(["error" => $e->getMessage()]);
} catch (InvalidAjaxEndpointException $e) {
    // We Know what this is, no need to log it.
    http_response_code($e->getCode());
    echo json_encode(["error" => $e->getMessage()]);
} catch (\Exception $e) {
    $GLOBALS['db']->rollBack();
    GlobalFunction::logError($e);
    http_response_code(($e->getCode() > 200 && $e->getCode() < 600) ? $e->getCode() : 500);
    echo json_encode(["error" => $e->getMessage()]);
}