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
    public $maxVersion = '2.2';

    public $module = array(
        'type' => 'integrated',
    );

    protected function _init()
    {
        $dataDir = dirname(dirname(dirname(dirname(__FILE__))))
            . DIRECTORY_SEPARATOR . 'libraries'
            . DIRECTORY_SEPARATOR . 'data';

        $script = $dataDir
        . DIRECTORY_SEPARATOR . 'mapping_elements_dublin_core_extended.php';
        $this->mapping_elements = require $script;
    }
}
