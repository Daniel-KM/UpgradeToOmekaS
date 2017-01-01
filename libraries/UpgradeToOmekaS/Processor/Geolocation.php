<?php

/**
 * Upgrade Geolocation to Omeka S.
 *
 * @package UpgradeToOmekaS
 */
class UpgradeToOmekaS_Processor_Geolocation extends UpgradeToOmekaS_Processor_Abstract
{
    public $pluginName = 'Geolocation';
    // Upstream release.
    public $minVersion = '2.0';
    // public $maxVersion = '2.2.5';
    // Not yet included Improvements.
    public $maxVersion = '2.3.3-2.2.5';

    public $module = array(
        'name' => 'Mapping',
        'version' => '1.0.0-beta',
        'url' => 'https://github.com/omeka-s-modules/Mapping/releases/download/v%s/Mapping.zip',
        'size' => 230015,
        'md5' => '2b1919eabef364f14cbe9cdc71eb4467',
        'type' => 'equivalent',
        'partial' => true,
        'note' => 'Really free (no Google Map but Leaflet/OpenStreetMap), smarter, with multipoints and layers.',
        'original_ids' => true,
        'install' => array(
            // Copied from the original module.php.
            'sql' => '
CREATE TABLE mapping_marker (id INT AUTO_INCREMENT NOT NULL, item_id INT NOT NULL, media_id INT DEFAULT NULL, lat DOUBLE PRECISION NOT NULL, lng DOUBLE PRECISION NOT NULL, `label` VARCHAR(255) DEFAULT NULL, INDEX IDX_667C9244126F525E (item_id), INDEX IDX_667C9244EA9FDD75 (media_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;
CREATE TABLE mapping (id INT AUTO_INCREMENT NOT NULL, item_id INT NOT NULL, bounds VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_49E62C8A126F525E (item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;
ALTER TABLE mapping_marker ADD CONSTRAINT FK_667C9244126F525E FOREIGN KEY (item_id) REFERENCES item (id) ON DELETE CASCADE;
ALTER TABLE mapping_marker ADD CONSTRAINT FK_667C9244EA9FDD75 FOREIGN KEY (media_id) REFERENCES media (id) ON DELETE SET NULL;
ALTER TABLE mapping ADD CONSTRAINT FK_49E62C8A126F525E FOREIGN KEY (item_id) REFERENCES item (id) ON DELETE CASCADE;
',
        ),
    );

    public $tables = array(
        'mapping_marker',
        'mapping',
    );

    public $processMethods = array(
        '_installModule',
    );

    protected function _upgradeData()
    {
        $recordType = 'Location';

        $totalRecords = total_records($recordType);
        if (empty($totalRecords)) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('No location to upgrade.'),
                Zend_Log::INFO);
            return;
        }
        $this->_progress(0, $totalRecords);

        $db = $this->_db;
        $target = $this->getTarget();
        $targetDb = $target->getDb();

        // Check the version.
        $tableName = $db->getTable('Location')->getTableName();
        $result = $db->describeTable($tableName);
        $withDescription = isset($result['description']);

        // Improved version.
        if ($withDescription) {
            $message = __('In the module "Mapping", the address and the description are replaced by a label marker.');
        }
        // Standard version.
        else {
            $message = __('In the module "Mapping", the address is replaced by a label marker.');
        }
        $this->_log('[' . __FUNCTION__ . ']: ' . $message
                . ' ' . __('The map type is replaced by layers, but at the choice of the visitor.'),
            Zend_Log::INFO);

        $this->_log('[' . __FUNCTION__ . ']: ' . __('The zoom level is replaced by the bounds, but currently not upgraded.'),
            Zend_Log::NOTICE);

        // TODO Zoom level to bounds : get the lat/lng and add a value.
        // Prepare the list for the conversion of zoom levels to bounds.
        $zoomBounds = array();

        // The process uses the regular queries of Omeka in order to keep
        // only good records and to manage filters.
        $table = $db->getTable($recordType);

        // Prepare the sql to get the first file (in the target table, because
        // file ids are not kept).
        $sqlMediaId = "SELECT `id` FROM media WHERE `item_id` = %d ORDER BY `id`;";

        $loops = floor(($totalRecords - 1) / $this->maxChunk) + 1;
        for ($page = 1; $page <= $loops; $page++) {
            $this->_progress(($page - 1) * $this->maxChunk);
            $records = $table->findBy(array(), $this->maxChunk, $page);

            $toInserts = array();
            foreach ($records as $record) {
                $itemId = (integer) $record->item_id;
                $mediaId = $targetDb->fetchOne(sprintf($sqlMediaId, $itemId)) ?: null;

                // Improved version.
                if ($withDescription) {
                    $label = empty($record->address) || empty($record->description)
                        ? $record->address . $record->description
                        :  $record->address . PHP_EOL . $record->description;
                }
                // Standard version.
                else {
                    $label = $record->address;
                }

                $bounds = null;
                // if ($zoomBounds) {
                // }

                $toInsert = array();
                $toInsert['id'] = $record->id;
                $toInsert['item_id'] = $itemId;
                $toInsert['media_id'] = $mediaId;
                $toInsert['lat'] = $record->latitude;
                $toInsert['lng'] = $record->longitude;
                $toInsert['label'] = $label;
                $toInserts['mapping_marker'][] = $target->cleanQuote($toInsert);

                // TODO Convert zoom level into mapping bound.
                if ($bounds) {
                    $toInsert = array();
                    $toInsert['id'] = null;
                    $toInsert['item_id'] = $record->item_id;
                    $toInsert['bounds'] = $bounds;
                    $toInserts['mapping'][] = $target->cleanQuote($toInsert, 'page_id');
                }
            }

            $target->insertRowsInTables($toInserts);
        }

        $this->_log('[' . __FUNCTION__ . ']: ' . __('All locations (%d) have been upgraded.',
            $totalRecords), Zend_Log::INFO);
    }

    protected function _convertNavigationPageToLink($page, $parsed, $site)
    {
        $omekaSPath = $site['omekaSPath'];
        $omekaSSitePath = $site['omekaSSitePath'];
        $path = $parsed['path'];
        switch ($path) {
            case '/map':
            case '/geolocation':
            case '/geolocation/map':
            case '/geolocation/map/browse':
                return array(
                    'type' => 'mapping',
                    'data' => array(
                        'label' => $page['label'],
                ));
        }
    }
}
