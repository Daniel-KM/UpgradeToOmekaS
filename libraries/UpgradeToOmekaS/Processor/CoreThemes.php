<?php

/**
 * Upgrade Core Themes to Omeka S.
 *
 * @package UpgradeToOmekaS
 */
class UpgradeToOmekaS_Processor_CoreThemes extends UpgradeToOmekaS_Processor_Abstract
{
    public $pluginName = 'Core / Themes';
    public $minVersion = '2.3.1';
    public $maxVersion = '2.5';

    public $module = array(
        'type' => 'integrated',
    );

    /**
     * List of methods to process for the upgrade.
     *
     * @var array
     */
    public $processMethods = array(
        '_copyThemes',
        '_downloadCompatibilityModule',
        '_unzipCompatibiltyModule',
        '_installCompatibiltyModule',
    );

    protected function _copyThemes()
    {
        // with theme media uploaded.
    }

    protected function _downloadCompatibilityModule()
    {
        // TODO Compatibility module.
    }

    protected function _unzipCompatibiltyModule()
    {
        // TODO Compatibility module.
    }

    protected function _installCompatibiltyModule()
    {
        // TODO Compatibility module.
    }
}
