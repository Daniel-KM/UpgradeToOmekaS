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
     * The base processor for generic features.
     *
     * @var array
     */
    protected $_processorBase;

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
        // Here, no process is running (else the logs are displayed).

        $isProcessing = $this->_isProcessing();
        $isCompleted = $this->_isCompleted();
        $isError = $this->_isError();
        $isStopped = $this->_isStopped();
        $isSiteDown = $this->_isSiteDown();
        $deadRunningJobs = $this->_getDeadRunningJobs();
        $livingRunningJobs = $this->_getLivingRunningJobs();
        $isLogEnabled = $this->_isLogEnabled();
        $isReset = $this->_isReset();
        $hasPreviousUpgrade = $this->_hasPreviousUpgrade();
        $previousParams = json_decode(get_option('upgrade_to_omeka_s_process_params'), true);
        $isConfirmation = false;
        $processors = $this->_listProcessors();
        $prechecks = $this->_precheckConfig();
        $plugins = $this->_listPlugins();
        $allowThemesOnly = $this->_allowThemesOnly();
        $upgradeType = ($allowThemesOnly && $this->getParam('submit_themes')) ? 'themes' : 'full';

        $prechecksPlugins = array_filter($prechecks, function ($k) {
            return strpos($k, 'Core/') !== 0;
        }, ARRAY_FILTER_USE_KEY);
        $prechecksCore = array_diff_key($prechecks, $prechecksPlugins);

        $this->view->isProcessing = $isProcessing;
        $this->view->isCompleted = $isCompleted;
        $this->view->isError = $isError;
        $this->view->isStopped = $isStopped;
        $this->view->isSiteDown = $isSiteDown;
        $this->view->deadRunningJobs = $deadRunningJobs;
        $this->view->livingRunningJobs = $livingRunningJobs;
        $this->view->isLogEnabled = $isLogEnabled;
        $this->view->isReset = $isReset;
        $this->view->hasPreviousUpgrade = $hasPreviousUpgrade;
        $this->view->previousParams = $previousParams;
        $this->view->isConfirmation = $isConfirmation;
        $this->view->processors = $processors;
        $this->view->prechecksCore = $prechecksCore;
        $this->view->prechecksPlugins = $prechecksPlugins;
        $this->view->plugins = $plugins;
        $this->view->checksCore = array();
        $this->view->checksPlugins = array();
        $this->view->form = null;
        $this->view->hasErrors = empty($prechecks) ? 'none' : 'precheck';
        $this->view->allowThemesOnly = $allowThemesOnly;

        if ($prechecksCore) {
            $message = __('Some requirements are not met.');
            $this->_helper->_flashMessenger($message, 'error');
            return;
        }

        $form = new UpgradeToOmekaS_Form_Main(array(
            'unupgradablePlugins' => count($prechecksPlugins),
            'allowThemesOnly' => (integer) $allowThemesOnly,
        ));
        $form->setAction($this->_helper->url('index'));
        $this->view->form = $form;

        // Simple display of the form if no post.
        if (!$this->getRequest()->isPost()) {
            // Set default parameters if set.
            $settings = $this->_getSecurityIni();
            $defaults = $settings->default;
            if ($defaults) {
                $form->setDefaults($defaults->toArray());
            }
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

        // Launch the check of the config with params for themes.
        if ($upgradeType == 'themes') {
            $baseDir = $this->getParam('base_dir');
            if (empty($baseDir)
                    || !file_exists($baseDir)
                    || !is_dir($baseDir)
                    || !is_writable($baseDir)
                ) {
                $message = __('To upgrade only themes, the base dir should be set and should contain a folder for themes.');
                $this->view->checksCore = array('Core/Server' => array($message));
                $this->view->checksPlugins = array();
                $this->view->hasErrors = 'checks';
                $message = __('The base dir should be set and should contain a folder for themes.');
                $this->_helper->_flashMessenger($message, 'error');
                return;
            }
        }
        // Launch the check of the config with params for a full upgrade.
        else {
            $checks = $this->_checkConfig();
            $checksPlugins = array_filter($checks, function ($k) {
                return strpos($k, 'Core/') !== 0;
            }, ARRAY_FILTER_USE_KEY);
            $checksCore = array_diff_key($checks, $checksPlugins);
            $this->view->checksCore = $checksCore;
            $this->view->checksPlugins = $checksPlugins;
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
            $form->setIsConfirmation(true);
            $this->view->isConfirmation = true;
            $confirm = $this->getParam('check_confirm_backup')
                && $this->getParam('check_confirm_license');
            if (!$confirm) {
                $message = __('Parameters are fine.') . ' ' . __('Confirm the upgrade below.');
                $this->_helper->_flashMessenger($message, 'success');
                return;
            }
        }

        $form->reset();

        $this->_launchUpgradeProcess($upgradeType);

        $this->_helper->redirector->goto('logs');
    }

    /**
     * Launch the upgrade process.
     *
     * @param string $upgradeType Can be "full" or "themes".
     * @return void
     */
    protected function _launchUpgradeProcess($upgradeType = 'full')
    {
        set_option('upgrade_to_omeka_s_process_status', Process::STATUS_STARTING);

        // Reset the progression.
        set_option('upgrade_to_omeka_s_process_progress', json_encode(array()));

        // Update the document root, that will be required during the background
        // process.
        set_option('upgrade_to_omeka_s_document_root', $this->_getDocumentRoot());

        // Set Omeka in service mode and launch the background process.
        set_option('upgrade_to_omeka_s_service_down', true);

        // Reset the logs here to hide them in the log view.
        if ($upgradeType == 'full') {
            set_option('upgrade_to_omeka_s_process_logs', json_encode(array()));
        }

        $params = $this->_cleanParams();

        if (!empty($params['first_user_password'])) {
            $firstUserPassword = password_hash($params['first_user_password'], PASSWORD_DEFAULT);
            $params['first_user_password'] = $firstUserPassword;
        }

        // Keep old params when processing themes only.
        if ($upgradeType == 'themes') {
            $baseDir = $params['base_dir'];
            $oldParams = json_decode(get_option('upgrade_to_omeka_s_process_params'), true);
            $params = $oldParams;
            $params['base_dir'] = $baseDir;
        }

        $params['upgrade_type'] = $upgradeType;
        $params['isProcessing'] = true;
        $params['start'] = date('Y-m-d H:i:s');

        // Keep various values, that may be lost in a background job.
        $url = $this->_determineUrl($params['base_dir']);
        $params['url'] = $url;
        $params['WEB_ROOT'] = WEB_ROOT;
        set_theme_base_url('public');
        $assetPaths = get_view()->getAssetPaths();
        revert_theme_base_url();
        $params['assetPaths'] = $assetPaths;
        $nav = new Omeka_Navigation;
        $nav->loadAsOption(Omeka_Navigation::PUBLIC_NAVIGATION_MAIN_OPTION_NAME);
        $nav->addPagesFromFilter(Omeka_Navigation::PUBLIC_NAVIGATION_MAIN_FILTER_NAME);
        $params['navigation'] = $nav->toArray();

        set_option('upgrade_to_omeka_s_process_params', version_compare(phpversion(), '5.4.0', '<')
            ? json_encode($params)
            : json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        // TODO Set a single id in options and in an option to find the process?

        // Launch the job.
        $jobDispatcher = Zend_Registry::get('bootstrap')->getResource('jobs');
        $options = array(
            'params' => $params,
            // The current user will be the global administrator.
            'user' => current_user(),
        );
        $jobDispatcher->setQueueName(UpgradeToOmekaS_Job_Upgrade::QUEUE_NAME);
        $jobDispatcher->sendLongRunning('UpgradeToOmekaS_Job_Upgrade', $options);
        $message = __('The upgrade process is launched.')
            . ' ' . __('This may take a while.');
        $this->_helper->flashMessenger($message, 'success');
        $message = __('Your site will be available at %s.', $url);
        $this->_helper->flashMessenger($message, 'info');
        $message = __('Please reload this page for progress status.');
        $this->_helper->flashMessenger($message, 'info');
    }

    public function logsAction()
    {
        $hasLivingJob = $this->_hasLivingRunningJobs();
        $isProcessing = $this->_isProcessing();

        if (!$hasLivingJob && $isProcessing) {
            $this->_cleanFatalError();
            $isProcessing = false;
            $message = __('A fatal error occurred during the last process.');
            $this->_helper->flashMessenger($message, 'error');
        }

        $isCompleted = $this->_isCompleted();
        $isError = $this->_isError();
        $isStopped = $this->_isStopped();
        $isReset = $this->_isReset();
        $progress = $this->_checkProgress();
        $hasPreviousUpgrade = $this->_hasPreviousUpgrade();
        $previousParams = json_decode(get_option('upgrade_to_omeka_s_process_params'), true);
        $deadRunningJobs = $this->_getDeadRunningJobs();
        $livingRunningJobs = $this->_getLivingRunningJobs();

        $isLogEnabled = $this->_isLogEnabled();
        $isSiteDown = $this->_isSiteDown();

        $this->view->isProcessing = $isProcessing;
        $this->view->isCompleted = $isCompleted;
        $this->view->isError = $isError;
        $this->view->isStopped = $isStopped;
        $this->view->isReset = $isReset;
        $this->view->progress = $progress;
        $this->view->hasPreviousUpgrade = $hasPreviousUpgrade;
        $this->view->previousParams = $previousParams;
        $this->view->isLogEnabled = $isLogEnabled;
        $this->view->isSiteDown = $isSiteDown;
        $this->view->deadRunningJobs = $deadRunningJobs;
        $this->view->livingRunningJobs = $livingRunningJobs;

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
            $message = __('No process to stop.');
            $this->_helper->flashMessenger($message, 'error');
        }
        set_option('upgrade_to_omeka_s_process_progress', json_encode(array()));

        $this->_helper->redirector->goto('logs');
    }

    public function resetAction()
    {
        if ($this->_isProcessing()) {
            $message = __('The process should be stopped before reset of its status.');
            $this->_helper->flashMessenger($message, 'error');
        }
        // No process.
        else {
            $status = $this->_getProcessorBase()->getStatus();
            set_option('upgrade_to_omeka_s_process_status', UpgradeToOmekaS_Processor_Abstract::STATUS_RESET);
            set_option('upgrade_to_omeka_s_process_progress', json_encode(array()));
            $message = __('The status of the process has been reset (was "%s").', $status);
            $this->_helper->flashMessenger($message, 'success');
        }

        $this->_helper->redirector->goto('logs');
    }

    public function clearAction()
    {
        if ($this->_isProcessing()) {
            $message = __('The process should be stopped before clear of its parameters.');
            $this->_helper->flashMessenger($message, 'error');
        }
        // No process.
        else {
            set_option('upgrade_to_omeka_s_process_params', json_encode(array()));
            set_option('upgrade_to_omeka_s_process_progress', json_encode(array()));
            set_option('upgrade_to_omeka_s_process_status', null);
            $message = __('Parameters of the previous upgrade have been cleared.');
            $this->_helper->flashMessenger($message, 'success');
        }

        $this->_helper->redirector->goto('logs');
    }

    public function wakeUpAction()
    {
        // Check if a wake up is possible.
        if ($this->_isProcessing()) {
            $message = __('The site can’t be woken up when a process is running.');
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
            $this->_setErrorJob($process);
        }

        $this->_helper->redirector->goto('index');
    }

    /**
     * Stop current process after an fatal error.
     *
     * @return void
     */
    protected function _cleanFatalError()
    {
        set_option('upgrade_to_omeka_s_process_status', Process::STATUS_ERROR);

        $processes = $this->_getDeadRunningJobs(0, true);

        foreach ($processes as $process) {
            $this->_setErrorJob($process);
        }

        $this->_helper->redirector->goto('index');
    }

    /**
     * Helper to set error to processes.
     *
     * @param Process $process
     * @return void
     */
    protected function _setErrorJob($process)
    {
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
        $this->_helper->_flashMessenger($message, 'info');
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
     * @param boolean $onlyUpgrader
     * @return array The list of process objects.
     */
    protected function _getDeadRunningJobs($limit = 10, $onlyUpgrader = false)
    {
        $processes = array();
        $params = array(
            'status' => array(Process::STATUS_STARTING, Process::STATUS_IN_PROGRESS),
            'sort_field' => 'id',
        );
        $result = get_records('Process', $params, 0);

        // Unselect processes with an existing pid.
        foreach ($result as $process) {
            if ($onlyUpgrader) {
                $arguments = $process->getArguments();
                $job = json_decode($arguments['job'], true);
                $classname = isset($job['className']) ? $job['className'] : '';
                if (!in_array($classname, array(
                        'UpgradeToOmekaS_Job_Upgrade',
                        'UpgradeToOmekaS_Job_Remove',
                    ))) {
                    continue;
                }
            }
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
     * Remove all created tables and copied files of the previous process.
     */
    public function removeAction()
    {
        if ($this->_isProcessing()) {
            $message = __('The process should be stopped before remove.');
            $this->_helper->flashMessenger($message, 'error');
        }
        // No process.
        elseif (!$this->_isReset()) {
            $message = __('The process should be reset before remove.');
            $this->_helper->flashMessenger($message, 'error');
        }
        // Check if there was a previous upgrade.
        elseif (!$this->_hasPreviousUpgrade()) {
            $message = __('No previous upgrade to remove.');
            $this->_helper->flashMessenger($message, 'info');
        }
        // No process, and the status is reset.
        else {
            $this->_launchRemoveProcess();
            $this->_helper->redirector->goto('logs');
        }

        $this->_helper->redirector->goto('index');
    }

    /**
     * Launch the remove process.
     *
     * @return void
     */
    protected function _launchRemoveProcess()
    {
        set_option('upgrade_to_omeka_s_process_status', Process::STATUS_STARTING);
        set_option('upgrade_to_omeka_s_process_progress', json_encode(array()));

        $params = json_decode(get_option('upgrade_to_omeka_s_process_params'), true);
        $params['isProcessing'] = true;
        $params['isRemoving'] = true;
        set_option('upgrade_to_omeka_s_process_params', version_compare(phpversion(), '5.4.0', '<')
            ? json_encode($params)
            : json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        // Launch the job.
        $jobDispatcher = Zend_Registry::get('bootstrap')->getResource('jobs');
        $options = array(
            'params' => $params,
            'user' => current_user(),
        );
        $jobDispatcher->setQueueName(UpgradeToOmekaS_Job_Remove::QUEUE_NAME);
        $jobDispatcher->sendLongRunning('UpgradeToOmekaS_Job_Remove', $options);
        $message = __('The remove process is launched.');
        $this->_helper->flashMessenger($message, 'success');
        $message = __('Your site %s will be removed in a while.', $params['url']);
        $this->_helper->flashMessenger($message, 'info');
        $message = __('Only logs will be kept.');
        $this->_helper->flashMessenger($message, 'info');
    }

    /**
     * Check if the process is running.
     *
     * @return boolean
     */
    protected function _isProcessing()
    {
        return $this->_getProcessorBase()->isProcessing();
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
        $status = $this->_getProcessorBase()->getStatus();
        return $status == Process::STATUS_COMPLETED;
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
        $status = $this->_getProcessorBase()->getStatus();
        return $status == Process::STATUS_STOPPED;
    }

    /**
     * Check if the process is error.
     *
     * @todo Uses the status of the process object.
     *
     * @return boolean
     */
    protected function _isError()
    {
        $status = $this->_getProcessorBase()->getStatus();
        return $status == Process::STATUS_ERROR;
    }

    /**
     * Check if the process is reset.
     *
     * @todo Uses the status of the process object.
     *
     * @return boolean
     */
    protected function _isReset()
    {
        $status = $this->_getProcessorBase()->getStatus();
        return $status == UpgradeToOmekaS_Processor_Abstract::STATUS_RESET;
    }

    /**
     * Get the current value and the total of the progression of the process.
     *
     * @return array|null The current and the total values of the current task.
     */
    protected function _checkProgress()
    {
        return $this->_getProcessorBase()->checkProgress();
    }

    /**
     * Check if there was a previous process not removed.
     *
     * @return boolean
     */
    protected function _hasPreviousUpgrade()
    {
        $previousParams = json_decode(get_option('upgrade_to_omeka_s_process_params'), true);
        return !empty($previousParams);
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
     * Check if the the button "Themes only" is allowed.
     *
     * @return boolean
     */
    protected function _allowThemesOnly()
    {
        $iniFile = dirname(dirname(__FILE__))
            . DIRECTORY_SEPARATOR . 'security.ini';
        $settings = new Zend_Config_Ini($iniFile, 'upgrade-to-omeka-s');
        return isset($settings->button_themes_only)
            ? (boolean) $settings->button_themes_only
            : false;
    }

    /**
     * Helper to get the backend settings from the file "security.ini".
     *
     * @return Zend_Config_Ini
     */
    protected function _getSecurityIni()
    {
        $iniFile = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'security.ini';
        $settings = new Zend_Config_Ini($iniFile, 'upgrade-to-omeka-s');
        return $settings;
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
     * List and precheck processors, active or not.
     *
     * @return array
     */
    protected function _listProcessors()
    {
        if (is_null($this->_processors)) {
            $processors = apply_filters('upgrade_omekas', array());

            //Add the security ini params.
            $settings = $this->_getSecurityIni()->toArray();
            $checks = $settings['check'];

            // Get installed plugins, includes active and inactive.
            $pluginLoader = Zend_Registry::get('pluginloader');
            $installedPlugins = $pluginLoader->getPlugins();

            // Keep only the name of plugins.
            $installedPlugins = array_map(function ($v) {
                return $v->name;
            }, $installedPlugins);
            // Add all core "plugins".
            $installedPlugins[] = 'Core/Server';
            $installedPlugins[] = 'Core/Site';
            $installedPlugins[] = 'Core/Elements';
            $installedPlugins[] = 'Core/Records';
            $installedPlugins[] = 'Core/Files';
            $installedPlugins[] = 'Core/Themes';
            $installedPlugins[] = 'Core/Checks';

            // Check processors to prevents possible issues with external plugins.
            foreach ($processors as $name => $class) {
                // Check if class exists.
                if (!class_exists($class)) {
                    $this->_prechecks[$name][] = __('Processor class "%s" is missing.', $class);
                }
                // Check if class extends UpgradeToOmekaS_Process_Abstract.
                elseif (!is_subclass_of($class, 'UpgradeToOmekaS_Processor_Abstract')) {
                    $this->_prechecks[$name][] = __('Processor class "%s" should extend UpgradeToOmekaS_Processor_Abstract.', $class);
                }
                // Only the processors with an active plugin will be processed,
                // but all are kept, even if not this version.
                else {
                    $this->_processors[$name] = new $class();
                    $this->_processors[$name]->setParam('checks', $checks);
                }
            }

            // Set "Core/Checks" the last processor.
            $processor = $this->_processors['Core/Checks'];
            unset($this->_processors['Core/Checks']);
            $this->_processors['Core/Checks'] = $processor;
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
                $hasProcessor = isset($this->_processors[$name]);
                $upgradable = $plugin->isInstalled()
                    && $plugin->isActive()
                    && $hasProcessor
                    && !($this->_processors[$name]->precheckProcessorPlugin())
                    && !isset($this->_prechecks[$name])
                    && !isset($this->_checks[$name]);
                $this->_plugins[$plugin->name]['name'] = $name;
                $this->_plugins[$plugin->name]['installed'] = $plugin->isInstalled();
                $this->_plugins[$plugin->name]['active'] = $plugin->isActive();
                $this->_plugins[$plugin->name]['version'] = $plugin->getIniVersion();
                $this->_plugins[$plugin->name]['processor'] = isset($this->_processors[$name])
                    ? $this->_processors[$name]
                    : null;
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
                if (!$processor->isPluginReady()) {
                    continue;
                }
                try {
                    $result = $processor->precheckConfig();
                } catch (UpgradeToOmekaS_Exception $e) {
                    $result = array($e->getMessage());
                } catch (Exception $e) {
                    $message = __('An error occurred during precheck of "%s".',
                        $processor->pluginName);
                    $result = array($message, $e->getMessage());
                }
                if ($result) {
                    // Some prechecks may have been added by processors.
                    $this->_prechecks[$name] = isset($this->_prechecks[$name])
                        ? array_unique(array_merge($this->_prechecks[$name], $result))
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
        static $isChecked = false;

        if (!$isChecked) {
            $params = $this->_cleanParams();
            foreach ($this->_processors as $name => $processor) {
                $processor->setParams($params);
                try {
                    $result = $processor->checkConfig();
                } catch (UpgradeToOmekaS_Exception $e) {
                    $result = array($e->getMessage());
                } catch (Exception $e) {
                    $message = __('An error occurred during precheck of "%s".',
                        $processor->pluginName);
                    $result = array($message, $e->getMessage());
                }
                if ($result) {
                    // Normally, no previous checks.
                    $this->_checks[$name] = isset($this->_checks[$name])
                        ? array_unique(array_merge($this->_checks[$name], $result))
                        : $result;
                }
            }
            $isChecked = true;
        }
        return $this->_checks;
    }

    /**
     * Clean and save parameters here.
     *
     * @return array
     */
    private function _cleanParams()
    {
        $params = $this->getAllParams();

        $unsetParams = array(
            'admin', 'module', 'controller', 'action',
            'check_backup_metadata', 'check_backup_files', 'check_backup_check',
            'plugins_confirm_unupgradable',
            'check_confirm_backup', 'check_confirm_license',
            'csrf_token', 'submit', 'submit_themes', 'check_params',
        );
        $params = array_diff_key($params, array_flip($unsetParams));

        // Create an array for some data.
        foreach (array(
                'database_' => 'database',
                'mapping_role_' => 'mapping_roles',
                'mapping_item_type_' => 'mapping_item_types',
                'mapping_element_' => 'mapping_elements',
            ) as $name => $set) {
            if (!isset($params[$set])) {
                $params[$set] = array();
            }
            foreach ($params as $key => $value) {
                if (strpos($key, $name) === 0) {
                    $subname = substr($key, strlen($name));
                    if (is_numeric($subname)) {
                        $subname = (integer) $subname;
                    }
                    $params[$set][$subname] = $value;
                    unset($params[$key]);
                }
            }
        }

        // Fix a Zend related bug (the "-" is not set in the form) for roles.
        if (isset($params['mapping_roles']['contributionanonymous'])) {
            $params['mapping_roles']['contribution-anonymous'] = $params['mapping_roles']['contributionanonymous'];
        }

        if (plugin_is_active('Tagging')) {
            $params['install_folksonomy'] = true;
        }

        return $params;
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
                || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')
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
        $settings = $this->_getSecurityIni();

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
