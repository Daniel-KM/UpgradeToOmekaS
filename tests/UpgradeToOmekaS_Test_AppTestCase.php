<?php
/**
 * @copyright Daniel Berthereau, 2017
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt
 * @package UpgradeToOmekaS
 */

/**
 * Base class for UpgradeToOmekaS tests.
 *
 * @todo True unit cases.
 */
class UpgradeToOmekaS_Test_AppTestCase extends Omeka_Test_AppTestCase
{
    const PLUGIN_NAME = 'UpgradeToOmekaS';

    protected $_tmpdir;
    protected $_zippath;
    protected $_baseDir;
    protected $_isBaseDirCreated = false;

    protected $_processor;
    protected $_processorName;

    protected $_defaultParams = array(
        'database' => array(
            'type' => 'share',
            'prefix' => 'omekas_test_unit_',
        ),
        // Set during setup.
        'base_dir' => null,
        'WEB_ROOT' => WEB_ROOT,
        'files_type' => 'copy',
    );

    /**
     * Call protected/private method of a class.
     *
     * @param object &$object    Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array  $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     */
    public function invokeMethod(&$object, $methodName, array $parameters = array())
    {
        $reflection = new ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }

    public function setUp()
    {
        parent::setUp();

        $pluginHelper = new Omeka_Test_Helper_Plugin;
        $pluginHelper->setUp(self::PLUGIN_NAME);

        $this->_tmpdir = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR . 'UpgradeToOmekaS_unit_test';

            // This is where the downloaded package omeka-s.zip is saved.
            // TODO Move it in the main setup of the tests.
        $this->_zippath = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR . 'omeka-s.zip';

        // Omeka S requires Apache, even if this just for tests.
        $_SERVER['SERVER_SOFTWARE'] = 'Apache 2.4';

        set_option('upgrade_to_omeka_s_document_root', $this->_tmpdir);
        // Set Omeka dir the base dir of the server.
        // $_SERVER['DOCUMENT_ROOT'] = sys_get_temp_dir();

        //This is where the install test will be done by default.
        $this->_baseDir = $this->_tmpdir
            . DIRECTORY_SEPARATOR . 'Semantic';
        UpgradeToOmekaS_Common::removeDir($this->_baseDir, true);
        $test = file_exists($this->_baseDir)
            ? __('The test base dir %s must not exist.', $this->_baseDir)
            : __('You should remove it.');
        $this->assertEquals($test, __('You should remove it.'));

        $this->_defaultParams['base_dir'] = $this->_baseDir;

        // To clear cache after a crash.
        $this->_removeStubPlugin();

        // The prechecks fail when there are some files in "files/original".
        // So a precheck is done to get the total default of answers.
        $path = FILES_DIR . DIRECTORY_SEPARATOR . 'original';
        $totalFiles = UpgradeToOmekaS_Common::countFilesInDir($path);
    }

    public function tearDown()
    {
        $this->_removeStubPlugin();
        $this->_removeBaseDir();
        $this->_removeEmptyDownloadedFile();
        $this->_removeTableOmekaS();
        $this->_removeRecords('Process');
        $this->_removeRecords('User');
        $this->_removeRecords('Item');
        $this->_removeRecords('Collection');

        parent::tearDown();
    }

    protected function _setupPlugin()
    {
        $this->assertNotEmpty($this->_processorName);

        // Authenticate and set the current user.
        $this->user = $this->db->getTable('User')->find(1);
        $this->_authenticateUser($this->user);

        $processorName = $this->_processorName;
        $this->_processor = new $processorName();

        $pluginHelper = new Omeka_Test_Helper_Plugin;
        try {
            $pluginHelper->setUp($this->_processor->pluginName);
        } catch (Omeka_Plugin_Loader_Exception $e) {
            $this->markTestSkipped(__('The plugin "%s" must be available to test it.',
                $this->_processor->pluginName));
        }

        $this->_installDatabase();
    }

    protected function _installDatabase()
    {
        $path = dirname(__FILE__)
            . DIRECTORY_SEPARATOR . 'suite'
            . DIRECTORY_SEPARATOR . '_files'
            . DIRECTORY_SEPARATOR . 'schema.sql';
        $sqls = file_get_contents($path);
        $sqls = array_filter(explode(';' . PHP_EOL, $sqls));
        foreach ($sqls as $sql) {
            $result = get_db()->prepare($sql)->execute();
        }
    }

    protected function _createStubPlugin()
    {
        $path = PLUGIN_DIR . DIRECTORY_SEPARATOR . 'Stub' . DIRECTORY_SEPARATOR . 'plugin.ini';
        $this->assertFalse(file_exists($path));

        $result = mkdir(dirname($path));
        $this->assertTrue($result);
        $content = <<<PLUGIN
[info]
name = "Stub"
author = "Daniel Berthereau"
description = "Stub description"
license = "CeCILL v2.1"
link = "https://github.com/Daniel-KM/UpgradeToOmekaS"
support_link = "https://github.com/Daniel-KM/UpgradeToOmekaS/issues"
optional_plugins = ""
version = "2.2"
omeka_minimum_version = "2.2.2"
omeka_target_version = "2.5"
tags = "archive, upgrade"
PLUGIN;
        $result = file_put_contents($path, $content);
        $this->assertTrue((boolean) $result);
    }

    protected function _removeStubPlugin()
    {
        $path = PLUGIN_DIR . DIRECTORY_SEPARATOR . 'Stub' . DIRECTORY_SEPARATOR . 'plugin.ini';
        $path = dirname($path);
        if (file_exists($path)) {
            UpgradeToOmekaS_Common::removeDir($path, true);
        }
    }

