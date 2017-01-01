<?php

/**
 * Upgrade Core Themes to Omeka S.
 *
 * @package UpgradeToOmekaS
 */
class UpgradeToOmekaS_Processor_CoreThemes extends UpgradeToOmekaS_Processor_Abstract
{
    public $pluginName = 'Core / Themes';
    public $minVersion = '2.3.1';
    public $maxVersion = '2.5';

    public $module = array(
        'type' => 'integrated',
    );

    /**
     * List of methods to process for the upgrade.
     *
     * @var array
     */
    public $processMethods = array(
        '_copyAssets',
        '_copyThemes',
        '_upgradeThemesParams',
        '_downloadCompatibilityModule',
        '_unzipCompatibiltyModule',
        '_installCompatibiltyModule',
    );

    /**
     * Check if the plugin is installed.
     *
     * @internal Always true for the Core.
     *
     * @return boolean
     */
    public function isPluginReady()
    {
        return true;
    }

    protected function _copyAssets()
    {
        $this->_log('[' . __FUNCTION__ . ']: ' . __('In Omeka S, all assets used for the themes (logo...) are managed in one place.'),
            Zend_Log::INFO);

        // There are usually few files in the folder.
        $sourceDir = FILES_DIR . DIRECTORY_SEPARATOR . 'theme_uploads';
        $files = UpgradeToOmekaS_Common::listFilesInDir($sourceDir);

        // Remove the file "index.html" from the list of files.
        $key = array_search('index.html', $files);
        if ($key !== false) {
            unset($files[$key]);
        }

        $totalRecords = count($files);
        if (empty($totalRecords)) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('The current site has no asset.'),
                Zend_Log::INFO);
            return;
        }

        $this->_progress(0, $totalRecords);

        // Prepare the destination directory.
        $destinationDir = $this->getParam('base_dir')
            . DIRECTORY_SEPARATOR . 'files'
            . DIRECTORY_SEPARATOR . 'asset';
        UpgradeToOmekaS_Common::createDir($destinationDir);

        // Prepare the asset.
        $targetDb = $this->getTarget()->getDb();

        // Process copy.
        $i = 0;
        foreach ($files as $filename) {
            $this->_progress(++$i);
            // Do a true copy, because they are not an archive and may change.
            $source = $sourceDir . DIRECTORY_SEPARATOR . $filename;
            $destination = $destinationDir . DIRECTORY_SEPARATOR . $filename;
            $result = copy($source, $destination);
            if (!$result) {
                throw new UpgradeToOmekaS_Exception(
                    __('The copy of the file "%s" to the directory "%s" failed.',
                        $source, $destinationDir . DIRECTORY_SEPARATOR));
            }

            $detect = new Omeka_File_MimeType_Detect($source);
            $mimeType = $detect->detect();

            // Create the asset after each copy in case of an error.
            $toInsert = array();
            $toInsert['id'] = null;
            $toInsert['name'] = $filename;
            $toInsert['media_type'] = $mimeType;
            $toInsert['storage_id'] = pathinfo($filename, PATHINFO_FILENAME);
            $toInsert['extension'] = pathinfo($filename, PATHINFO_EXTENSION);
            $targetDb->insert('asset', $toInsert);
        }

        $this->_log('[' . __FUNCTION__ . ']: ' . ($totalRecords <= 1
                ? __('One asset has been upgraded.')
                : __('%d assets have been upgraded.', $totalRecords)),
            Zend_Log::INFO);
    }

    protected function _copyThemes()
    {
        $this->_log('[' . __FUNCTION__ . ']: ' . __('In Omeka S, the home page is the first navigation link.'),
            Zend_Log::INFO);

        $source = PUBLIC_THEME_DIR;
        $destination = $this->getParam('base_dir')
            . DIRECTORY_SEPARATOR . 'themes';

        // Recheck for the symlinks, that can be bypassed.
        $result = UpgradeToOmekaS_Common::containsSymlinks(PUBLIC_THEME_DIR);
        if ($result) {
            $checks = $this->getParam('checks');
            if (!empty($checks['symlinks'])) {
                throw new UpgradeToOmekaS_Exception(
                    __('There are symbolic links inside the directory of themes.')
                        . ' ' . __('They cannot be managed.')
                        . ' ' . __('This precheck may be bypassed via "security.ini".'));
            }
            // Bypass the copy.
            else {
                $this->_log('[' . __FUNCTION__ . ']: ' . __('The themes were not copied, because there are symbolic links.')
                    . ' ' . __('You can find new ones on %shttps://omeka.org/s%s.', '<a href="omeka.org/s" target="_blank">', '</a>'),
                    Zend_Log::INFO);
                return;
            }
        }

        // First, copy the default scripts from Omeka in each theme, since they
        // are used when there is no script with the same name in the theme.
        $sourceDefaultTheme = BASE_DIR
            . DIRECTORY_SEPARATOR . 'application'
            . DIRECTORY_SEPARATOR . 'views'
            . DIRECTORY_SEPARATOR . 'scripts';
        $themes = UpgradeToOmekaS_Common::listDirsInDir($source);
        foreach ($themes as $theme) {
            $result = UpgradeToOmekaS_Common::copyDir(
                $sourceDefaultTheme,
                $destination . DIRECTORY_SEPARATOR . $theme,
                true);
            if (!$result) {
                throw new UpgradeToOmekaS_Exception(
                    __('An error occurred during the copy of the default directory of themes.'));
            }
        }

        // Second, overwrite the default scripts with the themes ones.
        $result = UpgradeToOmekaS_Common::copyDir($source, $destination, true);
        if (!$result) {
            throw new UpgradeToOmekaS_Exception(
                __('An error occurred during the copy of the directory of themes.'));
        }

        $this->_log('[' . __FUNCTION__ . ']: ' . __('All themes have been copied with default scripts into Omeka S.')
                . ' ' . __('You can find new ones on %shttps://omeka.org/s%s.', '<a href="omeka.org/s" target="_blank">', '</a>'),
            Zend_Log::INFO);
    }

    protected function _upgradeThemesParams()
    {
        $siteId = $this->getSiteId();

        $db = $this->_db;
        $target = $this->getTarget();
        $targetDb = $target->getDb();

        $select = $db->getTable('Option')->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->from(array(), array('name', 'value'))
            ->where('`name` LIKE "theme_%_options"')
            ->order('name');
        $options = $db->fetchPairs($select);

        $select = $targetDb->select()
            ->from('asset', array(
                'name' => new Zend_Db_Expr('CONCAT(`storage_id`, ".", `extension`)'),
                'id' => 'id',
            ))
            ->order('name');
        $assets = $targetDb->fetchPairs($select);

        // Extract the name and the values.
        $toInserts = array();
        foreach ($options as $key => $values) {
            $name = substr($key, strlen('theme_'), strlen($key) - strlen('theme__options'));
            if (empty($name)) {
                continue;
            }

            $values = unserialize($values);
            if (empty($values)) {
                continue;
            }

            // Check if there is an asset to upgrade the logo, else remove.
            if (!empty($values['logo'])) {
                $values['logo'] = isset($assets[$values['logo']])
                    ? $assets[$values['logo']]
                    : '';
            }

            $nameSetting = 'theme_settings_' . $name;
            $target->saveSiteSetting($nameSetting, $values, $siteId);
        }

        $this->_log('[' . __FUNCTION__ . ']: ' . __('The options of the themes have been upgraded as site settings.'),
            Zend_Log::INFO);
    }

    protected function _downloadCompatibilityModule()
    {
        // TODO Compatibility module.
    }

    protected function _unzipCompatibiltyModule()
    {
        // TODO Compatibility module.
    }

    protected function _installCompatibiltyModule()
    {
        // TODO Compatibility module.
    }
}
