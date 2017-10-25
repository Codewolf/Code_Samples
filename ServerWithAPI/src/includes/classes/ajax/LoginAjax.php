<?php

namespace Licencing\ajax;

use Authy\AuthyFormatException;
use Licencing\AjaxBase;
use Licencing\core\Audit;
use Licencing\core\Exceptions\InvalidLoginException;
use Licencing\AuthyWrapper;
use Licencing\core\UUID;
use Licencing\core\DBPDO;
use Licencing\GlobalFunction;

/**
 * Class LoginAjax
 *
 * @package includes\classes\ajax
 */
class LoginAjax extends AjaxBase
{

    /**
     * LoginAjax constructor.
     *
     * @param DBPDO $db (optional) Database Resource.
     */
    public function __construct(DBPDO $db = NULL)
    {
        parent::__construct($db);
        $this->_checkLogin();
    }

    /**
     * Check against the Authy 2FA
     *
     * @codeCoverageIgnore Ignoring coverage due to using external library.
     *
     * @param integer $uid     User Id.
     *
     * @param string  $authyId Authy ID from database.
     *
     * @return void
     * @throws InvalidLoginException On Invalid Key.
     * @throws AuthyFormatException On Invalid Key Format.
     */
    private function _checkAuthy(int $uid, string $authyId)
    {
        if (!preg_match("/[0-9]{9}/", $_POST['key'])) {
            throw new AuthyFormatException("Token format is invalid", 401);
        }
        if (!(DEBUG && $_POST['key'] === "123456789")) {
            $api          = new AuthyWrapper('Oqw7oQNE4rusxlQDxiN6qliIjFNSy6ER', 'https://api.authy.com', FALSE);
            $key          = preg_replace("/[\s]+/", '', $_POST['key']);
            $verification = $api->verifyToken($authyId, $key);

            if (!$verification->ok()) {
                Audit::log("Login Failure. 2FA Incorrect", 1, FALSE, $uid);
                throw new InvalidLoginException($verification->errors()->message, 401);
            }
        }
    }

    /**
     * Check the users Login Credentials.
     *
     * @return void
     * @throws \Exception On Database Error.
     * @throws InvalidLoginException On Invalid Login Error.
     */
    private function _checkLogin()
    {
        try {
            $this->db->beginTransaction();
            $user = $this->db->fetchQuery(
                "SELECT
                id,
                convert_from(decrypt(firstname, '" . KEY . "','aes'),'SQL_ASCII') AS firstname,
                convert_from(decrypt(lastname, '" . KEY . "','aes'),'SQL_ASCII') AS lastname,
                convert_from(decrypt(email, '" . KEY . "','aes'),'SQL_ASCII') AS email,
                pass AS password,
                failed_attempts AS failed,
                is_locked,
                session_id,
                authyid,
                array_to_json(groups) AS roles
                FROM users WHERE 
                convert_from(decrypt(email, '" . KEY . "','aes'),'SQL_ASCII')=:email",
                [":email" => $_POST["email"]]
            );
            if (!$user) {
                Audit::log("Invalid User: {$_POST['email']}.  Origin: {$_SERVER['REMOTE_ADDR']}", 1, FALSE, 0);
                throw new InvalidLoginException("User: {$_POST['email']} does not exist.", 401);
            }
            $this->_processUser($user);
            $this->db->commit();
        } catch (InvalidLoginException $e) {
            // Re-throw the Exception.
            throw new InvalidLoginException($e->getMessage(), $e->getCode());
        } catch (AuthyFormatException $e) {
            // Catch and Re-throw the Exception as an invalid login exception.
            throw new InvalidLoginException($e->getMessage(), 401);
        } catch (\Exception $e) {
            GlobalFunction::logError($e);
            throw new \Exception("Database Error, Please see error log for details.", 500, $e);
        }
    }

    /**
     * Process the user to see if they are locked, if their login credentials are successful etc.
     *
     * @param array $user User Details from the Database.
     *
     * @return void
     * @throws InvalidLoginException On incorrect Username/Password combination or if the account is locked.
     */
    private function _processUser(array $user)
    {
        if ($user['is_locked'] === 1) {
            Audit::log("Attempt to access Locked Account: {$user['email']}", 1, FALSE, $user['id']);
            throw new InvalidLoginException("User Account Is Locked. Please inform the Administrator", 403);
        } else if (password_verify($_POST['password'], $user['password'])) {
            $this->_checkAuthy($user['id'], $user['authyid']);
            $this->_processSuccessfulLogin($user);
        } else {
            if (($total = (MAX_ATTEMPTS - $user['failed'])) > 0) {
                $this->db->executeQuery("UPDATE users SET failed_attempts = failed_attempts + 1 WHERE id=:id", [":id" => intval($user['id'])]);
                Audit::log("Login Failure.", 1, FALSE, $user['id']);
                throw new InvalidLoginException("Incorrect Username or Password; Attempts remaining before your account is locked: {$total}", 401);
            } else {
                $this->_lockUser($user);
            }
        }
    }

    /**
     * Lock the user's Account and log the lock to the audit log.
     *
     * @param array $user User Details from the Database.
     *
     * @throws InvalidLoginException On Lock.
     *
     * @return void
     */
    private function _lockUser(array $user): void
    {
        $this->db->executeQuery("UPDATE users SET is_locked=1 WHERE id=:uid", [":uid" => intval($user['id'])]);
        Audit::log("Account: {$user['email']} has been locked.", 1, FALSE, $user['id']);
        throw new InvalidLoginException("Incorrect Username or Password, your account has been locked", 401);
    }

    /**
     * Process a successful login event.
     *
     * @param array $user User Details from the Database.
     *
     * @return void
     */
    private function _processSuccessfulLogin(array $user)
    {
        $ssid = UUID::generate(UUID::VERSION_4);
        $this->db->executeQuery(
            "UPDATE users SET failed_attempts=0,session_id=:ssid WHERE id=:uid",
            [
                ":ssid" => $ssid,
                ":uid"  => intval($user['id']),
            ]
        );
        Audit::log("Login Success.", 1, TRUE, $user['id']);
        $_SESSION['user'] = [
            "id"        => intval($user['id']),
            "shortname" => $user['firstname'],
            "name"      => "{$user['firstname']} {$user['lastname']}",
            "email"     => $user['email'],
            "ssid"      => $ssid,
            "roles"     => json_decode($user['roles'], TRUE)
        ];
    }

}