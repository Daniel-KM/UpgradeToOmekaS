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
        'version' => '3.1',
        'url' => 'https://github.com/Daniel-KM/Omeka-S-module-EasyInstall/archive/%s.zip',
        'size' => 12031,
        'sha1' => 'f39a9cac3b026088edf2935cd9527b1ba62cbf16',
        'type' => 'port',
    );

    public $processMethods = array(
        '_installModule',
    );
}
