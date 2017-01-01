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
            $this->_delTree($path);
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
        // An important internal check.
        if (empty($this->_isBaseDirCreated) || $this->_isBaseDirCreated != $this->_baseDir) {
            return;
        }
        $path = $this->_baseDir;
        if (file_exists($path)) {
            chmod($this->_baseDir, 0755);
            $this->_delTree($path);
        }
    }

    /**
     * Recursively delete a folder.
     *
     * @link https://php.net/manual/en/function.rmdir.php#110489
     * @param string $dir
     */
    protected function _delTree($dir)
    {
        $dir = realpath($dir);
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            (is_dir($path)) ? $this->_delTree($path) : unlink($path);
        }
        return rmdir($dir);
    }
}
