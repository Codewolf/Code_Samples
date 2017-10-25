<?php

namespace Licencing\core;

use Licencing\GlobalFunction;

/**
 * Class DBPDO
 * Extend PDO.
 *
 * @codeCoverageIgnore Ignoring coverage due to using Database.
 * @package            Licencing\core
 */
class DBPDO extends \PDO
{

    /**
     * Create a PDO instance linking to the database with default options.
     *
     * @param string $dsn      DSN Connection String.
     * @param string $username Database Username.
     * @param string $passwd   Database Password.
     * @param array  $options  Database Options [optional].
     */
    public function __construct($dsn, $username, $passwd, array $options = [])
    {
        $optionsArray = ([
                \PDO::ATTR_EMULATE_PREPARES => FALSE,
                \PDO::ATTR_ERRMODE          => \PDO::ERRMODE_EXCEPTION
            ] + $options);
        parent::__construct($dsn, $username, $passwd, $optionsArray);
    }

    /**
     * Execute prepared statement and fetch first row of results.
     *
     * @param string $query      Query String.
     * @param array  $parameters Parameter array for binding.
     *
     * @return array Results returned
     */
    public function fetchQuery($query, array $parameters = [])
    {
        $stmt = $this->executeQuery($query, $parameters, TRUE);

        return $stmt->fetch();
    }

    /**
     * Execute prepared statement.
     *
     * @param string  $query      Query String.
     * @param array   $parameters Parameter array for binding.
     *
     * @param boolean $skipLog    Skip the logging of this query?.
     *
     * @return \PDOStatement|string Returned Resource.
     */
    public function executeQuery($query, array $parameters = [], $skipLog = TRUE)
    {
        if (!$skipLog) {
            $this->_logQuery($this->mockQuery($query, $parameters));
        }
        $query = parent::prepare($query);
        $query->execute($parameters);

        return $query;
    }

    /**
     * Execute prepared statement and fetch all results.
     *
     * @param string $query      Query String.
     * @param string $key        Column name to use results from as the key in the array (optional).
     * @param array  $parameters Parameter array for binding (optional).
     *
     * @return array|boolean Results returned
     */
    public function fetchAllKeyQuery($query, $key = "id", array $parameters = [])
    {
        $return = [];
        $stmt   = $this->executeQuery($query, $parameters, TRUE);
        while (($row = $stmt->fetch()) !== FALSE) {
            $return[$row[$key]] = $row;
        }

        return $return;
    }

    /**
     * Return a stdClass Object
     *
     * @param string $func       Function to run.
     * @param string $query      Query String.
     * @param array  $parameters Parameter array for binding.
     *
     * @return object Results returned
     */
    public function objectQuery($func, $query, array $parameters = [])
    {
        return (object) $this->$func($query, $parameters);
    }

    /**
     * Execute prepared statement and fetch all results.
     *
     * @param string $query      Query String.
     * @param array  $parameters Parameter array for binding.
     *
     * @return array Results returned
     */
    public function fetchAllQuery($query, array $parameters = [])
    {
        $stmt = $this->executeQuery($query, $parameters);

        return $stmt->fetchAll();
    }

    /**
     * Returns String that will be inserted into the database.
     *
     * This function is used for debugging to return the query
     * that will be sent to the database.
     * NOTE: This method does not check to make sure number of parameters
     * in the parameter array are the same as the number of parameters!!!
     *
     * @param string $query      Query String.
     * @param array  $parameters Parameter Array.
     *
     * @return string
     */
    public function mockQuery($query, array $parameters = [])
    {
        // IF Associated array.
        return ((bool) count(array_filter(array_keys($parameters), 'is_string'))) ? $this->_mockAssociated($query, $parameters) : $this->_mockLinear($query, $parameters);
    }

    /**
     * Child of mockQuery, dealing with associative arrays.
     *
     * @param string $query      Query String.
     * @param array  $parameters Parameter Array.
     *
     * @see DBPDO::mockQuery() For details
     * @return string
     */
    private function _mockAssociated($query, array $parameters)
    {
        foreach ($parameters as $parameter => $value) {
            if (!is_integer($value) && !is_float($value) && !is_double($value)) {
                $value = "'{$value}'";
            }
            $query = preg_replace("/$parameter\b/", $value, $query);
        }
        return $query;
    }

    /**
     * Child of mockQuery, dealing with linear (number keyed) arrays.
     *
     * @param string $query      Query String.
     * @param array  $parameters Parameter Array.
     *
     * @see DBPDO::mockQuery() For details
     * @return string
     */
    private function _mockLinear($query, array $parameters)
    {
        foreach ($parameters as $value) {
            if (!is_integer($value) && !is_float($value) && !is_double($value) && ($value !== NULL)) {
                $value = "'{$value}'";
            } else if ($value === NULL) {
                $value = 'NULL';
            }
            $query = preg_replace("/\?/", $value, $query, 1);
        }
        return $query;
    }

    /**
     * Run an insert Query on the database, with an associated array of column to value.
     *
     * @param string $table  Table Name.
     * @param array  $values Associative array of columns to value.
     *
     * @return void
     */
    public function insert($table, array $values = [])
    {
        $columns     = [];
        $params      = [];
        $placeholder = [];
        foreach ($values as $column => $value) {
            $columns[]     = $column;
            $params[]      = $value;
            $placeholder[] = "?";
        }
        $this->executeQuery("INSERT INTO $table (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholder) . ")", $params);
    }

    /** Log the Query to a log file
     *
     * @param string $query Query String as seen by database.
     *
     * @return void
     */
    private function _logQuery($query)
    {
        GlobalFunction::logMessage("User: {$_SESSION['user']['id']} - Query: {$query}\n");
    }

    /**
     * Overwrite Commit to check for transaction first.
     *
     * @return void
     */
    public function commit()
    {
        if ($this->inTransaction()) {
            parent::commit();
        }
    }

    /**
     * Overwrite beginTransaction to check for transaction first.
     *
     * @return void
     */
    public function beginTransaction()
    {
        if (!$this->inTransaction()) {
            parent::beginTransaction();
        }
    }

    /**
     * Overwrite rollBack to check for transaction first.
     *
     * @return void
     */
    public function rollBack()
    {
        if ($this->inTransaction()) {
            parent::rollBack();
        }
    }

}

?>