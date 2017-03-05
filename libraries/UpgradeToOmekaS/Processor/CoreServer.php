<?php

/**
 * Upgrade Core Server to Omeka S.
 *
 * @internal All checks can be bypassed with another "Core" processor.
 *
 * @package UpgradeToOmekaS
 */
class UpgradeToOmekaS_Processor_CoreServer extends UpgradeToOmekaS_Processor_Abstract
{

    public $pluginName = 'Core/Server';
    public $minVersion = '2.3.1';
    public $maxVersion = '2.5';
    protected $_isCore = true;

    public $module = array(
        'name' => 'Omeka S',
        'version' => '1.0.0-beta2',
        'url' => 'https://github.com/omeka/omeka-s/releases/download/v%s/omeka-s.zip',
        'size' => 11526232,
        'sha1' => '6e6929c94363fc5059cae795233d91465e1d89be',
        'type' => 'equivalent',
        'requires' => array(
            'minDb' => array(
                'mariadb' => '5.5.3',
                'mysql' => '5.5.3',
            ),
        ),
    );

    /**
     * Define a minimum size for the install directory (without files).
     *
     * @var integer
     */
    public $minOmekaSemanticSize = 100000000;

    /**
     * Define a minimum size for the destination base dir.
     *
     * @var integer
     */
    public $minDestinationSize = 1000000000;

    /**
     * Define a minimum size for the temp directory.
     *
     * @var integer
     */
    public $minTempDirSize = 1000000000;

    public $processMethods = array(
        '_createDirectory',
        '_downloadOmekaS',
        '_unzipOmekaS',
    );

    /**
     * Store the full archive size (directory "files").
     *
     * @var integer
     */
    protected $_archiveSize;

    /**
     * Store the full database size.
     *
     * @var integer
     */
    protected $_databaseSize;

    /**
     * Store the free size of the destination directory.
     *
     * @var integer
     */
    protected $_destinationFreeSize;

    /**
     * @todo Load all the config checks from Omeka Semantic.
     *
     * {@inheritDoc}
     * @see UpgradeToOmekaS_Processor_Abstract::_precheckConfig()
     * @see application/config/module.config.php['installer']['pre_tasks']
     */
    protected function _precheckConfig()
    {
        $this->_precheckVersion();
        $checks = $this->getParam('checks');
        if (!empty($checks['background_dispatcher'])) {
            $this->_precheckBackgroundDispatcher();
        }
        // During the background process, the server is not Apache.
        if (!$this->isProcessing()) {
            $this->_precheckServer();
        }
        // See Omeka S ['installer']['pre_tasks']: CheckEnvironmentTask.php
        $this->_precheckPhp();
        $this->_precheckPhpModules();
        // See Omeka S ['installer']['pre_tasks']: CheckDbConfigurationTask.php
        $this->_precheckDatabaseServer();
        $this->_precheckZip();
        // Don't check the jobs during true process.
        if (!$this->isProcessing()) {
            $this->_precheckJobs();
        }
        if (!empty($checks['symlinks'])) {
            $this->_precheckSymlinks();
        }
    }

    protected function _checkConfig()
    {
        $this->_checkDatabase();
        // See Omeka S ['installer']['pre_tasks']: CheckDirPermissionsTask.php
        $this->_checkFileSystem();
        $this->_checkFreeSize();
    }

    /* Prechecks. */

    protected function _precheckVersion()
    {
        if (version_compare($this->minVersion, OMEKA_VERSION, '>')) {
            $this->_prechecks[] = __('The current release requires at least Omeka %s, current is only %s.',
                $this->minVersion, OMEKA_VERSION);
        }

        if (version_compare($this->maxVersion, OMEKA_VERSION, '<')) {
            $this->_prechecks[] = __('The current release requires at most Omeka %s, current is %s.',
                $this->maxVersion, OMEKA_VERSION);
        }
    }

