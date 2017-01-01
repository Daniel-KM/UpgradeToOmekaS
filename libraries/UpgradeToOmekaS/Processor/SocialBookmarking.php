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
        'type' => 'equivalent',
        'name' => 'Sharing',
        'version' => 'v1.0.0-beta',
        'size' => 8217,
        'md5' => '442c579734ca64c6f5f011d4e95da914',
        'url' => 'https://github.com/omeka-s-modules/Sharing/releases/download/%s/Sharing.zip',
        'partial' => true,
        'note' => 'Only common social networks (Twitter, Pinterest, Tumblr, email) and embed, but not AddThis.',
    );

    public $processMethods = array(
        '_installModule',
    );
}
