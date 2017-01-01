<?php

/**
 * Upgrade to Omeka S.
 *
 * A class that does nothing, but allows to access to generic abstract methods.
 *
 * @package UpgradeToOmekaS
 */
class UpgradeToOmekaS_Processor_Base extends UpgradeToOmekaS_Processor_Abstract
{
    public $pluginName = 'Base';

    /**
     * Check if the plugin is installed.
     *
     * @internal Always true for the Core.
     *
     * @return boolean
     */
    public function isPluginReady()
    {
        return true;
    }
}
