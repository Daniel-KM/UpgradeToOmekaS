<?php

/**
 * Define methods that should have upgrade classes.
 *
 * @package UpgradeToOmekaS
 */
abstract class UpgradeToOmekaS_Processor_Abstract
{

    /**
     * The name of the plugin.
     *
     * @var string
     */
    public $pluginName;

    /**
     * Minimum version of the plugin managed by the processor.
     *
     * @var string
     */
    public $minVersion;

    /**
     * Maximum version of the plugin managed by the processor.
     *
     * @var string
     */
    public $maxVersion;

    /**
     * List of methods to process for the upgrade.
     *
     * @var array
     */
    public $processMethods;

    /**
     * To bypass default prechecks. Should be true for the core only.
     *
     * @var boolean
     */
    protected $_bypassDefaultPrechecks = false;

    /**
     * The full dir where Omeka Semantic will be installed.
     *
     * @var string
     */
    protected $_baseDir;

    /**
     * Short to the database of Omeka Classic.
     *
     * @var object
     */
    protected $_db;

    /**
     * Short to the ini reader.
     *
     * @var object
     */
    protected $_iniReader;

    /**
     * List of parameters.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Contains the result of prechecks.
     *
     * @var array
     */
    protected $_prechecks = array();

    /**
     * Contains the result of checks.
     *
     * @var array
     */
    protected $_checks = array();

    /**
     * Define is the process is the real one.
     *
     * @var boolean
     */
    protected $_isProcessing;

    /**
     * Constructor of the class.
     *
     * @return void
     */
    public function __construct()
    {
        $this->_db = get_db();
        $this->_iniReader = new Omeka_Plugin_Ini(PLUGIN_DIR);

        // Check if each method exists.
        if ($this->processMethods) {
            foreach ($this->processMethods as $method) {
                if (!method_exists($this, $method)) {
                    throw new UpgradeToOmekaS_Exception(__('The method "%s" of the plugin %s does not exist.',
                        $method, $this->pluginName));
                }
            }
        }
    }

    /**
     * Set the params.
     *
     * @param array $params
     */
    public function setParams(array $params)
    {
        // The right trim of the base path should be trimmed, even when the form
        // is not used.
        if (isset($params['base_dir'])) {
            $params['base_dir'] = rtrim(trim($params['base_dir']), "/\ ");
        }
        $this->_params = $params;
    }

    /**
     * Get the params.
     *
     * @return array
     */
    public function getParams()
    {
        return $this->_params;
    }

    /**
     * Get a param.
     *
     * @param string $name
     * @return mixed
     */
    public function getParam($name)
    {
        return isset($this->_params[$name]) ? $this->_params[$name] : null;
    }

    /**
     * Quick precheck of the configuration (to display before form, not via a
     * background job).
     *
     * @return array
     */
    final public function precheckConfig()
    {
        if (!$this->_bypassDefaultPrechecks) {
            if (empty($this->pluginName)) {
                $this->_prechecks[] = __('The processor of a plugin should have a plugin name, %s hasn’t.', get_class($this));
            }

            // There is a plugin name, so check versions.
            else {
                $path = $this->pluginName;
                try {
                    $version = $this->_iniReader->getPluginIniValue($path, 'version');
                } catch (Exception $e) {
                    $this->_prechecks[] = __('The plugin.ini file of the plugin "%s" is not readable: %s',
                        $this->pluginName, $e->getMessage());
                    $version = null;
                }

                if ($version) {
                    if ($this->minVersion) {
                        if (version_compare($this->minVersion, $version, '>')) {
                            $this->_prechecks[] = __('The current release requires at least %s %s, current is only %s.',
                                $this->pluginName, $this->minVersion, $version);
                        }
                    }

                    if ($this->maxVersion) {
                        if (version_compare($this->maxVersion, $version, '<')) {
                            $this->_prechecks[] = __('The current release requires at most %s %s, current is %s.',
                                $this->pluginName, $this->maxVersion, $version);
                        }
                    }
                }
            }
        }

        $this->_precheckConfig();

        return $this->_prechecks;
    }

    /**
     * Specific precheck of the config.
     *
     * @return void
     */
    protected function _precheckConfig()
    {
    }

    /**
     * Quick check of the config with params, mainly for the core.
     *
     * @return array
     */
    final public function checkConfig()
    {
        $this->_checkConfig();

        return $this->_checks;
    }

    /**
     * Specific quick check of the config with params, mainly for the core.
     *
     * @return void
     */
    protected function _checkConfig()
    {
    }

    /**
     * Set if the process is real.
     *
     * @todo Replace by the check of the main process or by isProcessing().
     *
     * @param boolean
     */
    public function setIsProcessing($value)
    {
        $this->_isProcessing = (boolean) $value;
    }

    /**
     * Set the process id.
     *
     * @param Process|integer $process
     */
    public function setProcessId($process)
    {
        $this->_processId = is_object($process)
            ? (integer) $process->id
            : (integer) $process;
    }

    /**
     * Process the true import.
     *
     * @todo Move this in the job processor.
     *
     * @return null|string Null if no error, else the last message of error.
     */
    final public function process()
    {
        $this->_log(__('Start processing.'), Zend_Log::INFO);

        // The default methods are checked during the construction, but other
        // ones may be added because the list is public.
        $totalMethods = count($this->processMethods);
        foreach ($this->processMethods as $i => $method) {
            $baseMessage = '[' . $method . ']: ';
            // Process stopped externally.
            if (!$this->_isProcessing()) {
                $this->_log($baseMessage . __('The process has been stopped outside of the processor.'), Zend_Log::WARN);
                return;
            }

            // Missing method.
            if (!method_exists($this, $method)) {
                return __('Method "%s" does not exist.', $method);
            }

            $this->_log($baseMessage . __('Started.'), Zend_Log::INFO);
            try {
                $result = $this->$method();
                if ($result) {
                    return $result;
                }
            } catch (Exception $e) {
                return $e->getMessage();
            }
            $this->_log($baseMessage . __('Ended.'), Zend_Log::INFO);
        }

        $this->_log(__('End processing.'), Zend_Log::INFO);
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
        $status = get_option('upgrade_to_omeka_s_process_status');
        return in_array($status, array(
            Process::STATUS_STARTING,
            Process::STATUS_IN_PROGRESS,
        ));
    }

    /**
     * Log infos about process.
     *
     * @todo Merge with the job processor.
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
        $processor = $this->pluginName;
        $task = '';
        if (strpos($msg, '[') === 0 && $pos = strpos($msg, ']')) {
            $task = substr($msg, 1, $pos - 1);
            $msg = substr($msg, $pos + 1);
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
        if (strpos($message, '[') !== 0) {
            $message = ': ' . $message;
        }

        $message = '[UpgradeToOmekaS][' . $this->pluginName . ']' . $message;
        _log($message, $priority);
    }
}
