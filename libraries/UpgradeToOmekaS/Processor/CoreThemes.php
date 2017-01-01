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

        $totalAssets = count($files);
        if (empty($totalAssets)) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('The current site has no asset.'),
                Zend_Log::INFO);
            return;
        }

        // Prepare the destination directory.
        $destinationDir = $this->getParam('base_dir')
            . DIRECTORY_SEPARATOR . 'files'
            . DIRECTORY_SEPARATOR . 'asset';
        UpgradeToOmekaS_Common::createDir($destinationDir);

        // Prepare the asset.
        $targetDb = $this->getTarget()->getDb();

        // Process copy.
        foreach ($files as $filename) {
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

        $this->_log('[' . __FUNCTION__ . ']: ' . ($totalAssets <= 1
                ? __('One asset has been upgraded.')
                : __('%d assets have been upgraded.', $totalAssets)),
            Zend_Log::INFO);
    }

    protected function _copyThemes()
    {
        // with theme media uploaded.
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
