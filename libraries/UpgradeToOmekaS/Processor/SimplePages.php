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

    public $mapping_models = array(
        'simple_pages_page' => 'page',
    );

    protected function _upgradeData()
    {
        $recordType = 'SimplePagesPage';

        $totalRecords = total_records($recordType);
        if (empty($totalRecords)) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('No simple page to upgrade.'),
                Zend_Log::INFO);
            return;
        }
        $this->_progress(0, $totalRecords);

        $db = $this->_db;
        $target = $this->getTarget();

        $user = $this->getParam('user');

        // The process uses the regular queries of Omeka in order to keep
        // only good records and to manage filters.
        $table = $db->getTable($recordType);

        $siteId = $this->getSiteId();

        // Check if there are already records for a warn.
        $totalExisting = $target->totalRows('site_page');
        $previousRecordsExists = (boolean) $totalExisting;
        $previousRecordsExistsExceptHomepage = $previousRecordsExists > 1;
        // The homepage is automatically created.
        if ($previousRecordsExistsExceptHomepage) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('Some simple pages (%d) have been upgraded, so ids will change and the main menu should be checked.',
                $totalExisting - 1), Zend_Log::INFO);
        }

        $pageSlugs = array();

        $loops = floor(($totalRecords - 1) / $this->maxChunk) + 1;
        for ($page = 1; $page <= $loops; $page++) {
            $this->_progress(($page - 1) * $this->maxChunk);
            $records = $table->findBy(array(), $this->maxChunk, $page);

            $baseId = 0;
            $toInserts = array();
            foreach ($records as $record) {
                $slug = substr($record->slug, 0, 190);
                if (in_array($slug, $pageSlugs) || $slug == 'homepage-site') {
                    $slug = substr($record->slug, 0, 190 - 20) . '-page-' . $record->id;
                }
                $pageSlugs[] = $slug;

                $id = $previousRecordsExistsExceptHomepage ? null : (integer) $record->id;
                $toInsert = array();
                $toInsert['id'] = $id;
                $toInsert['site_id'] = $siteId;
                $toInsert['slug'] = $slug;
                $toInsert['title'] = substr($record->title, 0, 190);
                $toInsert['created'] = $record->inserted;
                $toInsert['modified'] = $record->updated;
                $toInserts['site_page'][] = $target->cleanQuote($toInsert);

                if ($previousRecordsExistsExceptHomepage) {
                    $id = 'LAST_INSERT_ID() + ' . $baseId;
                    ++$baseId;
                }

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
                $toInsert['layout'] = 'html';
                $toInsert['data'] = $target->toJson(array(
                    'html' => $record->text,
                ));
                $toInsert['position'] = 2;
                $toInserts['site_page_block'][] = $target->cleanQuote($toInsert, 'page_id');
            }

            $target->insertRowsInTables($toInserts);
        }

        $this->_log('[' . __FUNCTION__ . ']: ' . __('All simple pages (%d) have been upgraded.',
            $totalRecords), Zend_Log::INFO);
        $this->_log('[' . __FUNCTION__ . ']: ' . __('In Omeka S, the pages can be hidden or shown via the navigation menu.')
                . ' ' . __('Furthermore, shortcodes are not available.'),
            Zend_Log::INFO);
    }

    protected function _convertNavigationPageToLink($page, $args, $site)
    {
        // Check if this is a slug.
        $slug = ltrim($args['path'], '/');
        if (strlen($slug) == 0) {
            return;
        }

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
