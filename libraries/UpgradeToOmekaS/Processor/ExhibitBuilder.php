<?php

/**
 * Upgrade Exhibit Builder to Omeka S.
 *
 * @package UpgradeToOmekaS
 */
class UpgradeToOmekaS_Processor_ExhibitBuilder extends UpgradeToOmekaS_Processor_Abstract
{

    public $pluginName = 'ExhibitBuilder';
    public $minVersion = '3.1';
    public $maxVersion = '3.3.4';

    public $module = array(
        'type' => 'integrated',
        'note' => 'Exhibits are now standard sites. The new field "menu_title" is not yet upgradable (Exhibit >= 3.3.4).',
    );

    public $processMethods = array(
        '_upgradeData',
    );

    public $mapping_models = array(
        'exhibit' => 'site',
        'exhibit_page' => 'page',
        'exhibit_page_block' => 'site_page_block',
        'exhibit_block_attachment' => 'site_block_attachment',
    );

    public $mapping_layouts = array(
        'file' => 'media',
        'file-text' => array('media', 'html'),
        'gallery' => array('itemShowCase', 'html'),
        'text' => 'html',

        // Other from Omeka S.
        'browsePreview' => 'browsePreview',
        'itemWithMetadata' => 'itemWithMetadata',
        'lineBreak' => 'lineBreak',
        'pageTitle' => 'pageTitle',
        'tableOfContents' => 'tableOfContents',
    );

    public $mapping_exhibit_block_keys = array(
        'file-position' => 'alignment',
        'file-size' => 'thumbnail_type',
        'gallery-position' => 'alignment',
        'gallery-file-size' => 'thumbnail_type',
    );

    public $mapping_exhibit_block_values = array(
        // Omeka C type to Omeka S type.
        'original' => 'original',
        'fullsize' => 'large',
        'thumbnail' => 'medium',
        'square_thumbnail' => 'square',
    );

    protected function _checkConfig()
    {
        $totalRecordExhibits = total_records('Exhibit');
        $totalRecordPages = total_records('ExhibitPage');
        $totalRecordBlocks = total_records('ExhibitPageBlock');
        $totalRecordAttachments = total_records('ExhibitBlockAttachment');
        $totalRecords = $totalRecordExhibits + $totalRecordPages + $totalRecordBlocks + $totalRecordAttachments;
        if (empty($totalRecords)) {
            return;
        }

        if (empty($totalRecordExhibits) && !empty($totalRecordPages)) {
            $this->_checks[] = __('There are exhibit pages without exhibit.');
        }

        if (empty($totalRecordPages) && !empty($totalRecordBlocks)) {
            $this->_checks[] = __('There are exhibit blocks without exhibit page.');
        }

        if (empty($totalRecordBlocks) && !empty($totalAttachments)) {
            $this->_checks[] = __('There are exhibit block attachments without exhibit block.');
        }
    }

