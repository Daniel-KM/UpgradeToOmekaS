<?php

/**
 * Upgrade Social Bookmarking to Omeka S.
 *
 * This plugin is partially integrated only.
 *
 * @package UpgradeToOmekaS
 */
class UpgradeToOmekaS_Processor_SocialBookmarking extends UpgradeToOmekaS_Processor_Abstract
{
    public $pluginName = 'SocialBookmarking';
    // Upstream release.
    // public $minVersion = '2.0';
    // public $maxVersion = '2.0.2';
    // Not yet included Improvements.
    public $minVersion = '2.1';
    public $maxVersion = '2.2';

    public $processMethods = array(
        // TODO Add this list.
    );
}
