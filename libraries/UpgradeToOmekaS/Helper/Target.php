<?php

/**
 * Check, prepare and manage all target database methods.
 *
 * @package UpgradeToOmekaS
 */
class UpgradeToOmekaS_Helper_Target
{

    /**
     * The Zend database object.
     *
     * @var Object
     */
    protected $_db;

    /**
     * Some prechecks are different according to the upgrade process.
     *
     * @var boolean
     */
    protected $_isProcessing = false;

    /**
     * Params to connect to the database including its type (share or separate).
     *
     * @var array
     */
    protected $_params = array();

    /**
     * List of the tables of the database.
     *
     * @internal This allows to do some important checks before the database is
     * created, in particular when the database is shared.
     *
     * @var array
     */
    protected $_tables = array();

    /**
     * List of columns of tables of the database.
     *
     * @var
     */
    protected $_tablesColumns = array();

    /**
     * Set params of the database.
     *
     * @param array $params
     */
    public function setDatabaseParams($params)
    {
        $this->_params = $params;
    }

    /**
     * Set the list of tables of the database.
     *
     * @param array $tables
     */
    public function setTables($tables)
    {
        $this->_tables = $tables;
    }

    /**
     * Set if the process is running.
     *
     * @todo Remove this status.
     *
     * @param boolean $isProcessing
     */
    public function setIsProcessing($isProcessing)
    {
        $this->_isProcessing = (boolean) $isProcessing;
    }

    /**
     * Helper to get the Omeka S database object.
     *
     * @throws UpgradeToOmekaS_Exception
     * @return Db|null
     */
    public function getDb()
    {
        if (!empty($this->_db)) {
            return $this->_db;
        }

        if (empty($this->_params)) {
            throw new UpgradeToOmekaS_Exception(
                __('The params of the database are not defined.'));
        }

        if (empty($this->_tables)) {
            throw new UpgradeToOmekaS_Exception(
                __('The tables of the database are not defined.'));
        }

        $params = $this->_params;
        if (!isset($params['type'])) {
            throw new UpgradeToOmekaS_Exception(
                __('The type of the database is not defined.'));
        }

        $type = $params['type'];
        switch ($type) {
            case 'separate':
                $host = isset($params['host']) ? $params['host'] : '';
                $port = isset($params['port']) ? $params['port'] : '';
                $dbname = isset($params['dbname']) ? $params['dbname'] : '';
                $username = isset($params['username']) ? $params['username'] : '';
                $password = isset($params['password']) ? $params['password'] : '';
                break;
            // The default connection can't be reused, because there are the
            // application layers.
            case 'share':
                $db = get_db();
                $config = $db->getAdapter()->getConfig();
                $host = isset($config['host']) ? $config['host'] : '';
                $port = isset($config['port']) ? $config['port'] : '';
                $dbname = isset($config['dbname']) ? $config['dbname'] : '';
                $username = isset($config['username']) ? $config['username'] : '';
                $password = isset($config['password']) ? $config['password'] : '';
                break;
            default:
                throw new UpgradeToOmekaS_Exception(
                    __('The type "%s" is not possible for the database.', $type));
        }

        // The connection should be checked even for a shared database.
        $params = array(
            'host' => $host,
            'username' => $username,
            'password' => $password,
            'dbname' => $dbname,
        );
        if ($port) {
            $params['port'] = $port;
        }

        try {
            $db = Zend_Db::Factory('PDO_MYSQL', $params);
            if (empty($db)) {
                throw new UpgradeToOmekaS_Exception(
                    __('Database is null.'));
            }
        } catch (Exception $e) {
            throw new UpgradeToOmekaS_Exception(
                __('Cannot access to the database "%s": %s', $dbname, $e->getMessage()));
        }

        // Another check.
        switch ($type) {
            case 'separate':
                if (!$this->_isProcessing) {
                    $sql = 'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = "' . $dbname . '";';
                    $result = $db->fetchOne($sql);
                    if ($result) {
                        throw new UpgradeToOmekaS_Exception(
                            __('The target database "%s" should be empty when using a separate database.', $dbname));
                    }
                }
                break;

            case 'share':
                $sql = 'SHOW TABLES;';
                $result = $db->fetchCol($sql);
                if (!$this->_checkSharedTables($result)) {
                    throw new UpgradeToOmekaS_Exception(
                        __('Some names of tables exist in the shared database.'));
                }
                break;
        }

        $this->_db = $db;
        return $this->_db;
    }

