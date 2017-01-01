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

    protected function _init()
    {
        $dataDir = dirname(dirname(dirname(dirname(__FILE__))))
            . DIRECTORY_SEPARATOR . 'libraries'
            . DIRECTORY_SEPARATOR . 'data';

        $script = $dataDir
            . DIRECTORY_SEPARATOR . 'mapping_roles_more_user_roles.php';
        $this->mapping_roles = require $script;
    }
}
