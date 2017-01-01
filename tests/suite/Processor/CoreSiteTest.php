<?php

class UpgradeToOmekaS_Processor_CoreSiteTest extends UpgradeToOmekaS_Test_AppTestCase
{
    protected $_isAdminTest = true;

    public function setUp()
    {
        parent::setUp();

        // Authenticate and set the current user.
        $this->user = $this->db->getTable('User')->find(1);
        $this->_authenticateUser($this->user);

        $this->_checkDownloadedOmekaS();
    }

    public function testConfigOmekaS()
    {
        $processor = $this->_prepareProcessor('Core/Site', null, array('_unzipOmekaS'));
        $params = $processor->getParams();
        $this->assertNotEmpty($params);
        $processor->processMethods = array('_configOmekaS');
        $result = $processor->process();
        $this->assertEmpty($result);
    }

    public function testInstallOmekaS()
    {
        $processor = $this->_prepareProcessor(
            'Core/Site',
            array('user' => $this->user),
            array('_unzipOmekaS', '_configOmekaS', '_installOmekaS'));

        $target = $processor->getTarget();
        $targetDb = $target->getDb();
        $this->assertNotEmpty($targetDb);
        $sql = 'SELECT COUNT(*) FROM resource_class;';
        $result = $targetDb->fetchOne($sql);
        $this->assertEquals(105, $result);
        $sql = 'SELECT local_name FROM resource_class WHERE id = 105;';
        $result = $targetDb->fetchOne($sql);
        $this->assertEquals('OnlineChatAccount', $result);
        $sql = 'SELECT local_name FROM property WHERE id = 184;';
        $result = $targetDb->fetchOne($sql);
        $this->assertEquals('status', $result);
        $sql = 'SELECT value FROM setting WHERE id = "administrator_email";';
        $result = $targetDb->fetchOne($sql);
        $this->assertEquals($this->user->email, json_decode($result));
    }

    public function testUpgradeLocalConfig()
    {
        // TODO Check modified config.ini, for example for priority or locale.
        $processor = $this->_prepareProcessor(
            'Core/Site',
            array('user' => $this->user),
            array('_unzipOmekaS', '_configOmekaS', '_installOmekaS', '_upgradeLocalConfig'));

        $localConfigPhp = $processor->getFullPath('config/local.config.php');
        $localConfig = file_get_contents($localConfigPhp);
        $this->assertContains("'use_externals' => false", $localConfig);
        $this->assertContains("'priority' => \Zend\Log\Logger::DEBUG,", $localConfig);
        // $this->assertContains('fr_QC', $localConfig);
    }

    public function testUpgradeUsers()
    {
        $user = new User;
        $user->name = 'foo';
        $user->email = 'bar@foo.com';
        $user->active = '1';
        $user->role = 'none';
        $user->username = 'foo';
        $user->save();

        $processor = $this->_prepareProcessor(
            'Core/Site',
            array('user' => $this->user),
            array('_unzipOmekaS', '_configOmekaS', '_installOmekaS', '_upgradeUsers'));

        $target = $processor->getTarget();
        $targetDb = $target->getDb();

        // There are 4 users by default, 2 supers, 1 admin, 1 "none".
        $totalRecords = total_records('User');

        $sql = 'SELECT COUNT(*) FROM user;';
        $result = $targetDb->fetchOne($sql);
        $this->assertEquals($totalRecords, $result + 1);
    }

    public function testUpgradeSite()
    {
        $processor = $this->_prepareProcessor(
            'Core/Site',
            array('user' => $this->user),
            array('_unzipOmekaS', '_configOmekaS', '_installOmekaS', '_upgradeUsers',
                '_upgradeSite'));

        $target = $processor->getTarget();
        $targetDb = $target->getDb();

        $sql = 'SELECT COUNT(*) FROM site;';
        $result = $targetDb->fetchOne($sql);
        $this->assertEquals(1, $result);
        $sql = 'SELECT * FROM site;';
        $result = $targetDb->fetchRow($sql);
        $this->assertEquals($this->user->id, $result['owner_id']);

        $title = get_option('site_title');
        $this->assertEquals($title, $result['title']);
        $slugDirect = str_replace(' ', '-', strtolower($title));
        $slug = $processor->getSiteSlug();
        $this->assertEquals($slugDirect, $slug);
    }

    public function hookPublicNavigationMain($nav)
    {
        add_filter('public_navigation_main', array($this, 'hookPublicNavigationMain'));

        $nav[] = array(
            'label' => 'Foo',
            'uri' => url('foo'),
        );
        $nav[] = array(
            'label' => 'Search bar',
            'uri' => url('items/search'),
        );
        $nav[] = array(
            'label' => 'Bar',
            'uri' => 'https://example.org/path/to/bar?a=z&b=y#here',
        );
        return $nav;
    }

    public function testUpgradeSiteNavigationMain()
    {
        add_filter('public_navigation_main', array($this, 'hookPublicNavigationMain'));

        $this->_checkDownloadedOmekaS();
        $processor = $this->_prepareProcessor(
            'Core/Site',
            array('user' => $this->user),
            array('_unzipOmekaS', '_configOmekaS', '_installOmekaS', '_upgradeUsers',
                '_upgradeSite'));
        $target = $processor->getTarget();
        $targetDb = $target->getDb();
        $slug = $processor->getSiteSlug();
        $sql = 'SELECT * FROM site;';
        $result = $targetDb->fetchRow($sql);
        $nav = json_decode($result['navigation'], true);

        $this->assertEquals(5, count($nav));
        $this->assertEquals('Foo', $nav[2]['data']['label']);
        $this->assertEquals('/foo', $nav[2]['data']['url']);
        $this->assertEquals('Search bar', $nav[3]['data']['label']);
        $this->assertEquals('/s/' . $slug . '/item/search', $nav[3]['data']['url']);
        $this->assertEquals('Bar', $nav[4]['data']['label']);
        // TODO Omeka doesn't allow fragment?
        // $this->assertEquals('https://example.org/path/to/bar?a=z&b=y#here', $nav[4]['data']['url']);
        $this->assertEquals('https://example.org/path/to/bar?a=z&b=y#', $nav[4]['data']['url']);
    }
}
