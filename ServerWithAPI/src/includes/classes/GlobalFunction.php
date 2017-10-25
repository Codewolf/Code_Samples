<?php
/**
 * - Copyright (c) Matt Nunn - All Rights Reserved
 * - Unauthorized copying of this file via any medium is strictly prohibited
 * - Written by Matt Nunn <MH.Nunn@gmail.com> 2016.
 */

namespace Licencing;

use Licencing\core\Exceptions\MailException;
use Licencing\core\TwigMailer;
use Licencing\core\UUID;

/**
 * Global Function Abstract Class
 *
 * This class contains useful static functions that can be used anywherein the system
 * hook name, and creates Compliance Tasks from them.
 *
 * @package Licencing
 */
abstract class GlobalFunction
{

    /** PDO is an arse and does'nt like being caught, so catch it!.
     *
     * @param integer $errno   Error Number.
     * @param string  $errstr  Error string.
     * @param string  $errfile Error file.
     * @param integer $errline Error line.
     *
     * @codeCoverageIgnore Ignoring coverage due to internal PHP function override.
     * @return boolean Stop PHP internal error handler.
     * @throws \Exception If a non-catchable exception is thrown.
     */
    public static function catchErrors($errno, $errstr, $errfile, $errline)
    {
        if (!(error_reporting() & $errno)) {
            // This error code is not included in error_reporting.
            return FALSE;
        }

        switch ($errno) {
            case E_USER_ERROR:
            case E_RECOVERABLE_ERROR:
                throw new \Exception("$errstr [{$errfile}] [{$errline}]", $errno);

            default:
                // Don't do anything!
                break;
        }

        // Don't execute PHP internal error handler.
        return TRUE;
    }

    /**
     * Return Error message and http code for server-side $_FILE processing errors.
     *
     * @param integer|string $errorCode PHP error code.
     *
     * @return array
     */
    public static function fileErrors($errorCode)
    {
        switch ($errorCode) {
            case "1":
            case "2":
                $message = "The File is too large, please upload a smaller file.";
                $code    = 413;
                break;

            case "3":
            case "4":
                $message = "The File failed to upload successfully, please try again.";
                $code    = 400;
                break;

            case "6":
            case "7":
                $message = "Unable to write file.";
                $code    = 507;
                break;

            default:
                $message = "An unknown error occured.";
                $code    = 500;
                break;
        }

        return [
            "message" => $message,
            "code"    => $code,
        ];
    }

    /**
     * Search Multi-Dimensional array recursively for needle.
     *
     * @param string  $needle   Text to search for.
     * @param array   $haystack Array to search.
     * @param boolean $strict   Strict comparison (optional).
     *
     * @return boolean TRUE if string found, FALSE otherwise.
     */
    public static function inArrayR($needle, array $haystack, $strict = FALSE)
    {
        foreach ($haystack as $item) {
            if ((($strict) ? $item === $needle : $item == $needle) || (is_array($item) && self::inArrayR($needle, $item, $strict))) {
                return TRUE;
            }
        }

        return FALSE;
    }

    /**
     *  Format Postcode into human readable format.
     *
     * @param string $postcode Postcode.
     *
     * @return string Formatted postcode.
     */
    public static function formatPostcode($postcode)
    {
        $postcode = str_replace(" ", '', $postcode);

        return substr($postcode, 0, -3) . " " . substr($postcode, -3);
    }

    /**
     * Random Strong Password Generator.
     *
     * Random Strong password generator created by https://gist.github.com/tylerhall/
     *
     * @param integer $length         Length of password.
     * @param string  $available_sets Available character sets to be used.
     *
     * @return string Strong password.
     */
    public static function generatePassword($length, $available_sets = 'luds')
    {
        $sets = [];
        if (strpos($available_sets, 'l') !== FALSE) {
            $sets[] = 'abcdefghjkmnpqrstuvwxyz';
        }
        if (strpos($available_sets, 'u') !== FALSE) {
            $sets[] = 'ABCDEFGHJKMNPQRSTUVWXYZ';
        }
        if (strpos($available_sets, 'd') !== FALSE) {
            $sets[] = '23456789';
        }
        if (strpos($available_sets, 's') !== FALSE) {
            $sets[] = '!@#$%&*?';
        }

        $all      = '';
        $password = '';
        foreach ($sets as $set) {
            $password .= $set[array_rand(str_split($set))];

            $all .= $set;
        }

        $all   = str_split($all);
        $count = count($sets);
        for ($i = 0; $i < ($length - $count); $i++) {
            $password .= $all[array_rand($all)];
        }

        $password = str_shuffle($password);
        return $password;
    }