    protected function _upgradeData()
    {
        // Because the first site is the main site and pages are used by simple
        // pages before, the ids can't be kept. Anyway, they are not important,
        // because the pages are identified by slug.

        $this->_log('[' . __FUNCTION__ . ']: ' . __('In Omeka S, exhibits are sites like the main one.'),
            Zend_Log::INFO);
        $this->_log('[' . __FUNCTION__ . ']: ' . __('Status "featured" and "tags" are lost.')
                . ' ' . __('The themes of exhibits are not upgraded, because they are standard sites in Omeka S.'),
            Zend_Log::NOTICE);

        $totalRecordExhibits = total_records('Exhibit');
        $totalRecordPages = total_records('ExhibitPage');
        $totalRecordBlocks = total_records('ExhibitPageBlock');
        $totalRecordAttachments = total_records('ExhibitBlockAttachment');
        $totalRecords = $totalRecordExhibits + $totalRecordPages + $totalRecordBlocks + $totalRecordAttachments;
        if (empty($totalRecords)) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('No exhibit to upgrade.'),
                Zend_Log::INFO);
            return;
        }

        if (empty($totalRecordExhibits) && !empty($totalRecordPages)) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('There are exhibit pages without exhibit.'),
                Zend_Log::ERR);
            return;
        }

        if (empty($totalRecordPages) && !empty($totalRecordBlocks)) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('There are exhibit blocks without exhibit page.'),
                Zend_Log::ERR);
            return;
        }

        if (empty($totalRecordBlocks) && !empty($totalRecordAttachments)) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('There are exhibit attachments without exhibit blocks.'),
                Zend_Log::ERR);
            return;
        }

        $this->_progressCurrent = 0;
        $this->_progress(0, $totalRecords);

        // The process import each record separately to avoid to get the last
        // inserted id of sites, pages and blocks simultaneously.
        $this->_upgradeDataExhibits();
        $this->_upgradeDataExhibitSettings();
        $this->_upgradeDataExhibitSummary();
        $this->_upgradeDataExhibitPages();
        $this->_upgradeDataExhibitNavigation();
        $this->_upgradeDataExhibitPageBlocks();
        $this->_upgradeDataExhibitBlockAttachments();
    }

    protected function _upgradeDataExhibits()
    {
        $recordType = 'Exhibit';

        $totalRecords = total_records($recordType);
        if (empty($totalRecords)) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('No exhibit to upgrade.'),
                Zend_Log::INFO);
            return;
        }
        // Progress is managed by all exhibits, pages, blocks and attachments.

        $db = $this->_db;
        $target = $this->getTarget();
        $targetDb = $target->getDb();

        $user = $this->getParam('user');

        // The list of user ids allows to check if the owner of a record exists.
        // The id of users are kept between Omeka C and Omeka S.
        // Some users may not have been upgraded.
        $targetUserIds = $target->fetchIds('user');

        $mainSiteId = $this->getSiteId();
        $mainSiteSlug = $this->getSiteSlug();
        $mainSiteTheme = $this->getSiteTheme();

        // The process uses the regular queries of Omeka in order to keep
        // only good records and to manage filters.
        $table = $db->getTable($recordType);

        $mapExhibitSlugIds = array();

        $loops = floor(($totalRecords - 1) / $this->maxChunk) + 1;
        for ($page = 1; $page <= $loops; $page++) {
            $this->_progress($this->_progressCurrent);
            $records = $table->findBy(array(), $this->maxChunk, $page);

            $baseId = 0;
            $toInserts = array();
            foreach ($records as $record) {
                ++$this->_progressCurrent;
                $ownerId = isset($targetUserIds[$record->owner_id])
                    ? $targetUserIds[$record->owner_id]
                    : null;
                $slug = substr($record->slug, 0, 190);
                if ($slug == $mainSiteSlug) {
                    $slug = substr($record->slug, 0, 190 - 20) . '-exhibit-' . $record->id;
                }
                $mapExhibitSlugIds[$record->id] = $slug;
                $theme = $record->theme ?: $mainSiteTheme;
                // Navigation is updated after the upgrade of exhibit pages.
                $navigation = json_encode(array());

                $toInsert = array();
                $toInsert['id'] = null;
                $toInsert['owner_id'] = $ownerId;
                $toInsert['slug'] = $slug;
                $toInsert['theme'] = substr($theme, 0, 190);
                $toInsert['title'] = substr($record->title, 0, 190);
                $toInsert['navigation'] = $navigation;
                $toInsert['item_pool'] = json_encode(array());
                $toInsert['created'] = $record->added;
                $toInsert['modified'] = $this->_cleanSqlTimestamp($record->modified);
                $toInsert['is_public'] = $record->public;
                $toInserts['site'][] = $target->cleanQuote($toInsert);
            }

            $target->insertRowsInTables($toInserts);
        }

        // Prepare the mapping between ids.
        $siteIdsSlugs = $target->fetchPairs('site', 'id', 'slug');
        if ((count($siteIdsSlugs) - 1) != $totalRecords) {
            throw new UpgradeToOmekaS_Exception(
                __('An error occurred during the upgrade of exhibits.'));
        }

        $mapExhibitIds = array();
        foreach ($siteIdsSlugs as $id => $slug) {
            if ($slug == $mainSiteSlug) {
                continue;
            }
            $key = array_search($slug, $mapExhibitSlugIds);
            if ($key === false) {
                throw new UpgradeToOmekaS_Exception(
                    __('An error occurred during the upgrade of some exhibits.'));
            }
            $mapExhibitIds[(integer) $key] = (integer) $id;
        }

        $this->storeMappedIds('Exhibit', $mapExhibitIds);

        $this->_log('[' . __FUNCTION__ . ']: ' . __('All exhibits (%d) have been upgraded.',
            count($mapExhibitIds)), Zend_Log::INFO);
    }

    protected function _upgradeDataExhibitPages()
    {
        $recordType = 'ExhibitPage';

        $totalRecords = total_records($recordType);
        if (empty($totalRecords)) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('No exhibit page to upgrade.'),
                Zend_Log::INFO);
            return;
        }
        // Progress is managed by all exhibits, pages, blocks and attachments.

        $db = $this->_db;
        $target = $this->getTarget();
        $targetDb = $target->getDb();

        // The process uses the regular queries of Omeka in order to keep
        // only good records and to manage filters.
        $table = $db->getTable($recordType);

        $mainSiteId = $this->getSiteId();
        $mappedExhibitIds = $this->fetchMappedIds('Exhibit');

        $missing = 0;
        $mapExhibitPageSlugIds = array();

        $loops = floor(($totalRecords - 1) / $this->maxChunk) + 1;
        for ($page = 1; $page <= $loops; $page++) {
            $this->_progress($this->_progressCurrent);
            $records = $table->findBy(array(), $this->maxChunk, $page);

            $baseId = 0;
            $toInserts = array();
            foreach ($records as $record) {
                ++$this->_progressCurrent;
                if (!isset($mappedExhibitIds[$record->exhibit_id])) {
                    ++$missing;
                    continue;
                }
                $siteId = $mappedExhibitIds[$record->exhibit_id];
                if (!isset($mapExhibitPageSlugIds[$siteId])) {
                    $mapExhibitPageSlugIds[$siteId] = array();
                }

                // The size of slugs increases from 30 to 190 characters.
                // The check is used for non standard slugs.
                $slug = substr($record->slug, 0, 190);
                if (in_array($slug, $mapExhibitPageSlugIds[$siteId])) {
                    $slug = substr($record->slug, 0, 190 - 20) . '-exhibit-page-' . $record->id;
                }
                $mapExhibitPageSlugIds[$siteId][$record->id] = $slug;

                $toInsert = array();
                $toInsert['id'] = null;
                $toInsert['site_id'] = $siteId;
                $toInsert['slug'] = $slug;
                $toInsert['title'] = substr($record->title, 0, 190);
                $toInsert['created'] = $record->added;
                $toInsert['modified'] = $this->_cleanSqlTimestamp($record->modified);
                $toInserts['site_page'][] = $target->cleanQuote($toInsert);

                $pageId = 'LAST_INSERT_ID() + ' . $baseId;
                ++$baseId;

                $toInsert = array();
                $toInsert['id'] = null;
                $toInsert['page_id'] = $pageId;
                $toInsert['layout'] = 'pageTitle';
                $toInsert['data'] = $target->toJson(array());
                $toInsert['position'] = 1;
                $toInserts['site_page_block'][] = $target->cleanQuote($toInsert, 'page_id');
            }

            $target->insertRowsInTables($toInserts);
        }

        if ($missing) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('Some exhibit pages (%d) have no exhibit and weren’t imported.',
                    $missing),
                Zend_Log::WARN);
        }

        // Prepare the mapping between ids.
        $pageIdsSlugs = $target->fetchPairs('site_page', 'id', 'slug');
        $pageIdsExhibits = $target->fetchPairs('site_page', 'id', 'site_id');

        $mapExhibitPageIds = array();
        foreach ($pageIdsSlugs as $id => $slug) {
            $siteId = $pageIdsExhibits[$id];
            if ($siteId == $mainSiteId) {
                continue;
            }
            if (!in_array($siteId, $mappedExhibitIds)) {
                throw new UpgradeToOmekaS_Exception(
                    __('An error occurred during the upgrade of exhibit pages.'));
            }
            $key = array_search($slug, $mapExhibitPageSlugIds[$siteId]);
            // Check if this is an specific page (summary).
            if ($key === false) {
                continue;
                throw new UpgradeToOmekaS_Exception(
                    __('An error occurred during the upgrade of some exhibit pages.'));
            }
            $mapExhibitPageIds[(integer) $key] = (integer) $id;
        }

        $this->storeMappedIds('ExhibitPage', $mapExhibitPageIds);

        $this->_log('[' . __FUNCTION__ . ']: ' . __('All exhibit pages (%d) have been upgraded.',
                count($mapExhibitPageIds)),
            Zend_Log::INFO);
    }

    protected function _upgradeDataExhibitPageBlocks()
    {
        $recordType = 'ExhibitPageBlock';

        $totalRecords = total_records($recordType);
        if (empty($totalRecords)) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('No exhibit page block to upgrade.'),
                Zend_Log::INFO);
            return;
        }
        // Progress is managed by all exhibits, pages, blocks and attachments.

        $db = $this->_db;
        $target = $this->getTarget();
        $targetDb = $target->getDb();

        // The process uses the regular queries of Omeka in order to keep
        // only good records and to manage filters.
        $table = $db->getTable($recordType);

        $mappedExhibitPageIds = $this->fetchMappedIds('ExhibitPage');

        // Records are ordered to simplify process.
        $select = $table->getSelectForFindBy()
            ->reset(Zend_Db_Select::ORDER)
            ->order(array(
                'exhibit_page_blocks.page_id ASC',
                'exhibit_page_blocks.order ASC',
            ));

        $mappingLayouts = $this->getMerged('mapping_layouts');
        $mappingExhibitBlockKeys = $this->getMerged('mapping_exhibit_block_keys');
        $mappingExhibitBlockValues = $this->getMerged('mapping_exhibit_block_values');

        $missing = 0;
        $orderBlocksByPage = array();
        $mapExhibitBlockIndexesIds = array();

        $loops = floor(($totalRecords - 1) / $this->maxChunk) + 1;
        for ($page = 1; $page <= $loops; $page++) {
            $this->_progress($this->_progressCurrent);
            $select
                ->reset(Zend_Db_Select::LIMIT_COUNT)
                ->reset(Zend_Db_Select::LIMIT_OFFSET);
            $table->applyPagination($select, $this->maxChunk, $page);
            $records = $table->fetchObjects($select);

            $baseId = 0;
            $toInserts = array();
            foreach ($records as $record) {
                ++$this->_progressCurrent;
                if (!isset($mappedExhibitPageIds[$record->page_id])) {
                    ++$missing;
                    continue;
                }
                $pageId = $mappedExhibitPageIds[$record->page_id];

                if (isset($mappingLayouts[$record->layout])) {
                    $layout = $mappingLayouts[$record->layout];
                } else {
                    $layout = Inflector::variablize($record->layout);
                }

                // Initialize the first position (the page title).
                if (!isset($orderBlocksByPage[$pageId])) {
                    $orderBlocksByPage[$pageId] = 1;
                }

                // Need to manage the case of file-text, divided in two blocks.
                $layouts = is_array($layout) ? $layout : array($layout);
                foreach ($layouts as $layout) {
                    $data = empty($record->options)
                        ? array()
                        : json_decode($record->options, true);

                    // Upgrade upgradable data.
                    $cleanData = array();
                    foreach ($data as $key => $value) {
                        if (isset($mappingExhibitBlockKeys[$key])) {
                            $key = $mappingExhibitBlockKeys[$key];
                        }
                        if (isset($mappingExhibitBlockValues[$value])) {
                            $value = $mappingExhibitBlockValues[$value];
                        }
                        $cleanData[$key] = $value;
                    }
                    $data = $cleanData;

                    switch ($record->layout) {
                        case 'file-text':
                        case 'gallery':
                            if ($layout == 'html') {
                                $data = array('html' => $record->text);
                            }
                            break;
                        default:
                            if ($record->text) {
                                $data['html'] = $record->text;
                            }
                    }

                    $position = ++$orderBlocksByPage[$pageId];
                    $mapExhibitBlockIndexesIds[$record->id . '-' . $position] = $pageId . '-' . $position;

                    $toInsert = array();
                    $toInsert['id'] = null;
                    $toInsert['page_id'] = $pageId;
                    $toInsert['layout'] = $layout;
                    $toInsert['data'] = $target->toJson($data);
                    $toInsert['position'] = $position;
                    $toInserts['site_page_block'][] = $target->cleanQuote($toInsert);
                }
            }

            $target->insertRowsInTables($toInserts);
        }

        if ($missing) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('Some exhibit page blocks (%d) have no exhibit page and weren’t imported.',
                    $missing),
                Zend_Log::WARN);
        }

        // Prepare the mapping between ids.
        // Note: The couple page_id-position is always unique.
        $blockIdsPositions = $target->fetchPairs('site_page_block', 'id', 'position');
        $blockIdsPages = $target->fetchPairs('site_page_block', 'id', 'page_id');

        $mapExhibitBlockIds = array();
        foreach ($blockIdsPositions as $id => $position) {
            // Don't check page title.
            if ($position == 1) {
                continue;
            }
            $pageId = $blockIdsPages[$id];
            // Check if this is a block from the main site.
            if (!in_array($pageId, $mappedExhibitPageIds)) {
                continue;
            }
            $key = array_search($pageId . '-' . $position, $mapExhibitBlockIndexesIds);
            if ($key === false) {
                throw new UpgradeToOmekaS_Exception(
                    __('An error occurred during the upgrade of some exhibit page blocks.'));
            }
            // When a block is divided, keep only the first.
            $key = strtok($key, '-');
            if (!isset($mapExhibitBlockIds[$key])) {
                $mapExhibitBlockIds[(integer) $key] = (integer) $id;
            }
        }

        $this->storeMappedIds('ExhibitPageBlock', $mapExhibitBlockIds);

        $this->_log('[' . __FUNCTION__ . ']: ' . __('All exhibit page blocks (%d) have been upgraded.',
                count($mapExhibitBlockIds)),
            Zend_Log::INFO);
    }

    protected function _upgradeDataExhibitBlockAttachments()
    {
        $recordType = 'ExhibitBlockAttachment';

        $totalRecords = total_records($recordType);
        if (empty($totalRecords)) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('No exhibit block attachment to upgrade.'),
                Zend_Log::INFO);
            return;
        }
        // Progress is managed by all exhibits, pages, blocks and attachments.

        $db = $this->_db;
        $target = $this->getTarget();
        $targetDb = $target->getDb();

        // Prepare the mapping of file ids.
        $mappedFileIds = $this->fetchMappedIds('File');
        if (empty($mappedFileIds)) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('Some exhibit block attachments (%d) can’t be upgraded because files are not upgraded.',
                    $totalRecords),
                Zend_Log::WARN);
            return;
        }

        // The process uses the regular queries of Omeka in order to keep
        // only good records and to manage filters.
        $table = $db->getTable($recordType);

        $mappedExhibitPageBlockIds = $this->fetchMappedIds('ExhibitPageBlock');
        $missing = 0;

        $loops = floor(($totalRecords - 1) / $this->maxChunk) + 1;
        for ($page = 1; $page <= $loops; $page++) {
            $this->_progress($this->_progressCurrent);
            $records = $table->findBy(array(), $this->maxChunk, $page);

            $baseId = 0;
            $toInserts = array();
            foreach ($records as $record) {
                ++$this->_progressCurrent;
                if (!isset($mappedExhibitPageBlockIds[$record->block_id])) {
                    ++$missing;
                    continue;
                }
                $blockId = $mappedExhibitPageBlockIds[$record->block_id];

                if (!isset($mappedFileIds[$record->file_id])) {
                    ++$missing;
                    continue;
                }
                $mediaId = $mappedFileIds[$record->file_id];

                $toInsert = array();
                $toInsert['id'] = null;
                $toInsert['block_id'] = $blockId;
                $toInsert['item_id'] = $record->item_id;
                $toInsert['media_id'] = $mediaId;
                $toInsert['caption'] = (string) $record->caption;
                $toInsert['position'] = $record->order;
                $toInserts['site_block_attachment'][] = $target->cleanQuote($toInsert);
            }

            $target->insertRowsInTables($toInserts);
        }

        if ($missing) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('Some exhibit block attachements (%d) have no exhibit page block and weren’t imported.',
                $missing),
                Zend_Log::WARN);
        }

        $this->_log('[' . __FUNCTION__ . ']: ' . __('All exhibit block attachments (%d) have been upgraded.',
                $totalRecords),
            Zend_Log::INFO);

        $plugin = get_record('Plugin', array('name' => $this->pluginName));
        if (version_compare($plugin->getDbVersion(), '3.3.4', '<')) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('The new field "menu_title" is not yet upgradable (Exhibit >= 3.3.4).'),
                Zend_Log::INFO);
        }
    }

    protected function _upgradeDataExhibitSettings()
    {
        $recordType = 'Exhibit';

        $totalRecords = total_records($recordType);
        if (empty($totalRecords)) {
            return;
        }
        // Progress is managed by all exhibits, pages, blocks and attachments.

        $db = $this->_db;
        $target = $this->getTarget();
        $targetDb = $target->getDb();

        // Prepare options.
        $searchResourceTypes = $this->getProcessor('Core/Site')->upgradeSearchRecordTypes();
        $showEmptyProperties = (string) get_option('show_empty_elements');
        $upgradeShowVocabularyHeadings = (string) get_option('show_element_set_headings');
        $tagDelimiter = (string) get_option('tag_delimiter');
        $useAdvancedSearch = $this->_getThemeOption('use_advanced_search');
        $useSquareThumbnail = (string) get_option('use_square_thumbnail');

        // The process uses the regular queries of Omeka in order to keep
        // only good records and to manage filters.
        $table = $db->getTable($recordType);

        // Prepare the mapping  of file ids.
        $mappedFileIds = $this->fetchMappedIds('File');
        $mappedExhibitIds = $this->fetchMappedIds('Exhibit');
        $mainSiteTheme = $this->getSiteTheme();
        $defaultSettings = $this->getProcessor('Core/Themes')->prepareThemeSettings();

        $exhibits = $table->findBy(array());
        foreach ($exhibits as $exhibit) {
            if (!isset($mappedExhibitIds[$exhibit->id])) {
                continue;
            }
            $siteId = $mappedExhibitIds[$exhibit->id];

            // Set the site settings.
            $target->saveSiteSetting('upgrade_search_resource_types', $searchResourceTypes, $siteId);
            $target->saveSiteSetting('upgrade_show_empty_properties', $showEmptyProperties, $siteId);
            $target->saveSiteSetting('upgrade_show_vocabulary_headings', $upgradeShowVocabularyHeadings, $siteId);
            $target->saveSiteSetting('upgrade_tag_delimiter', $tagDelimiter, $siteId);
            $target->saveSiteSetting('upgrade_use_advanced_search', $useAdvancedSearch, $siteId);
            $target->saveSiteSetting('upgrade_use_square_thumbnail', $useSquareThumbnail, $siteId);

            // Set the theme settings.
            $theme = $exhibit->theme ?: $mainSiteTheme;
            $settings = isset($defaultSettings[$theme])
                ? $defaultSettings[$theme]
                : array();
            // Remove the option for the homepage.
            $settings['use_homepage_template'] = '0';
            $nameSetting = 'theme_settings_' . $theme;
            $target->saveSiteSetting($nameSetting, $settings, $siteId);
        }
    }

    protected function _upgradeDataExhibitSummary()
    {
        $recordType = 'Exhibit';

        $totalRecords = total_records($recordType);
        if (empty($totalRecords)) {
            return;
        }
        // Progress is managed by all exhibits, pages, blocks and attachments.

        $db = $this->_db;
        $target = $this->getTarget();
        $targetDb = $target->getDb();

        // The process uses the regular queries of Omeka in order to keep
        // only good records and to manage filters.
        $table = $db->getTable($recordType);

        $mappedExhibitIds = $this->fetchMappedIds('Exhibit');
        $mappedFileIds = $this->fetchMappedIds('File');

        $exhibits = $table->findBy(array());
        foreach ($exhibits as $exhibit) {
            $toInerts = array();
            if (!isset($mappedExhibitIds[$exhibit->id])) {
                continue;
            }
            $siteId = $mappedExhibitIds[$exhibit->id];

            $hasSummary = !empty($exhibit->cover_image_file_id)
                || !empty($exhibit->description)
                || !empty($exhibit->credits);
            if (!$hasSummary) {
                continue;
            }

            $id = null;

            $toInsert = array();
            $toInsert['id'] = $id;
            $toInsert['site_id'] = $siteId;
            $toInsert['slug'] = 'summary';
            $toInsert['title'] = __('Summary');
            $toInsert['created'] = $this->getDatetime();
            $toInsert['modified'] = $this->getDatetime();
            $toInserts['site_page'][] = $target->cleanQuote($toInsert);

            $id = 'LAST_INSERT_ID()';
            $position = 0;

            $toInsert = array();
            $toInsert['id'] = null;
            $toInsert['page_id'] = $id;
            $toInsert['layout'] = 'pageTitle';
            $toInsert['data'] = $target->toJson(array());
            $toInsert['position'] = ++$position;
            $toInserts['site_page_block'][] = $target->cleanQuote($toInsert, 'page_id');

            if ($exhibit->cover_image_file_id) {
                $toInsert = array();
                $toInsert['id'] = null;
                $toInsert['page_id'] = $id;
                $toInsert['layout'] = 'media';
                $toInsert['data'] = $target->toJson(array(
                    'thumbnail_type' => 'large',
                    'alignment' => 'left',
                    'show_title_option' => 'item_title',
                ));
                $toInsert['position'] = ++$position;
                $toInserts['site_page_block'][] = $target->cleanQuote($toInsert, 'page_id');

                if (!empty($mappedFileIds[$exhibit->cover_image_file_id])) {
                    $mediaId = $mappedFileIds[$exhibit->cover_image_file_id];
                    $file = get_record_by_id('File', $exhibit->cover_image_file_id);

                    $toInsert = array();
                    $toInsert['id'] = null;
                    // Last inserted id + 1 because the last inserted id is the
                    // first of the previous table, so the page title here.
                    $toInsert['block_id'] = 'LAST_INSERT_ID() + 1';
                    $toInsert['item_id'] = $file->item_id;
                    $toInsert['media_id'] = $mediaId;
                    $toInsert['caption'] = '';
                    $toInsert['position'] = 1;
                    $toInserts['site_block_attachment'][] = $target->cleanQuote($toInsert, 'block_id');
                }
            }

            if ($exhibit->description) {
                $toInsert = array();
                $toInsert['id'] = null;
                $toInsert['page_id'] = $id;
                $toInsert['layout'] = 'html';
                $toInsert['data'] = $target->toJson(array(
                    'html' => $exhibit->description,
                ));
                $toInsert['position'] = ++$position;
                $toInserts['site_page_block'][] = $target->cleanQuote($toInsert, 'page_id');
            }

            if ($exhibit->credits) {
                $toInsert = array();
                $toInsert['id'] = null;
                $toInsert['page_id'] = $id;
                $toInsert['layout'] = 'html';
                $toInsert['data'] = $target->toJson(array(
                    'html' => '<p>' . __('%sCredits%s: %s', '<strong>', '</strong>', $exhibit->credits) . '</p>',
                ));
                $toInsert['position'] = ++$position;
                $toInserts['site_page_block'][] = $target->cleanQuote($toInsert, 'page_id');
            }

            $target->insertRowsInTables($toInserts);
        }
    }

    protected function _upgradeDataExhibitNavigation()
    {
        $recordType = 'Exhibit';

        $totalRecords = total_records($recordType);
        if (empty($totalRecords)) {
            return;
        }
        // Progress is managed by all exhibits, pages, blocks and attachments.

        $db = $this->_db;
        $target = $this->getTarget();
        $targetDb = $target->getDb();

        // The process uses the regular queries of Omeka in order to keep
        // only good records and to manage filters.
        $table = $db->getTable($recordType);

        $mappedExhibitIds = $this->fetchMappedIds('Exhibit');

        $exhibits = $table->findBy(array());
        foreach ($exhibits as $exhibit) {
            if (!isset($mappedExhibitIds[$exhibit->id])) {
                continue;
            }
            $siteId = $mappedExhibitIds[$exhibit->id];

            $sitePages = array();

            // Add existing pages (summary).
            $hasSummary = !empty($exhibit->cover_image_file_id)
                || !empty($exhibit->description)
                || !empty($exhibit->credits);
            if ($hasSummary) {
                $select = $targetDb->select()
                    ->from('site_page', 'id')
                    ->order('id')
                    ->where('site_id = ' . $siteId);
                $page = $targetDb->fetchOne($select);
                if ($page) {
                    $sitePages[] = array(
                        'type' => 'page',
                        'data' => array(
                            'label' => '',
                            'id' => $page,
                        ));
                }
            }

            $pages = $exhibit->getPagesByParent();
            if ($pages && isset($pages[0])) {
                foreach ($pages[0] as $topPage) {
                    $sitePages[] = $this->_addBranch($pages, $topPage, array());
                }
            }

            if (empty($sitePages)) {
                continue;
            }

            // Check if there is an exhibit.
            $select = $targetDb->select()
                ->from('site')
                ->where('id = ?', $siteId);
            $result = $targetDb->fetchRow($select);
            if (!$result) {
                throw new UpgradeToOmekaS_Exception(
                    __('An error occurred during the upgrade of the navigation of the exhibit #%d.', $exhibit->id));
            }

            $where = array();
            $where[] = 'id = ' . $siteId;
            $result = $targetDb->update(
                'site',
                array('navigation' => $this->toJson($sitePages)),
                $where);
        }
    }

    /**
     * Recursively create the array for a "branch" (a page and its descendants)
     * of the tree, with ids of Omeka S.
     *
     * @param array $pages
     * @param ExhibitPage $page
     * @param array $ancestorIds
     * @return array
     */
    protected function _addBranch($pages, $page, $ancestorIds = array())
    {
        $mappedExhibitPageIds = $this->fetchMappedIds('ExhibitPage');

        if (!isset($mappedExhibitPageIds[$page->id])) {
            return;
        }

        $result = array(
            'type' => 'page',
            'data' => array(
                'label' => $page->title,
                'id' => $mappedExhibitPageIds[$page->id],
        ));

        if (isset($pages[$page->id])) {
            $result['links'] = array();
            foreach ($pages[$page->id] as $childPage) {
                $result['links'][] = $this->_addBranch($pages, $childPage, $ancestorIds);
            }
        }
        return $result;
    }

    protected function _convertNavigationPageToLink($page, $parsed, $site)
    {
        // TODO Get the matching slug in exhibit pages.
        return;

        static $slugs;

        // Check if this is a slug.
        $slug = ltrim($parsed['path'], '/');
        if (strlen($slug) == 0) {
            return;
        }

        // List all exhibit and page slugs (exhibit slug + parent page slugs
        // + page slug).

        // Check the slug.
        // if (in_array($slug, $slugs)) {
        //     // Get the matching slug in Omeka S.
        //
        //     return array(
        //         'type' => 'page',
        //         'data' => array(
        //             'label' => $page['label'],
        //             'id' => $sitePageId,
        //     ));
        // }
    }
}
