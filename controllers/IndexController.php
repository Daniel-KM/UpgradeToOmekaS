<?php

/**
 * Controller for UpgradeToOmekaS admin pages.
 *
 * @package UpgradeToOmekaS
 */
class UpgradeToOmekaS_IndexController extends Omeka_Controller_AbstractActionController
{
    /**
     * The list of processors used to upgrade Omeka Classic to Omeka Semantic.
     *
     * @var array
     */
    protected $_processors;

    /**
     * The list of precheck messages. Empty if no error.
     *
     * @var array
     */
    protected $_prechecks = array();

    /**
     * The list of check messages. Empty if no error.
     *
     * @var array
     */
    protected $_checks = array();

    /**
     * The list of existing plugins.
     *
     * @var array
     */
    protected $_plugins;

    /**
     * The list of parameters.
     *
     * @var array
     */
    protected $_params;

    public function init()
    {
        // A special check to allow only the super user to process actions.
        $user = current_user();
        if (empty($user) || $user->role != 'super') {
            throw new Omeka_Controller_Exception_403;
        }
    }

    public function indexAction()
    {
        if ($this->_isProcessing()) {
            $this->_helper->redirector->goto('logs');
        }
        else {
            $this->_prepare();
        }
    }

    protected function _prepare()
    {
        // Here, no process is running.

        $isProcessing = $this->_isProcessing();
        $isCompleted = $this->_isCompleted();
        $isError = $this->_isError();
        $isStopped = $this->_isStopped();
        $isSiteDown = $this->_isSiteDown();
        $deadRunningJobs = $this->_getDeadRunningJobs();
        $livingRunningJobs = $this->_getLivingRunningJobs();
        $isLogEnabled = $this->_isLogEnabled();
        $isConfirmation = false;

        $this->view->isProcessing = $isProcessing;
        $this->view->isCompleted = $isCompleted;
        $this->view->isError = $isError;
        $this->view->isStopped = $isStopped;
        $this->view->isSiteDown = $isSiteDown;
        $this->view->deadRunningJobs = $deadRunningJobs;
        $this->view->livingRunningJobs = $livingRunningJobs;
        $this->view->isLogEnabled = $isLogEnabled;
        $this->view->isConfirmation = $isConfirmation;
        $this->view->hasErrors = 'none';

        $processors = $this->_listProcessors();
        $prechecks = $this->_precheckConfig();
        $plugins = $this->_listPlugins();

        $this->view->processors = $processors;
        $this->view->prechecks = $prechecks;
        $this->view->plugins = $plugins;
        $this->view->checks = array();
        $this->view->form = null;

        if (!empty($prechecks)) {
            $message = __('Some requirements are not met.');
            $this->_helper->_flashMessenger($message, 'error');
            $this->view->hasErrors = 'precheck';
            return;
        }

        $form = new UpgradeToOmekaS_Form_Main();
        $form->setAction($this->_helper->url('index'));
        $this->view->form = $form;

        if (!$this->getRequest()->isPost()) {
            // Simple display of the form.
            return;
        }

        $csrf = new Omeka_Form_SessionCsrf;
        if (!$csrf->isValid($_POST)) {
            $message = __('There was an error on the form. Please try again.');
            $this->_helper->_flashMessenger($message, 'error');
            $this->view->hasErrors = 'form';
            return;
        }

        $post = $this->getRequest()->getPost();

        if (!$form->isValid($post)) {
            $message = __('Invalid form input.')
                . ' ' . __('Please see errors below and try again.');
            $this->_helper->flashMessenger($message, 'error');
            // Don't return to do checks immediately
            $this->view->hasErrors = 'form';
            // return;
        }

        // Save params here.
        $this->_cleanListParams();

        // Launch the check of the config with params.
        $checks = $this->_checkConfig();
        $this->view->checks = $checks;
        if (!empty($checks)) {
            $this->view->hasErrors = 'checks';
            $message = __('Some requirements or some parameters are not good.');
            $this->_helper->_flashMessenger($message, 'error');
            return;
        }

        if ($this->view->hasErrors != 'none') {
            return;
        }

        // Display the confirmation check boxes.
        $form->setConfirmation(true);
        $this->view->isConfirmation = true;
        $confirm = $this->getParam('check_database_confirm')
            && $this->getParam('check_backup_confirm');
        if (!$confirm) {
            $message = __('Parameters are fine.') . ' ' . __('Confirm the upgrade below.');
            $this->_helper->_flashMessenger($message, 'success');
            return;
        }

        $form->reset();

        $this->_launchProcess();
        $this->_helper->redirector->goto('logs');
    }

