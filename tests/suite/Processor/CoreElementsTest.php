<?php

class UpgradeToOmekaS_Processor_CoreElementsTest extends UpgradeToOmekaS_Test_AppTestCase
{
    protected $_isAdminTest = true;

    public function setUp()
    {
        parent::setUp();

        // Authenticate and set the current user.
        $this->user = $this->db->getTable('User')->find(1);
        $this->_authenticateUser($this->user);
    }

    public function testUpgradeItemTypes()
    {
        $this->markTestIncomplete();
    }

    public function testUpgradeElements()
    {
        $this->markTestIncomplete();
    }
}
