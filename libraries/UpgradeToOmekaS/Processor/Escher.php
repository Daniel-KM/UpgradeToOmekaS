<?php

/**
 * Upgrade Escher to Omeka S.
 *
 * @package UpgradeToOmekaS
 */
class UpgradeToOmekaS_Processor_Escher extends UpgradeToOmekaS_Processor_Abstract
{

    public $pluginName = 'Escher';
    public $minVersion = '';
    public $maxVersion = '';

    public $module = array(
        'name' => 'EasyInstall',
        'version' => '3.1.1',
        'url' => 'https://github.com/Daniel-KM/Omeka-S-module-EasyInstall/archive/%s.zip',
        'size' => 12353,
        'sha1' => 'b3cfb36426f5e73abf1020d385bc3170df0d5214',
        'type' => 'port',
    );

    public $processMethods = array(
        '_installModule',
    );
}
