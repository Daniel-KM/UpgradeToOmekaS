<?php

/**
 * Define methods that should have upgrade classes.
 *
 * @package UpgradeToOmekaS
 */
abstract class UpgradeToOmekaS_Processor_Abstract
{

    CONST STATUS_RESET = 'reset';

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
    public $minVersion = '0';

    /**
     * Maximum version of the plugin managed by the processor.
     *
     * @var string
     */
    public $maxVersion = '0';

    /**
     * Specify if the plugin is for the core.
     *
     * @internal This allows to avoid some checks and to manage specific tasks.
     *
     * @var boolean
     */
    protected $_isCore = false;

    /**
     * Indicate that the current plugin is replaced by multiple modules.
     *
     * @var boolean
     */
    public $multipleModules = false;

    /**
     * Infos about the module for Omeka S, if any.
     *
     * @var array
     */
    public $module = array();

    /**
     * List of tables of Omeka S, mergeable with those from modules.
     *
     * @var array
     */
    public $tables = array();

    /**
     * List of methods to process for the upgrade.
     *
     * @var array
     */
    public $processMethods = array();

    /**
     * List of methods to process for a specific upgrade type.
     *
     * @var array
     */
    public $specificProcessMethods = array();

    /**
     * Mapping of models between Omeka C and Omeka S.
     *
     * @var array
     */
    public $mapping_models = [
        'element_set' => 'vocabulary',
        'element' => 'property',
        'item_type' => 'resource_class',
        'item_type_element' => 'property',
        'record' => 'resource',
        'item' => 'item',
        'collection' => 'item_set',
        'file' => 'media',
        'element_text' => 'value',

        'user' => 'user',
        'users_activation' => 'password_creation',
        'key' => 'api_key',
        // Options can be "site_setting" or theme setting too.
        'option' => 'setting',
        'plugin' => 'module',
        'process' => 'job',
        'schema_migration' => 'migration',
        'search_text' => '',
        'session' => 'session',
        'tag' => '',
    ];

    /**
     * The mapping of derivative files between Omeka C and Omeka S.
     *
     * @var array
     */
    public  $mapping_derivatives = array();

    /**
     * Mapping of roles mapped from Omeka C to Omeka S.
     *
     * @var array
     */
    public $mapping_roles = array();

    /**
     * Mapping of item types from Omeka C to classes of Omeka S.
     *
     * @var array
     */
    public $mapping_item_types = array();

    /**
     * Mapping of elements from Omeka C to properties of Omeka S.
     *
     * @var array
     */
    public $mapping_elements = array();

    /**
     * Mapping of each theme folders between Omeka C and Omeka S.
     *
     * @var array
     */
    public $mapping_theme_folders = array();

    /**
     * Mapping of each theme files between Omeka C and Omeka S.
     *
     * @internal The key is the filepath of the files already moved in the good
     * Omeka S folder according to the mapping of theme folders.
     *
     * @var array
     */
    public $mapping_theme_files = array();

    /**
     * Mapping to replace strings via regex in converted themes of Omeka S.
     *
     * @var array
     */
    public $mapping_regex = array();

    /**
     * Mapping to replace strings in converted themes of Omeka S.
     *
     * @var array
     */
    public $mapping_replace = array();

    /**
     * List of each hook used by themes in Omeka C.
     *
     * @var array
     */
    public $list_hooks = array();

    /**
     * List of each file to upgrade individualy in themes of Omeka S.
     *
     * @var array
     */
    public $upgrade_files = array();

    /**
     * Mapping of each exhibit block layout between Omeka C and Omeka S.
     *
     * @var array
     */
    public $mapping_layouts = array();

    /**
     * Maximum rows to process by loop.
     *
     * @var integer
     */
    public $maxChunk = 100;

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
     * Short to the tools to use on Omeka Semantic, in particular the database.
     *
     * Even if the database is shared, this is not an alias of $_db since it has
     * a direct access to the database without the Zend layers of Omeka Classic.
     *
     * @var object
     */
    protected $_target;

    /**
     * Short to the security.ini.
     *
     * @var Zend_Ini
     */
    protected $_securityIni;

    /**
     * List of processors.
     *
     * @var array
     */
    protected $_processors = array();

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
     * Save the task currently processing.
     *
     * @var string
     */
    protected $_currentTask;

    /**
     * Single datetime for whole process.
     *
     * @var string
     */
    protected $_datetime;

    /**
     * The title of the first site on Omeka S.
     *
     * @var string
     */
    protected $_siteTitle;

    /**
     * The slug of the first site on Omeka S.
     *
     * @var string
     */
    protected $_siteSlug;

    /**
     * The theme of the first site on Omeka S.
     *
     * @var string
     */
    protected $_siteTheme;

    /**
     * The id of the first site once created (always #1).
     *
     * @var string
     */
    protected $_siteId = 1;

    /**
     * List of merged values from all plugins.
     *
     * @var array
     */
    protected $_merged = array();


    /**
     * Temp store for current mapping of record ids between Omeka C and Omeka S.
     *
     * @var array
     */
    protected $_mappingIds = array();

    /**
     * Store the mapped record ids between Omeka C and Omeka S.
     *
     * @var UpgradeToOmekaS_MappedIds
     */
    protected $_mappedIds;

    /**
     * Internal count the current progression.
     *
     * @var integer
     */
    protected $_progressCurrent = 0;

