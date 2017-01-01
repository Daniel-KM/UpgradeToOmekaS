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
        $processor->omekaSemanticMinDb = array(
            'mariadb' => '1005.5.3',
            'mysql' => '1005.5.3',
        );
        $result = $processor->precheckConfig();
        $this->assertEquals(1, count($result));
        $this->assertContains('The current release requires at least MariaDB 1005.5.3 or Mysql 1005.5.3', $result[0]);
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

    public function testCheckConfigDatabaseSeparateBadUsername()
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
            'database_type' => 'separate',
            'database_host' => $dbHost,
            'database_username' => 'foo',
            'database_password' => '',
            'database_name' => $dbName,
            'database_prefix' => '',
        );
        $processor = new UpgradeToOmekaS_Processor_Core();
        $processor->setParams($params);
        $this->expectException(Zend_Db_Adapter_Exception::class);
        $result = $processor->checkConfig();
        // $this->assertEquals(3, count($result));
        // $this->assertContains('The database name should be different from the Omeka Classic one when the databases are separate, but on the same server.', $result[0]);
    }

    public function testCheckConfigDatabaseSeparateBadEmptyDbname()
    {
        $params = array(
            'database_type' => 'separate',
            'database_prefix' => '',
        );
        $processor = new UpgradeToOmekaS_Processor_Core();
        $processor->setParams($params);
        $this->expectException(Zend_Db_Adapter_Exception::class);
        $result = $processor->checkConfig();
        // $this->assertEquals(3, count($result));
        // $this->assertEquals('The param "name" should be set when the databases are separate.', $result[2]);
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
        $sql = 'CREATE TABLE `vocabulary` (
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
     * @note This check fails if the basedir has not been cleaned after a crash.
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
        $processor = $this->_prepareProcessor(null, array('_createDirectory'), false);
        $result = $processor->process();
        $this->assertEmpty($result);
        $result = file_exists($this->_baseDir);
        $this->assertTrue($result);
        $this->assertTrue(UpgradeToOmekaS_Common::isDirEmpty($this->_baseDir));
        $this->_delTree($this->_baseDir);
    }

    public function testDownloadOmekaS()
    {
        $processor = $this->_prepareProcessor(null, array('_downloadOmekaS'), false);
        // TODO There are two different tests, with and without downloading.
        $path = $this->_zippath;
        $exists = file_exists($path);
        if ($exists) {
            if (filesize($path) == 0) {
                $this->markTestIncomplete(__('An empty file "%s" exists: replace it by the true omeka-s.zip.', $path));
            }
            elseif (filesize($path) != $processor->omekaSemantic['size']
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
            $this -> expectException (UpgradeToOmekaS_Exception::class);
            $result = $processor->process();
        }
    }

    /* The next steps require that omeka-s.zip is available in the temp folder. */

    public function testUnzipOmekaS()
    {
        $this->_checkDownloadedOmekaS();
        $processor = $this->_prepareProcessor(null, array('_unzipOmekaS'));
        $result = $processor->process();
        $baseDir = $processor->getParam('base_dir');
        $this->assertEmpty($result);
        $indexFile = $baseDir . DIRECTORY_SEPARATOR . 'index.php';
        $this->assertEquals('13ceb3fef1651b438721315340702ce4', md5_file($indexFile));
    }

    public function testConfigOmekaS()
    {
        $this->_checkDownloadedOmekaS();
        $processor = $this->_prepareProcessor(null, array('_unzipOmekaS', '_configOmekaS'));
        $result = $processor->process();
        $this->assertEmpty($result);
    }

    public function testInstallOmekaS()
    {
        $this->_checkDownloadedOmekaS();
        $processor = $this->_prepareProcessor(
            array('user' => $this->user),
            array('_unzipOmekaS', '_configOmekaS', '_installOmekaS'));
        $result = $processor->process();

        $targetDb = $processor->getTargetDb();
        $sql = 'SELECT COUNT(*) FROM resource_class;';
        $result = $targetDb->fetchOne($sql);
        $this->assertEquals(105, $result);
        $sql = 'SELECT local_name FROM resource_class WHERE id = 105;';
        $result = $targetDb->fetchOne($sql);
        $this->assertEquals('OnlineChatAccount', $result);
        $sql = 'SELECT local_name FROM property WHERE id = 184;';
        $result = $targetDb->fetchOne($sql);
        $this->assertEquals('status', $result);
        $sql = 'SELECT value FROM setting WHERE id = "administrator_email";';
        $result = $targetDb->fetchOne($sql);
        $this->assertEquals($this->user->email, json_decode($result));
    }

    public function testConvertLocalConfig()
    {
        // TODO Check modified config.ini, for example for priority or locale.
        $this->_checkDownloadedOmekaS();
        $processor = $this->_prepareProcessor(
            array('user' => $this->user),
            array('_unzipOmekaS', '_configOmekaS', '_installOmekaS', '_convertLocalConfig'));
        $result = $processor->process();

        $localConfigPhp = $processor->getFullPath('config/local.config.php');
        $localConfig = file_get_contents($localConfigPhp);
        $this->assertContains("'use_externals' => false", $localConfig);
        $this->assertContains("'priority' => \Zend\Log\Logger::DEBUG,", $localConfig);
        // $this->assertContains('fr_QC', $localConfig);
    }

    public function testImportUsers()
    {
        $user = new User;
        $user->name = 'foo';
        $user->email = 'bar@foo.com';
        $user->active = '1';
        $user->role = 'none';
        $user->username = 'foo';
        $user->save();

        $this->_checkDownloadedOmekaS();
        $processor = $this->_prepareProcessor(
            array('user' => $this->user),
            array('_unzipOmekaS', '_configOmekaS', '_installOmekaS', '_importUsers'));
        $result = $processor->process();

        $targetDb = $processor->getTargetDb();

        // There are 4 users by default, 2 supers, 1 admin, 1 "none".
        $totalRecords = total_records('User');

        $sql = 'SELECT COUNT(*) FROM user;';
        $result = $targetDb->fetchOne($sql);
        $this->assertEquals($totalRecords - 1, $result);
    }

    public function testImportSite()
    {
        $this->_checkDownloadedOmekaS();
        $processor = $this->_prepareProcessor(
            array('user' => $this->user),
            array('_unzipOmekaS', '_configOmekaS', '_installOmekaS', '_importUsers', '_importSite'));
        $result = $processor->process();

        $targetDb = $processor->getTargetDb();

        $sql = 'SELECT COUNT(*) FROM site;';
        $result = $targetDb->fetchOne($sql);
        $this->assertEquals(1, $result);
        $sql = 'SELECT * FROM site;';
        $result = $targetDb->fetchRow($sql);
        $this->assertEquals($this->user->id, $result['owner_id']);

        $title = get_option('site_title');
        $this->assertEquals($title, $result['title']);
        $slugDirect = str_replace(' ', '-', strtolower($title));
        $slug = $processor->getSiteSlug();
        $this->assertEquals($slugDirect, $slug);
    }

    public function hookPublicNavigationMain($nav)
    {
        add_filter('public_navigation_main', array($this, 'hookPublicNavigationMain'));

        $nav[] = array(
            'label' => 'Foo',
            'uri' => url('foo'),
        );
        $nav[] = array(
            'label' => 'Search bar',
            'uri' => url('items/search'),
        );
        $nav[] = array(
            'label' => 'Bar',
            'uri' => 'https://example.org/path/to/bar?a=z&b=y#here',
        );
        return $nav;
    }

    public function testImportSiteNavigationMain()
    {
        add_filter('public_navigation_main', array($this, 'hookPublicNavigationMain'));

        $this->_checkDownloadedOmekaS();
        $processor = $this->_prepareProcessor(
            array('user' => $this->user),
            array('_unzipOmekaS', '_configOmekaS', '_installOmekaS', '_importUsers', '_importSite'));
        $result = $processor->process();
        $targetDb = $processor->getTargetDb();
        $slug = $processor->getSiteSlug();
        $sql = 'SELECT * FROM site;';
        $result = $targetDb->fetchRow($sql);
        $nav = json_decode($result['navigation'], true);

        $this->assertEquals(5, count($nav));
        $this->assertEquals('Foo', $nav[2]['data']['label']);
        $this->assertEquals('/foo', $nav[2]['data']['url']);
        $this->assertEquals('Search bar', $nav[3]['data']['label']);
        $this->assertEquals('/s/' . $slug . '/item/search', $nav[3]['data']['url']);
        $this->assertEquals('Bar', $nav[4]['data']['label']);
        // TODO Omeka doesn't allow fragment?
        // $this->assertEquals('https://example.org/path/to/bar?a=z&b=y#here', $nav[4]['data']['url']);
        $this->assertEquals('https://example.org/path/to/bar?a=z&b=y#', $nav[4]['data']['url']);
    }

    protected function _prepareProcessor($params = null, $methods = array(), $checkDir = true)
    {
        set_option('upgrade_to_omeka_s_process_status', Process::STATUS_IN_PROGRESS);
        $defaultParams = array(
            'database_type' => 'share',
            'database_prefix' => 'omekas_',
            'base_dir' => $this->_baseDir,
            'files_type' => 'copy',
        );
        if (is_null($params)) {
            $params = $defaultParams;
        }
        // Add and replace values.
        else {
            $params = array_merge($defaultParams, $params);
        }

        $processor = new UpgradeToOmekaS_Processor_Core();
        $processor->setParams($params);

        if ($methods) {
            $processor->processMethods = $methods;
        }

        if ($checkDir) {
            $baseDir = $processor->getParam('base_dir');
            $result = !file_exists($baseDir) || UpgradeToOmekaS_Common::isDirEmpty($baseDir);
            $this->assertTrue($result);
            $this->_isBaseDirCreated = true;
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