    protected function _precheckBackgroundDispatcher()
    {
        $config = Zend_Registry::get('bootstrap')->config;
        if ($config) {
            if (isset($config->jobs->dispatcher->longRunning)) {
                if ($config->jobs->dispatcher->longRunning == 'Omeka_Job_Dispatcher_Adapter_Synchronous') {
                    $this->_prechecks[] = __('The process should be done in the background: modify the setting "jobs.dispatcher.longRunning" in the config of Omeka Classic.')
                        . ' ' . __('This precheck may be bypassed via an option in "security.ini".');
                }
            }
            // No long job.
            else {
                $this->_prechecks[] = __('The background job config is not defined in the config of Omeka Classic.');
            }
        }
        // No config.
        else {
            $this->_prechecks[] = __('The config of Omeka Classic has not been found.');
        }
    }

    protected function _precheckServer()
    {
        if ($this->_isServerWindows()) {
            $this->_prechecks[] = __('According to the readme of Omeka Semantic, the server should be a Linux one.');
        }

        if (!$this->_isServerApache()) {
            $this->_prechecks[] = __('According to the readme of Omeka Semantic, the server should be an Apache one.');
        }
    }

    protected function _precheckPhp()
    {
        if (version_compare(PHP_VERSION, '5.6', '<')) {
            $this->_prechecks[] = __('Omeka Semantic requires at least PHP 5.6 and prefers the last stable version.');
        }
        // TODO Add a check for the vesion of PHP in background process?
    }

    protected function _precheckPhpModules()
    {
        $requiredExtensions = array(
            'pdo',
            'pdo_mysql',
        );
        foreach ($requiredExtensions as $extension) {
            if (!extension_loaded($extension)) {
                // TODO Check under Windows.
                if (!function_exists('dl') || !dl($extension . '.so')) {
                    $this->_prechecks[] = __('Omeka Semantic requires the php extension "%s".', $extension);
                }
            }
        }
    }

    protected function _precheckDatabaseServer()
    {
        $sql = 'SHOW VARIABLES LIKE "version"';
        $result = $this->_db->query($sql)->fetchAll();
        if (empty($result)) {
            $this->_prechecks[] = __('The version of the database server cannot be checked.');
        }
        // Standard server.
        else {
            $result = strtolower($result[0]['Value']);
            $mariadb = strpos($result, '-mariadb');
            $version = strtok($result, '-');
            if ($mariadb) {
                $result = version_compare($this->module['requires']['minDb']['mariadb'], $version, '>');
            }
            // Probably a mysql database.
            else {
                $result = version_compare($this->module['requires']['minDb']['mysql'], $version, '>');
            }
            if ($result) {
                $this->_prechecks[] = __('The current release requires at least MariaDB %s or Mysql %s, current is only %s.',
                    $this->module['requires']['minDb']['mariadb'], $this->module['requires']['minDb']['mysql'], ($mariadb ? 'MariaDB' : 'MySQL') . ' ' . $version);
            }
        }
    }

    protected function _precheckZip()
    {
        if (!class_exists('ZipArchive')) {
            try {
                $messageError = __('Zip (as an available command line tool or as the php module ZipArchive) is required to extract downloaded packages.');
                UpgradeToOmekaS_Common::executeCommand('unzip', $status, $output, $errors);
                // A return value of 0 indicates the convert binary is working correctly.
                if ($status != 0) {
                    $this->_prechecks[] = $messageError;
                    $this->_prechecks[] = __('The shell returns an error: %s', $errors);
                }
            } catch (Exception $e) {
                $this->_prechecks[] = $messageError;
                $this->_prechecks[] = __('An error occurs: %s', $e->getMessage());
            }
        }
    }

    protected function _precheckJobs()
    {
        $totalRunningJobs = $this->_db->getTable('Process')
            ->count(array('status' => array(Process::STATUS_STARTING, Process::STATUS_IN_PROGRESS)));
        if ($totalRunningJobs) {
            // Plural needs v2.3.1.
            $message = function_exists('plural')
                ? __(plural('%d job is running.', '%d jobs are running.',
                    $totalRunningJobs), $totalRunningJobs)
                : __('%d jobs are running.', $totalRunningJobs);
            $this->_prechecks[] = $message . ' ' . __('See below to kill them.');
        }
    }

