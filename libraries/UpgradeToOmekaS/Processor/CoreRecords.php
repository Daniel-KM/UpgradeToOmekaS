<?php

/**
 * Upgrade Core Records to Omeka S.
 *
 * @package UpgradeToOmekaS
 */
class UpgradeToOmekaS_Processor_CoreRecords extends UpgradeToOmekaS_Processor_AbstractCore
{

    public $pluginName = 'Core/Records';

    public $processMethods = array(
        // Items are upgraded before collections in order to keep their ids.
        '_upgradeItems',
        '_createItemSetForSite',
        '_upgradeCollections',
        '_setCollectionsOfItems',
        '_upgradeItemFiles',
        '_upgradeMetadata',
    );

    /**
     * The mapping between classes between Omeka C and Omeka S.
     *
     * @var array
     */
    protected $_mappingRecordClasses = array(
        'Item' => 'Omeka\Entity\Item',
        'Collection' => 'Omeka\Entity\ItemSet',
        'File' => 'Omeka\Entity\Media',
    );

    /**
     * The item set for the site.
     *
     * @var integer
     */
    protected $_itemSetSiteId;

    /**
     * @see /application/data/migrations/20170601084322_DedupeMediaTypes.php
     */
    public $mediaTypeAliases = array(
        // application/ogg
        'application/x-ogg' => 'application/ogg',
        // application/rtf
        'text/rtf' => 'application/rtf',
        // audio/midi
        'audio/mid' => 'audio/midi',
        'audio/x-midi' => 'audio/midi',
        // audio/mpeg
        'audio/mp3' => 'audio/mpeg',
        'audio/mpeg3' => 'audio/mpeg',
        'audio/x-mp3' => 'audio/mpeg',
        'audio/x-mpeg' => 'audio/mpeg',
        'audio/x-mpeg3' => 'audio/mpeg',
        'audio/x-mpegaudio' => 'audio/mpeg',
        'audio/x-mpg' => 'audio/mpeg',
        // audio/ogg
        'audio/x-ogg' => 'audio/ogg',
        // audio/x-aac
        'audio/aac' => 'audio/x-aac',
        // audio/x-aiff
        'audio/aiff' => 'audio/x-aiff',
        // audio/x-ms-wma
        'audio/x-wma' => 'audio/x-ms-wma',
        'audio/wma' => 'audio/x-ms-wma',
        // audio/mp4
        'audio/x-mp4' => 'audio/mp4',
        'audio/x-m4a' => 'audio/mp4',
        // audio/x-wav
        'audio/wav' => 'audio/x-wav',
        // image/bmp
        'image/x-ms-bmp' => 'image/bmp',
        // image/x-icon
        'image/icon' => 'image/x-icon',
        // video/mp4
        'video/x-m4v' => 'video/mp4',
        // video/x-ms-asf
        'video/asf' => 'video/x-ms-asf',
        // video/x-ms-wmv
        'video/wmv' => 'video/x-ms-wmv',
        // video/x-msvideo
        'video/avi' => 'video/x-msvideo',
        'video/msvideo' => 'video/x-msvideo',
    );

    protected function _upgradeItems()
    {
        // Because items are the first resource upgraded, their ids are kept.
        // This implies that files are upgraded separately and that the
        // collection id of all items are set in a second step.
        $this->_upgradeRecords('Item');

        $this->_log('[' . __FUNCTION__ . ']: ' . __('The record status "Featured" doesn’t exist in Omeka S.'),
            Zend_Log::INFO);
    }

