<?php

/**
 * Upgrade OpenLayers Zoom to Omeka S.
 *
 * @package UpgradeToOmekaS
 */
class UpgradeToOmekaS_Processor_OpenLayersZoom extends UpgradeToOmekaS_Processor_Abstract
{

    public $pluginName = 'OpenLayersZoom';
    public $minVersion = '0.1';
    public $maxVersion = '';

    public $module = array(
        'name' => 'IiifServer',
        'version' => '3.5.9',
        'url' => 'https://github.com/Daniel-KM/Omeka-S-module-IiifServer/releases/download/%s/IiifServer.zip',
        'size' => 832980,
        'sha1' => '358ed63e24fed0d10c7d78b5d48abfef355efc67',
        'type' => 'port',
        'note' => 'OpenLayers can be replaced by OpenSeadragon, integrated in Omeka S, and the tiler is integrated in the module IIIF Server.',
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
    );

    public $processMethods = array(
        '_installModule',
    );

    protected function _installModule()
    {
        parent::_installModule();

        $this->_log('[' . __FUNCTION__ . ']: ' . __('OpenLayers was replaced by OpenSeadragon, integrated in Omeka S, and the tiler is integrated in the module IIIF Server.'),
            Zend_Log::NOTICE);
    }

    protected function _upgradeData()
    {
        //Check if each file is a zoomed image and upgrade its data.
        $recordType = 'File';

        $totalRecords = total_records($recordType);
        if (empty($totalRecords)) {
            return;
        }

        $olz = get_view()->OpenLayersZoom();

        $db = $this->_db;
        $target = $this->getTarget();
        $targetDb = $target->getDb();

        // Prepare the mapping of file ids.
        $mappedFileIds = $this->fetchMappedIds('File');
        if (empty($mappedFileIds)) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('An error prevents to upgrade possible zoomed files into tiles (update their data in the table "media").',
                $totalRecords),
                Zend_Log::ERR);
            return;
        }

        // The process uses the regular queries of Omeka in order to keep
        // only good records and to manage filters.
        $table = $db->getTable($recordType);

        $loops = floor(($totalRecords - 1) / $this->maxChunk) + 1;
        $totalZoomed = 0;
        for ($page = 1; $page <= $loops; $page++) {
            $this->_progress($this->_progressCurrent);
            $records = $table->findBy(array(), $this->maxChunk, $page);

            $baseId = 0;
            foreach ($records as $record) {
                ++$this->_progressCurrent;

                if (!$olz->isZoomed($record)) {
                    continue;
                }

                ++$totalZoomed;

                $mediaId = $mappedFileIds[$record->id];

                $toUpdate = array();
                $toUpdate['ingester'] = 'tile';
                $toUpdate['renderer'] = 'tile';

                $result = $targetDb->update('media', $toUpdate, 'id = ' . $mediaId);
            }
        }

        $this->_log('[' . __FUNCTION__ . ']: ' . __('%d zoomed images where upgraded.',
            $totalZoomed),
            Zend_Log::INFO);

        $this->_log('[' . __FUNCTION__ . ']: '
            . __('The display of zoomed files requires OpenSeadragon 2.2.2 or above, but only the version 2.1.0 is currently integrated in Omeka S, so add it yourself.'),
            Zend_Log::WARN);
    }

    protected function _upgradeFiles()
    {
        // Tiles are automatically copied by the core processor, so just rename
        // the zoom tiles dir to the new one.

        $filesDir = FILES_DIR . DIRECTORY_SEPARATOR;

        $source = get_option('openlayerszoom_tiles_dir');
        // Manage verty old versions.
        if (empty($source)) {
            $source = $filesDir . 'zoom_tiles';
        }

        $destination = $this->getParam('base_dir')
            . DIRECTORY_SEPARATOR . 'files'
            . DIRECTORY_SEPARATOR . $this->module['install']['settings']['iiifserver_image_tile_dir'];

        if (!file_exists($source) || !is_dir($source) || !is_readable($source)) {
            $this->_log('[' . __FUNCTION__ . ']: '
                    . __('The current dir for tiles ("%s") is not readable, so copy the tiles yourself in the new dir "%s".',
                        $source, $destination),
                Zend_Log::ERR);
            return false;
        }

        // Something very rare.
        if (file_exists($destination)) {
            if (!is_dir($destination)) {
                $this->_log('[' . __FUNCTION__ . ']: '
                    . __('A file "%s" exists instead of a dir for the tiles, so copy tiles yourself.',
                        $destination),
                    Zend_Log::ERR);
                return false;
            }
            // Probably something that never occurs.
            $this->_log('[' . __FUNCTION__ . ']: '
                . __('A dir "%s" exists, so check if tiles are copied inside it.',
                    $destination),
                Zend_Log::ERR);
            return false;
        }

        $result = UpgradeToOmekaS_Common::copyDir($source, $destination, true);

        if (!$result) {
            $this->_log('[' . __FUNCTION__ . ']: '
                . __('The tiles dir "%s" cannot be renamed "%s": rename it yourself.',
                    $source, $destination),
                Zend_Log::ERR);
            return false;
        }

        $this->_log('[' . __FUNCTION__ . ']: ' . __('The tiles were copied inside "%s".', $destination),
            Zend_Log::INFO);
    }
}
