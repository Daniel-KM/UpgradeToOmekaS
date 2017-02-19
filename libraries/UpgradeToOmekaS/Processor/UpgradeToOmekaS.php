<?php

/**
 * Install the compatibility layer for themes of Omeka 2 in Omeka S.
 *
 * @package UpgradeToOmekaS
 */
class UpgradeToOmekaS_Processor_UpgradeToOmekaS extends UpgradeToOmekaS_Processor_Abstract
{

    public $pluginName = 'UpgradeToOmekaS';
    public $minVersion = '2.0.1';
    public $maxVersion = '2.0.1';

    public $module = array(
        'name' => 'UpgradeFromOmekaClassic',
        'version' => '3.0.1',
        'url' => 'https://github.com/Daniel-KM/UpgradeFromOmekaClassic/archive/%s.zip',
        'size' => 63278,
        'md5' => 'd89a055bfe6535a50396c3c273d9eaf1',
        'type' => 'upgrade',
        'partial' => false,
        'note' => 'Install the compatibility layer.',
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
