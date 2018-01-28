<?php

/**
 * Upgrade CleanUrl to Omeka S.
 *
 * @package UpgradeToOmekaS
 */
class UpgradeToOmekaS_Processor_CleanUrl extends UpgradeToOmekaS_Processor_Abstract
{

    public $pluginName = 'CleanUrl';
    public $minVersion = '2.16';
    public $maxVersion = '';

    public $module = array(
        'name' => 'CleanUrl',
        'version' => null,
        'url' => 'https://github.com/Daniel-KM/Omeka-S-module-CleanUrl/archive/master.zip',
        'size' => null,
        'sha1' => null,
        'type' => 'port',
        'note' => '',
    );

    public $processMethods = array(
        '_installModule',
    );

    protected function _upgradeSettings()
    {
        $target = $this->getTarget();

        $mapping = $this->getProcessor('Core/Elements')
            ->getMappingElementsToPropertiesIds();

        $mapOptions = array(
            'clean_url_identifier_element' => 'cleanurl_identifier_property',
            'clean_url_identifier_prefix' => 'cleanurl_identifier_prefix',
            'clean_url_identifier_unspace' => 'cleanurl_identifier_unspace',
            'clean_url_case_insensitive' => 'cleanurl_case_insensitive',
            'clean_url_main_path' => 'cleanurl_main_path',
            'clean_url_collection_regex' => 'cleanurl_item_set_regex',
            'clean_url_collection_generic' => 'cleanurl_item_set_generic',
            'clean_url_item_default' => 'cleanurl_item_default',
            'clean_url_item_alloweds' => 'cleanurl_item_allowed',
            'clean_url_item_generic' => 'cleanurl_item_generic',
            'clean_url_file_default' => 'cleanurl_media_default',
            'clean_url_file_alloweds' => 'cleanurl_media_allowed',
            'clean_url_file_generic' => 'cleanurl_media_generic',
            'clean_url_use_admin' => 'cleanurl_use_admin',
            'clean_url_display_admin_browse_identifier' => 'cleanurl_display_admin_show_identifier',
            'clean_url_route_plugins' => null,
        );
        foreach ($mapOptions as $option => $setting) {
            if (empty($setting)) {
                continue;
            }
            $value = get_option($option);
            // Manage exceptions.
            switch ($option) {
                case 'clean_url_identifier_element':
                    // 10 is the hard set id of "dcterms:identifier" in default install.
                    $value = isset($mapping[$value]) ? (integer) $mapping[$value] : 10;
                    break;
                case 'clean_url_item_default':
                case 'clean_url_file_default':
                    if ($value == 'collection') {
                        $value = 'item_set';
                    }
                    elseif ($value == 'collection_item') {
                        $value = 'item_set_item';
                    }
                    break;
                case 'clean_url_item_alloweds':
                case 'clean_url_file_alloweds':
                    $value = unserialize($value);
                    foreach (array(
                            'collection' => 'item_set',
                            'collection_item' => 'item_set_item',
                        ) as $k => $v) {
                        $key = array_search($k, $value);
                        if ($key !== false) {
                            unset($value[$key]);
                            $value[] = $v;
                        }
                    }
                    break;
            }
            $target->saveSetting($setting, $value);
        }

        $this->_log('[' . __FUNCTION__ . ']: ' . __('All Clean Urls are maintained, but the site slug is added.'),
            Zend_Log::NOTICE);
    }
}
