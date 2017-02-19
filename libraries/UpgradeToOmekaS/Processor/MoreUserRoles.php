<?php

/**
 * Upgrade plugin "MoreUserRoles" to Omeka S.
 *
 * @package UpgradeToOmekaS
 */
class UpgradeToOmekaS_Processor_MoreUserRoles extends UpgradeToOmekaS_Processor_Abstract
{

    public $pluginName = 'MoreUserRoles';
    public $minVersion = '1.0';
    public $maxVersion = '1.0.1';

    public $module = array(
        'type' => 'integrated',
    );
}
