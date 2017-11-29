<?php

/**
 * Upgrade Guest User to Omeka S.
 *
 * @package UpgradeToOmekaS
 */
class UpgradeToOmekaS_Processor_GuestUser extends UpgradeToOmekaS_Processor_Abstract
{

    public $pluginName = 'GuestUser';
    public $minVersion = '1.1';
    public $maxVersion = '';

    public $module = array(
        'name' => 'GuestUser',
        'version' => '0.1.4',
        'url' => 'https://github.com/Daniel-KM/Omeka-S-module-GuestUser/archive/master.zip',
        'size' => null,
        'sha1' => null,
        'type' => 'port',
        'install' => array(
            // Copied from the original module.php.
            'sql' => '
CREATE TABLE `guest_user_token` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created` datetime NOT NULL,
  `confirmed` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_80ED0AF2A76ED395` (`user_id`),
  CONSTRAINT `FK_80ED0AF2A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
',
            'settings' => array(
                'guestuser_capabilities' => '',
                'guestuser_short_capabilities' => '',
                'guestuser_login_text' => 'Login', // @translate
                'guestuser_register_text' => 'Register', // @translate
                'guestuser_dashboard_label' => 'My Account', // @translate
                'guestuser_open' => false,
                'guestuser_recaptcha' => false,
            ),
        ),
    );

    public $tables = array(
        'guest_user_token',
    );

    public $processMethods = array(
        '_installModule',
    );

    public function isPluginReady()
    {
        // Add a specific check for Guest User, because it can be installed even
        // when the current plugin is not active.
        $db = $this->_db;
        $sql = "
        SELECT COUNT(*)
        FROM {$db->User} users
        WHERE users.`role` = 'guest'
        ;";
        $totalGuest = $db->fetchOne($sql);
        if ($totalGuest) {
            return true;
        }

        return parent::isPluginReady();
    }

    protected function _upgradeSettings()
    {
        $target = $this->getTarget();

        // Set default settings, that will be overridden by current Omeka ones.
        foreach ($this->module['install']['settings'] as $setting => $value) {
            $target->saveSetting($setting, $value);
        }

        $mapOptions = array(
            'guest_user_skip_activation_email' => null,
            'guest_user_instant_access' => null,

            'guest_user_capabilities' => 'guestuser_capabilities',
            'guest_user_short_capabilities' => 'guestuser_short_capabilities',
            'guest_user_login_text' => 'guestuser_login_text',
            'guest_user_register_text' => 'guestuser_register_text',
            'guest_user_dashboard_label' => 'guestuser_dashboard_label',
            'guest_user_open' => 'guestuser_open',
            'guest_user_recaptcha' => 'guestuser_recaptcha',
        );
        foreach ($mapOptions as $option => $setting) {
            if (is_null($setting)) {
                continue;
            }
            $value = get_option($option);
            $target->saveSetting($setting, $value);
        }
    }

    protected function _upgradeData()
    {
        // Add a specific check for Guest User, because it can be installed even
        // when the current plugin is not active.
        $plugin = get_record('Plugin', array('name' => $this->pluginName));
        if (!$plugin || !$plugin->isActive()) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('The module Guest User was installed, but the params and the new users tokens were lost because the plugin is not enabled.'
                ), Zend_Log::NOTICE);
            return;
        }

        $recordType = 'GuestUserToken';

        $totalRecords = total_records($recordType);
        if (empty($totalRecords)) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('No guest user token to upgrade.'),
                Zend_Log::INFO);
            return;
        }
        $this->_progress(0, $totalRecords);

        $db = $this->_db;
        $target = $this->getTarget();
        $targetDb = $target->getDb();

        // Unlike other records, this table is copied directly in one query.
        // The order of columns changed, so they are specified one by one.
        $toInserts = array();
        $sql = "SELECT gut.id, gut.user_id, gut.token, gut.email, gut.created, gut.confirmed FROM {$db->GuestUserToken} gut";
        $result = $db->fetchAll($sql);
        $toInserts['guest_user_token'] = $result;
        $target->insertRowsInTables($toInserts, array(), false);

        $this->_log('[' . __FUNCTION__ . ']: ' . __('All guest user token (%d) have been upgraded.',
            $totalRecords), Zend_Log::INFO);

        // Check if this is an improved guest user plugin to warn the user.
        $recordType = 'GuestUserDetails';
        $totalRecords = total_records($recordType);
        if ($totalRecords) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('The guest user details were not upgraded: they are not managed currently in Omeka S.',
                $totalRecords), Zend_Log::WARN);
        }
    }
}
