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
        'version' => null,
        'url' => 'https://github.com/Daniel-KM/Omeka-S-module-EasyInstall/archive/master.zip',
        'size' => null,
        'sha1' => null,
        'type' => 'port',
    );

    public $processMethods = array(
        '_installModule',
    );
}
