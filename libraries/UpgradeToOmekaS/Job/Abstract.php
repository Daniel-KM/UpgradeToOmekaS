<?php

/**
 * UpgradeToOmekaS_Job_Abstract class
 *
 * @todo Manage status via the main process object, not via an internal option.
 *
 * @package UpgradeToOmekaS
 */
abstract class UpgradeToOmekaS_Job_Abstract extends Omeka_Job_AbstractJob
{
    /**
     * Params of the job.
     *
     * @var array
     */
    protected $_params;

    /**
     * Short to the security.ini.
     *
     * @var Zend_Ini
     */
    protected $_securityIni;

    /**
     * Short to the processor base.
     *
     * @var UpgradeToOmekaS_Processor_Base
     */
    protected $_processorBase;

    /**
     * Performs the upgrade task.
     */
    abstract public function perform();

    public function setParams($params)
    {
        $this->_params = $params;
    }

    /**
     * Get security.ini of the plugin.
     *
     * @return Zend_Config_Ini
     */
    protected function _getSecurityIni()
    {
        if (is_null($this->_securityIni)) {
            $iniFile = dirname(dirname(dirname(dirname(__FILE__))))
                . DIRECTORY_SEPARATOR . 'security.ini';
            $this->_securityIni = new Zend_Config_Ini($iniFile, 'upgrade-to-omeka-s');
        }
        return $this->_securityIni;
    }

    /**
     * Get the base processor.
     *
     * @return UpgradeToOmekaS_Processor_Base
     */
    protected function _getProcessorBase()
    {
        if (empty($this->_processorBase)) {
            $this->_processorBase = new UpgradeToOmekaS_Processor_Base();
        }
        return $this->_processorBase;
    }

    /**
     * List and check processors for active plugins.
     *
     * @return array
     */
    protected function _listProcessors()
    {
        return $this->_getProcessorBase()->getProcessors();
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
        return $this->_getProcessorBase()->isProcessing();
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
        set_option('upgrade_to_omeka_s_process_logs', $this->toJson($logs));

        $message = ltrim($message, ': ');

        $message = '[UpgradeToOmekaS]: ' . $message;
        _log($message, $priority);
    }

    /**
     * Wrapper for json_encode() to get a clean json.
     *
     * @internal The database is Unicode and this is allowed since php 5.4.
     *
     * @param var $value
     * @return string
     */
    public function toJson($value)
    {
        return json_encode(
            $value,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