    /**
     * Launch the upgrade process.
     *
     * @return void
     */
    protected function _launchProcess()
    {
        set_option('upgrade_to_omeka_s_process_status', Process::STATUS_STARTING);

        // Update the document root, that will be required during the background
        // process.
        set_option('upgrade_to_omeka_s_document_root', $this->_getDocumentRoot());

        // Set Omeka in service mode and launch the background process.
        set_option('upgrade_to_omeka_s_service_down', true);

        // Reset the logs here to hide them in the log view.
        set_option('upgrade_to_omeka_s_process_logs', '[]');

        $params = $this->_getParams();
        $params['isProcessing'] = true;

        $url = $this->_determineUrl($params['base_dir']);
        set_option('upgrade_to_omeka_s_process_url', $url);

        // TODO Set a single id in options and in an option to find the process?

        // Launch the job.
        $jobDispatcher = Zend_Registry::get('bootstrap')->getResource('jobs');
        $options = array(
            'params' => $params,
        );
        $jobDispatcher->setQueueName(UpgradeToOmekaS_Job_Process::QUEUE_NAME);
        $jobDispatcher->sendLongRunning('UpgradeToOmekaS_Job_Process', $options);
        $message = __('The upgrade process is launched.')
            . ' ' . __('This may take a while.');
        $this->_helper->flashMessenger($message, 'success');
        $message = __('Your site will be available at %s.', $url);
        $this->_helper->flashMessenger($message, 'success');
        $message = __('Please reload this page for progress status.');
        $this->_helper->flashMessenger($message, 'success');

        // TODO Clean the password from the table of process when ended.
    }

    public function logsAction()
    {
        $isProcessing = $this->_isProcessing();
        $isCompleted = $this->_isCompleted();
        $isError = $this->_isError();
        $isStopped = $this->_isStopped();

        $isLogEnabled = $this->_isLogEnabled();
        $isSiteDown = $this->_isSiteDown();

        $this->view->isProcessing = $isProcessing;
        $this->view->isCompleted = $isCompleted;
        $this->view->isError = $isError;
        $this->view->isStopped = $isStopped;
        $this->view->isLogEnabled = $isLogEnabled;
        $this->view->isSiteDown = $isSiteDown;

        $logs = json_decode(get_option('upgrade_to_omeka_s_process_logs'), true);

        $this->view->logs = $logs;
    }

    public function stopAction()
    {
        // No automatic wake up.

        if ($this->_isProcessing()) {
            set_option('upgrade_to_omeka_s_process_status', Process::STATUS_STOPPED);
            $message = __('The process is stopped.');
            $this->_helper->flashMessenger($message, 'success');
        }
        // No process.
        else {
            $message = __('No upgrade process to stop.');
            $this->_helper->flashMessenger($message, 'info');
        }

        $this->_helper->redirector->goto('index');
    }

    public function resetAction()
    {
        if ($this->_isProcessing()) {
            $message = __('The process should be stopped before reset of its status.');
            $this->_helper->flashMessenger($message, 'error');
        }
        // No process.
        else {
            $status = get_option('upgrade_to_omeka_s_process_status');
            set_option('upgrade_to_omeka_s_process_status', null);
            $message = __('The status of the process has been reset (was "%s").', $status);
            $this->_helper->flashMessenger($message, 'success');
        }

        $this->_helper->redirector->goto('index');
    }

    public function wakeUpAction()
    {
        // Check if a wake up is possible.
        if ($this->_isProcessing()) {
            $message = __('The site can’t be woken up when an upgrade process is running.');
            $this->_helper->flashMessenger($message, 'error');
        }
        // Wake up the site.
        else {
            $message = (boolean) get_option('upgrade_to_omeka_s_service_down')
                ? __('The site is opened again.')
                : __('The site is already open.');
            set_option('upgrade_to_omeka_s_service_down', false);
            $this->_helper->flashMessenger($message, 'success');
        }
        $this->_helper->redirector->goto('index');
    }

    public function shutdownAction()
    {
        $message = (boolean) get_option('upgrade_to_omeka_s_service_down')
            ? __('The site is already down.')
            : __('The site has been set down.');
        set_option('upgrade_to_omeka_s_service_down', true);
        $this->_helper->flashMessenger($message, 'success');
        $this->_helper->redirector->goto('index');
    }

