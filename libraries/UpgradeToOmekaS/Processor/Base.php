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
    protected $_isCore = true;
}
