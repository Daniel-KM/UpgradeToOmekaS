<?php

/**
 * Install the compatibility layer for themes of Omeka 2 in Omeka S.
 *
 * @package UpgradeToOmekaS
 */
class UpgradeToOmekaS_Processor_UpgradeToOmekaS extends UpgradeToOmekaS_Processor_Abstract
{

    public $pluginName = 'UpgradeToOmekaS';
    public $minVersion = '2.0.8';
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

        $siteSlug = $this->getSiteSlug();

        $input = file_get_contents($source);
        $output = preg_replace('~' . preg_quote('$siteSlug = \'\';') . '~', "\$siteSlug = '$siteSlug';", $input);
        $result = file_put_contents($destination, $output);

        $this->_log('[' . __FUNCTION__ . ']: ' . __('The compatibility layer translates only standard functions: check your theme if there are custom ones.'),
            Zend_Log::INFO);

        $this->_log('[' . __FUNCTION__ . ']: ' . __('It adds two aliases for the home page and for the items, so main links from the web are maintained.'),
            Zend_Log::INFO);

        $this->_log('[' . __FUNCTION__ . ']: ' . __('Update the site slug in "modules/UpgradeFromOmekaClassic/config/module.config.php" if you change the main site slug.'),
            Zend_Log::NOTICE);
    }
}
