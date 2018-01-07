<?php

/**
 * Upgrade Tagging to Omeka S.
 *
 * @package UpgradeToOmekaS
 */
class UpgradeToOmekaS_Processor_Tagging extends UpgradeToOmekaS_Processor_Abstract
{

    public $pluginName = 'Tagging';
    public $minVersion = '2.1';
    public $maxVersion = '';

    public $module = array(
        'name' => 'Folksonomy',
        'version' => null,
        'url' => 'https://github.com/Daniel-KM/Omeka-S-module-Folksonomy/archive/master.zip',
        'size' => null,
        'sha1' => null,
        'type' => 'port',
        'note' => 'Add tags to resources and a tagging form in public pages.',
        'install' => array(
            // Copied from the original module.php.
            'sql' => '
CREATE TABLE `tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_389B7835E237E06` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `tagging` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tag_id` int(11) DEFAULT NULL,
  `resource_id` int(11) DEFAULT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `status` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `owner_tag_resource` (`owner_id`,`tag_id`,`resource_id`),
  KEY `IDX_A4AED1237B00651C` (`status`),
  KEY `IDX_A4AED123BAD26311` (`tag_id`),
  KEY `IDX_A4AED12389329D25` (`resource_id`),
  KEY `IDX_A4AED1237E3C61F9` (`owner_id`),
  CONSTRAINT `FK_A4AED1237E3C61F9` FOREIGN KEY (`owner_id`) REFERENCES `user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_A4AED12389329D25` FOREIGN KEY (`resource_id`) REFERENCES `resource` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_A4AED123BAD26311` FOREIGN KEY (`tag_id`) REFERENCES `tag` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
',
            'settings' => array(
                'folksonomy_public_allow_tag' => true,
                'folksonomy_public_require_moderation' => false,
                'folksonomy_public_notification' => true,
                'folksonomy_max_length_tag' => 190,
                'folksonomy_max_length_total' => 1000,
                'folksonomy_message' => '+',
                'folksonomy_legal_text' => '',
            ),
            'site_settings' => array(
                'folksonomy_append_item_set_show' => true,
                'folksonomy_append_item_show' => true,
                'folksonomy_append_media_show' => true,
            ),
        ),
    );

    public $tables = array(
        'tagging',
        'tag',
    );

    public $mapping_models = array(
        'tag' => 'tag',
        'tagging' => 'tagging',
    );

    public $processMethods = array(
        '_installModule',
    );

    protected function _installModule()
    {
        // Don't install twice: Core/Tags installed it automatically.
        if ($this->isCore()) {
            parent::_installModule();
        }
    }

    protected function _upgradeSettings()
    {
        if (!plugin_is_active('Tagging')) {
            $this->module['install']['settings']['folksonomy_legal_text'] = '<p>'
                . __('I agree with %sterms of use%s and I accept to free my contribution under the licence %sCC BY-SA%s.',
                    '<a rel="licence" href="#" target="_blank">', '</a>',
                    '<a rel="licence" href="https://creativecommons.org/licenses/by-sa/3.0/" target="_blank">', '</a>'
            );
            parent::_upgradeSettings();
            return;
        }

        $target = $this->getTarget();

        $mapOptions = array(
            'tagging_form_class' => null,
            'tagging_max_length_total' => 'folksonomy_max_length_total',
            'tagging_max_length_tag' => 'folksonomy_max_length_tag',
            'tagging_message' => 'folksonomy_message',
            'tagging_legal_text' => 'folksonomy_legal_text',
            'tagging_public_allow_tag' => 'folksonomy_public_allow_tag',
            'tagging_public_require_moderation' => 'folksonomy_public_require_moderation',
            'tagging_tag_roles' => null,
            'tagging_require_moderation_roles' => null,
            'tagging_moderate_roles' => null,
        );
        foreach ($mapOptions as $option => $setting) {
            if (empty($setting)) {
                continue;
            }
            $value = get_option($option);
            $target->saveSetting($setting, $value);
        }
    }

    protected function _upgradeData()
    {
        // Tag ids are kept, but not the tagging ids, because there is a merge
        // of two tables.
        $this->_upgradeDataTags();
        $this->_upgradeDataRecordsTags();
        if (plugin_is_active('Tagging')) {
            $this->_upgradeDataTaggings();
        }
    }

