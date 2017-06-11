<?php

/**
 * Upgrade Universal Viewer to Omeka S.
 *
 * @package UpgradeToOmekaS
 */
class UpgradeToOmekaS_Processor_UniversalViewer extends UpgradeToOmekaS_Processor_Abstract
{

    public $pluginName = 'UniversalViewer';
    public $minVersion = '2.4.2';
    public $maxVersion = '';

    public $multipleModules = true;
    public $module = array(
        array(
            'name' => 'IiifServer',
            'version' => '3.5.4',
            'url' => 'https://github.com/Daniel-KM/Omeka-S-module-IiifServer/releases/download/%s/IiifServer.zip',
            'size' => 261157,
            'sha1' => '043ee0411374ec042a081bd50d2085d3a153cd18',
            'type' => 'port',
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
        ),
        array(
            'name' => 'UniversalViewer',
            'version' => null,
            'url' => 'https://github.com/Daniel-KM/Omeka-S-module-UniversalViewer/archive/master.zip',
            'size' => null,
            'sha1' => null,
            'type' => 'port',
            'install' => array(
                'settings' => array(
                    'universalviewer_manifest_property' => '',
                    'universalviewer_append_item_set_show' => true,
                    'universalviewer_append_item_show' => true,
                    'universalviewer_append_item_set_browse' => false,
                    'universalviewer_append_item_browse' => false,
                    'universalviewer_class' => '',
                    'universalviewer_style' => 'background-color: #000; height: 600px;',
                    'universalviewer_locale' => 'en-GB:English (GB),fr:French',
                ),
            ),
        ),
    );

    public $processMethods = array(
        '_installModule',
    );

    protected function _upgradeSettings()
    {
        $target = $this->getTarget();

        // Set default settings, that will be overridden by current Omeka ones.
        foreach ($this->module as $module) {
            foreach ($module['install']['settings'] as $setting => $value) {
                $target->saveSetting($setting, $value);
            }
        }

        $mapping = $this->getProcessor('Core/Elements')
            ->getMappingElementsToProperties();

        $mapOptions = array(
            'universalviewer_manifest_description_element' => 'iiifserver_manifest_description_property',
            'universalviewer_manifest_description_default' => '',
            'universalviewer_manifest_attribution_element' => 'iiifserver_manifest_attribution_property',
            'universalviewer_manifest_attribution_default' => 'iiifserver_manifest_attribution_default',
            'universalviewer_manifest_license_element' => 'iiifserver_manifest_license_property',
            'universalviewer_manifest_license_default' => 'iiifserver_manifest_license_default',
            'universalviewer_manifest_logo_default' => 'iiifserver_manifest_logo_default',
            'universalviewer_alternative_manifest_element' => 'universalviewer_manifest_property',
            'universalviewer_append_collections_show' => 'universalviewer_append_item_set_show',
            'universalviewer_append_items_show' => 'universalviewer_append_item_show',
            'universalviewer_append_collections_browse' => 'universalviewer_append_item_set_browse',
            'universalviewer_append_items_browse' => 'universalviewer_append_item_browse',
            'universalviewer_class' => 'universalviewer_class',
            'universalviewer_style' => 'universalviewer_style',
            'universalviewer_locale' => 'universalviewer_locale',
            'universalviewer_iiif_creator' => 'iiifserver_image_creator',
            'universalviewer_max_dynamic_size' => 'iiifserver_image_max_size',
            'universalviewer_force_https' => 'iiifserver_manifest_force_https',
        );
        foreach ($mapOptions as $option => $setting) {
            if (empty($setting)) {
                continue;
            }
            $value = get_option($option);
            // Manage exceptions.
            switch ($option) {
                case 'universalviewer_manifest_description_element':
                case 'universalviewer_manifest_attribution_element':
                case 'universalviewer_manifest_license_element':
                case 'universalviewer_alternative_manifest_element':
                    $element = json_decode($value, true);
                    if ($element && count($element) == 2) {
                        $element = $element[0] . ':' . $element[1];
                        if (isset($mapping[$element])) {
                            $value = $mapping[$element];
                        }
                    }
                    break;
            }
            $target->saveSetting($setting, $value);
        }

        $this->_log('[' . __FUNCTION__ . ']: ' . __('All urls of the Universal Viewer are maintained, except the player: items/play/:id was replaced by item/:id/play.')
                . ' ' . __('To keep old urls, uncomment the specified lines in the config of the module.'),
            Zend_Log::NOTICE);
    }
}