    /**
     * Set status error to processes without pid or without a true pid.
     *
     * @internal Only ten process are cleaned.
     * @todo Move all of this in a special plugin or in the core, with display of logs (but this is done in Omeka S).
     *
     * @return void
     */
    public function cleanJobsAction()
    {
        $processes = $this->_getDeadRunningJobs();
        foreach ($processes as $process) {
            $arguments = $process->getArguments();
            $job = json_decode($arguments['job'], true);
            $classname = isset($job['className']) ? $job['className'] : '';
            $user = get_record_by_id('User', $process->user_id);
            $username = $user ? $user->username : __('deleted user');
            $message = empty($process->pid)
                ? __('The status of process #%d (%s, started at %s by user #%d [%s]), without pid, has been set to error.',
                    $process->id, $classname, $process->started, $process->user_id, $username)
                : __('The status of process #%d (%s, started at %s by user #%d [%s]), with a non existing pid, has been set to error.',
                    $process->id, $classname, $process->started, $process->user_id, $username);
            $process->status = Process::STATUS_ERROR;
            $process->save();
            _log('[UpgradeToOmekaS]: ' . $message, Zend_Log::WARN);
            $this->_helper->_flashMessenger($message, 'success');
        }

        $this->_helper->redirector->goto('index');
    }

    /**
     * Get the list of living running jobs.
     *
     * @param integer $limit
     * @return array The list of process objects.
     */
    protected function _getLivingRunningJobs($limit = 10)
    {
        $processes = array();
        $table = $this->_helper->db->getTable('Process');
        $alias = $table->getTableAlias();
        $select = $table->getSelectForFindBy(array(
            'status' => array(Process::STATUS_STARTING, Process::STATUS_IN_PROGRESS),
            'sort_field' => 'id',
            'sort_dir' => 'd',
        ));
        $select->where($alias . '.pid IS NOT NULL');
        $result = $table->fetchObjects($select);

        // Unselect processes with a non existing pid.
        foreach ($result as $process) {
            // TODO The check of the pid works only with Linux.
            if (file_exists('/proc/' . $process->pid)) {
                $processes[] = $process;
                if (count($processes) >= $limit) {
                    break;
                }
            }
        }

        return $processes;
    }

    /**
     * Get the list of dead running jobs.
     *
     * @param integer $limit
     * @return array The list of process objects.
     */
    protected function _getDeadRunningJobs($limit = 10)
    {
        $processes = array();
        $result = get_records('Process', array(
            'status' => array(Process::STATUS_STARTING, Process::STATUS_IN_PROGRESS),
            'sort_field' => 'id',
        ), 0);

        // Unselect processes with an existing pid.
        foreach ($result as $process) {
            // TODO The check of the pid works only with Linux.
            if (!$process->pid || !file_exists('/proc/' . $process->pid)) {
                $processes[] = $process;
                if (count($processes) >= $limit) {
                    break;
                }
            }
        }

        return $processes;
    }

    /**
     * Check if the process is running.
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
     * Check if the process is completed.
     *
     * @todo Uses the status of the process object.
     *
     * @return boolean
     */
    protected function _isCompleted()
    {
        return get_option('upgrade_to_omeka_s_process_status') == Process::STATUS_COMPLETED;
    }

    /**
     * Check if the process is stopped.
     *
     * @todo Uses the status of the process object.
     *
     * @return boolean
     */
    protected function _isStopped()
    {
        return get_option('upgrade_to_omeka_s_process_status') == Process::STATUS_STOPPED;
    }

    /**
     * Check if the process is completed.
     *
     * @todo Uses the status of the process object.
     *
     * @return boolean
     */
    protected function _isError()
    {
        return get_option('upgrade_to_omeka_s_process_status') == Process::STATUS_ERROR;
    }

    protected function _isSiteDown()
    {
        return (boolean) get_option('upgrade_to_omeka_s_service_down');
    }

    protected function _hasLivingRunningJobs()
    {
        return (boolean) $this->_getLivingRunningJobs(1);
    }

    protected function _hasDeadRunningJobs()
    {
        return (boolean) $this->_getDeadRunningJobs(1);
    }

    /**
     * Check if the logs are enabled with a minimum level of INFO.
     *
     * @return boolean
     */
    protected function _isLogEnabled()
    {
        $config = Zend_Registry::get('bootstrap')->config;
        if (!$config) {
            return false;
        }
        if (empty($config->log->errors)) {
            return false;
        }
        if (empty($config->log->priority)) {
            return false;
        }
        return in_array($config->log->priority, array(
            'Zend_Log::INFO',
            'Zend_Log::DEBUG',
        ));
    }

