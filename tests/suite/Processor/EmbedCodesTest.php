<?php

class UpgradeToOmekaS_Processor_EmbedCodesTest extends UpgradeToOmekaS_Test_AppTestCase
{
    protected $_isAdminTest = true;

    protected $_processor;

    public function setUp()
    {
        parent::setUp();

        // Authenticate and set the current user.
        $this->user = $this->db->getTable('User')->find(1);
        $this->_authenticateUser($this->user);

        $this->_processor = new UpgradeToOmekaS_Processor_EmbedCodes();
    }

    /**
     * @internal Should be a mock module.
     */
    public function testInstallModule()
    {
        $this->markTestIncomplete();
    }
}
