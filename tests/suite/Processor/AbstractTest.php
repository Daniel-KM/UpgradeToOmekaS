<?php

class UpgradeToOmekaS_Processor_AbstractTest extends UpgradeToOmekaS_Test_AppTestCase
{
    protected $_isAdminTest = true;

    public function setUp()
    {
        parent::setUp();

        // Authenticate and set the current user.
        $this->user = $this->db->getTable('User')->find(1);
        $this->_authenticateUser($this->user);

    }

    public function testPrecheckConfigEmpty()
    {
        $processor = $this->getMockForAbstractClass('UpgradeToOmekaS_Processor_Abstract');
        $result = $processor->precheckConfig();
        $this->assertEquals(1, count($result));
        $this->assertContains('The processor of a plugin should have a plugin name', $result[0]);
    }

    public function testPrecheckConfigBase()
    {
        $processor = $this->getMockForAbstractClass('UpgradeToOmekaS_Processor_Abstract');
        $processor->pluginName = 'Stub';
        $result = $processor->precheckConfig();
        $this->assertEquals(1, count($result));
        $this->assertContains('The plugin.ini file of the plugin "Stub" is not readable', $result[0]);
    }

    public function testPrecheckConfigVersion()
    {
        $this->_createStubPlugin();

        $processor = $this->getMockForAbstractClass('UpgradeToOmekaS_Processor_Abstract');
        $processor->pluginName = 'Stub';
        $processor->minVersion = '2.1';
        $processor->maxVersion = '2.3';
        $result = $processor->precheckConfig();
        $this->assertEmpty($result);
    }


    public function testPrecheckConfigBadVersions()
    {
        $this->_createStubPlugin();

        $processor = $this->getMockForAbstractClass('UpgradeToOmekaS_Processor_Abstract');
        $processor->pluginName = 'Stub';
        $processor->minVersion = '2.2.2';
        $processor->maxVersion = '2.1.2';
        $result = $processor->precheckConfig();
        $this->assertEquals(2, count($result));
        $this->assertEquals('The current release requires at most Stub 2.1.2, current is 2.2.', $result[1]);
    }

    public function testCheckConfig()
    {
        $processor = $this->getMockForAbstractClass('UpgradeToOmekaS_Processor_Abstract');
        $result = $processor->checkConfig();
        $this->assertEmpty($result);
    }
}
