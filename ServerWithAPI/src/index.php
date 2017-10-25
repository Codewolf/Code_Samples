<?php

// Check if installer exist.
use Licencing\core\Controller;
use Licencing\GlobalFunction;

try {
    include_once "includes/core.php";
    if (!!($ini['debug']['maintenance']) && !$currentUserGlobal->hasRole(1)) {
        http_response_code(503);
        header('HTTP/1.1 503 Service Temporarily Unavailable');
        header('Status: 503 Service Temporarily Unavailable');
        header('Retry-After: 3600');
        include_once "errors/maintenance.html";
    } else {
        $control = new Controller($twig, $ini);
        $control->render();
    }
} catch (\Exception $e) {
    GlobalFunction::logError($e);
    // If its an SQL error and hasn't been caught and thrown before now set it to 500.
    $code = ($e->getCode() < 200 || $e->getCode() > 600) ? 500 : $e->getCode();

    http_response_code($code);
    include_once "errors/{$code}.html";
}