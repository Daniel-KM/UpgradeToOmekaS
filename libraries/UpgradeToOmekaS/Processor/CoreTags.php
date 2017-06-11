<?php

/**
 * Upgrade Tags to Omeka S.
 *
 * Note: Extends UpgradeToOmekaS_Processor_Tagging.
 * @todo Extends UpgradeToOmekaS_Processor_AbstractCore
 *
 * @package UpgradeToOmekaS
 */
class UpgradeToOmekaS_Processor_CoreTags extends UpgradeToOmekaS_Processor_Tagging
{

    public $pluginName = 'Core/Tags';
    public $minVersion = '2.3.1';
    public $maxVersion = '2.5.9';
    protected $_isCore = true;

    protected function _installModule()
    {
        $installFolksonomy = $this->getParam('install_folksonomy');
        if ($installFolksonomy) {
            parent::_installModule();
        } else {
            $totalTags = total_records('Tag');
            $msg = __('The tags don’t exist in Omeka S, but the module %sFolksonomy%s can manage them.',
                    '<a href="https://github.com/Daniel-KM/Omeka-S-module-Folksonomy" target="_blank">', '</a>');
            if ($totalTags) {
                $this->_log('[' . __FUNCTION__ . ']: ' . $msg
                    . ' ' . __('%d tags were not imported.', $totalTags),
                    Zend_Log::WARN);
            } else {
                $this->_log('[' . __FUNCTION__ . ']: ' . $msg, Zend_Log::INFO);
            }
        }
    }
}
