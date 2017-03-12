<?php

/**
 * Upgrade Zoomit to Omeka S.
 *
 * @package UpgradeToOmekaS
 */
class UpgradeToOmekaS_Processor_Zoomit extends UpgradeToOmekaS_Processor_Abstract
{

    public $pluginName = 'Zoomit';
    public $minVersion = '2.0';
    public $maxVersion = '';

    public $module = array(
        'name' => 'IiifServer',
        'version' => '3.5.1',
        'url' => 'https://github.com/Daniel-KM/Omeka-S-module-IiifServer/archive/%s.zip',
        'size' => 244038,
        'sha1' => '02dabb99d5a7e9d2467228c54256ce8423cacf4c',
        'type' => 'integrated',
        'note' => 'The module IIIF Server may create tiles automatically for the default viewer OpenSeadragon.',
        'install' => array(
            'settings' => array(
                'iiifserver_manifest_description_property' => 'dcterms:bibliographicCitation',
                'iiifserver_manifest_attribution_property' => '',
                'iiifserver_manifest_attribution_default' => 'Provided by Example Organization',
                'iiifserver_manifest_license_property' => 'dcterms:license',
                'iiifserver_manifest_license_default' => 'http://www.example.org/license.html',
                'iiifserver_manifest_logo_default' => '',
                'iiifserver_manifest_force_https' => false,
                'iiifserver_image_creator' => 'Auto',
                'iiifserver_image_max_size' => 10000000,
                'iiifserver_image_tile_dir' => 'tile',
                'iiifserver_image_tile_type' => 'deepzoom',
            ),
        ),
    );

    public $processMethods = array(
        '_installModule',
    );

    protected function _installModule()
    {
        parent::_installModule();

        $this->_log('[' . __FUNCTION__ . ']: ' . __('The module IIIF Server may create tiles automatically for the default viewer OpenSeadragon.'),
            Zend_Log::INFO);
    }
}
