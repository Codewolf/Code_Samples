<?php

namespace Licencing\core;

/**
 * Class Audit
 * This class will save an entry in the Audit Table.
 *
 * @codeCoverageIgnore Ignoring coverage due to using DB.
 *
 * @package            Licencing\core
 */
abstract class Audit
{

    /**
     * Log the Audit to the database
     *
     * @param string  $description Description of What is to be logged.
     * @param integer $category    Audit Category.
     * @param boolean $outcome     Was this a success or a failure log.
     * @param integer $userId      <i>(optional)</i> This is the users ID in the database.<p> A user id of <b>0</b> inserts NULL.
     *
     * @return void
     */
    public static function log(string $description, int $category, bool $outcome, int $userId = NULL)
    {
        if (!defined("PHPUNIT_RUNNING")) {
            if ($userId === 0) {
                $userId = NULL;
            } else {
                $userId = ($userId === NULL) ? (($_SESSION['user']['id']) ?? NULL) : $userId;
            }
            $GLOBALS['db']->beginTransaction();
            $GLOBALS['db']->executeQuery(
                "INSERT INTO audit (user_id, audit_category, description,outcome) VALUES (:uid,:category,:description,:outcome)",
                [
                    ":uid"         => $userId,
                    ":category"    => $category,
                    ":description" => $description,
                    ":outcome"     => (($outcome) ? 1 : 0),
                ]
            );
            $GLOBALS['db']->commit();
        }
    }
}