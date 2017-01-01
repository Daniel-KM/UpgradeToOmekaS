<?php

/**
 * @note Some checks require that the free space is greater than 1GB.
 * @note Some checks require to define the document root.
 * @note Some checks fail if the basedir has not been cleaned after a crash.
 * @note Some checks fail if omeka-s.zip is not in the temp folder.
 */
class UpgradeToOmekaS_Processor_CoreServerTest extends UpgradeToOmekaS_Test_AppTestCase
{
    protected $_isAdminTest = true;

    public function setUp()
    {
        parent::setUp();

        // Authenticate and set the current user.
        $this->user = $this->db->getTable('User')->find(1);
        $this->_authenticateUser($this->user);
    }

    public function testPrecheckConfigBase()
    {
        $processor = new UpgradeToOmekaS_Processor_CoreServer();
        $result = $processor->precheckConfig();
        $this->assertEmpty($result, 'Error: ' . reset($result));
    }

    public function testPrecheckConfigServer()
    {
        $_SERVER['SERVER_SOFTWARE'] = 'Windows XP';
        $processor = new UpgradeToOmekaS_Processor_CoreServer();
        $result = $processor->precheckConfig();
        $this->assertEquals(1, count($result));
        $this->assertContains('According to the readme of Omeka Semantic, the server should be an Apache one.', $result[0]);
    }

    public function testPrecheckConfigBadVersions()
    {
        $processor = new UpgradeToOmekaS_Processor_CoreServer();
        $processor->minVersion = '3.6';
        $processor->maxVersion = '1.5';
        $result = $processor->precheckConfig();
        $this->assertEquals(2, count($result));
        $this->assertContains('The current release requires at most Omeka 1.5, current is', $result[1]);
    }

    public function testPrecheckConfigBadDatabaseVersions()
    {
        $processor = new UpgradeToOmekaS_Processor_CoreServer();
        $processor->module['requires']['minDb'] = array(
            'mariadb' => '1234.5.3',
            'mysql' => '5678.5.3',
        );
        $result = $processor->precheckConfig();
        $this->assertEquals(1, count($result));
        $this->assertContains('The current release requires at least MariaDB 1234.5.3 or Mysql 5678.5.3', $result[0]);
    }

    public function testPrecheckConfigExistingJob()
    {
        $job = new Process();
        $job->class = 'Omeka_Job_Process_Wrapper';
        $job->user_id = $this->user->id;
        $job->status = Process::STATUS_STARTING;
        $job->save();

        $processor = new UpgradeToOmekaS_Processor_CoreServer();
        $result = $processor->precheckConfig();
        $this->assertEquals(1, count($result));
        $this->assertEquals('1 job is running. See below to kill them.', $result[0]);

        $job->delete();
        $processor = new UpgradeToOmekaS_Processor_CoreServer();
        $result = $processor->precheckConfig();
        $this->assertEmpty($result);
    }

    public function testCheckConfigDatabase()
    {
        $params = $this->_defaultParams;
        $processor = new UpgradeToOmekaS_Processor_CoreServer();
        $processor->setParams($params);
        $result = $processor->checkConfig();
        if ($result) {
            $this->assertEquals('', reset($result));
        }
        $this->assertEmpty($result);
        $database = $processor->getParam('database');
        $this->assertEquals('omekas_test_unit_', $database['prefix']);

        $params = array(
            'database' => array(
                'type' => 'foo',
            ),
        ) + $this->_defaultParams;
        $processor = new UpgradeToOmekaS_Processor_CoreServer();
        $processor->setParams($params);
        $result = $processor->checkConfig();
        $this->assertEquals(1, count($result));
        $this->assertEquals('The type of database "foo" is not supported.', $result[0]);
    }

    public function testCheckConfigDatabaseSeparateBadUsername()
    {
        $params = array(
            'database' => array(
                'type' => 'separate',
                'host' => 'localhost',
                'username' => 'foo',
                'password' => '',
                'dbname' => 'bar',
                'prefix' => '',
            ),
        ) + $this->_defaultParams;
        $processor = new UpgradeToOmekaS_Processor_CoreServer();
        $processor->setParams($params);
        $this->expectException(Zend_Db_Adapter_Exception::class);
        $result = $processor->checkConfig();
        // $this->assertEquals(3, count($result));
        // $this->assertContains('Cannot access to the database "bar"', $result[0]);
    }

    public function testCheckConfigDatabaseSeparateBadDbname()
    {
        $config = get_db()->getAdapter()->getConfig();
        $dbName = $config['dbname'];
        $dbHost = $config['host'];

        $params = array(
            'database' => array(
                'type' => 'separate',
                'host' => $dbHost,
                'username' => 'foo',
                'password' => '',
                'dbname' => $dbName,
                'prefix' => '',
            ),
        ) + $this->_defaultParams;
        $processor = new UpgradeToOmekaS_Processor_CoreServer();
        $processor->setParams($params);
        $this->expectException(Zend_Db_Adapter_Exception::class);
        $result = $processor->checkConfig();
        // $this->assertEquals(3, count($result));
        // $this->assertContains('The database name should be different from the Omeka Classic one when the databases are separate, but on the same server.', $result[0]);
    }