    protected function _precheckSymlinks()
    {
        $result = UpgradeToOmekaS_Common::containsSymlinks(FILES_DIR);
        if ($result) {
            $this->_prechecks[] = __('There are symbolic links inside the directory of files.')
                . ' ' . __('Some errors may occur in some cases.')
                . ' ' . __('This precheck may be bypassed via an option in "security.ini".');
        }

        $result = UpgradeToOmekaS_Common::containsSymlinks(PUBLIC_THEME_DIR);
        if ($result) {
            $this->_prechecks[] = __('There are symbolic links inside the directory of themes.')
                . ' ' . __('Some errors may occur in some cases.')
                . ' ' . __('This precheck may be bypassed via an option in "security.ini".');
        }

        // A check is done on plugins since their views are copied in the theme.
        $result = UpgradeToOmekaS_Common::containsSymlinks(PLUGIN_DIR);
        if ($result) {
            $this->_prechecks[] = __('There are symbolic links inside the directory of plugins.')
                . ' ' . __('Some errors may occur in some cases.')
                . ' ' . __('This precheck may be bypassed via an option in "security.ini".');
        }
    }

    /* Checks. */

    protected function _checkDatabase()
    {
        // TODO Merge with the checks of getTargetDb().

        // Get the database name.
        $db = $this->_db;
        $config = $db->getAdapter()->getConfig();
        $currentDbName = $config['dbname'];
        if (empty($currentDbName)) {
            $this->_checks[] = __('Unable to get the current database name.');
            return;
        }
        $currentDbHost = $config['host'];
        if (empty($currentDbHost)) {
            $this->_checks[] = __('Unable to get the current database host.');
            return;
        }

        // Get size of the current database.
        $sql = 'SELECT SUM(data_length + index_length) AS "Size"
        FROM information_schema.TABLES
        WHERE table_schema = "' . $currentDbName . '";';
        $sizeDatabase = $db->fetchOne($sql);

        // Get snaky free size of the current database.
        $sql = 'SELECT SUM(data_free) AS "Free Size"
        FROM information_schema.TABLES
        WHERE table_schema = "' . $currentDbName . '";';
        $freeSizeDatabase = $db->fetchOne($sql);

        $databaseSize = $sizeDatabase + $freeSizeDatabase;
        $this->_databaseSize = $databaseSize;
        if (empty($sizeDatabase) || empty($databaseSize)) {
            $this->_checks[] = __('Cannot evaluate the size of the Omeka Classic database.');
        }

        // Check if matching params.
        $database = $this->getParam('database');
        if (!isset($database['type'])) {
            $this->_checks[] = __('The type of the database is not defined.');
        }
        $type = $database['type'];
        switch ($type) {
            case 'separate':
                $host = isset($database['host']) ? $database['host'] : '';
                $port = isset($database['port']) ? $database['port'] : '';
                $username = isset($database['username']) ? $database['username'] : '';
                $password = isset($database['password']) ? $database['password'] : '';
                $dbname = isset($database['dbname']) ? $database['dbname'] : '';
                if (empty($host)) {
                    $this->_checks[] = __('The param "%s" should be set when the databases are separate.', 'host');
                }
                if (empty($username)) {
                    $this->_checks[] = __('The param "%s" should be set when the databases are separate.', 'username');
                }
                if (empty($dbname)) {
                    $this->_checks[] = __('The param "%s" should be set when the databases are separate.', 'dbname');
                }
                if ($dbname == $currentDbName && $host == $currentDbHost) {
                    $this->_checks[] = __('The database name should be different from the Omeka Classic one when the databases are separate, but on the same server.');
                }

                // Check access rights.
                $params=array(
                    'host' => $host,
                    'username' => $username,
                    'password' => $password,
                    'dbname' => $dbname,
                );
                if ($port) {
                    $params['port'] = $port;
                }

                try {
                    $targetDb = Zend_Db::Factory('PDO_MYSQL', $params);
                    if (empty($targetDb)) {
                        $this->_checks[] = __('Can’t get access to the database "%s": %s', $dbname, $e->getMessage());
                    }
                } catch (Exception $e) {
                    $this->_checks[] = __('Cannot access to the database "%s": %s', $dbname, $e->getMessage());
                    return;
                }

                $sql = 'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = "' . $dbname . '";';
                $result = $targetDb->fetchOne($sql);
                if ($result) {
                    $this->_checks[] = __('The database "%s" should be empty.', $dbname);
                    return;
                }
                break;

                // The database is shared; so the prefix should be different.
            case 'share':
                $prefix = $database['prefix'];
                if (empty($prefix)) {
                    $this->_checks[] = __('A database prefix is required when the database is shared.');
                    return;
                }
                if ($prefix == $db->prefix) {
                    $this->_checks[] = __('The database prefix should be different from the Omeka Classic one when the database is shared.');
                    return;
                }

                // Check the names of the tables and the prefix.
                $sql = 'SHOW TABLES;';
                $result = $db->fetchCol($sql);
                if (empty($result)) {
                    $this->_checks[] = __('Cannot get the list of the tables of Omeka Classic.');
                    return;
                }
                $existings = array_filter($result, function ($v) use ($prefix) {
                    return strpos($v, $prefix) === 0;
                });
                if ($existings) {
                    $this->_checks[] = __('The prefix "%s" cannot be used, because it causes a conflict in the table names of Omeka Classic.', $prefix);
                    return;
                }

                // Check conflicts of table names.
                $tablesOmekas = $this->getMergedList('tables');
                if (array_intersect($result, $tablesOmekas)) {
                    $this->_checks[] = __('Some names of tables of Omeka S or its modules are existing in the database of Omeka Classic.');
                }
                break;

            default:
                $this->_checks[] = __('The type of database "%s" is not supported.', $type);
                return;
        }

        // Check max database size.
        // TODO Check max database size. Currently, this is done partially via
        // the check of the size of the filesystem, but the database may be
        // mounted differently or externalized, so some cases can't be managed.
    }