    /**
     * Return the list of the columns for a table of the target.
     *
     * @param string $table
     * @return array|null Null if target database is not loaded or not a table.
     */
    public function getTableColumns($table)
    {
        if (!$this->_checkTables($table)) {
            return;
        }

        if (!isset($this->_tablesColumns[$table])) {
            $db = $this->getDb();
            $config = $db->getConfig();
            $dbname = isset($config['dbname']) ? $config['dbname'] : '';
            $sql = '
            SELECT COLUMN_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = ' . $db->quote($dbname) . ' AND TABLE_NAME = ' . $db->quote($table) . ';';
            $result = $db->fetchCol($sql);
            $this->_tablesColumns[$table] = $result;
        }

        return $this->_tablesColumns[$table];
    }

    /**
     * Helper to get the total rows of a table.
     *
     * @param string $table
     * @return integer
     */
    public function totalRows($table)
    {
        if (!$this->_checkTables($table)) {
            return;
        }

        $db = $this->getDb();
        $select = $db->select()
            ->from($table, array(new Zend_Db_Expr('COUNT(*)')));
        $result = $db->fetchOne($select);
        return (integer) $result;
    }

    /**
     * Helper to list the ids of a table or any column with single values.
     *
     * @param string $table
     * @param string $column
     * @return array Associative array of ids.
     */
    public function fetchIds($table, $column = 'id')
    {
        $db = $this->getDb();
        $columns = $this->getTableColumns($table);
        if (!in_array($column, $columns)) {
            return;
        }
        $select = $db->select()
            ->from($table, $column)
            ->order($column);
        $result = $db->fetchCol($select);
        return array_combine($result, $result);
    }

    /**
     * Helper to get an associative array of two columns of a table.
     *
     * @param string $table
     * @param string $columnKey
     * @param string $columnValue
     * @return array Associative array of two columns.
     */
    public function fetchPairs($table, $columnKey, $columnValue)
    {
        $db = $this->getDb();
        $columns = $this->getTableColumns($table);
        if (!in_array($columnKey, $columns) || !in_array($columnValue, $columns)) {
            return;
        }
        $select = $db->select()
            ->from($table, array($columnKey, $columnValue))
            ->order($columnKey);
        $result = $db->fetchPairs($select);
        return $result;
    }

    /**
     * Helper to get an array of a table.
     *
     * @param string $table
     * @return array
     */
    public function fetchTable($table)
    {
        $db = $this->getDb();
        $select = $db->select()
            ->from($table);
        $result = $db->fetchAll($select);
        return $result;
    }

    /**
     * Helper to insert multiple quoted rows in a table of the target database.
     *
     * @uses self::_insertRowsInTables()
     *
     * @param string $table
     * @param array $rows
     * @param array $columns
     * @param boolean $areQuoted
     */
    public function insertRows($table, $rows, $columns = array(), $areQuoted = true)
    {
        if (!$this->_checkTables($table)) {
            return;
        }

        if (empty($rows)) {
            return;
        }

        $rowsByTable = array($table => $rows);
        $columnsByTable = $columns ? array($table => $columns) : array();

        $this->insertRowsInTables($rowsByTable, $columnsByTable, $areQuoted);
    }