    protected function _createItemSetForSite()
    {
        $db = $this->_db;
        $target = $this->getTarget();
        $targetDb = $target->getDb();

        $user = $this->getParam('user');

        $recordType = 'Collection';

        $siteId = $this->getSiteId();

        // This should be the first item set to simplify next processes.
        $totalTarget= $target->totalRows('item_set');
        if ($totalTarget) {
            throw new UpgradeToOmekaS_Exception(
                __('The item set created for the site should be the first one.'));
        }

        // TODO Add the resource template when it will be created.
        $defaultResourceTemplateId = 1;

        $id = null;

        $toInserts = array();

        $toInsert = array();
        $toInsert['id'] = $id;
        $toInsert['owner_id'] = $user->id;
        $toInsert['resource_class_id'] = null;
        $toInsert['resource_template_id'] = $defaultResourceTemplateId;
        $toInsert['is_public'] = 0;
        $toInsert['created'] = $this->getDatetime();
        $toInsert['modified'] = $this->getDatetime();
        $toInsert['resource_type'] = $this->_mappingRecordClasses[$recordType];
        $toInserts['resource'][] = $target->cleanQuote($toInsert);

        $id = 'LAST_INSERT_ID()';

        $toInsert = array();
        $toInsert['id'] = $id;
        $toInsert['is_open'] = 1;
        $toInserts['item_set'][] = $target->cleanQuote($toInsert, 'id');

        // Give it some metadata (6).
        $properties = array();
        $properties['dcterms:title'][] = __('All items of the site "%s"', $this->getSiteTitle());
        $properties['dcterms:creator'][] = get_option('author') ?: $user->name;
        $properties['dcterms:rights'][] = get_option('copyright') ?: __('Public Domain');
        $properties['dcterms:description'][] = get_option('description') ?: __('This collection contains all items of the site.');
        $properties['dcterms:date'][] = $this->getDatetime();
        $properties['dcterms:replaces'][] = array(
            'type' => 'uri',
            'uri' => $this->getParam('WEB_ROOT') ?: '#',
            'value' => __('Digital library powered by Omeka Classic'),
        );
        $toInserts['value'] = $this->_prepareRowsForProperties($id, $properties);

        $target->insertRowsInTables($toInserts);

        // Save the item set id of the site. This is always the first item set.
        $itemSetSiteId = $this->_saveItemSetSiteId();
        if (empty($itemSetSiteId)) {
            throw new UpgradeToOmekaS_Exception(
                __('The first created site can’t be found in Omeka S.'));
        }

        // Set the item set as the first one for the site.
        $toInserts = array();
        $toInsert = array();
        $toInsert['id'] = null;
        $toInsert['site_id'] = $siteId;
        $toInsert['item_set_id'] = $itemSetSiteId;
        $toInsert['position'] = 1;
        $toInserts[] = $target->cleanQuote($toInsert);
        $target->insertRows('site_item_set', $toInserts);

        $this->_log('[' . __FUNCTION__ . ']: ' . __('A private item set has been set for the site.'),
            Zend_Log::INFO);
    }

    protected function _upgradeCollections()
    {
        $this->_upgradeRecords('Collection');

        $siteId = $this->getSiteId();
        $itemSetSiteId = $this->_getItemSetSiteId();

        // Attach all the collections to the site.
        $target = $this->getTarget();
        $targetDb = $target->getDb();

        $select = $targetDb->select()
            ->from('item_set', array('id'));
        if ($itemSetSiteId) {
            $select
                ->where('id != ?', $itemSetSiteId);
        }
        $itemSets = $targetDb->fetchCol($select);

        $toInserts = array();
        $position = 1;
        foreach ($itemSets as $i => $id) {
            $toInsert = array();
            $toInsert['id'] = null;
            $toInsert['site_id'] = $siteId;
            $toInsert['item_set_id'] = (integer) $id;
            $toInsert['position'] = ++$position;
            $toInserts[] = $target->cleanQuote($toInsert);
        }
        $target->insertRows('site_item_set', $toInserts);

        $this->_log('[' . __FUNCTION__ . ']: ' . __('The status "Is Open" has been added to collections.'),
            Zend_Log::INFO);
    }

