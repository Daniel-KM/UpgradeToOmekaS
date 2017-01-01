<?php

class UpgradeToOmekaS_Processor_ExhibitBuilderTest extends UpgradeToOmekaS_Test_AppTestCase
{
    protected $_isAdminTest = true;

    protected $_processorName = 'UpgradeToOmekaS_Processor_ExhibitBuilder';

    public function setUp()
    {
        parent::setUp();

        $this->_setupPlugin();

        if (!class_exists('Exhibit')) {
            $this->markTestSkipped(__('The plugin "%s" must be available to test it.',
                $this->_processor->pluginName));
        }
    }

    public function testPrecheckConfig()
    {
        $this->markTestIncomplete();
    }
}
