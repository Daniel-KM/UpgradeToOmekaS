<?php

/**
 * Upgrade Core Files to Omeka S.
 *
 * @package UpgradeToOmekaS
 */
class UpgradeToOmekaS_Processor_CoreFiles extends UpgradeToOmekaS_Processor_Abstract
{
    public $pluginName = 'Core / Files';
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
        '_copyFiles',
    );

    /**
     * Initialized during init via libraries/data/mapping_derivatives.php.
     *
     * @var array
     */
    // public  $mapping_derivatives = array();

    protected function _init()
    {
        $dataDir = dirname(dirname(dirname(dirname(__FILE__))))
            . DIRECTORY_SEPARATOR . 'libraries'
            . DIRECTORY_SEPARATOR . 'data';

        $script = $dataDir
            . DIRECTORY_SEPARATOR . 'mapping_derivatives.php';
        $this->mapping_derivatives = require $script;
    }

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

    protected function _copyFiles()
    {
        $recordType = 'File';

        // Check the total of records.
        $totalRecords = total_records($recordType);
        if (empty($totalRecords)) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('No file to copy.'),
                Zend_Log::DEBUG);
            return;
        }

        // Get and check the type.
        $filesType = $this->getParam('files_type');
        if (!in_array($filesType, array('hard_link', 'copy', 'dummy'))) {
            throw new UpgradeToOmekaS_Exception(
                __('The type "%s" is not supported.', $type));
        }

        // Check the mapping of derivative files.
        $destDir = $this->getParam('base_dir') . DIRECTORY_SEPARATOR . 'files';
        $mapping = $this->getMerged('mapping_derivatives');
        if (empty($mapping)) {
            throw new UpgradeToOmekaS_Exception(
                __('The mapping of derivative files is empty.'));
        }

        // Prepare the mapping with the full paths
        foreach ($mapping as $type => $map) {
            $sourceDir = key($map);
            $destinationDir = reset($map);
            // Check if the path exists and absolute.
            $path = realpath($sourceDir);
            if ($path != $sourceDir) {
                $sourceDir = FILES_DIR . DIRECTORY_SEPARATOR . $sourceDir;
            }
            if (!file_exists($sourceDir) || !is_dir($sourceDir) || !is_readable($sourceDir)) {
                throw new UpgradeToOmekaS_Exception(
                    __('The source directory "%s" is not readable.', $sourceDir));
            }
            $path = realpath($destinationDir);
            if ($path != $destinationDir) {
                $destinationDir = $destDir . DIRECTORY_SEPARATOR . $destinationDir;
            }
            // The destination dirs are not included in the source, but created
            // dynamically. That what is done here.
            $result = UpgradeToOmekaS_Common::createDir($destinationDir);
            if (!$result) {
                throw new UpgradeToOmekaS_Exception(
                    __('The destination directory "%s" is not writable.', $destinationDir));
            }
            $mapping[$type] = array($sourceDir => $destinationDir);
        }

        $db = $this->_db;
        $table = $db->getTable($recordType);

        $path = key($mapping['original']);
        $totalFiles = UpgradeToOmekaS_Common::countFilesInDir($path);

        $totalCopied = 0;

        // Copy only the files that are referenced inside the database.
        $loops = floor(($totalRecords - 1) / $this->maxChunk) + 1;
        for ($page = 1; $page <= $loops; $page++) {
            $records = $table->findBy(array(), $this->maxChunk, $page);
            foreach ($records as $record) {
                foreach ($mapping as $type => $map) {
                    // Original is an exception: the extension is the original
                    // one and may  be in uppercase or not.
                    $filename = $type == 'original'
                        ? $record->filename
                        : $record->getDerivativeFilename($type);
                    $sourceDir = key($map);
                    $destinationDir = reset($map);
                    $source = $sourceDir . DIRECTORY_SEPARATOR . $filename;
                    $destination = $destinationDir . DIRECTORY_SEPARATOR . $filename;

                    // A check is done to manage the plugin Archive Repertory,
                    // that creates a relative dir for each record.
                    if (strpos($filename, DIRECTORY_SEPARATOR) !== false) {
                        $result = UpgradeToOmekaS_Common::createDir(dirname($destination));
                        if (!$result) {
                            throw new UpgradeToOmekaS_Exception(
                                __('Unable to create the directory "%s".', dirname($destination)));
                        }
                    }

                    switch ($filesType) {
                        case 'hard link':
                            break;
                        case 'copy':
                            $result = copy($source, $destination);
                            break;
                        case 'dummy':
                            break;
                    }
                    if (!$result) {
                        throw new UpgradeToOmekaS_Exception(
                            __('The copy of the file "%s" to the directory "%s" failed.',
                                $source, dirname($destination)));
                    }
                }
                // Count only one copy by record.
                ++$totalCopied;
            }
        }

        if ($totalCopied != $totalFiles) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('All %d files of records have been copied (mode: %s) into Omeka S, but there are %d files in Omeka Classic.',
                $totalCopied, $filesType, $totalFiles), Zend_Log::NOTICE);
        }
        // Fine.
        else {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('All %d files have been copied (%s) into Omeka S.',
                $totalCopied, $filesType), Zend_Log::INFO);
        }
    }
}
