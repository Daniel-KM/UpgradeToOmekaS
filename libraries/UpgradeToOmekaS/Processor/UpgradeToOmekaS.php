<?php

/**
 * Install the compatibility layer for themes of Omeka 2 in Omeka S.
 *
 * @package UpgradeToOmekaS
 */
class UpgradeToOmekaS_Processor_UpgradeToOmekaS extends UpgradeToOmekaS_Processor_Abstract
{

    public $pluginName = 'UpgradeToOmekaS';
    public $minVersion = '2.0.9';
    public $maxVersion = '';

    public $module = array(
        'name' => 'UpgradeFromOmekaClassic',
        'version' => null,
        'url' => 'https://github.com/Daniel-KM/Omeka-S-module-UpgradeFromOmekaClassic/archive/master.zip',
        'size' => null,
        'sha1' => null,
        'type' => 'upgrade',
        'partial' => false,
        'note' => 'Install the compatibility layer for upgraded themes.',
        'install' => array(),
    );

    public $processMethods = array(
        '_installModule',
    );

    protected function _upgradeSettings()
    {
        $source = $destination = $this->_getModuleDir()
            . DIRECTORY_SEPARATOR . 'config'
            . DIRECTORY_SEPARATOR . 'module.config.php';

        $target = $this->getTarget();
        if ($this->getParam('add_old_routes')) {
            $target->saveSetting('upgrade_add_old_routes', true);
            $this->_log('[' . __FUNCTION__ . ']: ' . __('Aliases for old routes were added via the module UpgradeFromOmekaClassic.'),
                Zend_Log::INFO);
        } else {
            $target->saveSetting('upgrade_add_old_routes', false);
            $this->_log('[' . __FUNCTION__ . ']: ' . __('No aliases were added for old routes.'),
                Zend_Log::INFO);
        }

        $this->_log('[' . __FUNCTION__ . ']: ' . __('The compatibility layer translates only standard functions: check your theme if there are custom ones.'),
            Zend_Log::INFO);
    }
}