    /**
     * Checks to see if the user is logged in.
     *
     * @codeCoverageIgnore Ignoring coverage due to database Usage.
     *
     * @return boolean True if user is logged in.
     */
    public static function isLoggedIn()
    {
        if (isset($_SESSION['user']) && isset($_SESSION['user']['id'])) {
            $session = $GLOBALS["db"]->fetchQuery(
                "SELECT session_id FROM users WHERE id=:uid",
                [":uid" => $_SESSION['user']['id']]
            );
            if ($_SESSION['user']['ssid'] == $session['session_id']) {
                $_SESSION['LAST_ACTIVITY'] = time();
                $GLOBALS["db"]->executeQuery("UPDATE users SET last_login=NOW() WHERE id=:uid", [":uid" => $_SESSION['user']['id']], TRUE);
                return TRUE;
            } else {
                return FALSE;
            }
        };

        return FALSE;
    }

    /**
     * Log Errors to custom error log.
     *
     * @codeCoverageIgnore Ignoring coverage due to File Writing.
     *
     * @param \Exception $e The Thrown Exception.
     *
     * @return string UUID reference String.
     */
    public static function logError(\Exception $e)
    {
        if (defined("DOC_ROOT")) {
            try {
                $uuid              = UUID::generate(UUID::VERSION_4);
                $dateTime          = date("Y-m-d H:i:s", time());
                $errorMessageToLog = "\n{$dateTime} - [ERROR][{$uuid}]: An unexpected error occured: \n" . $e->getMessage();
                foreach ($e->getTrace() as $trace) {
                    if (isset($trace['file'])) {
                        $errorMessageToLog .= "\n           File: " . $trace['file'] . " Line: " . $trace['line'];
                    }
                }
                file_put_contents(DOC_ROOT . DIRECTORY_SEPARATOR . "logs" . DIRECTORY_SEPARATOR . "error.log", $errorMessageToLog, FILE_APPEND);

                return $uuid;
            } catch (\Exception $e) {
                error_log("[ERROR]: Unable to write Error to log file: " . $e->getMessage());
            }
        }
        return '';
    }

    /**
     * Log Messages to custom error log.
     *
     * @codeCoverageIgnore Ignoring coverage due to File Writing.
     *
     * @param string $message The Message.
     *
     * @param string $file    File to log to.
     *
     * @return string UUID reference String.
     */
    public static function logMessage($message, $file = "debug.log")
    {
        try {
            $uuid              = UUID::generate(UUID::VERSION_4);
            $dateTime          = date("Y-m-d H:i:s", time());
            $errorMessageToLog = "\n{$dateTime} - [DEBUG][{$uuid}]:\n" . $message;
            file_put_contents(DOC_ROOT . DIRECTORY_SEPARATOR . "logs" . DIRECTORY_SEPARATOR . $file, $errorMessageToLog, FILE_APPEND);

            return $uuid;
        } catch (\Exception $e) {
            error_log("[ERROR]: Unable to write to log file: " . $message);
        }
        return '';
    }

    /**
     * Send an email with a Twig Template
     *
     * @codeCoverageIgnore Ignoring coverage due to Mail Sending, TwigMailer already Tested.
     *
     * @param string        $template       Template Name.
     * @param array         $mailOpts       Mail Options to pass through to twig templates.
     * @param string|array  $toAddress      Who the email is being sent to.
     * @param string|null   $replyTo        Who should the replyTo recipient be?.
     * @param array         $attachmentList Attached images/other attachments sorted by type Array("images"=>array("image1.png","image2.png")).
     * @param null|callable $callback       Callback function to add extra parameters to the email.
     * @param string|array  $from           Who the email is being sent from.
     *
     * @throws MailException If there is an exception.
     * @return void
     */
    public static function mailSend($template, array $mailOpts, $toAddress, $replyTo = NULL, array $attachmentList = [], $callback = NULL, $from = NULL)
    {
        if (!is_array($toAddress)) {
            $toAddress = [$toAddress];
        }
        if ($replyTo != NULL) {
            $replyTo = [$replyTo];
        }
        $generator = new TwigMailer($GLOBALS['twig']);
        $message   = $generator->getMessage($template, $mailOpts);
        $sendto    = (DEBUG) ? $GLOBALS['ini']['debug']['email'] : $toAddress;
        $failures  = NULL;
        $message->setTo($sendto);
        self::_setupMailFrom($from, $message);
        self::_addAttachmentsToMessage($attachmentList, $message);
        if ($callback) {
            $callback($message, $mailOpts);
        }
        if (is_array($replyTo)) {
            $message->setReplyTo($replyTo);
        }
        if (!$GLOBALS["mail"]->send($message, $failures)) {
            self::_mailSendFailure($failures);
        }
    }

