<?php
/**
 * @copyright Daniel Berthereau, 2017
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt
 * @package UpgradeToOmekaS
 */

/**
 * Base class for UpgradeToOmekaS tests.
 */
class UpgradeToOmekaS_Test_AppTestCase extends Omeka_Test_AppTestCase
{
    const PLUGIN_NAME = 'UpgradeToOmekaS';

    protected $_zippath;
    protected $_baseDir;
    protected $_isBaseDirCreated = false;

    public function setUp()
    {
        parent::setUp();

        $pluginHelper = new Omeka_Test_Helper_Plugin;
        $pluginHelper->setUp(self::PLUGIN_NAME);

        // Omeka S requires Apache.
        $_SERVER['SERVER_SOFTWARE'] = 'Apache 2.4';
        // Set Omeka dir the base dir of the server.
        // $_SERVER['DOCUMENT_ROOT'] = sys_get_temp_dir();

        //This is where the install test will be done by default.
        $this->_baseDir = BASE_DIR . DIRECTORY_SEPARATOR . 'Semantic';
        $test = file_exists($this->_baseDir)
            ? __('The test base dir %s must not exist.', $this->_baseDir)
            : __('You should remove it.');
        $this->assertEquals($test, __('You should remove it.'));

        // This is where the downloaded package omeka-s.zip is saved.
        // TODO Move it in the main setup of the tests.
        $this->_zippath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'omeka-s.zip';

        // To clear cache after a crash.
        $this->_removeStubPlugin();
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
        $result = mkdir($path);
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
        $processor = new UpgradeToOmekaS_Processor_Core();
        $omekasTables = $processor->getMerged('_tables_omekas');
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
}
