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
    public $minVersion = '0';

    /**
     * Maximum version of the plugin managed by the processor.
     *
     * @var string
     */
    public $maxVersion = '0';

    /**
     * Infos about the module for Omeka S, if any.
     *
     * @var array
     */
    public $module = array();

    /**
     * List of methods to process for the upgrade.
     *
     * @var array
     */
    public $processMethods = array();

    /**
     * Mapping of roles mapped from Omeka C to Omeka S.
     *
     * @var array
     */
    public $mapping_roles = array();

    /**
     * Mapping of item types from Omeka C to classes of Omeka S.
     */
    public $mapping_item_types = array();

    /**
     * Mapping of elements from Omeka C to properties of Omeka S.
     */
    public $mapping_elements = array();

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
     * Short to the database of Omeka Semantic.
     *
     * Even if the database is shared, this is not an alias of $_db since it has
     * a direct access to the database without the Zend layers of Omeka Classic.
     *
     * @var object
     */
    protected $_targetDb;

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
     * Define is the process is the real one.
     *
     * @var boolean
     */
    protected $_isProcessing;

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
     * List of merged values from all plugins.
     *
     * @var array
     */
    protected $_merged = array();

    /**
     * Constructor of the class.
     *
     * @return void
     */
    public function __construct()
    {
        $this->_db = get_db();

        // Check if each method exists.
        if ($this->processMethods) {
            foreach ($this->processMethods as $method) {
                if (!method_exists($this, $method)) {
                    throw new UpgradeToOmekaS_Exception(
                        __('The method "%s" of the plugin %s does not exist.', $method, $this->pluginName));
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

    /**
     * Get the list of all active processors.
     *
     * @internal Not very clean to set processors inside a processor, even if
     * there is no impact on memory. Used to get filtered values, in particular
     * the list of roles, and to convert the navigation menu.
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
            $activePlugins[] = 'Core';

            // Check processors to prevents possible issues with external plugins.
            foreach ($allProcessors as $name => $class) {
                if (class_exists($class)) {
                    if (is_subclass_of($class, 'UpgradeToOmekaS_Processor_Abstract')) {
                        if (in_array($name, $activePlugins)) {
                            $processor = new $class();
                            $result = $processor->isPluginReady()
                                && !($processor->precheckProcessorPlugin());
                            if ($result) {
                                $processors[$name] = $processor;
                            }
                        }
                    }
                }
            }
            $this->_processors = $processors;
        }
        return $this->_processors;
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
     * Helper to get the site title.
     *
     * @return string
     */
    public function getSiteTitle()
    {
        if (empty($this->_siteTitle)) {
            $title = get_option('site_title') ?: __('Site %s', WEB_ROOT);
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
                $this->_merged[$name] = array_merge(
                    $this->_merged[$name],
                    $processor->$name);
            }
        }
        return $this->_merged[$name];
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
     * Check if this class is the core one.
     *
     * @return boolean
     */
    final public function isCore()
    {
        // get_class() isn't used directly because it can be bypassed.
        $processors = apply_filters('upgrade_omekas', array());
        return get_class($this) == $processors['Core'];
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
        $plugin = get_record('Plugin', array('name' => $this->pluginName));
        return $plugin && $plugin->isActive();
    }

    /**
     * Precheck if the processor matches the plugin, even not installed.
     *
     * @return string|null Null means no error.
     */
    final public function precheckProcessorPlugin()
    {
        if ($this->pluginName == 'Core') {
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
                $this->_precheckIntegrity();
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
     * Specific precheck of the integrity of the base and the files.
     *
     * @return void
     */
    protected function _precheckIntegrity()
    {
    }

    /**
     * Quick check of the config with params, mainly for the core.
     *
     * @return array
     */
    final public function checkConfig()
    {
        if ($this->isPluginReady()) {
            $this->_checkConfig();
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
     * @throws UpgradeToOmekaS_Exception
     * @return null|string Null if no error, else the last message of error.
     */
    final public function process()
    {
        if (!$this->isPluginReady()) {
            return;
        }

        $this->_log(__('Start processing.'), Zend_Log::DEBUG);

        // The default methods are checked during the construction, but other
        // ones may be added because the list is public.
        $totalMethods = count($this->processMethods);
        foreach ($this->processMethods as $i => $method) {
            $baseMessage = '[' . $method . ']: ';
            // Process stopped externally.
            if (!$this->_isProcessing()) {
                $this->_log($baseMessage . __('The process has been stopped outside of the processor.'),
                    Zend_Log::WARN);
                return;
            }

            // Missing method.
            if (!method_exists($this, $method)) {
                throw new UpgradeToOmekaS_Exception(
                    $baseMessage . __('Method "%s" does not exist.', $method));
            }

            $this->_log($baseMessage . __('Started.'), Zend_Log::DEBUG);
            try {
                $result = $this->$method();
                // Needed for prechecks and checks.
                if ($result) {
                    throw new UpgradeToOmekaS_Exception($result);
                }
            } catch (Exception $e) {
                throw new UpgradeToOmekaS_Exception($baseMessage . $e->getMessage());
            }
            $this->_log($baseMessage . __('Ended.'), Zend_Log::DEBUG);
        }

        $this->_log(__('End processing.'), Zend_Log::DEBUG);
    }

    /**
     * Helper to get the Omeka S database object.
     *
     * @throws UpgradeToOmekaS_Exception
     * @return Db|null
     */
    public function getTargetDb()
    {
        if (!empty($this->_targetDb)) {
            return $this->_targetDb;
        }

        $params = $this->getParams();
        if (empty($params)) {
            throw new UpgradeToOmekaS_Exception(
                __('The params of the processor are not defined.'));
        }

        $type = $this->getParam('database_type');
        switch ($type) {
            case 'separate':
                $host = $this->getParam('database_host');
                $port = $this->getParam('database_port');
                $dbname = $this->getParam('database_name');
                $username = $this->getParam('database_username');
                $password = $this->getParam('database_password');
                break;
            // The default connection can't be reused, because there are the
            // application layers.
            case 'share':
                $db = $this->_db;
                $config = $db->getAdapter()->getConfig();
                $host = isset($config['host']) ? $config['host'] : '';
                $port = isset($config['port']) ? $config['port'] : '';
                $dbname = isset($config['dbname']) ? $config['dbname'] : '';
                $username = isset($config['username']) ? $config['username'] : '';
                $password = isset($config['password']) ? $config['password'] : '';
                break;
            default:
                throw new UpgradeToOmekaS_Exception(
                    __('The type "%s" is not possible for the database.', $type));
        }

        // Check the connection.
        $params = array(
            'host' => $host,
            'username' => $username,
            'password' => $password,
            'dbname' => $dbname,
        );
        if ($port) {
            $params['port'] = $port;
        }

        try {
            $targetDb = Zend_Db::Factory('PDO_MYSQL', $params);
            if (empty($targetDb)) {
                throw new UpgradeToOmekaS_Exception();
            }
        } catch (Exception $e) {
            throw new UpgradeToOmekaS_Exception(
                __('Cannot access to the database "%s".', $dbname));
        }

        $this->_targetDb = $targetDb;
        return $this->_targetDb;
    }

    /**
     * Helper to quote a value before insertion of multiple rows in database.
     *
     * It safely quotes a value for an SQL statement. If an array is passed as
     * the value, the array values are quoted and then returned as a
     * comma-separated string.
     *
     * @internal Unlike Zend_Db_Adapter_Abstract::quote(), it takes care of
     * the value "null", kept as NULL, not "".
     *
     * @param mixed $value If array, it should be a flat one.
     * @return string A database quoted string, according to the value type. The
     * separator is a comma + a tabulation.
     */
    protected function _dbQuote($value)
    {
        if (is_null($value)) {
            return 'NULL';
        }

        if (is_array($value)) {
            $result = array_map(array($this, '_dbQuote'), $value);
            return implode(",\t", $result);
        }

        return $this->_db->quote($value);
    }

    /**
     * Helper to insert multiple quoted rows in a table of the target database.
     *
     * @internal An early quotation of rows may save memory with big chunks.
     *
     * @param string $table
     * @param array $columns
     * @param array $rows
     * @param boolean $areQuoted
     * @throws UpgradeToOmekaS_Exception
     */
    protected function _insertRows($table, $columns, $rows, $areQuoted = true)
    {
        $targetDb = $this->getTargetDb();

        $sql = sprintf('INSERT INTO `%s` (`%s`) VALUES ', $table, implode('`, `', $columns));
        if (!$areQuoted) {
            foreach ($rows as &$values) {
                $values = $this->_dbQuote($values);
            }
        }
        $sql .= PHP_EOL . '(' . implode('),' . PHP_EOL . '(', $rows) . ');';
        $result = $targetDb->prepare($sql)->execute();
        if (!$result) {
            throw new UpgradeToOmekaS_Exception(
                __('Unable to insert data in table %s.', $table));
        }
    }

    /**
     * Return the greatest id for a target table.
     *
     * This is the last inserted id of the table, since there is no deletion
     * during the upgrade process, at least for main records.
     *
     * @param string $table
     * @return integer
     */
    protected function _getGreatestId($table)
    {
        $targetDb = $this->getTargetDb();
        $select = $targetDb->select()
            ->from($table, array('id'))
            ->order('id DESC');
        $lastId = $targetDb->fetchOne($select);
        return $lastId;
    }

    /**
     * Helper to get the total rows of a target table.
     *
     * @param string $table
     * @return integer
     */
    public function countTargetTable($table)
    {
        $targetDb = $this->getTargetDb();
        $select = $targetDb->select()
            ->from($table, array(new Zend_Db_Expr('COUNT(*)')));
        $result = $targetDb->fetchOne($select);
        return $result;
    }

    /**
     * Get the mapping from Omeka C item types to Omeka S classes.
     *
     * This method doesn't use the database of Omeka S, but the file "classes.php".
     *
     * @param string $itemTypeFormat "name" (default) or "id".
     * @param string $classFormat "prefix:name" (default), "label" or "id".
     * @return array
     */
    public function getMappingItemTypesToClasses($itemTypeFormat = 'name', $classFormat = 'prefix:name')
    {
        static $itemTypes;
        static $classes;
        static $mapping;

        if (empty($itemTypes)) {
            // Get the flat list of item types truly installed in Omeka.
            $select = $this->_db->getTable('ItemType')->getSelect()
                ->reset(Zend_Db_Select::COLUMNS)
                ->from(array(), array(
                    'id' => 'item_types.id',
                    'name' => 'item_types.name',
                ))
                ->order('item_types.id');
            $itemTypes = $this->_db->fetchAll($select);

            // Get the flat list of classes ids and prefix:name by prefix:name.
            $classes = $this->getClasses();
            $result = array();
            foreach ($classes as $vocabularyLabel => $vocabulary) {
                // Use only the prefix of the vocabulary.
                $prefix = trim(substr($vocabularyLabel, strpos($vocabularyLabel, '[')), '[] ');
                // Preformat the class.
                foreach ($vocabulary as $classId => $class) {
                    $label = reset($class);
                    $name = key($class);
                    $result[$prefix . ':' . $name] = array(
                        'id' => $classId,
                        'name' => $name,
                        'prefix:name' => $prefix . ':' . $name,
                        'label' => $label,
                        'prefix' => $prefix,
                    );
                }
            }
            $classes = $result;

            // Get the mapping of item types with classes as prefix:name.
            $mapping = $this->getMerged('mapping_item_types');
            $mapping = array_map(function ($v) {
                return key($v) . ':' . reset($v);
            } , $mapping);
        }

        // Process the requested format.
        $result = array();
        foreach ($itemTypes as $itemType) {
            $mappedClass = null;
            // Get the map of the element if any.
            if (!empty($mapping[$itemType['name']])) {
                $map = $mapping[$itemType['name']];
                if (isset($classes[$map])) {
                    $mappedClass = $classes[$map][$classFormat];
                }
            }
            // Format the result.
            // Set the mapping, even if not mapped.
            switch ($itemTypeFormat) {
                case 'id':
                    $result[$itemType['id']] = $mappedClass;
                    break;
                case 'name':
                default:
                    $result[$itemType['name']] = $mappedClass;
                    break;
            }
        }

        return $result;
    }

    /**
     * Get the list of classes of all vocabularies of Omeka S.
     *
     * This method doesn't use the database of Omeka S, but the file "classes.php".
     *
     * @todo Add an option to fetch the list from the database when possible.
     *
     * @return array
     */
    public function getClasses()
    {
        $script = dirname(dirname(dirname(dirname(__FILE__))))
            . DIRECTORY_SEPARATOR . 'libraries'
            . DIRECTORY_SEPARATOR . 'data'
            . DIRECTORY_SEPARATOR . 'classes.php';
        $classes = require $script;
        return $classes;
    }

    /**
     * Get the mapping from Omeka C elements to Omeka S properties.
     *
     * This method doesn't use the database of Omeka S, but the file "properties.php".
     *
     * @param string $elementFormat "set name:name" (default) or "id".
     * @param string $propertyFormat "prefix:name" (default), "label" or "id".
     * @param boolean $bySet Return a one or a two levels associative array.
     * @return array
     */
    public function getMappingElementsToProperties($elementFormat = 'set_name:name', $propertyFormat = 'prefix:name', $bySet = false)
    {
        static $elements;
        static $properties;
        static $mapping;

        if (empty($elements)) {
            // Get the flat list of elements truly installed in Omeka.
            $select = $this->_db->getTable('Element')->getSelect()
                ->reset(Zend_Db_Select::COLUMNS)
                ->from(array(), array(
                    'id' => 'elements.id',
                    'name' => 'elements.name',
                    'set_name' => 'element_sets.name',
                ))
                ->order('elements.id');
            $elements = $this->_db->fetchAll($select);

            // Get the flat list of property ids and prefix:name by prefix:name.
            $properties = $this->getProperties();
            $result = array();
            foreach ($properties as $vocabularyLabel => $vocabulary) {
                // Use only the prefix of the vocabulary.
                $prefix = trim(substr($vocabularyLabel, strpos($vocabularyLabel, '[')), '[] ');
                // Preformat the property.
                foreach ($vocabulary as $propertyId => $property) {
                    $label = reset($property);
                    $name = key($property);
                    $result[$prefix . ':' . $name] = array(
                        'id' => $propertyId,
                        'name' => $name,
                        'prefix:name' => $prefix . ':' . $name,
                        'label' => $label,
                        'prefix' => $prefix,
                    );
                }
            }
            $properties = $result;

            // Get the mapping of elements with properties as prefix:name.
            $mapping = $this->getMerged('mapping_elements');
            foreach ($mapping as $setName => &$mappedElements) {
                $mappedElements = array_map(function ($v) {
                    return key($v) . ':' . reset($v);
                } , $mappedElements);
            }
        }

        // The list is made by set, then the set level is removed if wanted.

        // Process the requested format.
        $result = array();
        foreach ($elements as $element) {
            $mappedProperty = null;
            // Get the map of the element if any.
            if (!empty($mapping[$element['set_name']][$element['name']])) {
                $map = $mapping[$element['set_name']][$element['name']];
                if (isset($properties[$map])) {
                    $mappedProperty = $properties[$map][$propertyFormat];
                }
            }
            // Format the result.
            // Set the mapping, even if not mapped.
            switch ($elementFormat) {
                case 'id':
                    $result[$element['id']] = $mappedProperty;
                    break;
                case 'set_name:name':
                default:
                    $result[$element['set_name'] . ':' . $element['name']] = $mappedProperty;
                    break;
            }
        }

        if (!$bySet) {
            $simple = array();
            foreach ($result as $set => $mapped) {
                $simple += $mapped;
            }
            $result = $simple;
        }

        return $result;
    }

    /**
     * Get the list of properties of all vocabularies of Omeka S.
     *
     * This method doesn't use the database of Omeka S, but the file "properties.php".
     *
     * @todo Add an option to fetch the list from the database when possible.
     *
     * @return array
     */
    public function getProperties()
    {
        $script = dirname(dirname(dirname(dirname(__FILE__))))
            . DIRECTORY_SEPARATOR . 'libraries'
            . DIRECTORY_SEPARATOR . 'data'
            . DIRECTORY_SEPARATOR . 'properties.php';
        $properties = require $script;
        return $properties;
    }

    /**
     * Wrapper to set a global setting.
     *
     * @param string $id The name of the value.
     * @param var $value The value will be json_encoded().
     * @return void
     */
    protected function _setSetting($id, $value)
    {
        $this->_setJsonSetting('setting', $id, $value);
    }

    /**
     * Wrapper to set a site setting.
     *
     * @param string $id The name of the value.
     * @param var $value The value will be json_encoded().
     * @param integer $siteId The main site, or an exhibit.
     * @return void
     */
    protected function _setSiteSetting($id, $value, $siteId = 1)
    {
        $this->_setJsonSetting('site_setting', $id, $value, $siteId);
    }

    /**
     * Set a setting in json.
     *
     * @param string $table "setting" or "site_setting"
     * @param string $id The name of the value.
     * @param var $value The value will be json_encoded().
     * @param integer $siteId The main site, or an exhibit.
     * @return void
     */
    protected function _setJsonSetting($table, $id, $value, $siteId = null)
    {
        if (empty($id)) {
            throw new UpgradeToOmekaS_Exception(
                __('The id of the setting is empty.'));
        }
        if (empty($table)) {
            throw new UpgradeToOmekaS_Exception(
                __('The table is not defined.'));
        }
        if (!in_array($table, array('setting', 'site_setting'))) {
            throw new UpgradeToOmekaS_Exception(
                __('The table is not managed.'));
        }
        if ($table == 'site_setting' && empty($siteId)) {
            throw new UpgradeToOmekaS_Exception(
                __('The id of the site is empty.'));
        }

        $value = $this->_toJson($value);

        // Check if there is a value.
        $targetDb = $this->getTargetDb();
        $select = $targetDb->select()
            ->from($table)
            ->where('id = ?', $id);
        if ($table == 'site_setting') {
            $select
                ->where('site_id = ?', $siteId);
        }
        $result = $targetDb->fetchRow($select);

        // Update row.
        if ($result) {
            $where = array();
            $where[] = 'id = ' . $targetDb->quote($id);
            if ($table == 'site_setting') {
                $where[] = 'site_id = ' . (integer) $siteId;
            }
            $result = $targetDb->update($table, array('value' => $value), $where);
        }
        // Insert new row.
        else {
            $toInsert = array();
            $toInsert['id'] = $id;
            $toInsert['value'] = $value;
            if ($table == 'site_setting') {
                $toInsert['site_id'] = $siteId;
            }
            $result = $targetDb->insert($table, $toInsert);
        }
    }

    /**
     * Wrapper to get a json_decoded global setting.
     *
     * @param string $id The name of the value.
     * @return var
     */
    protected function _getSetting($id)
    {
        return $this->_getJsonSetting('setting', $id);
    }

    /**
     * Wrapper to get a json_decoded site setting.
     *
     * @param string $id The name of the value.
     * @param integer $siteId The main site, or an exhibit.
     * @return var
     */
    protected function _getSiteSetting($id, $siteId = 1)
    {
        return $this->_getJsonSetting('site_setting', $id, $siteId);
    }

    /**
     * Get a json_decoded setting.
     *
     * @param string $table "setting" or "site_setting"
     * @param integer $id The name of the value.
     * @param integer $siteId The main site, or an exhibit.
     * @return var
     */
    protected function _getJsonSetting($table, $id, $siteId = null)
    {
        if (empty($id)) {
            return;
        }
        if (empty($table)) {
            throw new UpgradeToOmekaS_Exception(
                __('The table is not defined.'));
        }
        if (!in_array($table, array('setting', 'site_setting'))) {
            throw new UpgradeToOmekaS_Exception(
                __('The table is not managed.'));
        }
        if ($table == 'site_setting' && empty($siteId)) {
            throw new UpgradeToOmekaS_Exception(
                __('The id of the site is empty.'));
        }

        $targetDb = $this->getTargetDb();
        $select = $targetDb->select()
            ->from($table, array('value'))
            ->where('id = ?', $id);
        if ($table == 'site_setting') {
            $select
                ->where('site_id = ?', $siteId);
        }
        $result = $targetDb->fetchOne($select);
        if ($result) {
            return json_decode($result, true);
        }
    }

    /**
     * Wrapper for json_encode() to get a clean json.
     *
     * @internal The database is Unicode and this is allowed since php 5.4.
     *
     * @param var $value
     * @return string
     */
    protected function _toJson($value)
    {
        return json_encode(
            $value,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
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
     * Process all steps to install a module: dir, download, unzip, enable.
     */
    protected function _installModule()
    {
        $module = $this->_getModuleName();

        // Check if the module have been installed by another processor.
        $result = $this->_checkInstalledModule($module);
        if ($result) {
            // If installed and not active, there was an error already thrown.
            if (!$result['is_active']) {
                $this->_log('[' . __FUNCTION__ . ']: ' . __('The module is already installed, but not active.')
                    . ' ' . __('An error may have occurred in a previous step.'),
                    Zend_Log::WARN);
                return;
            }

            $this->_log('[' . __FUNCTION__ . ']: ' . __('The module is already installed and active.'),
                Zend_Log::DEBUG);

            $this->_upgradeSettings();
            $this->_upgradeData();
            return;
        }

        // A useless second check.
        $dir = $this->_getModuleDir();

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
                    . __('The plugin "%s" has already been unzipped.',
                        $this->_getModuleName()), Zend_Log::DEBUG);

                $this->_upgradeSettings();
                $this->_upgradeData();
                return;
            }
            // This is strange, but proceeds.
        }
        // Create dir.
        else {
            $this->_createDirectory();
        }

        // Download module.
        $this->_downloadModule();

        // Unzip module in the directory.
        $this->_unzipModule();

        // Process the install script.
        $this->_prepareModule();

        // Register the module.
        $this->_registerModule();

        // Activate the module.
        $this->_activateModule();

        // Upgrade settings.
        $this->_upgradeSettings();

        // Upgrade data and files.
        $this->_upgradeData();
    }

    /**
     * Check if a module is installed.
     *
     * @return array|null Null if not installed.
     */
    protected function _checkInstalledModule($module)
    {
        $targetDb = $this->getTargetDb();
        $sql = 'SELECT * FROM module WHERE id = ' . $targetDb->quote($module);
        $result = $targetDb->fetchRow($sql);
        return $result;
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
     * Helper to get the module dir.
     *
     * @return string
     */
    protected function _getModuleDir()
    {
        $dir = $this->getParam('base_dir');
        if (!$this->isCore()) {
            $dir .= DIRECTORY_SEPARATOR . 'modules'
                . DIRECTORY_SEPARATOR . $this->_getModuleName();
        }
        return $dir;
    }

   /**
     * Helper to create a directory.
     */
    protected function _createDirectory()
    {
        $dir = $this->_getModuleDir();
        $result = UpgradeToOmekaS_Common::createDir($dir);
        if (!$result) {
            throw new UpgradeToOmekaS_Exception(
                __('Unable to create the directory "%s".', $dir));
        }
    }

    /**
     * Download a module into the temp directory of the server.
     */
    protected function _downloadModule()
    {
        $url = sprintf($this->module['url'], $this->module['version']);
        $filename = basename($url);

        // TODO Use a temp name, but it's important to avoid re-downloads.
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;
        if (file_exists($path)) {
            // Check if the file is empty, in particular for network issues.
            if (!filesize($path)) {
                throw new UpgradeToOmekaS_Exception(
                    __('An empty file "%s" exists in the temp directory.', $filename)
                    . ' ' . __('You should remove it manually or replace it by the true file (%s).', $url));
            }
            if (filesize($path) != $this->module['size']
                    || md5_file($path) != $this->module['md5']
                ) {
                throw new UpgradeToOmekaS_Exception(
                    __('A file "%s" exists in the temp directory and this is not the release %s.',
                        $filename, $this->module['version']));
            }
            $this->_log('[' . __FUNCTION__ . ']: ' . __('The file is already downloaded.'),
                Zend_Log::INFO);
        }
        // Download the file.
        else {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('The size of the file to download is %dKB, so wait a while.', ceil($this->module['size'] / 1000)),
                Zend_Log::INFO);
            $handle = fopen($url, 'rb');
            $result = file_put_contents($path, $handle);
            @fclose($handle);
            if (empty($result)) {
                throw new UpgradeToOmekaS_Exception(
                    __('An issue occurred during the file download.')
                    . ' ' . __('Try to download it manually (%s) and to save it as "%s" in the temp folder of Apache.', $url, $path));
            }
            if (filesize($path) != $this->module['size']
                    || md5_file($path) != $this->module['md5']
                ) {
                throw new UpgradeToOmekaS_Exception(
                    __('The downloaded file is corrupted.')
                    . ' ' . __('Try to download it manually (%s) and to save it as "%s" in the temp folder of Apache.', $url, $path));
            }
        }
    }

    /**
     * Unzip a module in its directory.
     */
    protected function _unzipModule()
    {
        $url = sprintf($this->module['url'], $this->module['version']);
        $filename = basename($url);
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;

        $dir = $this->_getModuleDir();

        // No check is done here: if the module is already installed, the
        // previous check skipped it.

        $result = UpgradeToOmekaS_Common::extractZip($path, $dir);
        if (!$result) {
            throw new UpgradeToOmekaS_Exception(
                __('Unable to extract the zip file "%s" into the destination "%s".', $path, $dir));
        }
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
     */
    protected function _prepareModule()
    {
        if (empty($this->module['install']['sql'])) {
            return;
        }

        $targetDb = $this->getTarget()->getDb();

        $result = $targetDb->prepare($this->module['install']['sql'])->execute();
        if (!$result) {
            throw new UpgradeToOmekaS_Exception(
                __('Unable to create the tables for the module "%s".', $this->_getModuleName()));
        }
    }

    /**
     * Register the module.
     */
    protected function _registerModule()
    {
        // Normally useless, except if this function is called directly..
        $module = $this->_getModuleName();
        $result = $this->_checkInstalledModule($module);
        if ($result) {
            return;
        }

        $targetDb = $this->getTargetDb();

        $toInsert = array();
        $toInsert['id'] = $module;
        $toInsert['is_active'] = 0;
        $toInsert['version'] = $this->module['version'];

        $result = $targetDb->insert('module', $toInsert);
        if (!$result) {
            throw new UpgradeToOmekaS_Exception(
                __('Unable to register the module.'));
        }
    }

    /**
     * Activate the module.
     */
    protected function _activateModule()
    {
        $module = $this->_getModuleName();
        $targetDb = $this->getTargetDb();
        $bind = array();
        $bind['is_active'] = 1;
        $result = $targetDb->update(
            'module',
            $bind,
            'id = ' . $targetDb->quote($module));
        if (empty($result)) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('The module "%s" can’t be activated.',
                $this->_getModuleName()), Zend_Log::WARN);
        }
    }

    /**
     * Upgrade settings of a module once installed.
     */
    protected function _upgradeSettings()
    {
    }

    /**
     * Upgrade metadata and files of a module once installed.
     */
    protected function _upgradeData()
    {
    }

    /**
     * Helper to convert a navigation link from the Omeka 2 to Omeka S.
     *
     * @param array $page The page to convert.
     * @param array $args The url and parsed elements.
     * @param array $site Some data for the url of the site.
     * @return array|null The Omeka S formatted nav link, or null.
     */
    public function convertNavigationPageToLink($page, $args, $site)
    {
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
}