    /**
     * Constructor of the class.
     *
     * @return void
     */
    public function __construct()
    {
        $this->_db = get_db();

        // Check if each method exists.
        foreach ($this->processMethods as $method) {
            if (!method_exists($this, $method)) {
                throw new UpgradeToOmekaS_Exception(
                    __('The method "%s" of the plugin %s does not exist.',
                        $method, $this->pluginName));
            }
        }

        // Check if each specific method exists.
        foreach ($this->specificProcessMethods as $upgradeType => $methods) {
            foreach ($methods as $method) {
                if (!method_exists($this, $method)) {
                    throw new UpgradeToOmekaS_Exception(
                        __('The method "%s" of the plugin %s for the upgrade type "%s" does not exist.',
                            $method, $this->pluginName, $upgradeType));
                }
            }
        }

        try {
            $this->_init();
        } catch (Exception $e) {
            throw new UpgradeToOmekaS_Exception(
                __('An error occurred durring the init of %s: %s.', $this->pluginName, $e->getMessage()));
        }
    }

    /**
     * A method used to initialize classes.
     */
    protected function _init()
    {
        $dataDir = dirname(dirname(dirname(dirname(__FILE__))))
            . DIRECTORY_SEPARATOR . 'libraries'
            . DIRECTORY_SEPARATOR . 'data';

        $loadables = array(
            // Server.

            // Site.
            'mapping_roles',

            // Elements.
            'mapping_item_types',
            'mapping_elements',

            // Records.

            // Files.
            'mapping_derivatives',

            // Themes.
            'mapping_theme_folders',
            'mapping_theme_files',
            'mapping_regex',
            'mapping_replace',
            'list_hooks',
            'upgrade_files',
        );

        $underscoreName = $this->isCore() ? '' : ('_' . Inflector::underscore($this->pluginName));
        foreach ($loadables as $basename) {
            $file = $dataDir
                . DIRECTORY_SEPARATOR . $basename . $underscoreName . '.php';
            if (file_exists($file)) {
                $this->$basename = require $file;
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
     * @param mixed $value
     * @return mixed
     */
    public function setParam($name, $value)
    {
        $this->_params[$name] = $value;
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

    public function setAssetPaths($assetPaths = array())
    {
        $view = get_view();
        if (empty($assetPaths)) {
            $assetPaths = $view->getAssetPaths();
            if (!empty($assetPaths)) {
                return;
            }
            $assetPaths = $this->getParam('assetPaths');
            if (empty($assetPaths)) {
                $physical = $this->getParam('base_dir')
                    . DIRECTORY_SEPARATOR . 'application'
                    . DIRECTORY_SEPARATOR . 'views'
                    . DIRECTORY_SEPARATOR . 'scripts';
                $web = $this->getParam('WEB_ROOT') . '/application/views/scripts';
                $assetPaths = array($physical => $web);
            }
        }
        foreach ($assetPaths as $assetPath) {
            list($physical, $web) = $assetPath;
            $view->addAssetPath($physical, $web);
        }
        Zend_Registry::set('view', $view);
    }

    /**
     * Get the list of all active processors with current params.
     *
     * @return array
     */
    public function getProcessors()
    {
        if (empty($this->_processors)) {
            $processors = array();
            $allProcessors = apply_filters('upgrade_omekas', array());

            // Get installed plugins, includes active and inactive.
            $pluginLoader = Zend_Registry::get('pluginloader');
            $installedPlugins = $pluginLoader->getPlugins();

            // Keep only the name of plugins.
            $activePlugins = array_map(function ($v) {
                return $v->isActive() ? $v->name : null;
            }, $installedPlugins);
            $activePlugins = array_filter($activePlugins);
            // Add all core "plugins".
            $activePlugins[] = 'Core/Server';
            $activePlugins[] = 'Core/Site';
            $activePlugins[] = 'Core/Elements';
            $activePlugins[] = 'Core/Records';
            $activePlugins[] = 'Core/Files';
            $activePlugins[] = 'Core/Themes';
            $activePlugins[] = 'Core/Checks';

            // Check processors to prevents possible issues with external plugins.
            foreach ($allProcessors as $name => $class) {
                if (class_exists($class)) {
                    if (is_subclass_of($class, 'UpgradeToOmekaS_Processor_Abstract')) {
                        if (in_array($name, $activePlugins)) {
                            $processor = new $class();
                            $result = $processor->isPluginReady()
                                && !($processor->precheckProcessorPlugin());
                            if ($result) {
                                $processor->setParams($this->getParams());
                                $processor->setDatetime($this->getDatetime());
                                $processors[$name] = $processor;
                            }
                        }
                    }
                }
            }

            // Set "Core/Checks" the last processor.
            $processor = $processors['Core/Checks'];
            unset($processors['Core/Checks']);
            $processors['Core/Checks'] = $processor;

            $this->_processors = $processors;
        }
        return $this->_processors;
    }

    /**
     * Get a processor.
     *
     * @param string $name
     * @return Processor
     */
    public function getProcessor($name)
    {
        $processors = $this->getProcessors();
        if (isset($processors[$name])) {
            return $processors[$name];
        }
    }

    /**
     * Get the list of name of current processors.
     *
     * @return array
     */
    public function getProcessorNames()
    {
        static $processorNames;

        if (is_null($processorNames)) {
            $processors = $this->getProcessors();
            $processorNames = array_keys($processors);
        }

        return $processorNames;
    }

    /**
     * Get the list of the name of current processors for plugins.
     *
     * @return array
     */
    public function getPluginNames()
    {
        static $pluginNames;

        if (is_null($pluginNames)) {
            $processors = $this->getProcessors();
            $pluginNames = array();
            foreach ($processors as $processor) {
                if (!$processor->isCore()) {
                    $pluginNames[] = $processor->pluginName;
                }
            }
        }
        return $pluginNames;
    }


    /**
     * Set the datetime.
     *
     * @param string $datetime
     */
    public function setDatetime($datetime)
    {
        $this->_datetime = $datetime;
    }

    /**
     * Get the datetime.
     *
     * @return string
     */
    public function getDatetime()
    {
        if (is_null($this->_datetime)) {
            $this->setDatetime(date('Y-m-d H:i:s'));
        }
        return $this->_datetime;
    }

    /**
     * Helper to get the site id (#1).
     *
     * @return integer
     */
    public function getSiteId()
    {
        return $this->_siteId;
    }

    /**
     * Helper to get the site title.
     *
     * @return string
     */
    public function getSiteTitle()
    {
        if (empty($this->_siteTitle)) {
            $title = get_option('site_title') ?: __('Site %s', $this->getParam('WEB_ROOT') ?: __('[unknown]'));
            $title = substr($title, 0, 190);
            $this->_siteTitle = $title;
        }
        return $this->_siteTitle;
    }

    /**
     * Helper to get the site slug.
     *
     * @return string
     */
    public function getSiteSlug()
    {
        if (empty($this->_siteSlug)) {
            $title = $this->getSiteTitle();
            $slug = substr($this->_slugify($title), 0, 190);
            $this->_siteSlug = $slug;
        }
        return $this->_siteSlug;
    }

    /**
     * Helper to get the site theme in Omeka S.
     *
     * @internal The upgraded default theme is "classic".
     *
     * @return string
     */
    public function getSiteTheme()
    {
        if (empty($this->_siteTheme)) {
            $theme = get_option('public_theme');
            if (empty($theme) || $theme == 'default') {
                $theme = 'classic';
            }
            $this->_siteTheme = $theme;
        }
        return $this->_siteTheme;
    }

    /**
     * Get a specific array of values from all processors.
     *
     * @param string $name The name of the values to fetch.
     * @return array
     */
    public function getMerged($name)
    {
        if (empty($name)) {
            return;
        }
        if (!isset($this->_merged[$name])) {
            $this->_merged[$name] = array();
            $processors = $this->getProcessors();
            foreach ($processors as $processor) {
                if (!isset($processor->$name) || empty($processor->$name)) {
                    continue;
                }
                $this->_merged[$name] = $processor->$name + $this->_merged[$name];
            }
        }
        return $this->_merged[$name];
    }

    /**
     * Get a specific list of simple string values from all processors.
     *
     * @param string $name The name of the values to fetch.
     * @return array
     */
    public function getMergedList($name)
    {
        if (empty($name)) {
            return;
        }
        if (!isset($this->_merged[$name])) {
            $this->_merged[$name] = array();
            $processors = $this->getProcessors();
            foreach ($processors as $processor) {
                if (!isset($processor->$name) || empty($processor->$name)) {
                    continue;
                }
                $this->_merged[$name] = array_merge(
                    $this->_merged[$name],
                    array_values($processor->$name));
            }
            // Remove duplicates.
            $this->_merged[$name] = array_values(array_flip(array_flip($this->_merged[$name])));
        }
        return $this->_merged[$name];
    }

    /**
     * Get security.ini of the plugin.
     *
     * @return Zend_Config_Ini
     */
    public function getSecurityIni()
    {
        if (is_null($this->_securityIni)) {
            $iniFile = dirname(dirname(dirname(dirname(__FILE__))))
                . DIRECTORY_SEPARATOR . 'security.ini';
            $this->_securityIni = new Zend_Config_Ini($iniFile, 'upgrade-to-omeka-s');
        }
        return $this->_securityIni;
    }

    /**
     * Check if this class is a core one.
     *
     * @return boolean
     */
    final public function isCore()
    {
        return $this->_isCore;
    }

    /**
     * Check if the plugin is installed.
     *
     * @return boolean
     */
    public function isPluginReady()
    {
        if (empty($this->pluginName)) {
            return false;
        }

        if ($this->isCore()) {
            return true;
        }

        $plugin = get_record('Plugin', array('name' => $this->pluginName));
        return $plugin && $plugin->isActive();
    }

    /**
     * Return the status of the process.
     *
     * @return boolean
     */
    public function isProcessing()
    {
        $status = $this->getStatus();
        return in_array($status, array(
            Process::STATUS_STARTING,
            Process::STATUS_IN_PROGRESS,
        ));
    }

    /**
     * Return the status of the process.
     *
     * @return boolean
     */
    public function getStatus()
    {
        // A direct call, since the options are cached.
        // $status = get_option('upgrade_to_omeka_s_process_status');
        $db = $this->_db;
        $select = 'SELECT `value`
        FROM ' . $db->prefix . 'options
        WHERE `name` = "upgrade_to_omeka_s_process_status";';
        $status = $db->fetchOne($select);
        return $status;
    }

    /**
     * Precheck if the processor matches the plugin, even not installed.
     *
     * @return string|null Null means no error.
     */
    final public function precheckProcessorPlugin()
    {
        if (strpos($this->pluginName, 'Core/') === 0) {
            return;
        }

        if (empty($this->pluginName)) {
            return __('The processor of a plugin should have a plugin name, %s hasn’t.', get_class($this));
        }

        // There is a plugin name, so check versions.
        $path = $this->pluginName;
        try {
            $iniReader = new Omeka_Plugin_Ini(PLUGIN_DIR);
            $version = $iniReader->getPluginIniValue($path, 'version');
        } catch (Exception $e) {
            return __('The plugin.ini file of the plugin "%s" is not readable: %s',
                $this->pluginName, $e->getMessage());
        }

        if ($version) {
            if ($this->minVersion) {
                if (version_compare($this->minVersion, $version, '>')) {
                    return __('The processor for %s requires a version between %s and %s (current is %s).',
                        $this->pluginName, $this->minVersion, $this->maxVersion, $version);
                }
            }

            if ($this->maxVersion) {
                if (version_compare($this->maxVersion, $version, '<')) {
                    return __('The processor for %s requires a version between %s and %s (current is %s).',
                        $this->pluginName, $this->minVersion, $this->maxVersion, $version);
                }
            }
        }
    }

    /**
     * Quick precheck of the configuration (to display before form, not via a
     * background job).
     *
     * @return array
     */
    final public function precheckConfig()
    {
        if ($this->isPluginReady()) {
            $result = $this->precheckProcessorPlugin();
            if ($result) {
                $this->_prechecks[] = $result;
            }
            // The processor is fine for the plugin.
            else {
                $this->_precheckConfig();
            }
        }
        //  Not installed or disabled.
        else {
            $this->_prechecks[] = __('The plugin is not installed or not active.');
        }

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
        $upgradeType = $this->getParam('upgrade_type');
        if ($upgradeType != 'themes') {
            if ($this->isPluginReady()) {
                $this->_checkConfig();
            }
        }

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
     * Process the true upgrade.
     *
     * @todo Move this in the job processor.
     *
     * @throws UpgradeToOmekaS_Exception
     * @return null|string Null if no error, else the last message of error.
     */
    final public function process()
    {
        if (!$this->isPluginReady()) {
            return;
        }

        // May use specific process methods.
        $upgradeType = $this->getParam('upgrade_type');
        if (empty($upgradeType) || $upgradeType == 'full') {
            $processMethods = $this->processMethods;
        } elseif (isset($this->specificProcessMethods[$upgradeType])) {
            $processMethods = $this->specificProcessMethods[$upgradeType];
        } else {
            $processMethods = array();
        }

        if (empty($processMethods)) {
            return;
        }

        $this->_log(__('Start processing.'), Zend_Log::DEBUG);

        // The default methods are checked during the construction, but other
        // ones may be added because the list is public.
        $totalMethods = count($processMethods);
        foreach ($processMethods as $i => $method) {
            $baseMessage = '[' . $method . ']: ';
            // Process stopped externally.
            if (!$this->isProcessing()) {
                $this->_log($baseMessage . __('The process has been stopped outside of the processor.'),
                    Zend_Log::WARN);
                return;
            }

            // Missing method.
            if (!method_exists($this, $method)) {
                // Avoid a issue when there is a bug during process of themes.
                revert_theme_base_url();
                throw new UpgradeToOmekaS_Exception(
                    $baseMessage . __('Method "%s" does not exist.', $method));
            }

            $this->_log($baseMessage . __('Started.'), Zend_Log::DEBUG);

            // Initialize progression.
            $this->_currentTask = $method;
            $this->_progress();

            try {
                $result = $this->$method();
                // Needed for prechecks and checks.
                if ($result) {
                    throw new UpgradeToOmekaS_Exception($result);
                }
            } catch (Exception $e) {
                // Avoid a issue when there is a bug during process of themes.
                revert_theme_base_url();
                throw new UpgradeToOmekaS_Exception($baseMessage . $e->getMessage());
            }
            $this->_log($baseMessage . __('Ended.'), Zend_Log::DEBUG);
        }

        $this->_log(__('End processing.'), Zend_Log::DEBUG);
    }

    /**
     * Save the current state of progress of the current task.
     *
     * @todo Create a specific class for progress (with display) and log.
     * @todo Some glitches may occur because options are cached.
     *
     * @param integer $current
     * @param integer $total
     * @return array
     */
    protected function _progress($current = null, $total = null)
    {
        // This is a task to reset.
        if (!$this->isProcessing()) {
            $progress= array();
        }
        // This is a task to pre-init.
        elseif (is_null($current) && is_null($total)) {
            $progress = array(
                'processor' => $this->pluginName,
                'task' => $this->_currentTask,
                'start' => date('Y-m-d H:i:s'),
                'current' => null,
                'total' => null,
            );
        }
        // This may be an update or an init.
        else {
            $progress = json_decode(get_option('upgrade_to_omeka_s_process_progress'), true);
            if (empty($progress)) {
                $this->_log('[' . __FUNCTION__ . ']: ' . __('The progress variable is not set.'),
                    Zend_Log::WARN);
                return $this->_progress($current, 0);
            }
            //This may be an update.
            if (!is_null($current)) {
                $progress['current'] = $current;
            }
            // This may be a first init of the total.
            if (!is_null($total)) {
                $progress['total'] = $total;
            }
        }
        set_option('upgrade_to_omeka_s_process_progress', $this->toJson($progress));
        return $progress;
    }

    /**
     * Update the current state of progress of a download task and return it.
     *
     * @param integer $current
     * @param integer $total
     * @return array
     */
    public function checkProgress()
    {
        // This is a task to reset.
        if (!$this->isProcessing()) {
            return $this->_progress();
        }

        $progress = json_decode(get_option('upgrade_to_omeka_s_process_progress'), true);
        if (empty($progress)) {
            return $progress;
        }

        // Check if this is a task managed internally.
        if (!in_array($progress['task'], array('_downloadOmekaS', '_downloadModule'))) {
            return $progress;
        }

        $processor = $this->getProcessor($progress['processor']);
        $modules = $processor->_listModules();
        $current = 0;
        foreach ($modules as $module) {
            $filename = $processor->_moduleFilename($module);
            $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;
            if (!file_exists($path)) {
                continue;
            }
            $filesize = filesize($path);
            if ($filesize == $module['size']) {
                continue;
           }
           $current = $filesize;
        }

        return $this->_progress($current);
    }

    /**
     * Helper to get an absolute path to a file or a directory inside Omeka S.
     *
     * @param string $path A relative path.
     * @param boolean $check Check if exists and is readable.
     * @return string
     */
    public function getFullPath($path, $check = true)
    {
        $baseDir = $this->getParam('base_dir');
        if (empty($baseDir)) {
            throw new UpgradeToOmekaS_Exception(
                __('Base dir undefined.'));
        }
        $file = $baseDir . DIRECTORY_SEPARATOR . ltrim($path, '/');
        if ($check) {
            if (!file_exists($file)) {
                throw new UpgradeToOmekaS_Exception(
                    __('The file "%s" doesn’t exist.', $path));
            }
            if (!is_readable($file)) {
                throw new UpgradeToOmekaS_Exception(
                    __('The file "%s" is not readable.', $path));
            }
        }
        return $file;
    }

    /**
     * Wrapper to get the target tools on Omeka S.
     *
     * @throws UpgradeToOmekaS_Exception
     * @return UpgradeToOmekaS_Helper_Target|null
     */
    public function getTarget()
    {
        if (!empty($this->_target)) {
            return $this->_target;
        }

        $target = new UpgradeToOmekaS_Helper_TargetOmekaS();

        $databaseParams = $this->getParam('database');
        $target->setDatabaseParams($databaseParams);

        $omekasTables = $this->getMergedList('tables');
        $target->setTables($omekasTables);

        $target->setIsProcessing($this->isProcessing());

        $this->_target = $target;
        return $this->_target;
    }

    /**
     * Process all steps to install a module: dir, download, unzip, enable.
     */
    protected function _installModule()
    {
        //Set the default module name.
        $moduleName = $this->_getModuleName();

        //Check if the plugin is replaced by multiple plugins.
        $modules = $this->_listModules();
        if ($this->multipleModules) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('The plugin "%s" is replaced by %d modules.',
                    $moduleName, count($modules)),
                Zend_Log::INFO);
        }

        // Fetch all modules to avoid issues with undownloadable dependencies.
        foreach ($modules as $module) {
            if (empty($module['name'])) {
                $module['name'] = $moduleName;
            }
            $result = $this->_fetchModule($module);
            if (!$result) {
                $this->_log('[' . __FUNCTION__ . ']: ' .
                    __('An issue occurred during the preparation of the plugin "%s".', $moduleName),
                        Zend_Log::ERR);
                return;
            }
        }

        // Enable and install all modules.
        foreach ($modules as $module) {
            if (empty($module['name'])) {
                $module['name'] = $moduleName;
            }
            $result = $this->_initializeModule($module);
            if (!$result) {
                $this->_log('[' . __FUNCTION__ . ']: ' .
                    __('An issue occurred during the initialization of the module for the plugin "%s".', $moduleName),
                    Zend_Log::ERR);
                return;
            }
        }

        // Upgrade settings.
        $this->_upgradeSettings();

        // Upgrade data.
        $this->_upgradeData();

        // Upgrade files.
        $this->_upgradeFiles();
    }

    /**
     * Helper to get the module name, that may be not set when not changed.
     *
     * @return string
     */
    protected function _getModuleName()
    {
        return !empty($this->module['name'])
            ? $this->module['name']
            : $this->pluginName;
    }

    /**
     * Helper to get the list of modules (generally alone) to upgrade a plugin.
     *
     * @return array
     */
    protected function _listModules()
    {
        if ($this->multipleModules) {
            // These allows to keepgeneric infos about the plugin replacement.
            $modules = $this->module;
            foreach ($modules as $key => $module) {
                if (!is_array($module)) {
                    unset($modules[$key]);
                }
            }
        } else {
            $modules = array($this->module);
        }
        return $modules;
    }

    /**
     * Check if a module is installed.
     *
     * @param array $moduleName
     * @return array|null Null if not installed.
     */
    protected function _checkInstalledModule($moduleName)
    {
        $targetDb = $this->getTarget()->getDb();
        $sql = 'SELECT * FROM module WHERE id = ' . $targetDb->quote($moduleName);
        $result = $targetDb->fetchRow($sql);
        return $result;
    }

    /**
     * Helper to fetch and unzip a module.
     *
     * @internal These are the main cases where an error may occur.
     *
     * @param array $module
     * @return boolean
     */
    protected function _fetchModule($module)
    {
        // Check if the module have been installed by another processor.
        $result = $this->_checkInstalledModule($module['name']);
        if ($result) {
            // If installed and not active, there was an error already thrown.
            if (!$result['is_active']) {
                $this->_log('[' . __FUNCTION__ . ']: ' . __('The module is already installed, but not active.')
                    . ' ' . __('An error may have occurred in a previous step.'),
                    Zend_Log::WARN);
                return true;
            }

            $this->_log('[' . __FUNCTION__ . ']: ' . __('The module is already installed and active.'),
                Zend_Log::DEBUG);
            return true;
        }

        // A useless second check.
        $dir = $this->_getModuleDir($module['name']);

        // Check if the module is unzipped: it may have been downloaded by
        // another processor. This is not the case for the core, that has its
        // own checks.
        if (file_exists($dir)) {
            if (!is_dir($dir)) {
                throw new UpgradeToOmekaS_Exception(
                    __('The path "%s" is a file.', $dir));
            }
            if (!UpgradeToOmekaS_Common::isDirEmpty($dir)) {
                $this->_log('[' . __FUNCTION__ . ']: '
                    . __('The module "%s" has already been unzipped.',
                        $module['name']), Zend_Log::DEBUG);
            }
            return true;
        }

        // Create dir.
        $result = UpgradeToOmekaS_Common::createDir($dir);
        if (!$result) {
            throw new UpgradeToOmekaS_Exception(
                __('Unable to create the directory "%s".', $dir));
        }

        // Download module. Don't stop on error, but continue to next processor.
        try {
            $this->_downloadModule($module);
        } catch (UpgradeToOmekaS_Exception $e) {
            $this->_log('[' . __FUNCTION__ . ']: ' . $e, Zend_Log::ERR);
            return false;
        }

        // Unzip module in the directory.
        $this->_unzipModule($module);

        return true;
    }

    /**
     * Helper to fetch, unzip, register and enable a module.
     *
     * @internal These are the main cases wherre an error may occur.
     *
     * @param array $module
     * @return boolean
     */
    protected function _initializeModule($module = null)
    {
        if (is_null($module)) {
            $module = $this->module;
        }

        // Check if the module have been installed by another processor.
        $result = $this->_checkInstalledModule($module['name']);
        if ($result) {
            // If installed and not active, there was an error already thrown.
            if ($result['is_active']) {
                return true;
            }
        }
        // Downloaded, but not yet prepared and registered.
        else {
            // Process the install script.
            $this->_prepareModule($module);

            // Register the module.
            $this->_registerModule($module);
        }

        // Activate the module.
        $this->_activateModule($module);

        return true;
    }

    /**
     * Helper to get the module dir.
     *
     * @param string $moduleName
     * @return string
     */
    protected function _getModuleDir($moduleName = null)
    {
        $dir = $this->getParam('base_dir');
        if (!$this->isCore()) {
            if (empty($moduleName)) {
                $moduleName = $this->_getModuleName();
            }
            $dir .= DIRECTORY_SEPARATOR . 'modules'
                . DIRECTORY_SEPARATOR . $moduleName;
        }
        return $dir;
    }

    /**
     * Download a module into the temp directory of the server.
     *
     * @param array $module
     * @param boolean $throwsException
     * @return void
     */
    protected function _downloadModule($module, $throwsException = false)
    {
        if (is_null($module)) {
            $module = $this->module;
        }

        $this->_progress(0, $module['size']);

        $url = sprintf($module['url'], $module['version']);
        $filename = $this->_moduleFilename($module);
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;

        if (file_exists($path)) {
            $filesize = filesize($path);
            // Check if the file is empty, in particular for network issues.
            if (empty($filesize)) {
                $result = @unlink($path);
                if (!$result) {
                    throw new UpgradeToOmekaS_Exception(
                        __('An empty file "%s" exists in the temp directory.', $filename)
                        . ' ' . __('You should remove it manually or replace it by the true file (%s).', $url));
                }
                // Download it below.
            }
            // Check with the filesize.
            elseif ($filesize != $module['size']
                    || sha1_file($path) != $module['sha1']
                ) {
                throw new UpgradeToOmekaS_Exception(
                    __('A file "%s" exists in the temp folder of Apache and this is not the release %s.',
                        $filename, $module['version']));
            }
            // The file is the good one.
            else {
                $this->_log('[' . __FUNCTION__ . ']: ' . __('The file is already downloaded.'),
                    Zend_Log::INFO);
                return;
            }
        }

        // Download the file.
        $this->_log('[' . __FUNCTION__ . ']: ' . __('The size of the file to download is %dKB, so wait a while.', ceil($module['size'] / 1000)),
            Zend_Log::INFO);
        $handle = fopen($url, 'rb');
        $result = file_put_contents($path, $handle);
        @fclose($handle);
        if (empty($result)) {
            throw new UpgradeToOmekaS_Exception(
                __('An issue occurred during the file download.')
                . ' ' . __('Try to download it manually (%s) and to save it as "%s" in the temp folder of Apache.', $url, $filename));
        }
        if (filesize($path) != $module['size']
                || sha1_file($path) != $module['sha1']
            ) {
            throw new UpgradeToOmekaS_Exception(
                __('The downloaded file is corrupted.')
                . ' ' . __('Try to download it manually (%s) and to save it as "%s" in the temp folder of Apache.', $url, $filename));
        }
    }

    /**
     * Unzip a module in its directory.
     *
     * @param array $module
     */
    protected function _unzipModule($module = null)
    {
        if (is_null($module)) {
            $module = $this->module;
        }

        $filename = $this->_moduleFilename($module);
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;

        $dir = $this->_getModuleDir($module['name']);

        // No check is done here: if the module is already installed, the
        // previous check skipped it.

        $result = UpgradeToOmekaS_Common::extractZip($path, $dir);
        if (!$result) {
            throw new UpgradeToOmekaS_Exception(
                __('Unable to extract the zip file "%s" from the temp folder of Apache into the destination "%s".',
                    $filename, $dir)
                . ' ' . __('Check the rights of the folder. "%s".', dirname($dir)));
        }
    }

    /**
     * Helper to get a standard filename for the downloaded file in order to
     * avoid collisions and to avoid re-download.
     *
     * @param array $module
     *  @return string
     */
    protected function _moduleFilename($module = null)
    {
        if (is_null($module)) {
            $module = $this->module;
        }

        $url = sprintf($module['url'], $module['version']);
        $filename = $module['name'] . '-' . $module['version'] . '.' . pathinfo(basename($url), PATHINFO_EXTENSION);
        return $filename;
    }

    /**
     * Process the install script of the module.
     *
     * In fact, currently, it executes the sql query of the module if it is set
     * in the values.
     *
     * @internal Generally, just adapt the code inside the method install()
     * inside "module.php".
     *
     * @todo Process the install script of the module, if needed.
     * @param array $module
     */
    protected function _prepareModule($module = null)
    {
        if (is_null($module)) {
            $module = $this->module;
        }

        if (empty($module['install']['sql'])) {
            return;
        }

        $targetDb = $this->getTarget()->getDb();

        $result = $targetDb->prepare($module['install']['sql'])->execute();
        if (!$result) {
            throw new UpgradeToOmekaS_Exception(
                __('Unable to create the tables for the module "%s".', $module['name']));
        }
    }

    /**
     * Register the module.
     *
     * @param array $module
     */
    protected function _registerModule($module = null)
    {
        if (is_null($module)) {
            $module = $this->module;
        }

        $result = $this->_checkInstalledModule($module['name']);
        if ($result) {
            return;
        }

        $target = $this->getTarget();
        $targetDb = $target->getDb();

        $toInsert = array();
        $toInsert['id'] = $module['name'];
        $toInsert['is_active'] = 0;
        $toInsert['version'] = $module['version'];

        $result = $targetDb->insert('module', $toInsert);
        if (!$result) {
            throw new UpgradeToOmekaS_Exception(
                __('Unable to register the module "%s".', $module['name']));
        }
    }

    /**
     * Activate the module.
     *
     * @param array $module
     */
    protected function _activateModule($module = null)
    {
        if (is_null($module)) {
            $module = $this->module;
        }

        $targetDb = $this->getTarget()->getDb();
        $bind = array();
        $bind['is_active'] = 1;
        $result = $targetDb->update(
            'module',
            $bind,
            'id = ' . $targetDb->quote($module['name']));
        if (empty($result)) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('The module "%s" can’t be activated.',
                $module['name']), Zend_Log::WARN);
        }
    }

    /**
     * Upgrade settings once installed the module(s) is installed.
     */
    protected function _upgradeSettings()
    {
    }

    /**
     * Upgrade metadata once the module(s) is installed.
     */
    protected function _upgradeData()
    {
    }

    /**
     * Upgrade files of a module once the module(s) is installed.
     *
     * @internal There are generally nothing to add, because the processor for
     * themes upgrades all files inside the theme.
     */
    protected function _upgradeFiles()
    {
    }

    /**
     * Helper to convert a navigation link from the Omeka 2 to Omeka S.
     *
     * @param array $page The page to convert.
     * @param array $parsed The url and parsed elements.
     * @param array $site Some data for the url of the site.
     * @return array|null The Omeka S formatted nav link, or null.
     */
    protected function _convertNavigationPageToLink($page, $parsed, $site)
    {
    }

    /**
     * Helper to list the ids of a record type or any column with unique values.
     *
     * @param string $recordType
     * @param string $column
     * @return array Associative array of ids.
     */
    protected function _getRecordIds($recordType, $column = 'id')
    {
        $db = $this->_db;
        if (!$table->hasColumn($column)) {
            return;
        }
        $select = $db->getTable($recordType)->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->from(array(), $column)
            ->order($column);
        $result = $db->fetchCol($select);
        return array_combine($result, $result);
    }

    /**
     * Helper to get an associative array of two columns of a table.
     *
     * @param string $table
     * @param string $columnKey
     * @param string $columnValue
     * @return array Associative array of two columns.
     */
    protected function _fetchPairs($recordType, $columnKey, $columnValue)
    {
        $db = $this->_db;
        $table = $db->getTable($recordType);
        if (!$table->hasColumn($columnKey) || !$table->hasColumn($columnValue)) {
            return;
        }
        $select = $db->select()
            ->reset(Zend_Db_Select::COLUMNS)
            ->from($recordType, array($columnKey, $columnValue))
            ->order($columnKey);
        $result = $db->fetchPairs($select);
        return $result;
    }

    /**
     * Init the singleton for mapped ids.
     *
     * @return void
     */
    public function initMappedIds()
    {
        $this->_mappedIds = UpgradeToOmekaS_MappedIds::init();
    }

    /**
     * Store the mapped ids for a record type.
     *
     * @param string recordType
     * @param array $mappedIds
     * @return void
     */
    public function storeMappedIds($recordType, array $mappedIds)
    {
        if (is_null($this->_mappedIds)) {
            $this->initMappedIds();
        }
        $this->_mappedIds->store($recordType, $mappedIds);
    }

    /**
     * Get the mapping ids for a record type or all mappings.
     *
     * @param string $recordType
     * @return array
     */
    public function fetchMappedIds($recordType = null)
    {
        if (is_null($this->_mappedIds)) {
            $this->initMappedIds();
        }
        return $this->_mappedIds->fetch($recordType);
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

        if (strlen($msg) > 10000) {
            $msg = substr($msg, 0, 10000) . PHP_EOL
            . __('See the end of this message in the logs of Omeka.');
        }

        $msg = array(
            'date' => date(DateTime::ISO8601),
            'priority' => $priorities[$priority],
            'processor' => $processor,
            'task' => $task,
            'message' => $msg,
        );
        $logs[] = $msg;

        // Options are limited to 65535 bytes, about 32760 characters in the
        // common non English cases.
        while (strlen($this->toJson($logs)) > 32760) {
            // Keep key, unlike array_shift().
            reset($logs);
            unset($logs[key($logs)]);
        }

        set_option('upgrade_to_omeka_s_process_logs', $this->toJson($logs));

        $message = ltrim($message, ': ');
        if (strpos($message, '[') !== 0) {
            $message = ': ' . $message;
        }

        $message = '[UpgradeToOmekaS][' . $this->pluginName . ']' . $message;
        _log($message, $priority);
    }

    /**
     * Transform the given string into a valid URL slug
     *
     * @see SiteSlugTrait::slugify()
     *
     * @param string $input
     * @return string
     */
    protected function _slugify($input)
    {
        $slug = mb_strtolower($input, 'UTF-8');
        $slug = preg_replace('/[^a-z0-9-]+/u', '-', $slug);
        $slug = preg_replace('/-{2,}/', '-', $slug);
        $slug = preg_replace('/-*$/', '', $slug);
        return $slug;
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

    /**
     * Get the next record id.
     *
     * @internal This allows to insert a record in Omeka S and to keep ids.
     *
     * @param string $recordType
     * @return integer
     */
    protected function _getNextId($recordType)
    {
        try {
            $record = get_record($recordType, array(
                Omeka_Db_Table::SORT_PARAM => 'id',
                Omeka_Db_Table::SORT_DIR_PARAM => 'd',
            ));
        } catch (Exception $e) {
            return 1;
        }
        return $record ? $record->id + 1 : 1;
    }

    /* Methods for the theme conversion, to be refactored. */

    /**
     * Helper to get the output of a hook for upgraded plugicn.
     *
     * @internal The output of the upgraded plugins is useless, because they are
     * upgraded.
     *
     * @param unknown $hookName
     * @param unknown $args
     */
    protected function _upgradeGetOutputHook($hookName, $args)
    {
        set_theme_base_url('public');

        // Anyway, the process is done in the background.
        if (isset($args['view'])) {
            try {
                $view = get_view();
            } catch (Exception $e) {
            }
            $args['view'] = is_object($view) ? $view : new Zend_View();
        }

        // The output is processed one by one, because only upgraded plugin
        // should be fired. See get_specific_plugin_hook_output().
        $pluginNames = $this->getPluginNames();

        $output = '';
        foreach ($pluginNames as $pluginName) {
            try {
                $output .= get_specific_plugin_hook_output($pluginName, $hookName, $args);
            } catch (Exception $e) {
                $this->_log('[' . __FUNCTION__ . ']: ' . __('The hook "%s" threw an exception when fired for the plugin "%s".',
                    $hookName, $pluginName), Zend_Log::WARN);
            }
        }

        revert_theme_base_url();

        return $output;
    }

    /**
     * Helper to create a file or to replace it content.
     *
     * @param string $relativePath The file may not exist.
     * @param string $content
     * @return void
     */
    protected function _upgradeSaveContentInThemes($relativePath, $content)
    {
        $path = $this->getParam('base_dir')
            . DIRECTORY_SEPARATOR . 'themes';

        $themes = UpgradeToOmekaS_Common::listDirsInDir($path);
        $defaultKey = array_search('default', $themes);
        if ($defaultKey !== false) {
            unset($themes[$defaultKey]);
        }
        foreach ($themes as $theme) {
            $destination = $path
            . DIRECTORY_SEPARATOR . $theme
            . DIRECTORY_SEPARATOR . $relativePath;

            UpgradeToOmekaS_Common::createDir(dirname($destination));
            $result = file_put_contents($destination, $content);
        }
    }

    /**
     * Helper to prepend and/or append some code after content of a file.
     *
     * @param string $relativePath The file should exists to be updated.
     * @param string $args
     * @return void
     */
    protected function _upgradeFileInThemes($relativePath, $args = array())
    {
        $path = $this->getParam('base_dir') . DIRECTORY_SEPARATOR . 'themes';

        $defaultArgs = array(
            'remove' => false,
            'comment' => false,
            'file' => '',
            'preg_replace' => array(),
            'preg_replace_single' => array(),
            'replace' => array(),
            'prepend' => '',
            'append' => '',
        );
        $args = $args + $defaultArgs;

        $comment = array(
            '~\*/~' => '*_/',
            '~^(.+)$~m' => '<?php /* \1 */ ?>',
        );

        $baseDir = $this->getParam('base_dir');

        $themes = UpgradeToOmekaS_Common::listDirsInDir($path);
        $defaultKey = array_search('default', $themes);
        if ($defaultKey !== false) {
            unset($themes[$defaultKey]);
        }

        $flag = false;
        foreach ($themes as $theme) {
            $destination = $path
                . DIRECTORY_SEPARATOR . $theme
                . DIRECTORY_SEPARATOR . $relativePath;
            $destinationExists = file_exists($destination);
            $input = $destinationExists
                ? file_get_contents($destination)
                : '';
            if ($args['remove']) {
                $input = '';
            }
            if ($args['comment']) {
                $input = preg_replace(
                    array_keys($comment),
                    array_values($comment),
                    $input);
            }
            if ($args['file']) {
                $file = $baseDir . DIRECTORY_SEPARATOR . $args['file'];
                if (file_exists($file)) {
                    $input .= PHP_EOL
                        . '<?php // ' . __('Included from "%s".', $args['file']) . ' ?>' . PHP_EOL
                        . '<?php // ' . __('Remove this file if not customized.') . ' ?>' . PHP_EOL
                        . PHP_EOL
                        . file_get_contents($file);
                }
            }
            if (!empty($args['preg_replace'])) {
                $emptyInput = empty($input);
                $input = preg_replace(
                    array_keys($args['preg_replace']),
                    array_values($args['preg_replace']),
                    $input);
                if (!$emptyInput && empty($input)) {
                    $flag = true;
                }
            }
            if (!empty($args['preg_replace_single'])) {
                $emptyInput = empty($input);
                $input = preg_replace(
                    array_keys($args['preg_replace_single']),
                    array_values($args['preg_replace_single']),
                    $input,
                    1);
                if (!$emptyInput && empty($input)) {
                    $flag = true;
                }
            }
            if (!empty($args['replace'])) {
                $input = str_replace(
                    array_keys($args['replace']),
                    array_values($args['replace']),
                    $input);
            }
            $output = $args['prepend'] . $input . $args['append'];
            if (!(empty($output) && !$destinationExists)) {
                UpgradeToOmekaS_Common::createDir(dirname($destination));
                $result = file_put_contents($destination, $output);
            }
        }

        if ($flag) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('There is probably an error in the regex used for each file, because there is no output.'),
                Zend_Log::WARN);
        }
    }

    /**
     * Helper to fix a bug in Exhibit Builder.
     *
     * @param unknown $optionName
     * @param unknown $themeName
     */
    protected function _getThemeOption($optionName, $theme = null)
    {
        if (empty($theme)) {
            $theme = $this->getSiteTheme();
        }

        // It fails with the theme Neatscape (no config).
        if ($theme == 'neatscape') {
            return '';
        }

        // It fails with filters of old versions of Exhibit builder.
        if (plugin_is_active('ExhibitBuilder', '3.3.4', '<')) {
            $themeConfigOptions = get_option(Theme::getOptionName($theme));
            $themeConfigOptions = $themeConfigOptions
                ? unserialize($themeConfigOptions)
                : array();

            $themeOptionName = Inflector::underscore($optionName);
            $themeOptionValue = null;
            if ($themeConfigOptions && array_key_exists($themeOptionName, $themeConfigOptions)) {
                $themeOptionValue = $themeConfigOptions[$themeOptionName];
            }

            return (string) $themeOptionValue;
        }

        return (string) get_theme_option($optionName, $theme);
    }
}
