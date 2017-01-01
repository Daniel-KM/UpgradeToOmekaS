<?php

class UpgradeToOmekaS_AccessTest extends UpgradeToOmekaS_Test_AppTestCase
{
    protected $_isAdminTest = true;

    public function setUp()
    {
        parent::setUp();

        $this->adminUser = $this->_addNewUserWithRole('admin');
        $this->superUser = $this->_addNewUserWithRole('super');
    }

    public function testSuperCanAccessForm()
    {
        $this->_authenticateUser($this->superUser);
        $this->dispatch('/upgrade-to-omeka-s');
        $this->assertModule('upgrade-to-omeka-s');
        $this->assertController('index');
        $this->assertAction('index', 'Super users should be able to reach the upgrade form.');
    }

    /**
     * @expectedException Omeka_Controller_Exception_403
     */
    public function testAdminCannotAccessForm()
    {
        $this->_authenticateUser($this->adminUser);
        $this->dispatch('upgrade-to-omeka-s');
    }

    private function _addNewUserWithRole($role)
    {
        $username = $role . 'user';
        $existingUser = $this->_getUser($username);
        if ($existingUser) {
            $existingUser->delete();
            release_object($existingUser);
        }
        $newUser = new User;
        $newUser->username = $username;
        $newUser->setPassword('foobar');
        $newUser->role = $role;
        $newUser->active = 1;
        $newUser->name = ucwords($role) . ' User';
        $newUser->email = $role . '@example.com';
        $newUser->save();
        return $newUser;
    }

    private function _getUser($username)
    {
        return $this->db->getTable('User')->findBySql("username = ?", array($username), true);
    }
}