    public function testCheckConfigDatabaseSeparateBadEmptyDbname()
    {
        $params = array(
            'database' => array(
                'type' => 'separate',
                'prefix' => '',
            ),
        ) + $this->_defaultParams;
        $processor = new UpgradeToOmekaS_Processor_CoreServer();
        $processor->setParams($params);
        $this->expectException(Zend_Db_Adapter_Exception::class);
        $result = $processor->checkConfig();
        // $this->assertEquals(3, count($result));
        // $this->assertEquals('The param "name" should be set when the databases are separate.', $result[2]);
    }

    public function testCheckConfigBadDatabaseShare()
    {
        $params = array(
            'database' => array(
                'type' => 'share',
                'prefix' => '',
            ),
        ) + $this->_defaultParams;
        $processor = new UpgradeToOmekaS_Processor_CoreServer();
        $processor->setParams($params);
        $result = $processor->checkConfig();
        $this->assertEquals(1, count($result));
        $this->assertEquals('A database prefix is required when the database is shared.', $result[0]);

        $params = array(
            'database' => array(
                'type' => 'share',
                'prefix' => get_db()->prefix,
            ),
        ) + $this->_defaultParams;
        $processor = new UpgradeToOmekaS_Processor_CoreServer();
        $processor->setParams($params);
        $result = $processor->checkConfig();
        $this->assertEquals(1, count($result));
        $this->assertEquals('The database prefix should be different from the Omeka Classic one when the database is shared.', $result[0]);
    }

