<?php
/**
 * - Copyright (c) Matt Nunn - All Rights Reserved
 * - Unauthorized copying of this file via any medium is strictly prohibited
 * - Written by Matt Nunn <MH.Nunn@gmail.com> 2016.
 */

use Licencing\core\DBPDO;
use Licencing\core\User;
use Licencing\GlobalFunction;
use Licencing\Menu;

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set("Europe/London");

$ini = parse_ini_file("config.ini", TRUE);
define("DOC_ROOT", $ini['config']['document_root']);
define("BASE_PATH", $ini['config']['base_path']);
define("MAX_ATTEMPTS", $ini['config']['max_login_attempts']);
define("KEY", $ini['database']['key']);
define("DEBUG", $ini['debug']['status']);
define("PURIFIER_ALLOWED_TAGS", 'p,b,br,i,u,sup,sub,span[style],a[href],ul,li');

require_once DOC_ROOT . '/vendor/autoload.php';

switch (strtolower($ini['mail']['transport'])) {
    case "smtp":
        $transport = (new Swift_SmtpTransport($ini['smtp']['host'], 25))
            ->setUsername($ini['smtp']['user'])
            ->setPassword($ini['smtp']['passphrase']);
        break;

    case "local":
    case "sendmail":
    default:
        if (preg_match("/win(d|n|3)/i", PHP_OS)) {
            $transport = (new Swift_SmtpTransport($ini['smtp']['host'], 25))
                ->setUsername($ini['smtp']['user'])
                ->setPassword($ini['smtp']['passphrase']);
        } else {
            $transport = new Swift_SendmailTransport();
        }
        break;
}
$mail = new Swift_Mailer($transport);

$db = new DBPDO(
    "pgsql:host={$ini['database']['fqdn']};dbname={$ini['database']['dbname']}",
    $ini['database']['user'],
    $ini['database']['pass'],
    [
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        \PDO::ATTR_EMULATE_PREPARES   => FALSE,
        \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
    ]
);

set_error_handler(["Licencing\GlobalFunction", 'catchErrors']);

$loader = new Twig_Loader_Filesystem($ini['config']['document_root'] . '/templates');
$loader->addPath(DOC_ROOT . '/templates/emails', "emails");
/**
 * @var $twig \Twig_Environment Twig Environment.
 */
$twig = NULL;
if ($ini['debug']['status'] == 1) {
    $twig = new Twig_Environment($loader, ["debug" => TRUE]);

    $twig->addExtension($t = new Twig_Extension_Debug());
} else {
    $twig = new Twig_Environment(
        $loader,
        [
            'cache' => $ini['config']['document_root'] . '/templates/cache/compilation_cache'
        ]
    );
}
$versionFiles = new Twig_SimpleFunction(
    "versionize",
    function ($file) {
        $regex      = "/(.+\/)([\w\d-]+).([\w\d-.]+)$/";
        $fileOSpath = str_replace("/", DIRECTORY_SEPARATOR, ltrim($file, "/"));
        $mTime      = @filemtime(DOC_ROOT . DIRECTORY_SEPARATOR . $fileOSpath);
        if (!$mTime) {
            $mTime = mktime(0, 0, 0, date("m"), 1, date("y"));
        }
        return preg_replace($regex, "\$1\$2.{$mTime}.\$3", $file);
    }
);

$menu                  = new Menu();
$menuDetails           = $menu->createMenu();
$options['menuLinks']  = $menuDetails['menu'];
$options['menuBadges'] = $menuDetails['badges'];
$currentUserGlobal     = new User((($_SESSION['user']) ?? []));
$twig->addGlobal('currentUser', $currentUserGlobal);
$twig->addFunction($versionFiles);