    /**
     * Insert multiple rows in multiple tables of the target database.
     *
     * @internal An early quotation of rows may save memory with big chunks.
     * @internal This method uses sql transaction to manage auto-increment ids
     * as long as the "LAST_INSERT_ID() + ' . $baseId" is well formed in rows.
     * The last inserted id is the *first* autoincremented value of the previous
     * successfull insert, i.e. the first inserted id of the second table for
     * the third table. Finally, if the id is set manually, it is not an
     * autoincremented one, so it is not recommended to mix auto and manual ids.
     *
     * @internal There is no error or warning when the number of columns and
     * values are different. The query is simply skipped.
     *
     * @param array $rowsByTable The rows for each table.
     * @param array $columnsByTable The columns for each table.
     * @param boolean $areQuoted
     * @throws UpgradeToOmekaS_Exception
     */
    public function insertRowsInTables($rowsByTable, $columnsByTable = array(), $areQuoted = true)
    {
        if (!$this->_checkTables(array_keys($rowsByTable))) {
            return;
        }

        if (empty($rowsByTable)) {
            return;
        }

        // Get the columns of each table.
        foreach ($rowsByTable as $table => $rows) {
            if (!isset($columns[$table])) {
                $columns[$table] = $this->getTableColumns($table);
                if (empty($columns[$table])) {
                    return;
                }
            }
        }

        if (!$areQuoted) {
            foreach ($rowsByTable as $table => &$rows) {
                $rows = array_map(array($this, 'cleanQuote'), $rows);
            }
            unset($rows);
        }

        // Prepare the insert statements.
        $sql = '';
        foreach ($rowsByTable as $table => $rows) {
            $sql .= sprintf('INSERT INTO `%s` (`%s`) VALUES ', $table, implode('`, `', $columns[$table])) . PHP_EOL;
            $sql .= '(' . implode('),' . PHP_EOL . '(', $rows) . ');' . PHP_EOL;
        }

        $db = $this->getDb();
        $result = $db->prepare($sql)->execute();
        if (!$result) {
            throw new UpgradeToOmekaS_Exception(
                __('Unable to insert data in table %s.', $table));
        }
    }

    /**
     * Remove the tables of the database.
     *
     * @param array $tables
     * @return boolean
     */
    public function removeTables()
    {
        $db = $this->getDb();
        $sql = '
            BEGIN;
            SET foreign_key_checks = 0;
            DROP TABLE IF EXISTS `' . implode('`, `', $this->_tables) . '`;
            SET foreign_key_checks = 1;
            COMMIT;
        ';
        try {
            $db->prepare($sql)->execute();
        } catch (Exception $e) {
            throw new UpgradeToOmekaS_Exception(
                __('An unknown error occurred during the drop of tables of the database: %s.',
                $e->getMessage()));
        }

        return true;
    }

    /**
     * Helper to quote a value before insertion of multiple rows in database.
     *
     * It safely quotes a value for an SQL statement. If an array is passed as
     * the value, the array values are quoted and then returned as a
     * comma-separated string.
     *
     * @internal Unlike Zend_Db_Adapter_Abstract::quote(), it takes care of
     * the value "null", kept as NULL, not "".
     * @internal Moreover, it allows not to quote some keys for an array of values.
     *
     * @param mixed $value If array, it should be a *flat* one.
     * @param array $skipQuoteKeys Keys of the values to not quote; it requires an
     * associative array in $value.
     * @return string A database quoted string, according to the value type. The
     * separator is a comma + a tabulation.
     */
    public function cleanQuote($value, $skipQuoteKeys = array())
    {
        if (is_null($value)) {
            return 'NULL';
        }

        if (is_array($value)) {
            if ($skipQuoteKeys) {
                if (!is_array($skipQuoteKeys)) {
                    $skipQuoteKeys = array($skipQuoteKeys);
                }
                $result = $value;
                foreach ($result as $key => &$val) {
                    if (!in_array($key, $skipQuoteKeys)) {
                        $val = $this->cleanQuote($val);
                    }
                }
            }
            else {
                $result = array_map(array($this, 'cleanQuote'), $value);
            }
            return implode(",\t", $result);
        }

        $db = $this->getDb();
        return $db->quote($value);
    }

    /**
     * Helper to check the tables.
     *
     * @param string|array $tables
     * @return boolean
     */
    protected function _checkTables($tables)
    {
        if (empty($tables)) {
            return false;
        }

        if (is_string($tables)) {
            return in_array($tables, $this->_tables);
        }

        $result = array_intersect($tables, $this->_tables);

        return count($result) == count($tables);
    }

    /**
     * Helper to check the shared tables.
     *
     * @param string|array $tables
     * @return boolean
     */
    protected function _checkSharedTables($tables)
    {
        return !$this->_checkTables($tables);
    }
}
