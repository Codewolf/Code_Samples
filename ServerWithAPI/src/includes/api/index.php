<?php

use Licencing\core\api\APIServer;
use Licencing\core\DBPDO;

require_once "../../vendor/autoload.php";
if (!array_key_exists('HTTP_ORIGIN', $_SERVER)) {
    $_SERVER['HTTP_ORIGIN'] = $_SERVER['SERVER_NAME'];
}
try {
    $ini     = parse_ini_file("../config.ini", TRUE);
    $db      = new DBPDO(
        "pgsql:host={$ini['database']['fqdn']};dbname={$ini['database']['dbname']}",
        $ini['database']['user'],
        $ini['database']['pass'],
        [
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => FALSE,
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
        ]
    );
    $oauthDB = new DBPDO(
        "pgsql:host={$ini['oauth']['fqdn']};dbname={$ini['oauth']['dbname']}",
        $ini['oauth']['user'],
        $ini['oauth']['pass'],
        [
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => FALSE,
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
        ]
    );
    if (!isset($_REQUEST['request'])) {
        $_REQUEST['request'] = '';
    }
    $API = new APIServer($_REQUEST['request'], $db, $oauthDB);
    echo $API->processAPI();
} catch (Exception $e) {
    $error = [
        "error" => $e->getMessage()
    ];
    if (method_exists($e, 'getAdditionalData')) {
        if ($e->getAdditionalData() != NULL) {
            $error['details'] = $e->getAdditionalData();
        }
    }
    if ($e->getCode() > 0) {
        http_response_code($e->getCode());
    }
    echo json_encode($error);
}