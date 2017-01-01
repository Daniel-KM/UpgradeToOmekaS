<?php

/**
 * Upgrade Core to Omeka S.
 *
 * @package UpgradeToOmekaS
 */
class UpgradeToOmekaS_Processor_SimplePages extends UpgradeToOmekaS_Processor_Abstract
{
    public $pluginName = 'SimplePages';
    public $minVersion = '3.0.8';
    public $maxVersion = '3.0.8';

    public $processMethods = array(
    );

    public function _precheckConfig()
    {
        // No specific precheck.
    }
}