    /**
     * Setup the mail from.
     *
     * @codeCoverageIgnore Ignoring coverage due to Mail Sending, TwigMailer already Tested.
     *
     * @param string|array   $from    Who the email is being sent from.
     * @param \Swift_Message $message Message Object.
     *
     * @return void
     */
    private static function _setupMailFrom(&$from, \Swift_Message &$message)
    {
        if ($from != NULL) {
            if (!is_array($from)) {
                $from = [$from];
            }
            $message->setFrom($from);
        } else {
            $message->setFrom([$GLOBALS['ini']['mail']['sendfrom'] => $GLOBALS['ini']['mail']['sendname']]);
        }
    }

    /**
     * Add any and all attachments to Message.
     *
     * @codeCoverageIgnore Ignoring coverage due to Mail Sending, TwigMailer already Tested.
     *
     * @param array          $attachmentList Attachment List array.
     * @param \Swift_Message $message        Message Object.
     *
     * @return void
     */
    private static function _addAttachmentsToMessage(array $attachmentList, \Swift_Message &$message)
    {
        foreach ($attachmentList as $type => $attachments) {
            foreach ($attachments as $attachment) {
                $message->attach(\Swift_Attachment::fromPath(DOC_ROOT . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR . $attachment));
            }
        }
    }

    /**
     * Log Failed email addresses straight to a log file
     *
     * @codeCoverageIgnore Ignoring coverage due to Mail Sending, TwigMailer already Tested.
     *
     * @param array $failed Array of failed email addresses.
     *
     * @return void
     */
    private static function _mailSendFailure(array $failed)
    {
        try {
            $errorMessageToLog = "\n[ERROR]: Unable to Send an email to the following email addresses:\n";
            foreach ($failed as $email) {
                $errorMessageToLog .= "\n           $email";
            }
            file_put_contents(DOC_ROOT . DIRECTORY_SEPARATOR . "logs" . DIRECTORY_SEPARATOR . "email.log", $errorMessageToLog, FILE_APPEND);
        } catch (\Exception $e) {
            error_log("[ERROR]: Unable to write to email log file: " . $e->getMessage());
        }
    }

    /**
     *  Typecast from a standard PHP Object to a custom class.
     *
     * @codeCoverageIgnore Ignoring coverage due to typecasting.
     *
     * @param \stdClass $instance  The PHP stdClass.
     * @param string    $className The Class to map to.
     *
     * @return mixed
     */
    public static function objectToObject(\stdClass $instance, $className)
    {
        return unserialize(
            sprintf(
                'O:%d:"%s"%s',
                strlen($className),
                $className,
                strstr(strstr(serialize($instance), '"'), ':')
            )
        );
    }

    /** Check if URL is up and available.
     *
     * @codeCoverageIgnore Ignoring coverage due to External HTTP call.
     *
     * @param string $url URL.
     *
     * @return boolean
     */
    public static function urlUp($url)
    {
        $fileHeaders = @get_headers($url);

        return ($fileHeaders[0] == 'HTTP/1.1 200 OK');
    }

    /** Sort Array by subkey
     *
     * @param  array          $array     Array to sort.
     * @param  string|integer $key       Key to sort by.
     * @param  string         $direction ASC/DESC.
     * @param  boolean        $keepKeys  Keep original (associative) keys.
     *
     * @return void
     */
    public static function uSortSubkey(array &$array, $key, $direction = "ASC", $keepKeys = FALSE)
    {
        $func = ($keepKeys) ? "uasort" : "usort";
        $func(
        // @codeCoverageIgnoreStart
            $array,
            // @codeCoverageIgnoreEnd
            function ($a, $b) use ($direction, $key) {
                if (strtolower($direction) != "asc") {
                    return ($b[$key] - $a[$key]);
                } else {
                    return ($a[$key] - $b[$key]);
                }
            }
        );
    }

    /**
     * Implode but with a final character.
     *
     * @param array  $array           Array of strings.
     * @param string $glue            Glue String.
     * @param string $final_character Final glue character.
     *
     * @return string
     */
    public static function englishImplode(array $array, $glue = ',', $final_character = ' & ')
    {
        $count = count($array);
        $loop  = 0;
        $str   = "";
        foreach ($array as $item) {
            $str .= $item;
            if ($loop == ($count - 2)) {
                $str .= $final_character;
            } else if (($loop < ($count - 1))) {
                $str .= $glue;
            }
            $loop++;
        }

        return $str;
    }

    /**
     * @param array $array Array to implode.
     *
     * @return string
     */
    public static function pgImplode(array $array)
    {
        if (array_keys($array) !== range(0, (count($array) - 1))) {
            // Associated array.
            return json_encode($array, JSON_FORCE_OBJECT);
        } else {
            // Sequential Array.
            foreach ($array as &$item) {
                if (is_numeric($item)) {
                    $item = intval($item);
                }
            }
            return '{' . implode(',', $array) . '}';
        }
    }
}