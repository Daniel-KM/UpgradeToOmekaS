<?php

/**
 * UpgradeToOmekaS_Job_Upgrade class
 *
 * @package UpgradeToOmekaS
 */
class UpgradeToOmekaS_Job_Upgrade extends UpgradeToOmekaS_Job_Abstract
{
    const QUEUE_NAME = 'upgrade_to_omeka_s_upgrade';

    /**
     * Performs the upgrade task.
     */
    public function perform()
    {
        // Set current user for this long running job.
//        Zend_Registry::get('bootstrap')->bootstrap('Acl');

        // Reset the status of the site.
        set_option('upgrade_to_omeka_s_service_down', true);
        set_option('upgrade_to_omeka_s_process_status', Process::STATUS_IN_PROGRESS);
        set_option('upgrade_to_omeka_s_process_progress', json_encode(array()));

        // Reset existing logs. They are available in the logs of Omeka if
        // enabled (level default).
        set_option('upgrade_to_omeka_s_process_logs', '[]');

        $this->_log(__('Starting upgrade from Omeka Classic to Omeka Semantic.'),
            Zend_Log::INFO);

        $user = $this->getUser();
        $params = $this->_params;
        $params['user'] = $user;

        //Add the security ini params.
        $security = $this->_getSecurityIni()->toArray();
        $params['checks'] = $security['check'];

        // This constant may be needed in some scripts fetched from Omeka S.
        defined('OMEKA_PATH') or define('OMEKA_PATH', $params['base_dir']);

        $startMessage = __('Params are:') . PHP_EOL;
        $startMessage .= __('Base dir: %s', $params['base_dir']) . PHP_EOL;
        $startMessage .= __('Installation Title: %s', $params['installation_title']) . PHP_EOL;
        $startMessage .= __('Time Zone: %s', $params['time_zone']) . PHP_EOL;
        $startMessage .= __('Database type: %s', $params['database']['type']) . PHP_EOL;
        if ($params['database']['type'] == 'separate') {
            $startMessage .= __('Database host: %s', $params['database']['host']) . PHP_EOL;
            $startMessage .= __('Database port: %s', isset($params['database']['port']) ? $params['database']['port'] : '') . PHP_EOL;
            // $startMessage .= __('Database prefix: %s', $params['database']['prefix']) . PHP_EOL;
            $startMessage .= __('Database name: %s', $params['database']['dbname']) . PHP_EOL;
            $startMessage .= __('Database username: %s', $params['database']['username']) . PHP_EOL;
            $startMessage .= __('Database password: %s', 'xxxxxxxxxxxxxxxx') . PHP_EOL;
        }
        // Database is shared.
        else {
            // $startMessage .= __('Database prefix: %s', $params['database']['prefix']) . PHP_EOL;
        }
        $startMessage .= __('Files type: %s', $params['files_type']) . PHP_EOL;
        $startMessage .= __('Url: %s', $params['url']) . PHP_EOL;
        $startMessage .= __('User: %s (#%d)', $user->username, $user->id);
        $this->_log($startMessage, Zend_Log::INFO);

        $processors = $this->_listProcessors();
        $datetime = date('Y-m-d H:i:s');

        // Reprocess the checks, because some time may have been occurred and
        // this is not a problem in a background process. Above all, the config
        // of the php cli is generally different from the php web one.
        $this->_log(__('Prechecks start.'), Zend_Log::DEBUG);

        foreach ($processors as $name => $processor) {
            if ($this->_isProcessing()) {
                try {
                    // The params should be set now, because there is the processing
                    // parameter.
                    $processor->setParams($params);
                    $processor->setAssetPaths();
                    $processor->setDatetime($datetime);
                    $result = $processor->precheckConfig();
                    if (!empty($result)) {
                        $this->_processError(__('An error occurred during precheck of "%s".',
                            $processor->pluginName), $result);
                        return;
                    }
                } catch (Exception $e) {
                    $this->_processError(__('An error occurred during precheck of "%s".',
                        $processor->pluginName), array($e->getMessage()));
                    return;
                }
            }
            // Process stopped externally.
            else {
                $this->_log(__('The process has been stopped outside of the processor.'), Zend_Log::WARN);
                return;
            }
        }
        $this->_log(__('Prechecks ended successfully.'), Zend_Log::DEBUG);

        $this->_log(__('Checks start.'), Zend_Log::DEBUG);
        foreach ($processors as $name => $processor) {
            if ($this->_isProcessing()) {
                try {
                    $result = $processor->checkConfig();
                    if (!empty($result)) {
                        $this->_processError(__('An issue occurred during check of "%s".',
                            $processor->pluginName), $result);
                        return;
                    }
                } catch (Exception $e) {
                    $this->_processError(__('An error occurred during check of "%s".',
                        $processor->pluginName), array($e->getMessage()));
                    return;
                }
            }
            // Process stopped externally.
            else {
                $this->_log(__('The process has been stopped outside of the processor.'), Zend_Log::WARN);
                return;
            }
        }
        $this->_log(__('Checks ended successfully.'), Zend_Log::DEBUG);

        // Launch process.
        $this->_log(__('Process start.'), Zend_Log::DEBUG);
        foreach ($processors as $name => $processor) {
            if ($this->_isProcessing()) {
                try {
                    $result = $processor->process();
                    // The result should be empty.
                    if ($result) {
                        throw new UpgradeToOmekaS_Exception($result);
                    }
                } catch (UpgradeToOmekaS_Exception $e) {
                        $this->_processError(__('An error occurred during process of "%s".',
                            $processor->pluginName), array('[' . $processor->pluginName . ']' . $e->getMessage()));
                        return;
                } catch (Exception $e) {
                    $this->_processError(__('An unknown error occurred during process of "%s".',
                        $processor->pluginName), array('[' . $processor->pluginName . ']' . $e->getMessage()));
                    return;
                }
            }
            // Process stopped externally.
            else {
                $this->_log(__('The process has been stopped outside of the processor.'), Zend_Log::WARN);
                return;
            }
        }
        $this->_log(__('Process ended successfully.'), Zend_Log::DEBUG);

        // No error.
        $this->_log(__('End of the upgrade from Omeka Classic to Omeka Semantic.'),
            Zend_Log::INFO);

        set_option('upgrade_to_omeka_s_process_status', Process::STATUS_COMPLETED);
        set_option('upgrade_to_omeka_s_process_progress', json_encode(array()));
        set_option('upgrade_to_omeka_s_service_down', false);
    }
}
