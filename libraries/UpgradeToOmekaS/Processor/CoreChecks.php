<?php

/**
 * Upgrade Core Checks to Omeka S.
 *
 * @package UpgradeToOmekaS
 */
class UpgradeToOmekaS_Processor_CoreChecks extends UpgradeToOmekaS_Processor_AbstractCore
{

    public $pluginName = 'Core/Checks';

    /**
     * Store the translated files from Omeka C to Omeka S.
     *
     * @var array
     */
    protected $_checkedFiles = array();

    public $processMethods = array(
        '_checkThemeFiles',
        '_hashFiles',
    );

    public $specificProcessMethods = array(
        'themes' => array(
            '_checkThemeFiles',
        ),
    );

    protected function _checkThemeFiles()
    {
        // Check the syntax for info, but don't throw error, because any file
        // should be manually reviewed.
        $path = $this->getParam('base_dir')
        . DIRECTORY_SEPARATOR . 'themes';

        $this->_checkedFiles = array();

        $files = UpgradeToOmekaS_Common::listFilesInFolder($path, array('php', 'phtml'));
        $this->_progress(0, count($files));

        $toExclude = 'default'
            . DIRECTORY_SEPARATOR . 'view'
            . DIRECTORY_SEPARATOR . 'layout'
            . DIRECTORY_SEPARATOR . 'layout.phtml';
        $toExcludeMatch = '~^' . preg_quote($path, '~') . '/(\w|\-)+/asset/~';

        $progress = 0;
        foreach ($files as $file) {
            $this->_progress(++$progress);
            // Exclude some files from Omeka S.
            if (strpos($file, $toExclude)) {
                continue;
            }
            // Don't process files that are in assets.
            if (preg_match($toExcludeMatch, $file)) {
                continue;
            }

            $filesize = filesize($file);
            if (empty($filesize)) {
                $this->_checkedFiles[$file] = __('Empty file %s', $file);
                continue;
            }

            $result = $this->_checkPhp($file);
            $this->_checkedFiles[$file] = trim($result);
        }

        $filesWithErrors = array_filter($this->_checkedFiles);
        $totalErrors = count($filesWithErrors);

        if ($totalErrors) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('The files (%d) of the themes below have errors.', $totalErrors)
                    . ' ' . __('They are generally related to multiline, nested or custom functions and php comments /* ... */.')
                    . ' ' . __('See the background PHP error logs for more information (enable debug in php.ini before).'),
                Zend_Log::ERR);
            $this->_log('[' . __FUNCTION__ . ']: ' . snippet(implode(PHP_EOL, $filesWithErrors), 0, 40000),
                Zend_Log::INFO);
        }
        // Message for no error.
        else {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('The files of the themes have no syntax error.')
                    . ' ' . __('Nevertheless, check them yourself.')
                    . ' ' . __('There may be errors with multiline, nested or custom functions.'),
                Zend_Log::WARN);
        }
    }

    /**
     * Helper to check the syntax of a php file.
     *
     * @param string $file
     * @return null|string Null if cli is disabled, empty string if no error,
     * else the error.
     */
    protected function _checkPhp($file)
    {
        static $cliPath;

        if (is_null($cliPath)) {
            $cliPath = (string) Omeka_Job_Process_Dispatcher::getPHPCliPath();
        }

        if (empty($cliPath)) {
            return null;
        }

        $command = $cliPath . ' --syntax-check ' . escapeshellarg($file);

        try {
            UpgradeToOmekaS_Common::executeCommand($command, $status, $output, $errors);
            // A return value of 0 indicates the convert binary is working correctly.
            $result = $status == 0;
        } catch (Exception $e) {
            return false;
        }
        if ($result) {
            return '';
        }
        return $output;
    }

    protected function _hashFiles()
    {
        $skipHashFiles = $this->getParam('skip_hash_files');
        if ($skipHashFiles) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('The hashing of files is skipped.')
                . ' ' . __('Don’t forget to hash them in Omeka S.'),
                Zend_Log::NOTICE);
            return;
        }

        $resourceType = 'media';

        $target = $this->getTarget();
        $targetDb = $target->getDb();

        $totalResources = $target->totalRows('media');
        if (empty($totalResources)) {
            return;
        }
        $this->_progress(0, $totalResources);

        $basepath = $this->getParam('base_dir')
            . DIRECTORY_SEPARATOR . 'files'
            . DIRECTORY_SEPARATOR . 'original';

        $select = $targetDb->select()
            ->from('media', array('id', 'storage_id', 'extension', 'sha256', 'has_original'))
            ->order('id ASC');

        $stmt = $targetDb->query($select);
        $progress = 0;
        while ($row = $stmt->fetch()) {
            $this->_progress(++$progress);
            if (!empty($row['sha256']) || empty($row['has_original'])) {
                continue;
            }

            $filename = $row['storage_id'] . (strlen($row['extension']) ? '.' . $row['extension'] : '');
            $filepath = $basepath . DIRECTORY_SEPARATOR . $filename;
            if (!file_exists($filepath)) {
                continue;
            }

            $hash = hash_file('sha256', $filepath);
            $result = $targetDb->update('media', array('sha256' => $hash), 'id = ' . $row['id']);
        }

        $this->_log('[' . __FUNCTION__ . ']: ' . __('All %d files have been hashed.', $totalResources),
            Zend_Log::INFO);
    }
}
