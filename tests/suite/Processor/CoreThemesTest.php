<?php

class UpgradeToOmekaS_Processor_CoreThemesTest extends UpgradeToOmekaS_Test_AppTestCase
{
    protected $_isAdminTest = true;

    public function setUp()
    {
        parent::setUp();

        // Authenticate and set the current user.
        $this->user = $this->db->getTable('User')->find(1);
        $this->_authenticateUser($this->user);
    }

    public function testCopyAssets()
    {
        $this->_installDatabase();

        $processor = new UpgradeToOmekaS_Processor_CoreThemes();
        $processor->setParams($this->_defaultParams);
        $target = $processor->getTarget();
        $targetDb = $target->getDb();

        // $result = $this->invokeMethod($processor, '_copyAssets');
        $this->markTestIncomplete();
    }

    public function testUpgradeConfigTheme()
    {
        $path = dirname(dirname(__FILE__))
            . DIRECTORY_SEPARATOR . '_files'
            . DIRECTORY_SEPARATOR . 'upgradedTheme';

        // Copy theme in the temp folder.
        $tmpPath = $this->_tmpdir . DIRECTORY_SEPARATOR . 'UpgradedTheme';
        UpgradeToOmekaS_Common::removeDir($tmpPath, true);
        UpgradeToOmekaS_Common::copyDir($path, $tmpPath, true);

        $processor = new UpgradeToOmekaS_Processor_CoreThemes();
        $result = $this->invokeMethod($processor, '_upgradeConfigTheme', array($tmpPath));

        $resultPath = $tmpPath
            . DIRECTORY_SEPARATOR . 'config'
            . DIRECTORY_SEPARATOR . 'theme.ini';
        $fileExists = file_exists($resultPath);
        $this->assertTrue($fileExists);

        $resultIni = parse_ini_file($resultPath, INI_SCANNER_RAW);
        $this->assertEquals('Omeka\Form\Element\Asset', $resultIni['config']['elements.logo.type']);
        $this->assertEquals('Footer Content', $resultIni['config']['elements.footer.options.label']);
        $this->assertFalse(isset($resultIni['config']['elements.footer_text']));
    }
}