    protected function _setCollectionsOfItems()
    {
        $recordType = 'Item';
        $mappedType = 'item_item_set';

        $db = $this->_db;
        $target = $this->getTarget();
        $targetDb = $target->getDb();

        $totalRecords = total_records($recordType);
        if (empty($totalRecords)) {
            return;
        }
        $this->_progress(0, $totalRecords);

        $siteId = $this->getSiteId();

        $mappedCollectionIds = $this->fetchMappedIds('Collection');

        $table = $db->getTable($recordType);

        // Get the item set id of the site, if any.
        $itemSetSiteId = $this->_getItemSetSiteId();

        $loops = floor(($totalRecords - 1) / $this->maxChunk) + 1;
        for ($page = 1; $page <= $loops; $page++) {
            $this->_progress(($page - 1) * $this->maxChunk);
            $records = $table->findBy(array(), $this->maxChunk, $page);

            $toInserts = array();
            foreach ($records as $record) {
                if (!empty($record->collection_id)) {
                    if (!isset($mappedCollectionIds[$record->collection_id])) {
                        throw new UpgradeToOmekaS_Exception(
                            __('The collection #%d for item #%d can’t be found in Omeka S.',
                                $record->collection_id, $record->id)
                            . ' ' . __('Check the processors of the plugins.'));
                    }

                    $toInsert = array();
                    $toInsert['item_id'] = $record->id;
                    $toInsert['item_set_id'] = $mappedCollectionIds[$record->collection_id];
                    $toInserts[] = $target->cleanQuote($toInsert);
                }

                if ($itemSetSiteId) {
                    $toInsert = array();
                    $toInsert['item_id'] = $record->id;
                    $toInsert['item_set_id'] = $itemSetSiteId;
                    $toInserts[] = $target->cleanQuote($toInsert);
                }
            }

            $target->insertRows($mappedType, $toInserts);
        }

        // A final check, normally useless.

        // Get the total of items with a collection.
        $select = $db->getTable('Item')->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->from(array(), array(new Zend_Db_Expr('COUNT(*)')))
            ->where('items.collection_id IS NOT NULL');
        $totalItemsWithCollection = $db->fetchOne($select);

        $totalTarget = $target->totalRows($mappedType);
        if ($itemSetSiteId) {
            $totalTarget -= $totalRecords;
        }
        if ($totalItemsWithCollection > $totalTarget) {
            throw new UpgradeToOmekaS_Exception(
                __('Only %d/%d %s have been upgraded into "%s".',
                    $totalTarget, $totalItemsWithCollection, __('items with a collection'), $mappedType));
        }

        // May be possible with plugins?
        if ($totalItemsWithCollection < $totalTarget) {
            throw new UpgradeToOmekaS_Exception(
                __('There are %d upgraded "%s" in Omeka S, but only %d %s in Omeka C.',
                    $totalTarget, $mappedType, $totalItemsWithCollection, __('items with a collection'))
                . ' ' . __('Check the processors of the plugins.'));
        }

        $this->_log('[' . __FUNCTION__ . ']: ' . __('In Omeka S, an item can belong to multiple collections (item sets) and multipe sites.'),
            Zend_Log::INFO);
    }

    protected function _upgradeItemFiles()
    {
        $this->_upgradeRecords('File');

        $this->_log('[' . __FUNCTION__ . ']: ' . __('In Omeka S, each attached files can be hidden/shown separately.'),
            Zend_Log::INFO);
    }