    protected function _checkFileSystem()
    {
        $path = $this->getParam('base_dir');

        // The dir is already validated by the form, but this is an important
        // param and revalidation is quick. The checks are important in
        // particular because the document root may be changed between a web
        // request (the one set by Apache) and a command line request (empty, so
        // saved from document root during install).
        if (!UpgradeToOmekaS_Form_Validator::validateBaseDir($path)) {
            $this->_checks[] = __('The base dir "%s" is not empty, not allowed or not writable.', $path);
            // Other checks are not processed when this one fails.
            return;
        }

        // Check access rights inside the directory, in particular when the
        // directory preexists.
        $isCreated = !file_exists($path);
        if ($isCreated) {
            $result = UpgradeToOmekaS_Common::createDir($path);
            if (empty($result)) {
                $this->_checks[] = __('The base dir "%s" is not writable.', $path);
                return;
            }
        }

        // Check creation of a sub directory, because this is forbidden on some
        // servers.
        $testDir = $path . DIRECTORY_SEPARATOR . 'testdir';
        $result = UpgradeToOmekaS_Common::createDir($testDir);
        if (empty($result)) {
            $this->_checks[] = __('The base dir "%s" is not usable.', $path);
            return;
        }

        // Check creation of a file.
        $testFile = $testDir . DIRECTORY_SEPARATOR . md5(rtrim(strtok(substr(microtime(), 2), ' '), '0'));
        $result = touch($testFile);
        if (empty($result)) {
            $this->_checks[] = __('The base dir "%s" does not creation of files.', $path);
            // Remove the test dir with a security check.
            $this->_removeTestPath($isCreated, $path, $testDir);
            return;
        }

        // Check hard linking if needed. This is important when the dir is
        // different from the Omeka Classic one.
        $type = $this->getParam('files_type');
        if (in_array($type, array('hard_link', 'dummy_hard'))) {
            $testLink = $testDir . DIRECTORY_SEPARATOR . md5(rtrim(strtok(substr(microtime() + 1, 2), ' '), '0'));
            $result = link($testFile, $testLink);
            if (empty($result)) {
                $this->_checks[] = __('The base dir "%s" does not allow creation of hard links.', $path);
                $this->_removeTestPath($isCreated, $path, $testDir);
                return;
            }
        }

        // Get free size on the temp folder.
        $tempDir = sys_get_temp_dir();
        $result = disk_free_space($tempDir);
        if ($result < $this->minTempDirSize) {
            $this->_checks[] = __('The free size of the temp directory should be greater than %dMB.', ceil($this->minTempDirSize / 1000000));
            $this->_removeTestPath($isCreated, $path, $testDir);
            return;
        }

        // Get free size on the destination file sytem.
        $result = disk_free_space($path);
        if ($result < $this->minDestinationSize) {
            $this->_checks[] = __('The free size of the base dir should be greater than %dMB.', ceil($this->minDestinationSize / 1000000));
            $this->_removeTestPath($isCreated, $path, $testDir);
            return;
        }
        $this->_destinationFreeSize = $result;

        // Get current size of the files folder.
        $result = UpgradeToOmekaS_Common::getDirectorySize(FILES_DIR);
        if (empty($result)) {
            $this->_checks[] = __('Cannot evaluate the size of the Omeka Classic files dir.');
            $this->_removeTestPath($isCreated, $path, $testDir);
            return;
        }

        $this->_archiveSize = $result;

        $this->_removeTestPath($isCreated, $path, $testDir);
    }

