<?php

/**
 * Upgrade Dublin Core Extended to Omeka S.
 *
 * @package UpgradeToOmekaS
 */
class UpgradeToOmekaS_Processor_DublinCoreExtended extends UpgradeToOmekaS_Processor_Abstract
{

    public $pluginName = 'DublinCoreExtended';
    public $minVersion = '2.0';
    public $maxVersion = '';

    public $module = array(
        'type' => 'integrated',
    );
}
