<?php

/**
 * Upgrade Archive Repertory to Omeka S.
 *
 * @package UpgradeToOmekaS
 */
class UpgradeToOmekaS_Processor_ArchiveRepertory extends UpgradeToOmekaS_Processor_Abstract
{

    public $pluginName = 'ArchiveRepertory';
    public $minVersion = '2.10';
    public $maxVersion = '';

    public $module = array(
        'name' => 'ArchiveRepertory',
        'version' => null,
        'url' => 'https://github.com/Daniel-KM/Omeka-S-module-ArchiveRepertory/archive/master.zip',
        'size' => null,
        'sha1' => null,
        'type' => 'port',
    );

    public $processMethods = array(
        '_installModule',
    );

    protected function _upgradeSettings()
    {
        $target = $this->getTarget();

        $mapOptions = array(
            // Collections options.
            'archive_repertory_collection_folder' => 'archive_repertory_item_set_folder',
            'archive_repertory_collection_prefix' => 'archive_repertory_item_set_prefix',
            'archive_repertory_collection_names' => '',
            'archive_repertory_collection_convert' => 'archive_repertory_item_set_prefix',
            // Items options.
            'archive_repertory_item_folder' => 'archive_repertory_item_folder',
            'archive_repertory_item_prefix' => 'archive_repertory_item_prefix',
            'archive_repertory_item_convert' => 'archive_repertory_item_convert',
            // Files options.
            'archive_repertory_file_keep_original_name' => '',
            'archive_repertory_file_convert' => 'archive_repertory_media_convert',
            'archive_repertory_file_base_original_name' => '',
            // Other derivative folders.
            'archive_repertory_derivative_folders' => '',
            'archive_repertory_move_process' => '',
            // Max download without captcha (default to 30 MB).
            'archive_repertory_download_max_free_download' => '',
            'archive_repertory_legal_text' => '',
        );
        foreach ($mapOptions as $option => $setting) {
            if (empty($setting)) {
                continue;
            }
            $value = get_option($option);
            // Manage exceptions.
            switch ($option) {
                case 'archive_repertory_collection_folder':
                case 'archive_repertory_item_folder':
                    $value = strtolower($value);
                    if ($value == 'none') {
                        $value = '';
                    }
                    break;

                case 'archive_repertory_collection_convert':
                case 'archive_repertory_item_convert':
                    $value = strtolower($value);
                    if ($value == 'keep name') {
                        $value = 'keep';
                    }
                    break;

                case 'archive_repertory_file_convert':
                    // Manage the remove of the option in 2.14.1.
                    $keep = get_option('archive_repertory_file_keep_original_name');
                    if ($keep || is_null($keep)) {
                        $value = strtolower($value);
                        if ($value == 'keep name') {
                            $value = 'keep';
                        }
                    } else {
                        $value = 'hash';
                    }
                    break;
            }
            $target->saveSetting($setting, $value);
        }

        $ingesters = array(
            'upload' => array(),
            'url' => array(),
        );
        if (plugin_is_active('OpenLayersZoom')
                || plugin_is_active('OpenSeadragon')
                || plugin_is_active('UniversalViewer')
                || plugin_is_active('Zoomit')
            ) {
            $ingesters['tile'] = array(
                'path' => 'tile',
                'extension' => array(
                    '.dzi',
                    '.js',
                    '_files',
                    '_zdata',
                ),
            );
        }
        $mapOptions = array(
            'archive_repertory_ingesters' => $ingesters,
        );
        foreach ($mapOptions as $setting => $value) {
            $target->saveSetting($setting, $value);
        }

        $this->_log('[' . __FUNCTION__ . ']: ' . __('Omeka S allows multiple collections, so if set, the folder of an item will be the first one.'),
            Zend_Log::NOTICE);
    }
}
