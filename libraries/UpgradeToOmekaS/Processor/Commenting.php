<?php

/**
 * Upgrade Commenting to Omeka S.
 *
 * @package UpgradeToOmekaS
 */
class UpgradeToOmekaS_Processor_Commenting extends UpgradeToOmekaS_Processor_Abstract
{

    public $pluginName = 'Commenting';
    public $minVersion = '2.1';
    // public $maxVersion = '2.1.4';
    // Not yet included Improvements.
    // public $maxVersion = '2.1.4.1';

    public $module = array(
        'name' => 'Comment',
        'version' => '3.1.4',
        'url' => 'https://github.com/omeka-s-modules/Mapping/releases/download/v%s/Comment.zip',
        'size' => '',
        'sha1' => '',
        'type' => 'equivalent',
        'note' => 'Improvements and full rewrite based on a fork of Commenting.',
        'original_ids' => true,
        'install' => array(
            // Copied from the original module.php.
            'sql' => '
CREATE TABLE comment (
    id INT AUTO_INCREMENT NOT NULL,
    owner_id INT DEFAULT NULL,
    resource_id INT DEFAULT NULL,
    site_id INT DEFAULT NULL,
    parent_id INT DEFAULT NULL,
    path VARCHAR(1024) NOT NULL,
    email VARCHAR(255) NOT NULL,
    name VARCHAR(190) NOT NULL,
    website VARCHAR(760) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    user_agent VARCHAR(65535) NOT NULL,
    body LONGTEXT NOT NULL,
    approved TINYINT(1) NOT NULL,
    flagged TINYINT(1) NOT NULL,
    spam TINYINT(1) NOT NULL,
    created DATETIME NOT NULL,
    modified DATETIME DEFAULT NULL,
    INDEX IDX_9474526C7E3C61F9 (owner_id),
    INDEX IDX_9474526C89329D25 (resource_id),
    INDEX IDX_9474526CF6BD1646 (site_id),
    INDEX IDX_9474526C727ACA70 (parent_id),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;
ALTER TABLE comment ADD CONSTRAINT FK_9474526C7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id) ON DELETE SET NULL;
ALTER TABLE comment ADD CONSTRAINT FK_9474526C89329D25 FOREIGN KEY (resource_id) REFERENCES resource (id) ON DELETE SET NULL;
ALTER TABLE comment ADD CONSTRAINT FK_9474526CF6BD1646 FOREIGN KEY (site_id) REFERENCES site (id) ON DELETE SET NULL;
ALTER TABLE comment ADD CONSTRAINT FK_9474526C727ACA70 FOREIGN KEY (parent_id) REFERENCES comment (id) ON DELETE SET NULL;
',
        ),
        'settings' => [
            'comment_resources' => ['items'],
            'comment_public_allow_view' => true,
            'comment_public_allow_comment' => true,
            'comment_public_require_moderation' => true,
            'comment_public_notify_post' => [],
            'comment_threaded' => true,
            'comment_max_length' => 2000,
            'comment_comments_label' => 'Comments',
            'comment_legal_text' => '',
            'comment_wpapi_key' => '',
            'comment_antispam' => true,
        ],
        'site_settings' => [
            'comment_append_item_set_show' => true,
            'comment_append_item_show' => true,
            'comment_append_media_show' => true,
        ],
    );

    public $tables = array(
        'comment',
    );

    public $processMethods = array(
        '_installModule',
    );

    public $mapping_models = array(
        'comment' => 'comment',
    );

    protected function _upgradeSettings()
    {
        parent::_upgradeSettings();

        $target = $this->getTarget();

         // Global settings.
        $mapOptions = array(
            'commenting_pages' => 'comment_resources',
            'commenting_comment_roles' => null,
            'commenting_moderate_roles' => null,
            'commenting_reqapp_comment_roles' => null,
            'commenting_view_roles' => null,
            'commenting_comments_label' => 'comment_comments_label',
            'commenting_flag_email' => 'comment_public_notify_post',
            'commenting_threaded' => 'comment_threaded',
            'commenting_legal_text' => 'comment_legal_text',
            'commenting_allow_public' => 'comment_public_allow_comment',
            'commenting_require_public_moderation' => 'comment_public_require_moderation',
            'commenting_allow_public_view' => 'comment_public_allow_view',
            'commenting_wpapi_key' => 'comment_wpapi_key',
            'commenting_antispam' => 'comment_antispam' ,
            'commenting_honeypot' => null,
        );
        foreach ($mapOptions as $option => $setting) {
            if (empty($setting)) {
                continue;
            }
            $value = get_option($option);
            // Manage exceptions.
            switch ($option) {
                case 'commenting_pages':
                    $valueCommentingPages = unserialize($value) ? unserialize($value) : [];
                    $commentingPagesMap = array(
                        'collections/show' => 'item_sets',
                        'items/show' => 'items',
                        'files/show' => 'media',
                        // 'page/show' => 'site_pages',
                        // 'exhibits/summary' => 'site_pages',
                        // 'exhibits/show' => 'site_pages',
                    );
                    $value = [];
                    foreach ($valueCommentingPages as $valueCommentingPage) {
                        if (isset($commentingPagesMap[$valueCommentingPage])) {
                            $value[] = $commentingPagesMap[$valueCommentingPage];
                        }
                    }
                    break;

                case 'commenting_flag_email':
                    if ($value) {
                        $value = [];
                        $value[] = get_option('administrator_email');
                    } else {
                        $value = [];
                    }
                    break;
            }

            $target->saveSetting($setting, $value);
        }

        // Site settings (only the first).
        $mapOptions = array(
            'universalviewer_append_collections_show' => 'universalviewer_append_item_set_show',
            'universalviewer_append_items_show' => 'universalviewer_append_item_show',
            'universalviewer_append_collections_browse' => 'universalviewer_append_item_set_browse',
            'universalviewer_append_items_browse' => 'universalviewer_append_item_browse',
            'universalviewer_class' => 'universalviewer_class',
            'universalviewer_style' => 'universalviewer_style',
            'universalviewer_locale' => 'universalviewer_locale',
        );
        foreach ($mapOptions as $option => $setting) {
            if (empty($setting)) {
                continue;
            }
            $value = get_option($option);
            $target->saveSiteSetting($setting, $value);
        }

        $this->_log('[' . __FUNCTION__ . ']: ' . __('All urls of the Universal Viewer are maintained, except the player: items/play/:id was replaced by item/:id/play.')
            . ' ' . __('To keep old urls, uncomment the specified lines in the config of the module.'),
            Zend_Log::NOTICE);
    }

