<?php

/**
 * @note Some check require that the free space is greater than 1GB.
 */
class UpgradeToOmekaS_Processor_CoreTest extends UpgradeToOmekaS_Test_AppTestCase
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
        $processor = new UpgradeToOmekaS_Processor_Core();
        $result = $processor->precheckConfig();
        $this->assertEmpty($result);
    }

    public function testPrecheckConfigServer()
    {
        $_SERVER['SERVER_SOFTWARE'] = 'Windows XP';
        $processor = new UpgradeToOmekaS_Processor_Core();
        $result = $processor->precheckConfig();
        $this->assertEquals(1, count($result));
        $this->assertContains('According to the readme of Omeka Semantic, the server should be an Apache one.', $result[0]);
        }

    public function testPrecheckConfigBadVersions()
    {
        $processor = new UpgradeToOmekaS_Processor_Core();
        $processor->minVersion = '3.6';
        $processor->maxVersion = '1.5';
        $result = $processor->precheckConfig();
        $this->assertEquals(2, count($result));
        $this->assertContains('The current release requires at most Omeka 1.5, current is', $result[1]);
    }

    public function testPrecheckConfigBadDatabaseVersions()
    {
        $processor = new UpgradeToOmekaS_Processor_Core();
        $processor->minVersionMysql = '15.5.3';
        $processor->minVersionMariadb = '15.5.3';
        $result = $processor->precheckConfig();
        $this->assertEquals(1, count($result));
        $this->assertContains('The current release requires at least MariaDB 15.5.3 or Mysql 15.5.3', $result[0]);
    }

    public function testPrecheckConfigExistingJob()
    {
        $job = new Process();
        $job->class = 'Omeka_Job_Process_Wrapper';
        $job->user_id = $this->user->id;
        $job->status = Process::STATUS_STARTING;
        $job->save();

        $processor = new UpgradeToOmekaS_Processor_Core();
        $result = $processor->precheckConfig();
        $this->assertEquals(1, count($result));
        $this->assertEquals('1 job is running.', $result[0]);

        $job->delete();
        $processor = new UpgradeToOmekaS_Processor_Core();
        $result = $processor->precheckConfig();
        $this->assertEmpty($result);
    }

    public function testCheckConfigDatabase()
    {
        $params = array(
            'database_type' => 'share',
            'database_prefix' => 's_' . get_db()->prefix,
        );
        $processor = new UpgradeToOmekaS_Processor_Core();
        $processor->setParams($params);
        $result = $processor->checkConfig();
        $this->assertEquals(2, count($result));
        $prefix = $processor->getParam('database_prefix');
        $this->assertEquals('s_omeka_', $prefix);

        $params = array(
            'database_type' => 'foo',
        );
        $processor = new UpgradeToOmekaS_Processor_Core();
        $processor->setParams($params);
        $result = $processor->checkConfig();
        $this->assertEquals(3, count($result));
        $this->assertEquals('The type of database "foo" is not supported.', $result[0]);
    }

    public function testCheckConfigBadDatabaseSeparate()
    {
        $params = array(
            'database_type' => 'separate',
            'database_host' => 'localhost',
            'database_username' => 'foo',
            'database_password' => '',
            'database_name' => 'bar',
            'database_prefix' => '',
        );
        $processor = new UpgradeToOmekaS_Processor_Core();
        $processor->setParams($params);
        $result = $processor->checkConfig();
        $this->assertEquals(3, count($result));
        $this->assertContains('Cannot access to the database "bar"', $result[0]);

        $config = get_db()->getAdapter()->getConfig();
        $dbName = $config['dbname'];
        $dbHost = $config['host'];

        $params = array(
            'database_type' => 'separate',
            'database_host' => $dbHost,
            'database_username' => 'foo',
            'database_password' => '',
            'database_name' => $dbName,
            'database_prefix' => '',
        );
        $processor = new UpgradeToOmekaS_Processor_Core();
        $processor->setParams($params);
        $result = $processor->checkConfig();
        $this->assertEquals(4, count($result));
        $this->assertContains('The database name should be different from the Omeka Classic one when the databases are separate, but on the same server.', $result[0]);

        $params = array(
            'database_type' => 'separate',
            'database_prefix' => '',
        );
        $processor = new UpgradeToOmekaS_Processor_Core();
        $processor->setParams($params);
        $result = $processor->checkConfig();
        $this->assertEquals(6, count($result));
        $this->assertEquals('The param "name" should be set when the databases are separate.', $result[2]);
    }

    public function testCheckConfigBadDatabaseShare()
    {
        $params = array(
            'database_type' => 'share',
            'database_prefix' => '',
        );
        $processor = new UpgradeToOmekaS_Processor_Core();
        $processor->setParams($params);
        $result = $processor->checkConfig();
        $this->assertEquals(3, count($result));
        $this->assertEquals('A database prefix is required when the database is shared.', $result[0]);

        $params = array(
            'database_type' => 'share',
            'database_prefix' => get_db()->prefix,
        );
        $processor = new UpgradeToOmekaS_Processor_Core();
        $processor->setParams($params);
        $result = $processor->checkConfig();
        $this->assertEquals(3, count($result));
        $this->assertEquals('The database prefix should be different from the Omeka Classic one when the database is shared.', $result[0]);
    }

    public function testCheckConfigBadDatabaseShareTableExists()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `vocabulary` (
            `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY
        );';
        $result = get_db()->prepare($sql)->execute();
        $this->assertTrue($result);
        $params = array(
            'database_type' => 'share',
            'database_prefix' => 's_' . get_db()->prefix,
        );
        $processor = new UpgradeToOmekaS_Processor_Core();
        $processor->setParams($params);
        $result = $processor->checkConfig();
        $this->assertEquals(3, count($result));
        $this->assertEquals('Some names of tables of Omeka S are existing in the database of Omeka Classic.', $result[0]);
    }

    /**
     * @note Some check require that the free space is greater than 1GB.
     * @note This check requires to define the document root.
     */
    public function testCheckConfigFileSystemBase()
    {
        $params = array(
            'database_type' => 'share',
            'database_prefix' => 'omekas_',
            'base_dir' => $this->_baseDir,
            'files_type' => 'copy',
        );
        $processor = new UpgradeToOmekaS_Processor_Core();
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
            'database_type' => 'share',
            'database_prefix' => 'omekas_',
            'base_dir' => $this->_baseDir . " \/ /\\ ",
            'files_type' => 'copy',
        );
        $processor = new UpgradeToOmekaS_Processor_Core();
        $processor->setParams($params);
        $this->assertEquals($this->_baseDir, $processor->getParam('base_dir'));
    }

    public function testCheckConfigFileSystemBadBase()
    {
        $params = array(
            'database_type' => 'share',
            'database_prefix' => 'omekas_',
            'base_dir' => dirname(BASE_DIR) . DIRECTORY_SEPARATOR,
            'files_type' => 'copy',
        );
        $processor = new UpgradeToOmekaS_Processor_Core();
        $processor->setParams($params);
        $result = $processor->checkConfig();
        $this->assertEquals(2, count($result));

        $params = array(
            'database_type' => 'share',
            'database_prefix' => 'omekas_',
            'base_dir' => BASE_DIR . DIRECTORY_SEPARATOR,
            'files_type' => 'copy',
        );
        $processor = new UpgradeToOmekaS_Processor_Core();
        $processor->setParams($params);
        $result = $processor->checkConfig();
        $this->assertEquals(2, count($result));
    }

    public function testCheckConfigFileSystemBadBase2()
    {
        $this->_createBaseDir();
        $file = $this->_baseDir . DIRECTORY_SEPARATOR . md5(rtrim(strtok(substr(microtime(), 2), ' '), '0'));
        touch($file);
        $params = array(
            'database_type' => 'share',
            'database_prefix' => 'omekas_',
            'base_dir' => $file,
            'files_type' => 'copy',
        );
        $processor = new UpgradeToOmekaS_Processor_Core();
        $processor->setParams($params);
        $result = $processor->checkConfig();
        $this->assertEquals(2, count($result));

        // Base dir is not empty.
        $params = array(
            'database_type' => 'share',
            'database_prefix' => 'omekas_',
            'base_dir' => $this->_baseDir,
            'files_type' => 'copy',
        );
        $processor = new UpgradeToOmekaS_Processor_Core();
        $processor->setParams($params);
        $result = $processor->checkConfig();
        $this->assertEquals(2, count($result));
    }

    public function testCheckConfigFileSystemBadBase3()
    {
        $this->_createBaseDir();
        chmod($this->_baseDir, 0555);
        $params = array(
            'database_type' => 'share',
            'database_prefix' => 'omekas_',
            'base_dir' => $this->_baseDir,
            'files_type' => 'copy',
        );
        $processor = new UpgradeToOmekaS_Processor_Core();
        $processor->setParams($params);
        $result = $processor->checkConfig();
        $this->assertEquals(2, count($result));
    }

    public function testCheckConfigFileSystemSize()
    {
        $this->_createBaseDir();
        $params = array(
            'database_type' => 'share',
            'database_prefix' => 'omekas_',
            'base_dir' => $this->_baseDir,
            'files_type' => 'copy',
        );
        $processor = new UpgradeToOmekaS_Processor_Core();
        $processor->setParams($params);
        $result = $processor->checkConfig();
        $this->assertEmpty($result);
        $processor->minTempDirSize = 1000000000000000;
        $result = $processor->checkConfig();
        $this->assertEquals(1, count($result));
        $this->assertEquals('The free size of the temp directory should be greater than 1000000000MB.', $result[0]);

        $processor = new UpgradeToOmekaS_Processor_Core();
        $processor->setParams($params);
        $processor->minDestinationSize = 1000000000000000;
        $result = $processor->checkConfig();
        $this->assertEquals(2, count($result));
        $this->assertEquals('The free size of the base dir should be greater than 1000000000MB.', $result[0]);

        $processor = new UpgradeToOmekaS_Processor_Core();
        $processor->setParams($params);
        $processor->minOmekaSemanticSize = 1000000000000000;
        $result = $processor->checkConfig();
        $this->assertEquals(1, count($result));
        $this->assertRegexp('/A minimum size of [0-9]+MB is required in the base dir\, only [0-9]+MB is available\./', $result[0]);

        // Determine the minimum Omeka Semantic size from the result.
        $minSize = (strtok(substr($result[0], strlen('A minimum size of ')), 'M') * 1000000) - 1000000000000000;
        $destinationFreeSize = disk_free_space($this->_baseDir);

        $processor = new UpgradeToOmekaS_Processor_Core();
        $processor->setParams($params);
        $processor->minOmekaSemanticSize = $destinationFreeSize - $minSize;
        $result = $processor->checkConfig();
        $this->assertEquals(1, count($result));
        $this->assertRegexp('/A minimum size of [0-9]+MB \([0-9]+MB for the files and [0-9]+MB for the database\) is required in the base dir\, only [0-9]+MB is available\./', $result[0]);

        $processor = new UpgradeToOmekaS_Processor_Core();
        $processor->setParams($params);
        $processor->minOmekaSemanticSize = $destinationFreeSize - 2 * $minSize;
        $result = $processor->checkConfig();
        $this->assertEmpty($result);
    }

    /* Methods used for the upgrade. */

    public function testCreateDirectory()
    {
        $this->assertFalse(file_exists($this->_baseDir));
        $processor = $this->_prepareProcessor(null, array('_createDirectory'));
        $result = $processor->process();
        $this->assertEmpty($result);
        $result = file_exists($this->_baseDir);
        $this->assertTrue($result);
        $this->assertTrue(UpgradeToOmekaS_Common::isDirEmpty($this->_baseDir));
        $this->_delTree($this->_baseDir);
    }

    public function testDownloadOmekaS()
    {
        $processor = $this->_prepareProcessor(null, array('_downloadOmekaS'));
        // TODO There are two different tests, with and without downloading.
        $path = $this->_zippath;
        $exists = file_exists($path);
        if ($exists) {
            if (filesize($path) != $processor->omekaSemantic['size']
                    || md5_file($path) != $processor->omekaSemantic['md5']
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
            $result = $processor->process();
            unlink($path);
            $this->assertContains('An empty file "omeka-s.zip" exists in the temp d', $result);
        }
    }

    /* The next steps require that omeka-s.zip is available in the temp folder. */

    public function testUnzipOmekaS()
    {
        $this->_checkDownloadedOmekaS();
        $processor = $this->_prepareProcessor(null, array('_unzipOmekaS'));
        $path = $this->_zippath;
        $baseDir = $processor->getParam('base_dir');
        $result = !file_exists($baseDir) || UpgradeToOmekaS_Common::isDirEmpty($baseDir);
        $this->assertTrue($result);
        $this->_isBaseDirCreated = true;
        $result = $processor->process();
        $this->assertEmpty($result);
        $indexFile = $baseDir . DIRECTORY_SEPARATOR . 'index.php';
        $this->assertEquals('13ceb3fef1651b438721315340702ce4', md5_file($indexFile));
    }

    public function testConfigOmekaS()
    {
        // TODO To be moved.
        $this->_checkDownloadedOmekaS();
        $processor = $this->_prepareProcessor(null, array('_unzipOmekaS', '_configOmekaS'));
        $path = $this->_zippath;
        $baseDir = $processor->getParam('base_dir');
        $result = !file_exists($baseDir) || UpgradeToOmekaS_Common::isDirEmpty($baseDir);
        $this->assertTrue($result);
        $this->_isBaseDirCreated = true;
        $result = $processor->process();
        $this->assertEmpty($result);
    }

    public function testInstallOmekaS()
    {
        // TODO To be moved.
        $this->_checkDownloadedOmekaS();
        $processor = $this->_prepareProcessor(null, array('_unzipOmekaS', '_configOmekaS', '_installOmekaS'));
        $path = $this->_zippath;
        $baseDir = $processor->getParam('base_dir');
        $result = !file_exists($baseDir) || UpgradeToOmekaS_Common::isDirEmpty($baseDir);
        $this->assertTrue($result);
        $this->_isBaseDirCreated = true;
        $result = $processor->process();
        $this->assertEmpty($result);
    }

    protected function _prepareProcessor($params = null, $methods = array())
    {
        set_option('upgrade_to_omeka_s_process_status', Process::STATUS_IN_PROGRESS);
        if (is_null($params)) {
            $params = array(
                'database_type' => 'share',
                'database_prefix' => 'omekas_',
                'base_dir' => $this->_baseDir,
                'files_type' => 'copy',
            );
        }

        $processor = new UpgradeToOmekaS_Processor_Core();
        $processor->setParams($params);
        if ($methods) {
            $processor->processMethods = $methods;
        }
        return $processor;
    }

    protected function _checkDownloadedOmekaS()
    {
        $path = $this->_zippath;
        if (!file_exists($path)) {
            $this->markTestSkipped(__('The test requires that the file "omeka-s.zip" is saved in temp folder.'));
        }
        // Check correct file.
        else {
            $processor = new UpgradeToOmekaS_Processor_Core();
            if (filesize($path) != $processor->omekaSemantic['size']
                    || md5_file($path) != $processor->omekaSemantic['md5']
                ) {
                $this->markTestSkipped(__('A file "%s" exists and this is not a test one.', $path));
            }
        }
    }
}
