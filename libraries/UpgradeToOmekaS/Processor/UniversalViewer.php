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
            'version' => '3.5.9',
            'url' => 'https://github.com/Daniel-KM/Omeka-S-module-IiifServer/releases/download/%s/IiifServer.zip',
            'size' => 832980,
            'sha1' => '358ed63e24fed0d10c7d78b5d48abfef355efc67',
            'type' => 'port',
            'install' => array(
                'settings' => array(
                    'iiifserver_manifest_description_property' => 'dcterms:bibliographicCitation',
                    'iiifserver_manifest_attribution_property' => '',
                    'iiifserver_manifest_attribution_default' => 'Provided by Example Organization', // @translate
                    'iiifserver_manifest_license_property' => 'dcterms:license',
                    'iiifserver_manifest_license_default' => 'http://www.example.org/license.html',
                    'iiifserver_manifest_logo_default' => '',
                    'iiifserver_manifest_force_url_from' => '',
                    'iiifserver_manifest_force_url_to' => '',
                    'iiifserver_image_creator' => 'Auto',
                    'iiifserver_image_max_size' => 10000000,
                    'iiifserver_image_tile_dir' => 'tile',
                    'iiifserver_image_tile_type' => 'deepzoom',
                ),
            ),
        ),
        array(
            'name' => 'UniversalViewer',
            'version' => '3.5.6',
            'url' => 'https://github.com/Daniel-KM/Omeka-S-module-UniversalViewer/releases/download/%s/UniversalViewer.zip',
            'size' => 2083326,
            'sha1' => 'f1af1348bf80581e7f990f7c9f1be0d45c76c086',
            'type' => 'port',
            'install' => array(
                'settings' => array(
                    'universalviewer_manifest_property' => '',
                ),
                'site_settings' => array(
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

        // Set default params to the first site only.
        foreach ($this->module as $module) {
            if (empty($module['install']['site_settings'])) {
                continue;
            }
            foreach ($module['install']['site_settings'] as $setting => $value) {
                $target->saveSiteSetting($setting, $value);
            }
        }

        $mapping = $this->getProcessor('Core/Elements')
            ->getMappingElementsToProperties();

        // Global settings.
        $mapOptions = array(
            'universalviewer_manifest_description_element' => 'iiifserver_manifest_description_property',
            'universalviewer_manifest_description_default' => '',
            'universalviewer_manifest_attribution_element' => 'iiifserver_manifest_attribution_property',
            'universalviewer_manifest_attribution_default' => 'iiifserver_manifest_attribution_default',
            'universalviewer_manifest_license_element' => 'iiifserver_manifest_license_property',
            'universalviewer_manifest_license_default' => 'iiifserver_manifest_license_default',
            'universalviewer_manifest_logo_default' => 'iiifserver_manifest_logo_default',

            'universalviewer_force_https' => 'iiifserver_manifest_force_url_to',
            'universalviewer_manifest_force_url_from' => 'iiifserver_manifest_force_url_from',
            'universalviewer_manifest_force_url_to' => 'iiifserver_manifest_force_url_to',

            'universalviewer_iiif_creator' => 'iiifserver_image_creator',
            'universalviewer_max_dynamic_size' => 'iiifserver_image_max_size',

            'universalviewer_alternative_manifest_element' => 'universalviewer_manifest_property',
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

                case 'universalviewer_force_https':
                    if ($value) {
                        $target->saveSetting('universalviewer_manifest_force_url_from', 'http:');
                        $value = 'https:';
                    }
                    break;
            }

            $target->saveSetting($setting, $value);
        }

        // Site settings (only the first).
        $mapOptions = array(
            'universalviewer_append_collections_show' => 'universalviewer_append_item_set_show',
            'universalviewer_append_items_show' => 'universalviewer_append_item_show',
            'universalviewer_append_collections_browse' => 'universalviewer_append_item_set_browse',
            'universalviewer_append_items_browse' => 'universalviewer_append_item_browse',
            'universalviewer_class' => 'universalviewer_class',
            'universalviewer_style' => 'universalviewer_style',
            'universalviewer_locale' => 'universalviewer_locale',
        );
        foreach ($mapOptions as $option => $setting) {
            if (empty($setting)) {
                continue;
            }
            $value = get_option($option);
            $target->saveSiteSetting($setting, $value);
        }

        $this->_log('[' . __FUNCTION__ . ']: ' . __('All urls of the Universal Viewer are maintained, except the player: items/play/:id was replaced by item/:id/play.')
                . ' ' . __('To keep old urls, uncomment the specified lines in the config of the module.'),
            Zend_Log::NOTICE);
    }
}