    protected function _upgradeData()
    {
        $recordType = 'Comment';

        $totalRecords = total_records($recordType);
        if (empty($totalRecords)) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('No comment to upgrade.'),
                Zend_Log::INFO);
            return;
        }
        $this->_progress(0, $totalRecords);

        $db = $this->_db;
        $target = $this->getTarget();
        $targetDb = $target->getDb();

        // Prepare the mapping of record ids (only item ids are kept).
        $mappedIds = array();
        $mappedIds['Collection'] = $this->fetchMappedIds('Collection');
        $mappedIds['Item'] = $this->fetchMappedIds('Item');
        $mappedIds['File'] = $this->fetchMappedIds('File');

        // The list of user ids allows to check if the owner of a record exists.
        // The id of users are kept between Omeka C and Omeka S.
        // Some users may not have been upgraded.
        $targetUserIds = $target->fetchIds('user');

        // The process uses the regular queries of Omeka in order to keep
        // only good records and to manage filters.
        $table = $db->getTable($recordType);

        $siteId = $this->getSiteId();

        // Simple pages and exhibits comments are not imported.
        $totalUnmanaged = 0;

        $loops = floor(($totalRecords - 1) / $this->maxChunk) + 1;
        for ($page = 1; $page <= $loops; $page++) {
            $this->_progress(($page - 1) * $this->maxChunk);
            $records = $table->findBy(array(), $this->maxChunk, $page);

            $toInserts = array();
            foreach ($records as $record) {
                $ownerId = $record->user_id;
                $email = substr($record->author_email, 0, 255);
                $name = substr($record->author_name, 0, 190);
                // Manage the case where some user are in Omeka C, but not
                // upgraded.
                if ($ownerId) {
                    if (isset($targetUserIds[$ownerId])) {
                        $ownerId = $targetUserIds[$ownerId];
                    }else {
                        $owner = get_record_by_id('User', $ownerId);
                        if ($owner) {
                            $email = substr($owner->email, 0, 255);
                            $name = substr($owner->name, 0, 190);
                        } else {
                            $ownerId = null;
                        }
                    }
                }

                $resourceId = null;
                switch ($record->record_type) {
                    case 'Collection':
                    case 'Item':
                    case 'File':
                        if (isset($mappedIds[$record->record_type][$record->record_id])) {
                            $resourceId = $mappedIds[$record->record_type][$record->record_id];
                        } else {
                            ++$totalUnmanaged;
                        }
                        break;
                    case 'Exhibit':
                    case 'ExhibitPage':
                    case 'SimplePagesPage':
                    default:
                        ++$totalUnmanaged;
                        break;
                }

                $toInsert['id'] = $record->id;
                $toInsert['owner_id'] = $owner;
                $toInsert['resource_id'] = $resourceId;
                $toInsert['site_id'] = $siteId;
                $toInsert['parent_id'] = $record->parent_comment_id;
                $toInsert['path'] = $record->path;
                $toInsert['email'] = $email;
                $toInsert['name'] = $name;
                $toInsert['website'] = $record->author_url;
                $toInsert['ip'] = subst($record->ip, 0, 45);
                $toInsert['user_agent'] = $record->user_agent;
                $toInsert['body'] = $record->body;
                $toInsert['approved'] = $record->approved;
                $toInsert['flagged'] = $record->flagged;
                $toInsert['spam'] = $record->is_spam;
                $toInsert['created'] = $record->added;
                $toInsert['modified'] = null;
                $toInserts['comment'][] = $target->cleanQuote($toInsert);
            }

            $target->insertRowsInTables($toInserts);
        }

        $this->_log('[' . __FUNCTION__ . ']: ' . __('All comments (%d) have been upgraded.',
            $totalRecords), Zend_Log::INFO);
        if ($totalUnmanaged) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('There are %d unmanaged comments (comments on simple pages or exhibit pages.'
                    . ' ' . __('They were imported as comments without resource.'),
                $totalRecords), Zend_Log::WARN);
        }
    }
}
