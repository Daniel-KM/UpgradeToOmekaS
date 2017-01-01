<?php

/**
 * UpgradeToOmekaS_Job_Process class
 *
 * @todo Manage status via the main process object, not via an internal option.
 *
 * @package UpgradeToOmekaS
 */
class UpgradeToOmekaS_Job_Process extends Omeka_Job_AbstractJob
{
    const QUEUE_NAME = 'upgrade_to_omeka_s';

    protected $_params;

    /**
     * Performs the import task.
     */
    public function perform()
    {
        // Set current user for this long running job.
//        Zend_Registry::get('bootstrap')->bootstrap('Acl');

        // Reset the status of the site.
        set_option('upgrade_to_omeka_s_service_down', true);
        set_option('upgrade_to_omeka_s_process_status', Process::STATUS_IN_PROGRESS);

        // Reset existing logs. They are available in the logs of Omeka if
        // enabled (level default).
        set_option('upgrade_to_omeka_s_process_logs', '[]');

        $this->_log(__('Starting upgrade from Omeka Classic to Omeka Semantic.'), Zend_Log::INFO);

        $user = $this->getUser();
        $params = $this->_params;
        $params['user'] = $user;

        // This constant may be needed in some scripts fetched from Omeka S.
        defined('OMEKA_PATH') or define('OMEKA_PATH', $params['base_dir']);

        $startMessage = __('Params are:') . PHP_EOL;
        $startMessage .= __('Base dir: %s', $params['base_dir']) . PHP_EOL;
        $startMessage .= __('Installation Title: %s', $params['installation_title']) . PHP_EOL;
        $startMessage .= __('Time Zone: %s', $params['time_zone']) . PHP_EOL;
        $startMessage .= __('Database type: %s', $params['database_type']) . PHP_EOL;
        if ($params['database_type'] == 'separate') {
            $startMessage .= __('Database host: %s', $params['database_host']) . PHP_EOL;
            $startMessage .= __('Database port: %s', $params['database_port']) . PHP_EOL;
            // $startMessage .= __('Database prefix: %s', $params['database_prefix']) . PHP_EOL;
            $startMessage .= __('Database name: %s', $params['database_name']) . PHP_EOL;
            $startMessage .= __('Database username: %s', $params['database_username']) . PHP_EOL;
            $startMessage .= __('Database password: %s', 'xxxxxxxxxxxxxxxx') . PHP_EOL;
        }
        // Database is shared.
        else {
            // $startMessage .= __('Database prefix: %s', $params['database_prefix']) . PHP_EOL;
        }
        $startMessage .= __('Files type: %s', $params['files_type']) . PHP_EOL;
        $startMessage .= __('Url: %s', get_option('upgrade_to_omeka_s_process_url')) . PHP_EOL;
        $startMessage .= __('User: %s (#%d)', $user->username, $user->id);
        $this->_log($startMessage, Zend_Log::INFO);

        $processors = $this->_listProcessors();

        // Reprocess the checks, because some time may have been occurred and
        // this is not a problem in a background process. Above all, the config
        // of the php cli is generally different from the php web one.
        $this->_log(__('Prechecks start.'), Zend_Log::INFO);
        foreach ($processors as $name => $processor) {
            if ($this->_isProcessing()) {
                try {
                    $processor->setIsProcessing(true);
                    // The params should be set now, because there is the processing
                    // parameter.
                    $processor->setParams($params);
                    $result = $processor->precheckConfig();
                    if (!empty($result)) {
                        $this->_processError(__('An error occurred during precheck of "%s".',
                            $processor->pluginName), $result);
                        return;
                    }
                } catch (Exception $e) {
                    $message = $e->getMessage();
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
        $this->_log(__('Prechecks end successfully.'), Zend_Log::INFO);

        $this->_log(__('Checks start.'), Zend_Log::INFO);
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
                    $message = $e->getMessage();
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
        $this->_log(__('Checks end successfully.'), Zend_Log::INFO);

        // Launch process.
        $this->_log(__('Process start.'), Zend_Log::INFO);
        foreach ($processors as $name => $processor) {
            if ($this->_isProcessing()) {
                try {
                    $result = $processor->process();
                    // The result should be empty.
                    if ($result) {
                        throw new UpgradeToOmekaS_Exception($result);
                    }
                } catch (UpgradeToOmekaS_Exception $e) {
                        $message = $e->getMessage();
                        $this->_processError(__('An error occurred during process of "%s".',
                            $processor->pluginName), array('[' . $processor->pluginName . ']' . $e->getMessage()));
                        return;
                } catch (Exception $e) {
                    $message = $e->getMessage();
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
        $this->_log(__('Process end successfully.'), Zend_Log::INFO);

        // No error.
        $this->_log(__('End of upgrade from Omeka Classic to Omeka Semantic.'), Zend_Log::INFO);

        set_option('upgrade_to_omeka_s_process_status', Process::STATUS_COMPLETED);
        set_option('upgrade_to_omeka_s_service_down', false);
    }

    public function setParams($params)
    {
        $this->_params = $params;
    }

    /**
     * List and check processors.
     *
     * @todo Move this in another place to merge it with UpgradeToOmekaS_IndexController?
     *
     * @return array
     */
    protected function _listProcessors()
    {
        $processors = array();
        $allProcessors = apply_filters('upgrade_omekas', array());

        // Get installed plugins, includes active and inactive.
        $pluginLoader = Zend_Registry::get('pluginloader');
        $installedPlugins = $pluginLoader->getPlugins();

        // Keep only the name of plugins.
        $installedPlugins = array_map(function ($v) {
            return $v->name;
        }, $installedPlugins);
        $installedPlugins[] = 'Core';

        // Check processors to prevents possible issues with external plugins.
        foreach ($allProcessors as $name => $processor) {
            $class = $processor['class'];
            if (class_exists($class)) {
                if (is_subclass_of($class, 'UpgradeToOmekaS_Processor_Abstract')) {
                    if (in_array($name, $installedPlugins)) {
                        $processors[$name] = new $class();
                    }
                }
            }
        }

        return $processors;
    }

    /**
     * Return the status of the process.
     *
     * @todo Uses the status of the process object.
     *
     * @return boolean
     */
    protected function _isProcessing()
    {
        return in_array(get_option('upgrade_to_omeka_s_process_status'), array(
            Process::STATUS_STARTING,
            Process::STATUS_IN_PROGRESS,
        ));
    }

    /**
     * Quit the process after an error.
     *
     * @param string $message
     * @param array $messages
     */
    protected function _processError($message, $messages = array())
    {
        set_option('upgrade_to_omeka_s_process_status', Process::STATUS_ERROR);
        $this->_log($message, Zend_Log::ERR);
        foreach ($messages as $message) {
            $this->_log($message, Zend_Log::ERR);
        }
    }

    /**
     * Log infos about process.
     *
     * @todo Merge with the logs of the processor or use a full logging class.
     *
     * @param string $message
     * @param integer $priority
     */
    protected function _log($message, $priority = Zend_Log::DEBUG)
    {
        $priorities = array(
            Zend_Log::EMERG => 'emergency',
            Zend_Log::ALERT => 'alert',
            Zend_Log::CRIT => 'critical',
            Zend_Log::ERR => 'error',
            Zend_Log::WARN => 'warning',
            Zend_Log::NOTICE => 'notice',
            Zend_Log::INFO => 'info',
            Zend_Log::DEBUG => 'debug',
        );
        if (!isset($priorities[$priority])) {
            $priority = Zend_Log::ERR;
        }

        $logs = json_decode(get_option('upgrade_to_omeka_s_process_logs'), true);

        $msg = $message;
        $processor = '';
        $task = '';
        if (strpos($msg, '[') === 0 && $pos = strpos($msg, ']')) {
            $processor = substr($msg, 1, $pos - 1);
            $msg = substr($msg, $pos + 1);
            if (strpos($msg, '[') === 0 && $pos = strpos($msg, ']')) {
                $task = substr($msg, 1, $pos - 1);
                $msg = substr($msg, $pos + 1);
            }
        }
        $msg = ltrim($msg, ': ');

        $msg = array(
            'date' => date(DateTime::ISO8601),
            'priority' => $priorities[$priority],
            'processor' => $processor,
            'task' => $task,
            'message' => $msg,
        );
        $logs[] = $msg;
        set_option('upgrade_to_omeka_s_process_logs', json_encode($logs));

        $message = ltrim($message, ': ');

        $message = '[UpgradeToOmekaS]: ' . $message;
        _log($message, $priority);
    }
}