    /**
     * Helper to upgrade standard records of Omeka C (items, collections, files).
     *
     * @param string $recordType
     * @throws UpgradeToOmekaS_Exception
     * @return void
     */
    protected function _upgradeRecords($recordType)
    {
        if (!isset($this->mapping_models[inflector::underscore($recordType)])) {
            return;
        }
        $mappedType = $this->mapping_models[inflector::underscore($recordType)];

        // Prepare a string for the messages.
        $recordTypeSingular = strtolower(Inflector::humanize(Inflector::underscore($recordType)));
        $recordTypePlural = strtolower(Inflector::pluralize($recordTypeSingular));

        $totalRecords = total_records($recordType);
        if (empty($totalRecords)) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('No %s to upgrade.',
                $recordTypeSingular), Zend_Log::INFO);
            return;
        }
        $this->_progress(0, $totalRecords);

        $siteId = $this->getSiteId();

        $db = $this->_db;
        $target = $this->getTarget();
        $targetDb = $target->getDb();

        $user = $this->getParam('user');

        // The process uses the regular queries of Omeka in order to keep
        // only good records and to manage filters.
        $table = $db->getTable($recordType);

        // The list of user ids allows to check if the owner of a record exists.
        // The id of users are kept between Omeka C and Omeka S.
        // Some users may not have been upgraded.
        $targetUserIds = $target->fetchIds('user');

        // TODO Add the resource template when it will be created.
        $defaultResourceTemplateId = null;

        $totalItemTypesUnmapped = 0;

        // Specificities for each record type. This avoids to loop some process.
        switch ($recordType) {
            case 'Item':
                // Check if there are already records.
                $totalExisting = $target->totalRows('resource');
                if ($totalExisting) {
                    // TODO Allow to upgrade without ids (need the last inserted id and a temp mapping of source and destination ids)?
                    throw new UpgradeToOmekaS_Exception(
                        __('Some items (%d) have been upgraded, so ids won’t be kept.',
                            $totalExisting)
                        . ' ' . __('Check the processors of the plugins.'));
                }

                $mappingItemTypes = $this->getProcessor('Core/Elements')
                    ->getMappingItemTypesToClasses();
                break;

            // Nothing specific for collections and files.
        }

        $loops = floor(($totalRecords - 1) / $this->maxChunk) + 1;
        for ($page = 1; $page <= $loops; $page++) {
            $this->_progress(($page - 1) * $this->maxChunk);
            $records = $table->findBy(array(), $this->maxChunk, $page);

            // Initialize the array to map ids of collections and files.
            $remapIds = array();

            $baseId = 0;
            $toInserts = array();
            foreach ($records as $record) {
                $toInsert = array();
                switch ($recordType) {
                    case 'Item':
                        $id = $record->id;
                        $ownerId = isset($targetUserIds[$record->owner_id])
                            ? $record->owner_id
                            : null;
                        if (empty($mappingItemTypes[$record->item_type_id])) {
                            $resourceClassId = null;
                        } elseif (isset($mappingItemTypes[$record->item_type_id])) {
                            $resourceClassId = $mappingItemTypes[$record->item_type_id];
                        } else {
                            $resourceClassId = null;
                            ++$totalItemTypesUnmapped;
                        }
                        $isPublic = (integer) (boolean) $record->public;
                        break;
                    case 'Collection':
                        $id = null;
                        $ownerId = isset($targetUserIds[$record->owner_id])
                            ? $targetUserIds[$record->owner_id]
                            : null;
                        $resourceClassId = null;
                        $isPublic = (integer) (boolean) $record->public;
                        break;
                    case 'File':
                        $id = null;
                        $item = $record->getItem();
                        $ownerId = isset($targetUserIds[$item->owner_id])
                            ? $targetUserIds[$item->owner_id]
                            : null;
                        $resourceClassId = null;
                        $isPublic = 1;
                        break;
                }

                $toInsert['id'] = $id;
                $toInsert['owner_id'] = $ownerId;
                $toInsert['resource_class_id'] = $resourceClassId;
                $toInsert['resource_template_id'] = $defaultResourceTemplateId;
                $toInsert['is_public'] = $isPublic;
                $toInsert['created'] = $record->added;
                $toInsert['modified'] = $record->modified;
                $toInsert['resource_type'] = $this->_mappingRecordClasses[$recordType];
                $toInserts['resource'][] = $target->cleanQuote($toInsert);

                // Check if this is an autoinserted id.
                if (empty($id)) {
                    $remapIds[$recordType][] = $record->id;
                    $id = 'LAST_INSERT_ID() + ' . $baseId;
                    ++$baseId;
                }
                else {
                    $this->_mappingIds[$recordType][$record->id] = (integer) $id;
                }

                $toInsert = array();
                $toInsert['id'] = $id;
                switch ($recordType) {
                    case 'Item':
                        // Currently, the table "item" contains only the id.
                        break;
                    case 'Collection':
                        $toInsert['is_open'] = 1;
                        break;
                    case 'File':
                        // Clean the filename to manage broken filenames if needed.
                        $source = trim($record->original_filename, '/\\' . DIRECTORY_SEPARATOR);
                        $scheme = parse_url($source, PHP_URL_SCHEME);
                        $isRemote = UpgradeToOmekaS_Common::isRemote($source);
                        $extension = pathinfo($source, PATHINFO_EXTENSION);
                        // Clean the filename to manage broken filenames if needed.
                        $filename = trim($record->filename, '/\\' . DIRECTORY_SEPARATOR);
                        // This allows to manage the plugin Archive Repertory.
                        $filename = str_replace(array('/', '\\', DIRECTORY_SEPARATOR), '/', $filename);
                        $storageId = strlen($extension)
                            ? (strlen(pathinfo($filename, PATHINFO_EXTENSION))
                                ? substr($filename, 0, strrpos($filename, pathinfo($filename, PATHINFO_EXTENSION)) - 1)
                                : $filename
                            )
                            : $filename;
                        // Normalize the media type if needed.
                        $mediaType = isset($this->mediaTypeAliases[$record->mime_type])
                            ? $this->mediaTypeAliases[$record->mime_type]
                            : $record->mime_type;
                        $toInsert['item_id'] = $item->id;
                        $toInsert['ingester'] = $isRemote ? 'url' : 'upload';
                        $toInsert['renderer'] = 'file';
                        $toInsert['data'] = null;
                        $toInsert['source'] = $source;
                        $toInsert['media_type'] = $mediaType;
                        $toInsert['storage_id'] = $storageId;
                        $toInsert['extension'] = $extension;
                        // The sha256 is optional and set later (here or in the
                        // compatibility module).
                        $toInsert['sha256'] = null;
                        $toInsert['has_original'] = 1;
                        $toInsert['has_thumbnails'] = (integer) (boolean) $record->has_derivative_image;
                        $toInsert['position'] = $record->order ?: 1;
                        $toInsert['lang'] = null;
                        break;
                }
                $toInserts[$mappedType][] = $target->cleanQuote($toInsert, 'id');
            }

            $target->insertRowsInTables($toInserts);

            // Remaps only if needed.
            if (!empty($remapIds[$recordType])) {
                $this->_remapIds($remapIds[$recordType], $recordType);
            }
        }

        $this->storeMappedIds($recordType, $this->_mappingIds[$recordType]);

        // A final check, normally useless.
        $totalTarget = $target->totalRows($mappedType);

        // Substract the default item set for the site beforechecks, if any.
        $isItemSetSite = (boolean) $this->_getItemSetSiteId();
        if ($recordType == 'Collection' && $isItemSetSite) {
            --$totalTarget;
        }

        if ($totalRecords > $totalTarget) {
            throw new UpgradeToOmekaS_Exception(
                __('Only %d/%d %s have been upgraded into "%s".',
                    $totalTarget, $totalRecords, $recordTypePlural, $mappedType));
        }

        // May be possible with plugins?
        if ($totalRecords < $totalTarget) {
            throw new UpgradeToOmekaS_Exception(
                __('There are %d upgraded "%s" in Omeka S, but only %d %s in Omeka C.',
                    $totalTarget, $mappedType, $totalRecords, $recordTypePlural)
                . ' ' . __('Check the processors of the plugins.'));
        }

        if (in_array($recordType, array('Item', 'Collection'))) {
            // The roles are checked, because at this point, all users were
            // upgraded according to the role and there is no option to set a
            // default role to the users without an existing role.
            $lostOwners = $this->countRecordsWithoutOwner($recordType, 'owner_id', true);
            if ($lostOwners) {
                $this->_log('[' . __FUNCTION__ . ']: '
                    . ($lostOwners <= 1
                        ? __('One %s has lost its owner.', $recordTypeSingular)
                        : __('%d %s have lost their owner.', $lostOwners, $recordTypePlural)),
                    Zend_Log::NOTICE);
            }

            if ($totalItemTypesUnmapped) {
                $this->_log('[' . __FUNCTION__ . ']: ' . __('The item type of %d items was not mapped and was not upgraded.',
                    $totalItemTypesUnmapped), Zend_Log::WARN);
            }
        }

        $this->_log('[' . __FUNCTION__ . ']: ' . __('All %s (%d) have been upgraded as "%s".',
            $recordTypePlural, $totalRecords, $mappedType), Zend_Log::INFO);
    }

    /**
     * Helper to remap ids for a record type.
     *
     * @internal This is possible, because only one user uses the database.
     *
     * @param array $remapIds
     * @param string $recordType
     * @throws UpgradeToOmekaS_Exception
     */
    protected function _remapIds($remapIds, $recordType)
    {
        $db = $this->_db;
        $target = $this->getTarget();
        $targetDb = $target->getDb();

        $mappedType = $this->mapping_models[inflector::underscore($recordType)];

        // Prepare a string for the messages.
        $recordTypeSingular = strtolower(Inflector::humanize(Inflector::underscore($recordType)));
        $recordTypePlural = strtolower(Inflector::pluralize($recordTypeSingular));

        // Initialize the mapping if needed.
        if (empty($this->_mappingIds[$recordType])) {
            $this->_mappingIds[$recordType] = array();
        }

        // Do a precheck on the source ids.
        if (array_intersect(array_keys($this->_mappingIds[$recordType]), $remapIds)) {
            throw new UpgradeToOmekaS_Exception(
                __('Some %s ids are already mapped between source and destination.',
                    $recordTypeSingular));
        }

        // Get the last greatest ids of the record table, in order.
        $max = count($remapIds);
        $sql = '
        (
            SELECT `id`
            FROM ' . $mappedType . '
            ORDER BY `id` DESC
            LIMIT ' . $max . '
        )
        ORDER BY `id` ASC;';
        $result = $targetDb->fetchCol($sql);

        if (empty($result) || count($result) != count($remapIds)) {
            throw new UpgradeToOmekaS_Exception(
                __('Unable to fetch the last %d %s ids in the target database.',
                    count($remapIds), $recordTypePlural));
        }

        // Check if this is really the n last destination ids. They must
        // not be already mapped.
        if (array_intersect($this->_mappingIds[$recordType], $result)) {
            throw new UpgradeToOmekaS_Exception(
                __('Unable to get the last %d %s ids in the target database.',
                    count($remapIds), $recordTypePlural));
        }

        $this->_mappingIds[$recordType] += array_combine($remapIds, $result);
        $this->_mappingIds[$recordType] = array_map('intval', $this->_mappingIds[$recordType]);
    }

    protected function _upgradeMetadata()
    {
        $recordType = 'ElementText';

        $mappedType = $this->mapping_models[inflector::underscore($recordType)];

        // Prepare a string for the messages.
        $recordTypeSingular = strtolower(Inflector::humanize(Inflector::underscore($recordType)));
        $recordTypePlural = strtolower(Inflector::pluralize($recordTypeSingular));

        $db = $this->_db;
        $target = $this->getTarget();
        $targetDb = $target->getDb();

        $totalRecords = total_records($recordType);
        if (empty($totalRecords)) {
            return;
        }
        $this->_progress(0, $totalRecords);

        $siteId = $this->getSiteId();

        $table = $db->getTable($recordType);

        $mapping = $this->getProcessor('Core/Elements')
            ->getMappingElementsToPropertiesIds();

        $totalRecordsUnmapped = 0;

        $loops = floor(($totalRecords - 1) / $this->maxChunk) + 1;
        for ($page = 1; $page <= $loops; $page++) {
            $this->_progress(($page - 1) * $this->maxChunk);
            $records = $table->findBy(array(), $this->maxChunk, $page);

            $toInserts = array();
            foreach ($records as $record) {
                $etRecordType = $record->record_type;
                $etRecordId = $record->record_id;

                // Check if a mapping is needed (for collections and files).
                if ($etRecordType == 'Item') {
                    $resourceId = $etRecordId;
                }
                // The record id has changed: check it.
                else {
                    $mappedIds = $this->fetchMappedIds($etRecordType);
                    if (!isset($mappedIds[$etRecordId])) {
                        throw new UpgradeToOmekaS_Exception(
                            __('The %s #%d can’t be found in Omeka S.',
                                $etRecordType, $etRecordId));
                    }
                    $resourceId = $mappedIds[$etRecordId];
                }

                // Check if the element has been mapped to a property.
                if (empty($mapping[$record->element_id])) {
                    ++$totalRecordsUnmapped;
                    continue;
                }

                $toInsert = array();
                $toInsert['id'] = null;
                $toInsert['resource_id'] = $resourceId;
                $toInsert['property_id'] = $mapping[$record->element_id];
                $toInsert['value_resource_id'] = null;
                $toInsert['type'] = 'literal';
                $toInsert['lang'] = null;
                $toInsert['value'] = $record->text;
                $toInsert['uri'] = null;
                $toInserts[] = $target->cleanQuote($toInsert);
            }

            $target->insertRows('value', $toInserts);
        }

        // A final check, normally useless.
        $totalRecordsMapped = $totalRecords - $totalRecordsUnmapped;
        $totalTarget = $target->totalRows($mappedType);
        // Substract the six properties of the default item set for the site.
        $isItemSetSite = (boolean) $this->_getItemSetSiteId();
        if ($isItemSetSite) {
            $totalTarget -= 6;
        }

        $sql = "SELECT id, resource_id, property_id, value FROM value";
        $result = $targetDb->fetchAll($sql);

        if ($totalRecordsMapped > $totalTarget) {
            throw new UpgradeToOmekaS_Exception(
                __('Only %d/%d %s have been upgraded into "%s".',
                    $totalTarget, $totalRecordsMapped, $recordTypePlural, $mappedType));
        }
        // May be possible with plugins?
        if ($totalRecords < $totalTarget) {
            throw new UpgradeToOmekaS_Exception(
                __('An error occurred: there are %d upgraded "%s" in Omeka S, but only %d %s in Omeka C.',
                    $totalTarget, $mappedType, $totalRecordsMapped, $recordType)
                . ' ' . __('Check the processors of the plugins.'));
        }

        if ($totalRecordsUnmapped) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('%d metadata with not mapped elements were not upgraded.',
                $totalRecordsUnmapped), Zend_Log::WARN);
            $this->_log('[' . __FUNCTION__ . ']: ' . __('The other %d metadata have been upgraded as "%s".',
                $totalRecordsMapped, $mappedType), Zend_Log::INFO);
        }
        else {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('All %s (%d) have been upgraded as "%s".',
                __('metadata'), $totalRecords, $mappedType), Zend_Log::INFO);
        }
    }

    /**
     * Helper to get and save the item set site id.
     *
     * @internal It is saved just after the item set is created for the site.
     * This is always the first item set.
     *
     * @return integer
     */
    protected function _saveItemSetSiteId()
    {
        if (empty($this->_itemSetSiteId)) {
            $targetDb = $this->getTarget()->getDb();
            $select = $targetDb->select()
                ->from('item_set', array(new Zend_Db_Expr('MIN(id)')));
            $this->_itemSetSiteId = (integer) $targetDb->fetchOne($select);
        }
        return $this->_itemSetSiteId;
    }

    protected function _getItemSetSiteId()
    {
        return $this->_itemSetSiteId;
    }

    /**
     * Helper to get the count of lost owners for a record type.
     *
     * The user may be removed or not upgradable (no role).
     *
     * @param string $recordType
     * @param string $columnName
     * @param boolean $checkRoles Check only for records whose owner has an
     * role upgradable in Omeka S.
     * @return integer
     */
    public function countRecordsWithoutOwner($recordType, $columnName = 'owner_id', $checkRoles = false)
    {
        $db = $this->_db;

        $sqlRoles = '';
        if ($checkRoles) {
            $mappingRoles = $this->getMerged('mapping_roles');
            $roles = array_keys($mappingRoles);
            if ($roles) {
                $sqlRoles = 'OR users.`role` NOT IN ("' . implode('", "', $roles) . '")';
            }
        }

        // Mysql doesn't support full outer join.
        $sql = "
        SELECT
            COUNT(records.`id`) AS total
        FROM {$db->$recordType} records
        LEFT JOIN {$db->User} users
            ON records.`$columnName` = users.`id`
        WHERE records.`$columnName` IS NULL
            OR users.`id` IS NULL
            $sqlRoles
        ;";
        $result = $db->fetchOne($sql);
        return $result;
    }

    /**
     * Helper to get the count of lost owners for a record type in the target.
     *
     * @param string $tableName
     * @param string $columnName
     * @return integer
     */
    public function countTargetRecordsWithoutOwner($tableName, $columnName = 'owner_id')
    {
        // Because there are constraints in the database of Omeka S, a simple
        //check on null is enough.
        $targetDb = $this->getTarget()->getDb();
        $sql = "
        SELECT
            COUNT(record.`id`) AS total
        FROM {$tableName} record
        WHERE record.`$columnName` IS NULL
        ;";
        $result = $targetDb->fetchOne($sql);
        return $result;
    }

    /**
     * Prepare properties for a resource.
     *
     * @param integer|string $resourceId A number or equivalent sql expression.
     * @param array $properties
     * @return void
     */
    protected function _prepareRowsForProperties($resourceId, $properties)
    {
        $target = $this->getTarget();

        // Get the flat list of properties to get the id of each property.
        $propertiesIds = $this->getProcessor('Core/Elements')
            ->getPropertyIds();

        // Set the default values for the "value", that is a literal.
        // As all other insertions, keep order and completion of the list.
        $toInsertBase = array();
        $toInsertBase['id'] = null;
        $toInsertBase['resource_id'] = $resourceId;
        $toInsertBase['property_id'] = null;
        $toInsertBase['value_resource_id'] = null;
        $toInsertBase['type'] = 'literal';
        $toInsertBase['lang'] = null;
        $toInsertBase['value'] = null;
        $toInsertBase['uri'] = null;

        $toInserts = array();
        foreach ($properties as $property => $values) {
            if (!isset($propertiesIds[$property])) {
                throw new UpgradeToOmekaS_Exception(
                    __('The property "%s" does not exist in Omeka S.', $property));
            }
            $toInsertBase['property_id'] = $propertiesIds[$property];
            foreach ($values as $value) {
                // This is not a literal value.
                if (is_array($value)) {
                    $toInsert = array_merge($toInsertBase, $value);
                }
                // This is a litteral.
                else {
                    $toInsert = $toInsertBase;
                    $toInsert['value'] = $value;
                }
                $toInserts[] = $target->cleanQuote($toInsert, 'resource_id');
            }
        }

        return $toInserts;
    }

    /**
     * Helper to insert properties for a resource.
     *
     * @uses self::_prepareRowsForProperties()
     * @uses self::_insertRows()
     *
     * @param integer|string $resourceId A number or equivalent sql expression.
     * @param array $properties
     * @return void
     */
    protected function _insertProperties($resourceId, $properties)
    {
        $toInserts = $this->_prepareRowsForProperties($resourceId, $properties);
        $target->insertRows('value', $toInserts);
    }
}