    /**
     * List and precheck processors.
     *
     * @todo Move this in another place to merge it with UpgradeToOmekaS_Job_Process?
     *
     * @return array
     */
    protected function _listProcessors()
    {
        if (is_null($this->_processors)) {
            $processors = apply_filters('upgrade_omekas', array());

            // Get installed plugins, includes active and inactive.
            $pluginLoader = Zend_Registry::get('pluginloader');
            $installedPlugins = $pluginLoader->getPlugins();

            // Keep only the name of plugins.
            $installedPlugins = array_map(function ($v) {
                return $v->name;
            }, $installedPlugins);
            $installedPlugins[] = 'Core';

            // Check processors to prevents possible issues with external plugins.
            foreach ($processors as $name => $processor) {
                $class = $processor['class'];
                // Check if class exists.
                if (!class_exists($class)) {
                    $this->_prechecks[$name][] = __('Processor class "%s" is missing.', $class);
                }
                // Check if class extends UpgradeToOmekaS_Process_Abstract.
                elseif (!is_subclass_of($class, 'UpgradeToOmekaS_Processor_Abstract')) {
                    $this->_prechecks[$name][] = __('Processor class "%s" should extend UpgradeToOmekaS_Processor_Abstract.', $class);
                }
                // Apparently a good processor, but check for the plugin.
                // No error is given about non installed plugins.
                elseif (in_array($name, $installedPlugins)) {
                    $this->_processors[$name] = new $class();
                }
            }
        }

        return $this->_processors;
    }

    /**
     * Quick check if there is a processor to process the upgrade of a plugin.
     *
     * @return array
     */
    protected function _listPlugins()
    {
        if (is_null($this->_plugins)) {
            $this->_listProcessors();
            $this->_precheckConfig();

            // Prepare the list of plugins.
            // See PluginsController::browseAction().
            $pluginLoader = Zend_Registry::get('pluginloader');

            // Get installed plugins, includes active and inactive.
            $installedPlugins = $pluginLoader->getPlugins();

            // Get plugins that are not installed and load them.
            $factory = new Omeka_Plugin_Factory(PLUGIN_DIR);
            $uninstalledPlugins = $factory->getNewPlugins($installedPlugins);
            $pluginLoader->loadPlugins($uninstalledPlugins);

            // Get the combination of installed and not-installed plugins.
            $allPlugins = $pluginLoader->getPlugins();

            foreach ($allPlugins as $plugin) {
                $name = $plugin->name;
                $skip = !isset($this->_processors[$name]);
                $upgradable = !$skip && !isset($this->_prechecks[$name]) && !isset($this->_checks[$name]);
                $this->_plugins[$plugin->name]['name'] = $name;
                $this->_plugins[$plugin->name]['installed'] = $plugin->isInstalled();
                $this->_plugins[$plugin->name]['active'] = $plugin->isActive();
                $this->_plugins[$plugin->name]['version'] = $plugin->getIniVersion();
                $this->_plugins[$plugin->name]['skip'] = $skip;
                $this->_plugins[$plugin->name]['upgradable'] = $upgradable;
            }
            ksort($this->_plugins);
        }

        return $this->_plugins;
    }

    /**
     * Precheck the installation, the server and the versions of the plugins.
     *
     * @return array
     */
    protected function _precheckConfig()
    {
        static $isChecked = false;

        if (!$isChecked) {
            $this->_listProcessors();

            foreach ($this->_processors as $name => $processor) {
                try {
                    $result = $processor->precheckConfig();
                } catch (UpgradeToOmekaS_Exception $e) {
                    $result = array($e->getMessage());
                } catch (Exception $e) {
                    $message = __('A recoverable error occured during precheck of "%s".',
                        $processor->pluginName);
                    $result = array($message, $e->getMessage());
                }
                if ($result) {
                    // Some prechecks may have been added by processors.
                    $this->_prechecks[$name] = isset($this->_prechecks[$name])
                        ? array_merge($this->_prechecks[$name], $result)
                        : $result;
                }
            }
            $isChecked = true;
        }

        return $this->_prechecks;
    }

    /**
     * Quick check of the configuration with params, mainly for the database and
     * the file system.
     *
     * @return void
     */
    protected function _checkConfig()
    {
        $params = $this->_getParams();
        foreach ($this->_processors as $name => $processor) {
            $processor->setParams($params);
            try {
                $result = $processor->checkConfig();
            } catch (UpgradeToOmekaS_Exception $e) {
                $result = array($e->getMessage());
            } catch (Exception $e) {
                $message = __('A recoverable error occured during precheck of "%s".',
                    $processor->pluginName);
                $result = array($message, $e->getMessage());
            }
            if ($result) {
                // Normally, no previous checks.
                $this->_checks[$name] = isset($this->_checks[$name])
                    ? array_merge($this->_checks[$name], $result)
                    : $result;
            }
        }
        return $this->_checks;
    }

