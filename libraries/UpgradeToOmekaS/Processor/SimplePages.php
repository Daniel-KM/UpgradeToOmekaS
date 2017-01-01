<?php

/**
 * Upgrade Simple Pages to Omeka S.
 *
 * @package UpgradeToOmekaS
 */
class UpgradeToOmekaS_Processor_SimplePages extends UpgradeToOmekaS_Processor_Abstract
{

    public $pluginName = 'SimplePages';
    public $minVersion = '3.0.8';
    public $maxVersion = '3.0.8';

    public $module = array(
        'type' => 'integrated',
    );

    public $processMethods = array(
        '_upgradeData',
    );

    protected function _upgradeData()
    {
        $recordType = 'SimplePagesPage';

        $totalRecords = total_records($recordType);
        if (empty($totalRecords)) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('No simple page to import.'),
                Zend_Log::INFO);
            return;
        }

        $db = $this->_db;
        $targetDb = $this->getTargetDb();

        $user = $this->getParam('user');

        // The process uses the regular queries of Omeka in order to keep
        // only good records and to manage filters.
        $table = $db->getTable($recordType);

        $siteId = 1;

        // Check if there are already records for a warn.
        $totalExisting = $this->countTargetTable('site_page');
        $previousRecordsExists = (boolean) $totalExisting;
        if ($previousRecordsExists) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('Some simple pages (%d) have been imported, so ids will change and the main menu should be checked.',
                $totalExisting), Zend_Log::INFO);
        }

        $loops = floor(($totalRecords - 1) / $this->maxChunk) + 1;
        for ($page = 1; $page <= $loops; $page++) {
            $records = $table->findBy(array(), $this->maxChunk, $page);

            $baseId = 0;
            $toInserts = array();
            foreach ($records as $record) {
                $id = $previousRecordsExists ? null : (integer) $record->id;
                $toInsert = array();
                $toInsert['id'] = $id;
                $toInsert['site_id'] = $siteId;
                $toInsert['slug'] = substr($record->slug, 0, 190);
                $toInsert['title'] = substr($record->title, 0, 190);
                $toInsert['created'] = $record->inserted;
                $toInsert['modified'] = $record->updated;
                $toInserts['site_page'][] = $this->_dbQuote($toInsert);

                if ($previousRecordsExists) {
                    $id = 'LAST_INSERT_ID() + ' . $baseId;
                    ++$baseId;
                }

                $toInsert = array();
                $toInsert['id'] = null;
                $toInsert['page_id'] = $id;
                $toInsert['layout'] = 'pageTitle';
                $toInsert['data'] = $this->_toJson(array());
                $toInsert['position'] = 1;
                $toInserts['site_page_block'][] = $this->_dbQuote($toInsert, 'page_id');

                $toInsert = array();
                $toInsert['id'] = null;
                $toInsert['page_id'] = $id;
                $toInsert['layout'] = 'html';
                $toInsert['data'] = $this->_toJson(array(
                    'html' => $record->text,
                ));
                $toInsert['position'] = 2;
                $toInserts['site_page_block'][] = $this->_dbQuote($toInsert, 'page_id');
            }

            $this->_insertRowsInTables($toInserts);
        }

        $this->_log('[' . __FUNCTION__ . ']: ' . __('All simple pages (%d) have been imported.',
            $totalRecords), Zend_Log::INFO);
        $this->_log('[' . __FUNCTION__ . ']: ' . __('In Omeka S, the pages can be hidden or shown via the navigation menu.')
                . ' ' . __('Furthermore, shortcodes are not available.'),
            Zend_Log::INFO);
    }

    public function convertNavigationPageToLink($page, $args, $site)
    {
        // Check if this is a slug.
        $slug = ltrim($args['path'], '/');
        $simplePage = get_record('SimplePagesPage', array(
            'slug' => $slug,
        ));
        if ($simplePage) {
            return array(
                'type' => 'page',
                'data' => array(
                    'label' => $page['label'],
                    // The ids are kept between Omeka 2 and Omeka S.
                    'id' => $simplePage->id,
            ));
        }
    }
}
