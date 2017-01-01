<?php

/**
 * Upgrade Embed Codes to Omeka S.
 *
 * @package UpgradeToOmekaS
 */
class UpgradeToOmekaS_Processor_EmbedCodes extends UpgradeToOmekaS_Processor_Abstract
{
    public $pluginName = 'EmbedCodes';
    public $minVersion = '1.0';
    public $maxVersion = '1.0';

    public $module = array(
        'type' => 'equivalent',
        'name' => 'Sharing',
        'version' => 'v1.0.0-beta',
        'size' => 8217,
        'md5' => '442c579734ca64c6f5f011d4e95da914',
        'url' => 'https://github.com/omeka-s-modules/Sharing/releases/download/%s/Sharing.zip',
        'partial' => true,
        'note' => 'No embedded statistics.'
    );

    // Nothing to do.
}
