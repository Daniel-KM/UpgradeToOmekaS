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

    public function testListProcessors()
    {
        $this->markTestIncomplete();
    }

    public function testPrecheckProcessorPluginEmpty()
    {
        $processor = $this->getMockForAbstractClass('UpgradeToOmekaS_Processor_Abstract');
        $result = $processor->precheckProcessorPlugin();
        $this->assertNotEmpty($result);
        $this->assertContains('The processor of a plugin should have a plugin name', $result);
    }

    public function testPrecheckProcessorPluginVersion()
    {
        $this->_createStubPlugin();
        $processor = $this->getMockForAbstractClass('UpgradeToOmekaS_Processor_Abstract');
        $processor->pluginName = 'Stub';
        $processor->minVersion = '2.1';
        $processor->maxVersion = '2.3';
        $result = $processor->precheckProcessorPlugin();
        $this->assertEmpty($result);
        $result = $processor->precheckConfig();
        $this->assertEquals(1, count($result));
        $this->assertEquals('The plugin is not installed or not active.', $result[0]);
        }

    public function testPrecheckProcessorPluginBadVersions()
    {
        $this->_createStubPlugin();
        $processor = $this->getMockForAbstractClass('UpgradeToOmekaS_Processor_Abstract');
        $processor->pluginName = 'Stub';
        $processor->minVersion = '2.2.2';
        $processor->maxVersion = '2.1.2';
        $result = $processor->precheckProcessorPlugin();
        $this->assertEquals('The processor for Stub requires a version between 2.2.2 and 2.1.2 (current is 2.2).', $result);
        $result = $processor->precheckConfig();
        $this->assertEquals(1, count($result));
        $this->assertEquals('The plugin is not installed or not active.', $result[0]);
    }

    public function testPrecheckConfigEmpty()
    {
        $processor = $this->getMockForAbstractClass('UpgradeToOmekaS_Processor_Abstract');
        $result = $processor->precheckProcessorPlugin();
        $this->assertContains('The processor of a plugin should have a plugin name', $result);
        $result = $processor->precheckConfig();
        $this->assertEquals(1, count($result));
        $this->assertEquals('The plugin is not installed or not active.', $result[0]);
    }

    public function testPrecheckConfigBase()
    {
        $processor = $this->getMockForAbstractClass('UpgradeToOmekaS_Processor_Abstract');
        $processor->pluginName = 'Stub';
        $result = $processor->precheckProcessorPlugin();
        $this->assertContains('The plugin.ini file of the plugin "Stub" is not readable', $result);
        $result = $processor->precheckConfig();
        $this->assertEquals(1, count($result));
        $this->assertEquals('The plugin is not installed or not active.', $result[0]);
    }

    public function testCheckConfig()
    {
        $processor = $this->getMockForAbstractClass('UpgradeToOmekaS_Processor_Abstract');
        $result = $processor->checkConfig();
        $this->assertEmpty($result);
    }

    public function testMergedMappingRoles()
    {
        $processor = $this->getMockForAbstractClass('UpgradeToOmekaS_Processor_Abstract');
        $result = $processor->getMerged('mapping_roles');
        $this->assertEquals(9, count($result));
        $this->assertEquals('site_admin', $result['admin']);
    }

    public function filterUpgradeOmekas($processors)
    {
        $processors['AbstractMock'] = get_class($this->_processorMock);
        return $processors;
    }

    public function testMappingRolesAdd()
    {
        // TODO Finish the test with added roles.
        $this->markTestIncomplete();
        add_filter('upgrade_omekas', array($this, 'filterUpgradeOmekas'));
        $this->_processorMock = $this->getMockForAbstractClass('UpgradeToOmekaS_Processor_Abstract');
        $processor = $this->_processorMock;
        $processor->mapping_roles = array(
            'foo' => 'bar',
            'omeka-c-role' => 'omeka-s-role',
        );
        $result = $processor->getMerged('mapping_roles');
        $this->assertEquals('site_admin', $result['admin']);
        $this->assertEquals(11, count($result));
        $this->assertEquals('bar', $result['foo']);
    }
}
