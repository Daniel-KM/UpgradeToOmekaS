<?php

/**
 * Upgrade Core Files to Omeka S.
 *
 * @package UpgradeToOmekaS
 */
class UpgradeToOmekaS_Processor_CoreFiles extends UpgradeToOmekaS_Processor_AbstractCore
{

    public $pluginName = 'Core/Files';

    public $processMethods = array(
        '_copyFiles',
    );

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
        if (!in_array($filesType, array('hard_link', 'copy', 'dummy_hard', 'dummy'))) {
            throw new UpgradeToOmekaS_Exception(
                __('The type "%s" is not supported.', $type));
        }
        $copyMode = in_array($filesType, array('hard_link', 'dummy_hard')) ? 'hard_link' : 'copy';
        $isDummy = in_array($filesType, array('dummy_hard', 'dummy'));

        // Prepare the dummies if needed.
        $dummies = array();
        if ($isDummy) {
            $dummiesPath = dirname(dirname(dirname(dirname(__FILE__))))
                . DIRECTORY_SEPARATOR . 'views'
                . DIRECTORY_SEPARATOR . 'admin'
                . DIRECTORY_SEPARATOR . 'images'
                . DIRECTORY_SEPARATOR;
            $dummies = array(
                'audio' => $dummiesPath . 'fallback-audio.png',
                'image' => $dummiesPath . 'fallback-image.png',
                'video' => $dummiesPath . 'fallback-video.png',
                'file' => $dummiesPath . 'fallback-file.png',
            );
        }

        // Check the mapping of derivative files.
        $mapping = $this->getMerged('mapping_derivatives');
        if (empty($mapping)) {
            throw new UpgradeToOmekaS_Exception(
                __('The mapping of derivative files is empty.'));
        }

        // Prepare the mapping with the full paths
        $destinationFilesDir = $this->getParam('base_dir')
            . DIRECTORY_SEPARATOR . 'files';
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
                $destinationDir = $destinationFilesDir . DIRECTORY_SEPARATOR . $destinationDir;
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
        $this->_progress(0, $totalFiles);

        $totalCopied = 0;

        // Copy only the files that are referenced inside the database.
        $loops = floor(($totalRecords - 1) / $this->maxChunk) + 1;
        for ($page = 1; $page <= $loops; $page++) {
            $this->_progress($totalCopied);
            $records = $table->findBy(array(), $this->maxChunk, $page);
            foreach ($records as $record) {
                foreach ($mapping as $type => $map) {
                    if ($type != 'original' && !$record->has_derivative_image) {
                        continue;
                    }

                    // Original is an exception: the extension is the original
                    // one and may  be in uppercase or not.
                    $filename = $type == 'original'
                        ? $record->filename
                        : $record->getDerivativeFilename($type);

                    // Clean the filename to manage broken filenames if needed.
                    $filename = trim($filename, '/\\' . DIRECTORY_SEPARATOR);

                    if ($isDummy) {
                        $mainMimeType = strtok($record->mime_type, '/');
                        $source = isset($dummies[$mainMimeType])
                            ? $dummies[$mainMimeType]
                            : $dummies['file'];
                    }
                    // Use the true file.
                    else {
                        $sourceDir = key($map);
                        $source = $sourceDir . DIRECTORY_SEPARATOR . $filename;
                    }
                    $destinationDir = reset($map);
                    $destination = $destinationDir . DIRECTORY_SEPARATOR . $filename;

                    // A check is done to manage the plugin Archive Repertory,
                    // that creates a relative dir for each record.
                    $filenameArchiveRepertory = str_replace(array('/', '\\', DIRECTORY_SEPARATOR), '/', $filename);
                    if (strpos($filenameArchiveRepertory, '/') !== false) {
                        $result = UpgradeToOmekaS_Common::createDir(dirname($destination));
                        if (!$result) {
                            throw new UpgradeToOmekaS_Exception(
                                __('Unable to create the directory "%s".', dirname($destination)));
                        }
                    }

                    if ($copyMode == 'hard_link') {
                        $result = link($source, $destination);
                    }
                    // Standard copy.
                    else {
                        $result = copy($source, $destination);
                    }
                    if (!$result) {
                        throw new UpgradeToOmekaS_Exception(
                            __('The copy of the file "%s" (%sfile #%d%s, %sitem #%d%s) to the directory "%s" failed (mode: %s).',
                                $source,
                                '<a href="' . /* WEB_ROOT . */ '../../../admin/files/show/' . $record->id . '">',
                                $record->id,
                                '</a>',
                                '<a href="' . /* WEB_ROOT . */ '../../../admin/items/show/' . $record->item_id . '">',
                                $record->item_id,
                                '</a>',
                                dirname($destination) . DIRECTORY_SEPARATOR,
                                $filesType));
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
            $this->_log('[' . __FUNCTION__ . ']: ' . __('All %d files have been copied (mode: %s) into Omeka S.',
                $totalCopied, $filesType), Zend_Log::INFO);
        }
    }
}
