<?php

/**
 * Upgrade Social Bookmarking to Omeka S.
 *
 * @package UpgradeToOmekaS
 */
class UpgradeToOmekaS_Processor_SocialBookmarking extends UpgradeToOmekaS_Processor_Abstract
{

    public $pluginName = 'SocialBookmarking';
    // Upstream release.
    public $minVersion = '2.0';
    // public $maxVersion = '2.0.2';
    // Not yet included Improvements.
    // public $minVersion = '2.1';
    public $maxVersion = '2.2';

    public $module = array(
        'name' => 'Sharing',
        'version' => '1.0.0-beta',
        'url' => 'https://github.com/omeka-s-modules/Sharing/releases/download/v%s/Sharing.zip',
        'size' => 8217,
        'md5' => '442c579734ca64c6f5f011d4e95da914',
        'type' => 'equivalent',
        'partial' => true,
        'note' => 'Only common social networks (Facebook, Twitter, Pinterest, Tumblr, email) and embed, but not AddThis.',
        'install' => array(
            'settings' => array(
                'sharingServices' => array(
                    'fb', 'twitter', 'tumblr', 'pinterest', 'email', 'embed',
                ),
            ),
        ),
    );

    public $processMethods = array(
        '_installModule',
    );

    protected function _upgradeSettings()
    {
        $target = $this->getTarget();

        // Get current options in Omeka Classic.
        $existingServices = array();
        $sbServices = unserialize(get_option('social_bookmarking_services'));
        $sbServices = array_filter($sbServices);
        if (isset($sbServices['facebook']) || isset($sbServices['facebook_like'])) {
            $existingServices[] = 'fb';
        }
        if (isset($sbServices['twitter'])) {
            $existingServices[] = 'twitter';
        }
        if (isset($sbServices['tumblr'])) {
            $existingServices[] = 'tumblr';
        }
        if (isset($sbServices['pinterest']) || isset($sbServices['pinterest_share'])) {
            $existingServices[] = 'pinterest';
        }
        if (isset($sbServices['email']) || isset($sbServices['mailto'])) {
            $existingServices[] = 'email';
        }

        $totalSbServices = count($sbServices);
        $totalExistingServices = count($existingServices);
        if ($totalSbServices > $totalExistingServices) {
            $message = __('Some services (%d) have not been upgraded.',
                $totalSbServices - $totalExistingServices);
            $this->_log('[' . __FUNCTION__ . ']: ' . $message,
                Zend_Log::NOTICE);
        }

        // Get current data.
        $sharingMethods = $target->selectSiteSetting('sharing_methods');
        if (empty($sharingMethods)) {
            $sharingMethods = $existingServices;
        }
        // There are some values already, so update them.
        else {
            $sharingMethods = array_merge($sharingMethods, $existingServices);
            $sharingMethods = array_unique($sharingMethods);
        }
        $target->saveSiteSetting('sharing_methods', $sharingMethods);

        // Set a second option.
        $target->saveSiteSetting('sharing_placement', 'view.show.before');
    }
}