    /**
     * Clean and save parameters here.
     *
     * @return array
     */
    private function _cleanListParams()
    {
        $params = $this->getAllParams();
        $unsetParams = array(
            'admin', 'module', 'controller', 'action',
            'check_backup_metadata', 'check_backup_files', 'check_backup_check',
            'check_database_confirm', 'check_backup_confirm',
            'database_type_note_separate',
            'csrf_token', 'submit', 'check_params',
        );
        $params = array_diff_key($params, array_flip($unsetParams));
        $this->_params = $params;
        return $this->_params;
    }

    /**
     * Get parameters.
     *
     * @return void
     */
    protected function _getParams()
    {
        if (is_null($this->_params)) {
            $this->_cleanListParams();
        }
        return $this->_params;
    }

    /**
     * Determine the url from the base dir.
     *
     * @internal Omeka Classic and Omeka Semantic are on the same web server.
     *
     * @see bootstrap.php
     *
     * @param string $baseDir
     * @return string
     */
    protected function _determineUrl($baseDir)
    {
        // Set the scheme.
        if ((isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] === true))
                || (isset($_SERVER['HTTP_SCHEME']) && $_SERVER['HTTP_SCHEME'] == 'https')
                || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
            ) {
            $scheme = 'https';
        } else {
            $scheme = 'http';
        }

        // Set the domain.
        if (!isset($_SERVER['HTTP_HOST'])) {
            $_SERVER['HTTP_HOST'] = null;
        }
        $host = $_SERVER['HTTP_HOST'];

        // Set to port, if any.
        if (!isset($_SERVER['SERVER_PORT'])) {
            $_SERVER['SERVER_PORT'] = null;
        }
        $port = $_SERVER['SERVER_PORT'];

        $base_url = $scheme . '://' . preg_replace('/[^a-z0-9-:._]/i', '', $host);
        // In bootstrap, the web root has no port.
        if (($scheme == 'http' && $port != '80') || ($scheme == 'https' && $port != '443')) {
            $base_url .= ":$port";
        }

        // Set the path.
        $documentRoot = $this->_getDocumentRoot();
        $dir = trim(substr($baseDir, strlen($documentRoot)), '\,/');

        // Set the web root.
        $webRoot = $base_url . (strlen($dir) ? '/' . $dir : '');
        return $webRoot;
    }

    /**
     * Helper to get the document root of the server.
     *
     * @todo Merge with UpgradeToOmekaSPlugin::_getDocumentRoot
     *
     * @return string
     */
    private function _getDocumentRoot()
    {
        // Get the backend settings from the security.ini file.
        $iniFile = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'security.ini';
        $settings = new Zend_Config_Ini($iniFile, 'upgrade-to-omeka-s');

        // Check if the document root is set in security.ini.
        $documentRoot = $settings->document_root;
        if ($documentRoot) {
            return $documentRoot;
        }

        // The document root may be hidden.
        if (!empty($_SERVER['DOCUMENT_ROOT'])) {
            return $_SERVER['DOCUMENT_ROOT'];
        }

        // Determine document root via the current script.
        if (!empty($_SERVER['SCRIPT_NAME'])) {
            $dir = trim(dirname($_SERVER['SCRIPT_NAME']), '\,/');
            // Remove the '/admin' part of the URL by regex, if necessary.
            if (defined('ADMIN')) {
                $dir = preg_replace('/(.*)admin$/', '$1', $dir, 1);
                $dir = rtrim($dir, '/');
            }

            $documentRoot = rtrim(substr(BASE_DIR, 0, strlen(BASE_DIR) - strlen($dir)), '/');
            return $documentRoot;
        }

        return get_option('upgrade_to_omeka_s_document_root');
    }

    /**
     * Determine if Omeka Semantic is installed in a subfolder of Omeka Classic.
     *
     * @internal Omeka Classic and Omeka Semantic are on the same web server.
     *
     * @param string $url
     * @return boolean
     */
    protected function _isInsideWebroot($url)
    {
        $baseDir = rtrim(WEB_ROOT, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $url = rtrim($url, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        return $baseDir != $url && strpos($url, $baseDir) === 0;
    }
}
