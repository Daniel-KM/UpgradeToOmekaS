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
        $siteId = 1;

        // Check if there are already records for a warn.
        $lastId = null;
        $select = $targetDb->select()
            ->from('site_page', array(new Zend_Db_Expr('COUNT(*)')));
        $totalExisting = $targetDb->fetchOne($select);
        $noPreviousRecord = !$totalExisting;
        if (!$noPreviousRecord) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('Some simple pages (%d) have been imported, so ids will change and the main menu should be checked.',
                $totalExisting), Zend_Log::INFO);

            // Get the greatest record id.
            $select = $targetDb->select()
                ->from('site_page', array('id'))
                ->order('id DESC');
            $lastId = $targetDb->fetchOne($select);
        }

        // The process uses the regular queries of Omeka in order to keep
        // only good records and to manage filters.
        $table = $db->getTable($recordType);

        $loops = floor(($totalRecords - 1) / $this->maxChunk) + 1;
        for ($page = 1; $page <= $loops; $page++) {
            $records = $table->findBy(array(), $this->maxChunk, $page);

            $toInsertPages = array();
            $toInsertPageBlocks = array();
            foreach ($records as $record) {
                $id = $noPreviousRecord ? $record->id : ++$lastId;
                $toInsert = array();
                $toInsert['id'] = (integer) $id;
                $toInsert['site_id'] = (integer) $siteId;
                $toInsert['slug'] = $targetDb->quote(substr($record->slug, 0, 190));
                $toInsert['title'] = $targetDb->quote(substr($record->title, 0, 190));
                $toInsert['created'] = $targetDb->quote($record->inserted);
                $toInsert['modified'] = $targetDb->quote($record->updated);
                $toInsertPages[] = implode(",\t", $toInsert);

                $toInsert = array();
                $toInsert['page_id'] = (integer) $id;
                $toInsert['layout'] = $targetDb->quote('pageTitle');
                $toInsert['data'] = $targetDb->quote($this->_toJson(array()));
                $toInsert['position'] = 1;
                $toInsertPageBlocks[] = implode(",\t", $toInsert);

                $toInsert = array();
                $toInsert['page_id'] = (integer) $id;
                $toInsert['layout'] = $targetDb->quote('html');
                $toInsert['data'] = $targetDb->quote($this->_toJson(array(
                    'html' => $record->text,
                )));
                $toInsert['position'] = 2;
                $toInsertPageBlocks[] = implode(",\t", $toInsert);
            }

            if ($toInsertPages) {
                $sql = 'INSERT INTO `site_page` (`id`, `site_id`, `slug`, `title`, `created`, `modified`) VALUES ';
                $sql .= '(' . implode('),' . PHP_EOL . '(', $toInsertPages) . ');';
                $result = $targetDb->prepare($sql)->execute();
                if (!$result) {
                    throw new UpgradeToOmekaS_Exception(
                        __('Unable to insert pages.'));
                }

                $sql = 'INSERT INTO `site_page_block` (`page_id`, `layout`, `data`, `position`) VALUES ';
                $sql .= '(' . implode('),' . PHP_EOL . '(', $toInsertPageBlocks) . ');';
                $result = $targetDb->prepare($sql)->execute();
                if (!$result) {
                    throw new UpgradeToOmekaS_Exception(
                        __('Unable to insert a page blocks.'));
                }
            }
        }

        $this->_log('[' . __FUNCTION__ . ']: ' . __('All simple pages (%d) have been imported.',
            $totalRecords), Zend_Log::INFO);
        $this->_log('[' . __FUNCTION__ . ']: ' . __('In Omeka S, simple pages can be hidden or shown via the navigation menu.',
            $totalRecords), Zend_Log::INFO);
        $this->_log('[' . __FUNCTION__ . ']: ' . __('In Omeka S, shortcodes are not available.',
            $totalRecords), Zend_Log::INFO);
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