    public function testCheckConfigBadDatabaseShareTableExists()
    {
        $sql = 'CREATE TABLE `value` (
            `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY
        );';
        $result = get_db()->prepare($sql)->execute();
        $this->assertTrue($result);
        $params = array(
            'database' => array(
                'type' => 'share',
                'prefix' => 's_' . get_db()->prefix,
            ),
        ) + $this->_defaultParams;
        $processor = new UpgradeToOmekaS_Processor_CoreServer();
        $processor->setParams($params);
        $result = $processor->checkConfig();
        $this->assertEquals(1, count($result));
        $this->assertEquals('Some names of tables of Omeka S or its modules are existing in the database of Omeka Classic.', $result[0]);
    }

    /**
     * @note Some checks require that the free space is greater than 1GB.
     * @note Some checks require to define the document root.
     * @note Some checks fail if the basedir has not been cleaned after a crash.
     * @note Some checks fail if omeka-s.zip is not in the temp folder.
     */
    public function testCheckConfigFileSystemBase()
    {
        $params = array(
            'base_dir' => $this->_baseDir,
            'files_type' => 'copy',
        ) + $this->_defaultParams;
        $processor = new UpgradeToOmekaS_Processor_CoreServer();
        $processor->setParams($params);
        $this->assertEquals('copy', $processor->getParam('files_type'));
        $result = $processor->precheckConfig();
        $this->assertEmpty($result);
        $result = $processor->checkConfig();
        $this->assertEmpty($result);
    }

    public function testCheckConfigFileSystemBaseSeparator()
    {
        $params = array(
            'base_dir' => $this->_baseDir . " \/ /\\ ",
            'files_type' => 'copy',
        ) + $this->_defaultParams;
        $processor = new UpgradeToOmekaS_Processor_CoreServer();
        $processor->setParams($params);
        $this->assertEquals($this->_baseDir, $processor->getParam('base_dir'));
    }

    public function testCheckConfigFileSystemBadBase()
    {
        $params = array(
            'base_dir' => dirname(BASE_DIR) . DIRECTORY_SEPARATOR,
            'files_type' => 'copy',
        ) + $this->_defaultParams;
        $processor = new UpgradeToOmekaS_Processor_CoreServer();
        $processor->setParams($params);
        $result = $processor->checkConfig();
        $this->assertEquals(1, count($result));

        $params = array(
            'base_dir' => BASE_DIR . DIRECTORY_SEPARATOR,
            'files_type' => 'copy',
        ) + $this->_defaultParams;
        $processor = new UpgradeToOmekaS_Processor_CoreServer();
        $processor->setParams($params);
        $result = $processor->checkConfig();
        $this->assertEquals(1, count($result));
    }

    public function testCheckConfigFileSystemBadBase2()
    {
        $this->_createBaseDir();
        $file = $this->_baseDir . DIRECTORY_SEPARATOR . md5(rtrim(strtok(substr(microtime(), 2), ' '), '0'));
        touch($file);
        $params = array(
            'base_dir' => $file,
            'files_type' => 'copy',
        ) + $this->_defaultParams;
        $processor = new UpgradeToOmekaS_Processor_CoreServer();
        $processor->setParams($params);
        $result = $processor->checkConfig();
        $this->assertEquals(1, count($result));

        // Base dir is not empty.
        $params = array(
            'base_dir' => $this->_baseDir,
            'files_type' => 'copy',
        ) + $this->_defaultParams;
        $processor = new UpgradeToOmekaS_Processor_CoreServer();
        $processor->setParams($params);
        $result = $processor->checkConfig();
        $this->assertEquals(1, count($result));
    }

    public function testCheckConfigFileSystemBadBase3()
    {
        $this->_createBaseDir();
        chmod($this->_baseDir, 0555);
        $params = array(
            'base_dir' => $this->_baseDir,
            'files_type' => 'copy',
        ) + $this->_defaultParams;
        $processor = new UpgradeToOmekaS_Processor_CoreServer();
        $processor->setParams($params);
        $result = $processor->checkConfig();
        $this->assertEquals(1, count($result));
    }

    public function testCheckConfigFileSystemSize()
    {
        $this->_createBaseDir();
        $params = array(
            'base_dir' => $this->_baseDir,
            'files_type' => 'copy',
        ) + $this->_defaultParams;
        $processor = new UpgradeToOmekaS_Processor_CoreServer();
        $processor->setParams($params);
        $result = $processor->checkConfig();
        $this->assertEmpty($result);
        $processor->minTempDirSize = 1000000000000000;
        $result = $processor->checkConfig();
        $this->assertEquals(1, count($result));
        $this->assertEquals('The free size of the temp directory should be greater than 1000000000MB.', $result[0]);

        $processor = new UpgradeToOmekaS_Processor_CoreServer();
        $processor->setParams($params);
        $processor->minDestinationSize = 1000000000000000;
        $result = $processor->checkConfig();
        $this->assertEquals(1, count($result));
        $this->assertEquals('The free size of the base dir should be greater than 1000000000MB.', $result[0]);

        $processor = new UpgradeToOmekaS_Processor_CoreServer();
        $processor->setParams($params);
        $processor->minOmekaSemanticSize = 1000000000000000;
        $result = $processor->checkConfig();
        $this->assertEquals(1, count($result));
        $this->assertRegexp('/A minimum size of [0-9]+MB is required in the base dir\, only [0-9]+MB is available\./', $result[0]);

        // Determine the minimum Omeka Semantic size from the result.
        $minSize = (strtok(substr($result[0], strlen('A minimum size of ')), 'M') * 1000000) - 1000000000000000;
        $destinationFreeSize = disk_free_space($this->_baseDir);

        $processor = new UpgradeToOmekaS_Processor_CoreServer();
        $processor->setParams($params);
        $processor->minOmekaSemanticSize = $destinationFreeSize - $minSize;
        $result = $processor->checkConfig();
        $this->assertEquals(1, count($result));
        $this->assertRegexp('/A minimum size of [0-9]+MB \([0-9]+MB for the files and [0-9]+MB for the database\) is required in the base dir\, only [0-9]+MB is available\./', $result[0]);

        $processor = new UpgradeToOmekaS_Processor_CoreServer();
        $processor->setParams($params);
        $processor->minOmekaSemanticSize = $destinationFreeSize - 2 * $minSize;
        $result = $processor->checkConfig();
        $this->assertEmpty($result);
    }

    // Methods used for the upgrade.

    public function testCreateDirectory()
    {
        $this->assertFalse(file_exists($this->_baseDir));
        $processor = $this->_prepareProcessor('Core/Server', null, array(), false);
        $processor->processMethods = array('_createDirectory');
        $result = $processor->process();
        $this->assertEmpty($result);
        $result = file_exists($this->_baseDir);
        $this->assertTrue($result);
        $this->assertTrue(UpgradeToOmekaS_Common::isDirEmpty($this->_baseDir));
        UpgradeToOmekaS_Common::removeDir($this->_baseDir, true);
    }

    public function testDownloadOmekaS()
    {
        $processor = $this->_prepareProcessor('Core/Server', null, array(), false);
        $processor->processMethods = array('_downloadOmekaS');
        // TODO There are two different tests, with and without downloading.
        $path = $this->_zippath;
        $fileExists = file_exists($path);
        if ($fileExists) {
            $filesize = filesize($path);
            if (empty($filesize)) {
                $this->markTestIncomplete(__('An empty file "%s" exists: replace it by the true omeka-s.zip.', $path));
            }
            elseif ($filesize != $processor->module['size']
                    || md5_file($path) != $processor->module['md5']
                ) {
                $this->markTestSkipped(__('A file "%s" exists and this is not a test one.', $path));
            }
            else {
                $result = $processor->process();
                $this->assertEmpty($result);
            }
        }
        else {
            touch($path);
            $this -> expectException(UpgradeToOmekaS_Exception::class);
            $result = $processor->process();
        }
    }

    // The next steps require that omeka-s.zip is available in the temp folder.

    public function testUnzipOmekaS()
    {
        $this->_checkDownloadedOmekaS();
        $processor = $this->_prepareProcessor('Core/Server', null, array());
        $processor->processMethods = array('_unzipOmekaS');
        $result = $processor->process();
        $this->assertEmpty($result);

        $baseDir = $processor->getParam('base_dir');
        $indexFile = $baseDir . DIRECTORY_SEPARATOR . 'index.php';
        $this->assertEquals('13ceb3fef1651b438721315340702ce4', md5_file($indexFile));
    }
}