    protected function _upgradeDataTags()
    {
        $recordType = 'Tag';

        $totalRecords = total_records($recordType);
        if (empty($totalRecords)) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('No tag to upgrade.'),
                Zend_Log::INFO);
            return;
        }
        $this->_progress(0, $totalRecords);

        $db = $this->_db;
        $target = $this->getTarget();
        $targetDb = $target->getDb();

        // Unlike other records, this table is copied directly in one query.
        // Note that tag without records are copied too (they are tags detached
        // of records by the plugin Tagging, if any, or dead tags, that will be
        // easily managed in Omeka S Folksonomy, without loss, and without issue
        // with the case of characters.
        $toInserts = array();
        $sql = "SELECT tags.* FROM {$db->Tag} tags";
        $result = $db->fetchAll($sql);
        $toInserts['tag'] = $result;
        $target->insertRowsInTables($toInserts, array(), false);

        // // The process uses the regular queries of Omeka in order to keep
        // // only good records and to manage filters.
        // $table = $db->getTable($recordType);

        // $loops = floor(($totalRecords - 1) / $this->maxChunk) + 1;
        // for ($page = 1; $page <= $loops; $page++) {
        //     $this->_progress(($page - 1) * $this->maxChunk);
        //     $records = $table->findBy(array(), $this->maxChunk, $page);

        //     $toInserts = array();
        //     foreach ($records as $record) {
        //         $toInsert = array();
        //         $toInsert['id'] = $record->id;
        //         $toInsert['name'] = $record->name;
        //         $toInserts['tag'][] = $target->cleanQuote($toInsert);
        //     }

        //     $target->insertRowsInTables($toInserts);
        // }

        $this->_log('[' . __FUNCTION__ . ']: ' . __('All tags (%d) have been upgraded.',
            $totalRecords), Zend_Log::INFO);
        if (count($result) != $totalRecords) {
            if (!plugin_is_active('Tagging')) {
                $this->_log('[' . __FUNCTION__ . ']: ' . __('Some tags (%d) are not attached to a record.',
                    count($result) - $totalRecords), Zend_Log::NOTICE);
            }
        }
    }

    protected function _upgradeDataRecordsTags()
    {
        $recordType = 'RecordsTags';

        $totalRecords = total_records($recordType);
        if (empty($totalRecords)) {
            return;
        }
        $this->_progress(0, $totalRecords);

        $db = $this->_db;
        $target = $this->getTarget();
        $targetDb = $target->getDb();

        $targetTags = $target->fetchPairs('tag', 'id', 'name');
        $targetUserIds = $target->fetchIds('user');

        // The process uses the regular queries of Omeka in order to keep
        // only good records and to manage filters.
        $table = $db->getTable($recordType);

        $skipped = 0;
        $loops = floor(($totalRecords - 1) / $this->maxChunk) + 1;
        for ($page = 1; $page <= $loops; $page++) {
            $this->_progress(($page - 1) * $this->maxChunk);
            $records = $table->findBy(array(), $this->maxChunk, $page);

            $toInserts = array();
            foreach ($records as $record) {
                if ($record->record_type !== 'Item') {
                    ++$skipped;
                    continue;
                }

                // Check the taggings to see if there is  the status, the owner
                // and the time.
                $sourceTagging = isset($targetTags[$record->tag_id])
                    ? get_record('Tagging', array(
                        'record_type' => $record->record_type,
                        'record_id' => $record->record_id,
                        'name' => $targetTags[$record->tag_id],
                    ))
                    : null;
                if ($sourceTagging) {
                    $status = $sourceTagging->status;
                    $ownerId = isset($targetUserIds[$sourceTagging->user_id])
                        ? $targetUserIds[$sourceTagging->user_id]
                        : null;
                    $created = $sourceTagging->added >= $record->time
                        ? $record->time
                        : $sourceTagging->added;
                    $modified = $sourceTagging->added < $record->time
                        ? $record->time
                        : ($sourceTagging->added > $record->time ? $sourceTagging->added: null);
                } else {
                    $status = 'approved';
                    $ownerId = null;
                    $created = $record->time;
                    $modified = null;
                }

                $toInsert = array();
                $toInsert['id'] = $record->id;
                $toInsert['tag_id'] = $record->tag_id;
                $toInsert['resource_id'] = $record->record_id;
                $toInsert['owner_id'] = $ownerId;
                $toInsert['status'] = $status;
                $toInsert['created'] = $created;
                $toInsert['modified'] = $modified;
                $toInserts['tagging'][] = $target->cleanQuote($toInsert);
            }

            $target->insertRowsInTables($toInserts);
        }

        if ($skipped) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('Some tags (%d/%d) were attached to exhibits and were not upgraded.',
                $skipped, $totalRecords), Zend_Log::NOTICE);
        }
    }

    protected function _upgradeDataTaggings()
    {
        $recordType = 'Tagging';

        $totalRecords = total_records($recordType);
        if (empty($totalRecords)) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('No tagging to upgrade.'),
                Zend_Log::INFO);
            return;
        }
        $this->_progress(0, $totalRecords);

        $db = $this->_db;
        $target = $this->getTarget();
        $targetDb = $target->getDb();

        // In the plugin, the name are not immediately saved in the tag table,
        // but they are set in the module.

        // First, add missing tag names from tagging not approved or rejected.
        $sql = "SELECT tagging.name
        FROM {$db->Tagging} tagging
        LEFT JOIN {$db->Tag} tag on tagging.name = tag.name
        WHERE tag.name IS NULL
        group by tagging.name;";
        $nonMatchingTags = $db->fetchCol($sql);

        $toInserts = array();
        foreach ($nonMatchingTags as $tag) {
            $toInsert = array();
            $toInsert['id'] = null;
            $toInsert['name'] = $tag;
            $toInserts['tag'][] = $target->cleanQuote($toInsert);
        }
        $target->insertRowsInTables($toInserts);

        // Get the matching tags to avoid issues with the case of characters.
        $sql = "SELECT tagging.name AS tagging, tag.name AS tag, tag.id AS id
        FROM {$db->Tag} tag
        LEFT JOIN {$db->Tagging} tagging on tagging.name = tag.name
        WHERE tagging.name IS NOT NULL
        group by tagging.name;";
        $matchingTags = $db->fetchAssoc($sql);

        // Second, import taggings that are not records-tags (normally, only
        // proposed and rejected tags).

        $targetTags = $target->fetchPairs('tag', 'id', 'name');
        $targetTaggings = $target->fetchTable('tagging');
        // Ids of resources and owners are kept, but the plugin didn't manage
        // the remove, so a check is done.
        $targetResourceIds = $target->fetchIds('resource');
        $targetUserIds = $target->fetchIds('user');

        // The process uses the regular queries of Omeka in order to keep
        // only good records and to manage filters.
        $table = $db->getTable($recordType);

        $skipped = 0;
        $loops = floor(($totalRecords - 1) / $this->maxChunk) + 1;
        for ($page = 1; $page <= $loops; $page++) {
            $this->_progress(($page - 1) * $this->maxChunk);
            // Only the private taggings are search: other ones are already
            // upgraded.
            $records = $table->findBy(array(
                // 'status' => array('proposed', 'rejected'),
                // 'record_type' => 'Item',
            ), $this->maxChunk, $page);

            $toInserts = array();
            foreach ($records as $record) {
                if ($record->record_type !== 'Item' || empty($record->record_id)) {
                    ++$skipped;
                    continue;
                }

                // The removed records should have been removed by the plugin.
                $resourceId = isset($targetResourceIds[$record->record_id])
                    ? $targetResourceIds[$record->record_id]
                    : null;
                $ownerId = isset($targetUserIds[$record->user_id])
                    ? $targetUserIds[$record->user_id]
                    : null;

                $name = isset($matchingTags[$record->name])
                    ? $matchingTags[$record->name]['tag']
                    : $record->name;
                $tagId = array_search($name, $targetTags);

                // Check the unique key for tag / resource / owner.
                // Note: the search for a unique key that contains a null is
                // always false in sql.
                // Normally, all allowed and approved tags will be found, not
                // the proposed and rejected.
                if ($tagId && $resourceId && $ownerId) {
                    $select = $targetDb->select()
                        ->from('tagging')
                        ->where('tag_id = ?', $tagId)
                        ->where('resource_id = ?', $resourceId)
                        ->where('owner_id = ?', $ownerId);
                    $result = $targetDb->fetchOne($select);
                    if ($result) {
                        continue;
                    }
                }

                $toInsert = array();
                $toInsert['id'] = null;
                $toInsert['tag_id'] = $tagId;
                $toInsert['resource_id'] = $resourceId;
                $toInsert['owner_id'] = $ownerId;
                $toInsert['status'] = $record->status;
                $toInsert['created'] = $record->added;
                $toInsert['modified'] = null;
                $toInserts['tagging'][] = $target->cleanQuote($toInsert);
            }

            $target->insertRowsInTables($toInserts);
        }

        if ($skipped) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('Some taggings from exhibits (%d/%d) have been skipped.',
                $skipped, $totalRecords), Zend_Log::NOTICE);
        } else {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('All taggings (%d) have been upgraded.',
                $totalRecords), Zend_Log::INFO);
        }
    }

    protected function _convertNavigationPageToLink($page, $parsed, $site)
    {
        $path = $parsed['path'];
        if (strlen($path) == 0) {
            return;
        }

        switch ($path) {
            case strpos($path, '/items/tags') === 0:
                return array(
                    'type' => 'url',
                    'data' => array(
                        'label' => $page['label'],
                        'url' => $site['omekaSSitePath'] . '/tags',
                ));
        }
    }
}
