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
    public $maxVersion = '2.4.2';

    public $module = array(
        'name' => 'UniversalViewer',
        'version' => '3.4.2',
        'url' => 'https://github.com/Daniel-KM/Omeka-S-module-UniversalViewer/archive/%s.zip',
        'size' => 2383972,
        'sha1' => 'a85ef39b559c07085048e70a89d3d41eb7a0cd90',
        'type' => 'port',
        'install' => array(),
    );

    public $processMethods = array(
        '_installModule',
    );

    protected function _upgradeSettings()
    {
        $target = $this->getTarget();

        $mapping = $this->getProcessor('Core/Elements')
            ->getMappingElementsToProperties();

        $mapOptions = array(
            'universalviewer_manifest_description_element' => 'universalviewer_manifest_description_property',
            'universalviewer_manifest_description_default' => '',
            'universalviewer_manifest_attribution_element' => 'universalviewer_manifest_attribution_property',
            'universalviewer_manifest_attribution_default' => 'universalviewer_manifest_attribution_default',
            'universalviewer_manifest_license_element' => 'universalviewer_manifest_license_property',
            'universalviewer_manifest_license_default' => 'universalviewer_manifest_license_default',
            'universalviewer_manifest_logo_default' => 'universalviewer_manifest_logo_default',
            'universalviewer_alternative_manifest_element' => 'universalviewer_alternative_manifest_property',
            'universalviewer_append_collections_show' => 'universalviewer_append_item_set_show',
            'universalviewer_append_items_show' => 'universalviewer_append_item_show',
            'universalviewer_append_collections_browse' => 'universalviewer_append_item_set_browse',
            'universalviewer_append_items_browse' => 'universalviewer_append_item_browse',
            'universalviewer_class' => 'universalviewer_class',
            'universalviewer_style' => 'universalviewer_style',
            'universalviewer_locale' => 'universalviewer_locale',
            'universalviewer_iiif_creator' => 'universalviewer_iiif_creator',
            'universalviewer_max_dynamic_size' => 'universalviewer_max_dynamic_size',
            'universalviewer_force_https' => 'universalviewer_force_https',
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