    protected function _createBaseDir()
    {
        $path = $this->_baseDir;
        $this->assertFalse(file_exists($path));
        $result = UpgradeToOmekaS_Common::createDir($path);
        $this->assertTrue($result);
        $this->_isBaseDirCreated = $this->_baseDir;
    }

    protected function _removeBaseDir()
    {
        $path = rtrim($this->_baseDir, '/ ');
        // An important internal check.
        if (empty($this->_isBaseDirCreated)
                || $path == BASE_DIR
                || $path == dirname(BASE_DIR)
                || $path == dirname(dirname(BASE_DIR))
                || $path == dirname(dirname(dirname(BASE_DIR)))
                || $path == dirname(dirname(dirname(dirname(BASE_DIR))))
            ) {
            return;
        }
        if (file_exists($path)) {
            chmod($path, 0755);
            UpgradeToOmekaS_Common::removeDir($path, true);
        }
    }

    protected function _removeEmptyDownloadedFile()
    {
        $path = $this->_zippath;
        if (file_exists($path) && filesize($path) === 0) {
            unlink($path);
        }
    }

    protected function _removeTableOmekaS()
    {
        $processor = new UpgradeToOmekaS_Processor_Base();
        // $target = $processor->getTarget();
        // $result = $target->removeTables();
        $omekasTables = $processor->getMergedList('tables');
        $sql = 'SET foreign_key_checks = 0;';
        $result = get_db()->query($sql);
        $sql = 'DROP TABLE IF EXISTS `' . implode('`, `', $omekasTables) . '`;';
        $result = get_db()->query($sql);
        $sql = 'SET foreign_key_checks = 1;';
        $result = get_db()->query($sql);
    }

    protected function _removeRecords($recordType)
    {
        $records = get_records($recordType, array(), 0);
        foreach ($records as $record) {
            $record->delete();
        }
    }

    protected function _prepareProcessor(
        $processorName,
        $params = null,
        $methods = array(),
        $checkDir = true,
        $isProcessing = true
    ) {
        set_option('upgrade_to_omeka_s_process_status', Process::STATUS_IN_PROGRESS);
        if (is_null($params)) {
            $params = $this->_defaultParams;
        }
        // Add and replace values.
        else {
            $params = array_merge($this->_defaultParams, $params);
        }

        $processorBase = new UpgradeToOmekaS_Processor_Base();
        $processorBase->setParams($params);
        $processors = $processorBase->getProcessors();
        $this->assertTrue(isset($processors[$processorName]));

        if ($checkDir) {
            $baseDir = $processorBase->getParam('base_dir');
            $result = !file_exists($baseDir) || UpgradeToOmekaS_Common::isDirEmpty($baseDir);
            $this->assertTrue($result);
            $this->_isBaseDirCreated = true;
        }

        set_option('upgrade_to_omeka_s_process_status',
            $isProcessing ? Process::STATUS_IN_PROGRESS : '');

        if ($methods) {
            foreach ($methods as $method) {
                foreach ($processors as $name => $processor) {
                    if (in_array($method, $processor->processMethods)) {
                        $defaultmethods = $processor->processMethods;
                        $processor->processMethods = array($method);
                        $processor->setParams($params);
                        $processor->process();
                        $processor->processMethods = $defaultmethods;
                    }
                }
            }
        }
        // In the case there is no method.
        else {
            $processor = $processors[$processorName];
            $processor->setParams($params);
            if ($checkDir) {
                $baseDir = $processor->getParam('base_dir');
                $result = !file_exists($baseDir) || UpgradeToOmekaS_Common::isDirEmpty($baseDir);
                $this->assertTrue($result);
                $this->_isBaseDirCreated = true;
            }
        }

        return $processors[$processorName];
    }

    protected function _checkDownloadedOmekaS()
    {
        $path = $this->_zippath;
        if (!file_exists($path)) {
            $this->markTestSkipped(__('The test requires that the file "omeka-s.zip" is saved in temp folder.'));
        }
        // Check correct file.
        else {
            $processor = new UpgradeToOmekaS_Processor_CoreServer();
            if (filesize($path) != $processor->module['size']
                   || md5_file($path) != $processor->module['md5']
                ) {
                $this->markTestSkipped(__('A file "%s" exists and this is not a test one.', $path));
            }
        }
    }

    /**
     * Helper to create an item directly in the target base.
     *
     * @param Target $target
     * @return id
     */
    protected function _createItemViaDb($target)
    {
        $targetDb = $target->getDb();

        $toInserts = array();

        $toInsert = array();
        $toInsert['id'] = null;
        $toInsert['owner_id'] = null;
        $toInsert['resource_class_id'] = null;
        $toInsert['resource_template_id'] = null;
        $toInsert['is_public'] = 0;
        $toInsert['created'] = date('Y-m-d H:i:s');
        $toInsert['modified'] = date('Y-m-d H:i:s');
        $toInsert['resource_type'] = 'Omeka\Entity\Item';
        $toInserts['resource'][] = $target->cleanQuote($toInsert);

        $id = 'LAST_INSERT_ID()';

        $toInsert = array();
        $toInsert['id'] =$id;
        $toInserts['item'][] = $target->cleanQuote($toInsert, 'id');

        // No properties.

        $target->insertRowsInTables($toInserts);

        // Get the id.
        $sql = 'SELECT MAX(id) FROM item;';
        $itemId = $targetDb->fetchOne($sql);

        return (integer) $itemId;
    }
}
