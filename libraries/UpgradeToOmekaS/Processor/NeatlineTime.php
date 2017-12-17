<?php

/**
 * Upgrade NeatlineTime to Omeka S.
 *
 * @package UpgradeToOmekaS
 */
class UpgradeToOmekaS_Processor_NeatlineTime extends UpgradeToOmekaS_Processor_Abstract
{

    public $pluginName = 'NeatlineTime';
    public $minVersion = '2.2.4';
    public $maxVersion = '';

    public $module = array(
        'name' => 'Timeline',
        'version' => null,
        'url' => 'https://github.com/Daniel-KM/Omeka-S-module-Timeline/archive/master.zip',
        'size' => null,
        'sha1' => null,
        'type' => 'port',
        'note' => 'TImelines are imported fully, except the queries.',
    );

    public $processMethods = array(
        '_installModule',
    );

    protected function _upgradeSettings()
    {
        $target = $this->getTarget();

        $mapping = $this->getProcessor('Core/Elements')
            ->getMappingElementIdsToProperties();

        $properties = $this->getProcessor('Core/Elements')
            ->getPropertyIds();

        $mapOptions = array(
            'neatline_time_library' => 'timeline_library',
            'neatline_time_internal_assets' => 'timeline_internal_assets',
            'neatline_time_link_to_nav' => null,
            'neatline_time_link_to_nav_main' => null,
            'neatline_time_defaults' => 'timeline_defaults',
        );
        foreach ($mapOptions as $option => $setting) {
            if (empty($setting)) {
                continue;
            }
            $value = get_option($option);
            // Manage exceptions.
            switch ($option) {
                case 'neatline_time_defaults':
                    $value = json_decode($value, true);
                    $value['item_title'] = isset($mapping[$value['item_title']])
                        ? $mapping[$value['item_title']]
                        : 'dcterms:title';
                    $value['item_description'] = isset($mapping[$value['item_description']])
                        ? $mapping[$value['item_description']]
                        : 'dcterms:description';
                    $value['item_date'] = isset($mapping[$value['item_date']])
                        ? $mapping[$value['item_date']]
                        : 'dcterms:date';
                    $value['item_date_end'] = isset($mapping[$value['item_date_end']])
                        ? $mapping[$value['item_date_end']]
                        : '';
                    $value['item_date_id'] = isset($properties[$value['item_date']])
                        ? (string) $properties[$value['item_date']]
                        : '7';
                    break;
            }
            $target->saveSetting($setting, $value);
        }
    }

    protected function _upgradeData()
    {
        $recordType = 'NeatlineTime_Timeline';

        $totalRecords = total_records($recordType);
        if (empty($totalRecords)) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('No timeline to upgrade.'),
                Zend_Log::INFO);
            return;
        }
        $this->_progress(0, $totalRecords);

        $db = $this->_db;
        $target = $this->getTarget();
        $targetDb = $target->getDb();

        // The process uses the regular queries of Omeka in order to keep
        // only good records and to manage filters.
        $table = $db->getTable($recordType);

        $mapping = $this->getProcessor('Core/Elements')
            ->getMappingElementIdsToProperties();

        $properties = $this->getProcessor('Core/Elements')
            ->getPropertyIds();

        $siteId = $this->getSiteId();
        $pageSlugs = array();

        $loops = floor(($totalRecords - 1) / $this->maxChunk) + 1;
        for ($page = 1; $page <= $loops; $page++) {
            $this->_progress(($page - 1) * $this->maxChunk);
            $records = $table->findBy(array(), $this->maxChunk, $page);

            $baseId = 0;
            $toInserts = array();
            foreach ($records as $record) {
                $slug = substr($this->_slugify($record->title), 0, 190);
                if (in_array($slug, $pageSlugs) || $slug == 'homepage-site') {
                    $slug = substr($slug, 0, 190 - 20) . '-timeline-' . $record->id;
                }
                $pageSlugs[] = $slug;

                $args = json_decode($record->parameters, true) ?: array();
                $args['item_title'] = isset($mapping[$args['item_title']])
                    ? $mapping[$args['item_title']]
                    : 'dcterms:title';
                $args['item_description'] = isset($mapping[$args['item_description']])
                    ? $mapping[$args['item_description']]
                    : 'dcterms:description';
                $args['item_date'] = isset($mapping[$args['item_date']])
                    ? $mapping[$args['item_date']]
                    : 'dcterms:date';
                $args['item_date_end'] = isset($mapping[$args['item_date_end']])
                    ? $mapping[$args['item_date_end']]
                    : '';
                $args['item_date_id'] = isset($properties[$args['item_date']])
                    ? (string) $properties[$args['item_date']]
                    : '7';

                $data = array();
                $data['args'] = $args;
                // TODO Upgrade query to item pool.
                $data['item_pool'] = json_decode($record->query, true) ?: array();

                $toInsert = array();
                $toInsert['id'] = null;
                $toInsert['site_id'] = $siteId;
                $toInsert['slug'] = $slug;
                $toInsert['title'] = substr($record->title, 0, 190);
                $toInsert['created'] = $record->added;
                $toInsert['modified'] = $this->_cleanSqlTimestamp($record->modified);
                $toInserts['site_page'][] = $target->cleanQuote($toInsert);

                $id = 'LAST_INSERT_ID() + ' . $baseId;
                ++$baseId;

                $toInsert = array();
                $toInsert['id'] = null;
                $toInsert['page_id'] = $id;
                $toInsert['layout'] = 'pageTitle';
                $toInsert['data'] = $target->toJson(array());
                $toInsert['position'] = 1;
                $toInserts['site_page_block'][] = $target->cleanQuote($toInsert, 'page_id');

                $toInsert = array();
                $toInsert['id'] = null;
                $toInsert['page_id'] = $id;
                $toInsert['layout'] = 'timeline';
                $toInsert['data'] = $target->toJson($data);
                $toInsert['position'] = 2;
                $toInserts['site_page_block'][] = $target->cleanQuote($toInsert, 'page_id');

                $toInsert = array();
                $toInsert['id'] = null;
                $toInsert['page_id'] = $id;
                $toInsert['layout'] = 'html';
                $toInsert['data'] = $target->toJson(array(
                    'html' => $record->description,
                ));
                $toInsert['position'] = 3;
                $toInserts['site_page_block'][] = $target->cleanQuote($toInsert, 'page_id');
            }

            $target->insertRowsInTables($toInserts);
        }

        $this->_log('[' . __FUNCTION__ . ']: ' . __('All timelines (%d) have been upgraded.',
            $totalRecords), Zend_Log::INFO);
        $this->_log('[' . __FUNCTION__ . ']: ' . __('The item pool of timelines must be upgraded manually.'),
            Zend_Log::WARN);
    }
}
