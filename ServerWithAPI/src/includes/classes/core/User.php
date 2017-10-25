<?php
/**
 * - Copyright (c) Matt Nunn - All Rights Reserved
 * - Unauthorized copying of this file via any medium is strictly prohibited
 * - Written by Matt Nunn <MH.Nunn@gmail.com> 2016.
 */

namespace Licencing\core;

/**
 * Class User
 *
 * @package Licencing\core
 */
class User
{

    private $_user;

    /**
     * @var \Licencing\core\DBPDO Database Resource.
     */
    private $_db;

    /**
     * Employee constructor.
     *
     * @param array $user User Id.
     */
    public function __construct(array $user)
    {
        $this->_user = $user;
        $this->_db   = $GLOBALS['db'];
    }

    /**
     * Return the Users ID.
     *
     * @return integer Id.
     */
    public function getId()
    {
        return intval($this->_user['id']);
    }

    /**
     * @return string Users Email.
     */
    public function getEmail()
    {
        return $this->_user['email'];
    }

    /**
     * @param string $type Name Type: full <i>(default)</i> / first / firstname.
     *
     * @return mixed
     */
    public function getName($type = "full")
    {
        switch (strtolower($type)) {
            case "first":
            case "firstname":
            case "short":
                return $this->_user['shortname'];

            case "full":
            default:
                return $this->_user['name'];
        }
    }

    /**
     * Does the current user have te required roles?
     *
     * @param integer|integer[] $role Role id, or array of role ids that are required.
     *
     * @return boolean
     */
    public function hasRole($role)
    {
        if (is_array($role)) {
            foreach ($role as $roleId) {
                if (in_array($roleId, $this->_user['roles'])) {
                    return TRUE;
                }
            }
            return FALSE;
        } else {
            return array_search($role, $this->_user['roles']) !== FALSE;
        }
    }
}