    private function _removeTestPath($isCreated, $path, $testDir)
    {
        if (realpath($testDir) == $testDir && UpgradeToOmekaS_Common::countFilesInDir($testDir) <= 2) {
            UpgradeToOmekaS_Common::removeDir($isCreated ? $path : $testDir, true);
        }
    }

    protected function _checkFreeSize()
    {
        // This check requires that the previous ones are fine (need sizes).
        $archiveSize = $this->_archiveSize;
        $databaseSize = $this->_databaseSize;
        $destinationFreeSize = $this->_destinationFreeSize;

        if (empty($archiveSize)) {
            if (empty($this->_checks)) {
                $this->_checks[] = __('The size of the archive can’t be determined.');
            }
            return;
        }
        if (empty($databaseSize)) {
            if (empty($this->_checks)) {
                $this->_checks[] = __('The size of the database can’t be determined.');
            }
            return;
        }
        if (empty($destinationFreeSize)) {
            if (empty($this->_checks)) {
                $this->_checks[] = __('The free space size can’t be determined.');
            }
            return;
        }

        $type = $this->getParam('files_type');
        switch ($type) {
            case 'copy':
                $minDestinationSize = 1.2 * $archiveSize + $this->minOmekaSemanticSize;
                break;
            case 'hard_link':
            case 'dummy_hard':
                $numberFiles = UpgradeToOmekaS_Common::countFilesInDir(FILES_DIR);
                $minDestinationSize = 5000 * $numberFiles + $this->minOmekaSemanticSize;
                break;
            case 'dummy':
                $numberFiles = UpgradeToOmekaS_Common::countFilesInDir(FILES_DIR);
                $minDestinationSize = 10000 * $numberFiles + $this->minOmekaSemanticSize;
                break;
            default:
                $this->_checks[] = __('The type of files "%s" is unknown.', $type);
                return;
        }

        if ($destinationFreeSize < $minDestinationSize) {
            $this->_checks[] = __('A minimum size of %dMB is required in the base dir, only %dMB is available.',
                ceil($minDestinationSize / 1000000), ceil($destinationFreeSize / 1000000));
            return;
        }

        // TODO Check when the file systems of the database and files are different.

        $minSize = $minDestinationSize + 2 * $databaseSize;
        if ($destinationFreeSize < $minSize) {
            $this->_checks[] = __('A minimum size of %dMB (%dMB for the files and %dMB for the database) is required in the base dir, only %dMB is available.',
                ceil($minSize / 1000000), ceil($minDestinationSize / 1000000), ceil($databaseSize / 1000000), ceil($destinationFreeSize / 1000000));
            return;
        }
    }

    /**
     * Helper to get the server OS.
     *
     * @return string
     */
    protected function _isServerWindows()
    {
        return strncasecmp(PHP_OS, 'WIN', 3) == 0;
    }

    /**
     * Helper to get the server OS.
     *
     * @return string
     */
    protected function _isServerApache()
    {
        $serverSofware = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '';
        return strpos(strtolower($serverSofware), 'apache') === 0;
    }

    protected function _downloadOmekaS()
    {
        $this->_downloadModule(true);
    }

    protected function _unzipOmekaS()
    {
        $this->_unzipModule();
    }
}
