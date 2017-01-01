<?php

/**
 * Upgrade Core Site to Omeka S.
 *
 * @package UpgradeToOmekaS
 */
class UpgradeToOmekaS_Processor_CoreSite extends UpgradeToOmekaS_Processor_Abstract
{
    public $pluginName = 'Core/Site';
    public $minVersion = '2.3.1';
    public $maxVersion = '2.5';

    public $module = array(
        'type' => 'integrated',
        // The version is required here only to save it in the database.
        'version' => '1.0.0-beta2',
    );

    /**
     * List of methods to process for the upgrade.
     *
     * @var array
     */
    public $processMethods = array(
        '_configOmekaS',
        '_installOmekaS',
        '_upgradeLocalConfig',
        // A user is required to create a site.
        '_upgradeUsers',
        '_upgradeSite',
    );

    public $tables = array(
        'api_key', 'asset', 'item', 'item_item_set', 'item_set', 'job', 'media',
        'migration', 'module', 'password_creation', 'property', 'resource',
        'resource_class', 'resource_template', 'resource_template_property',
        'session', 'setting', 'site', 'site_block_attachment', 'site_item_set',
        'site_page', 'site_page_block', 'site_permission', 'site_setting',
        'user', 'value', 'vocabulary',
    );

    /**
     * The target default local config.
     *
     * @internal Because the target config uses a namespace for priority, it
     * can't be directly processed and a compatibility with old versions of
     * Omeka and PHP is needed. The priority is hacked when the file is written.
     *
     * @var array
     */
    protected $_omekaSLocalConfig = array(
        'logger' => array(
            'log' => false,
            'priority' => '\Zend\Log\Logger::NOTICE',
        ),
        'http_client' => array(
            'sslcapath' => null,
            'sslcafile' => null,
        ),
        'cli' => array(
            'phpcli_path' => null,
        ),
        'file_manager' => array(
            'thumbnailer' => 'Omeka\File\ImageMagickThumbnailer',
            'thumbnail_types' => array(
                'large' => array('constraint' => 800),
                'medium' => array('constraint' => 200),
                'square' => array('constraint' => 200),
            ),
            'thumbnail_options' => array(
                'imagemagick_dir' => null,
            ),
        ),
        'translator' => array(
            'locale' => 'en_US',
        ),
    );

    /**
     * The target extension whitelist.
     *
     * Removed from the default white list of Omeka 2: "audio/x-m4a",
     * "video/x-m4v" and "video/webm".
     *
     * @var array
     */
    protected $_omekaSMediaTypeWhitelist = array(
        'application/msword', 'application/ogg', 'application/pdf',
        'application/rtf', 'application/vnd.ms-access',
        'application/vnd.ms-excel', 'application/vnd.ms-powerpoint',
        'application/vnd.ms-project', 'application/vnd.ms-write',
        'application/vnd.oasis.opendocument.chart',
        'application/vnd.oasis.opendocument.database',
        'application/vnd.oasis.opendocument.formula',
        'application/vnd.oasis.opendocument.graphics',
        'application/vnd.oasis.opendocument.presentation',
        'application/vnd.oasis.opendocument.spreadsheet',
        'application/vnd.oasis.opendocument.text',
        'application/x-ms-wmp', 'application/x-ogg', 'application/x-gzip',
        'application/x-msdownload', 'application/x-shockwave-flash',
        'application/x-tar', 'application/zip', 'audio/aac', 'audio/aiff',
        'audio/mid', 'audio/midi', 'audio/mp3', 'audio/mp4', 'audio/mpeg',
        'audio/mpeg3', 'audio/ogg', 'audio/wav', 'audio/wma', 'audio/x-aac',
        'audio/x-aiff', 'audio/x-midi', 'audio/x-mp3', 'audio/x-mp4',
        'audio/x-mpeg', 'audio/x-mpeg3', 'audio/x-mpegaudio', 'audio/x-ms-wax',
        'audio/x-realaudio', 'audio/x-wav', 'audio/x-wma', 'image/bmp',
        'image/gif', 'image/icon', 'image/jpeg', 'image/pjpeg', 'image/png',
        'image/tiff', 'image/x-icon', 'image/x-ms-bmp', 'text/css',
        'text/plain', 'text/richtext', 'text/rtf', 'video/asf', 'video/avi',
        'video/divx', 'video/mp4', 'video/mpeg', 'video/msvideo',
        'video/ogg', 'video/quicktime', 'video/x-ms-wmv', 'video/x-msvideo',
    );

    /**
     * The target extension whitelist.
     *
     * Removed from the default white list of Omeka 2: "m4v", "opus" and "webm".
     *
     * @var array
     */
    protected $_omekaSExtensionWhitelist = array(
        'aac', 'aif', 'aiff', 'asf', 'asx', 'avi', 'bmp', 'c', 'cc', 'class',
        'css', 'divx', 'doc', 'docx', 'exe', 'gif', 'gz', 'gzip', 'h', 'ico',
        'j2k', 'jp2', 'jpe', 'jpeg', 'jpg', 'm4a', 'mdb', 'mid', 'midi', 'mov',
        'mp2', 'mp3', 'mp4', 'mpa', 'mpe', 'mpeg', 'mpg', 'mpp', 'odb', 'odc',
        'odf', 'odg', 'odp', 'ods', 'odt', 'ogg', 'pdf', 'png', 'pot', 'pps',
        'ppt', 'pptx', 'qt', 'ra', 'ram', 'rtf', 'rtx', 'swf', 'tar', 'tif',
        'tiff', 'txt', 'wav', 'wax', 'wma', 'wmv', 'wmx', 'wri', 'xla', 'xls',
        'xlsx', 'xlt', 'xlw', 'zip',
    );

    /**
     * Check if the plugin is installed.
     *
     * @internal Always true for the Core.
     *
     * @return boolean
     */
    public function isPluginReady()
    {
        return true;
    }

    protected function _configOmekaS()
    {
        // Load and check the database.
        $target = $this->getTarget();
        $targetDb = $target->getDb();

        // Create database.ini.
        $database = $this->getParam('database');
        if (!isset($database['type'])) {
            throw new UpgradeToOmekaS_Exception(
                __('The type of the database is not defined.', $type));
        }
        $type = $database['type'];
        switch ($type) {
            case 'separate':
                $host = isset($database['host']) ? $database['host'] : '';
                $port = isset($database['port']) ? $database['port'] : '';
                $username = isset($database['username']) ? $database['username'] : '';
                $password = isset($database['password']) ? $database['password'] : '';
                $dbname = isset($database['dbname']) ? $database['dbname'] : '';
                // $prefix = isset($database['prefix']) ? $database['prefix'] : '';
                break;

            case 'share':
                $db = $this->_db;
                $config = $db->getAdapter()->getConfig();
                $host = isset($config['host']) ? $config['host'] : '';
                $port = isset($config['port']) ? $config['port'] : '';
                $dbname = isset($config['dbname']) ? $config['dbname'] : '';
                $username = isset($config['username']) ? $config['username'] : '';
                $password = isset($config['password']) ? $config['password'] : '';
                // $prefix = isset($config['prefix']) ? $config['prefix'] : '';
                break;

            default:
                throw new UpgradeToOmekaS_Exception(
                    __('The type "%s" is not possible for the database.', $type));
        }

        $databaseConfig = 'user     = "' . $username . '"'. PHP_EOL;
        $databaseConfig .= 'password = "' . $password . '"'. PHP_EOL;
        $databaseConfig .= 'dbname   = "' . $dbname . '"'. PHP_EOL;
        $databaseConfig .= 'host     = "' . $host . '"'. PHP_EOL;
        // $databaseConfig .= empty($prefix)
        //     ? ';prefix   = '. PHP_EOL
        //     : 'prefix   = "' . $prefix . '"'. PHP_EOL;
        $databaseConfig .= empty($port)
            ? ';port     = '. PHP_EOL
            : 'port     = "' . $port . '"'. PHP_EOL;
        $databaseConfig .= ';unix_socket = "' . '' . '"'. PHP_EOL;
        $databaseConfig .= ';log_path = "' . '' . '"'. PHP_EOL;

        $databaseIni = $this->getFullPath('config/database.ini');
        $result = file_put_contents($databaseIni, $databaseConfig);
        if (empty($result)) {
            throw new UpgradeToOmekaS_Exception(
                __('Unable to save file "%s".', 'database.ini'));
        }

        $this->_log('[' . __FUNCTION__ . ']: ' . __('The file "config/database.ini" has been updated successfully.'),
            Zend_Log::INFO);
    }

    /**
     * @see application/config/module.config.php['installer']['tasks']
     * @throws UpgradeToOmekaS_Exception
     */
    protected function _installOmekaS()
    {
        $target = $this->getTarget();
        $targetDb = $target->getDb();

        // See Omeka S ['installer']['tasks']: DestroySessionTask.php
        // Nothing to do: there is no session by default in the tables and no
        // user is logged since it is processed automatically.
        $this->_log('[' . __FUNCTION__ . ']: ' . __('Installer Task "%s" ended (nothing to do).', 'Destroy Session'),
            Zend_Log::DEBUG);

        // See Omeka S ['installer']['tasks']: ClearCacheTask.php
        // Nothing to do.
        $this->_log('[' . __FUNCTION__ . ']: ' . __('Installer Task "%s" ended (nothing to do).', 'Clear Cache'),
            Zend_Log::DEBUG);

        // See Omeka S ['installer']['tasks']: InstallSchemaTask.php
        // The Omeka S schema is an optimized sql script, so use it.
        $script = $this->getFullPath('application/data/install/schema.sql');
        $sql = file_get_contents($script);
        $result = $targetDb->prepare($sql)->execute();
        if (!$result) {
            throw new UpgradeToOmekaS_Exception(
                __('Unable to execute install queries.'));
        }
        $this->_log('[' . __FUNCTION__ . ']: ' . __('Installer Task "%s" ended.', 'Install Schema'),
            Zend_Log::DEBUG);

        // See Omeka S ['installer']['tasks']: RecordMigrationsTask.php
        // See Omeka\Db\Migration\Manager::getAvailableMigrations()
        // Omeka 2 should be compatible with 5.3.2.
        $path = $this->getFullPath('application/data/migrations');
        $migrations = array();
        $globPattern = $path . DIRECTORY_SEPARATOR . '*.php';
        $regexPattern = '/^(\d+)_(\w+)\.php$/';
        $files = glob($globPattern, GLOB_MARK);
        foreach ($files as $filename) {
            if (preg_match($regexPattern, basename($filename), $matches)) {
                $version = $matches[1];
                $migrations[] = $version;
            }
        }
        $sql = 'INSERT INTO migration VALUES(' . implode('),(', $migrations) . ');';
        $result = $targetDb->prepare($sql)->execute();
        if (!$result) {
            throw new UpgradeToOmekaS_Exception(
                __('Unable to update list of migrations.'));
        }
        $this->_log('[' . __FUNCTION__ . ']: ' . __('Installer Task "%s" ended.', 'Record Migrations'),
            Zend_Log::DEBUG);

        // See Omeka S ['installer']['tasks']: InstallDefaultVocabulariesTask.php
        // To simplify process for vocabularies, that Omeka doesn't manage, an
        // export of a fresh automatic install is used (the installer task
        // imports rdf vocabularies in application/data/vocabularies).
        $script = dirname(dirname(dirname(dirname(__FILE__))))
            . DIRECTORY_SEPARATOR . 'libraries'
            . DIRECTORY_SEPARATOR . 'data'
            . DIRECTORY_SEPARATOR . 'rdf_vocabularies.sql';
        $sql = file_get_contents($script);
        $result = $targetDb->prepare($sql)->execute();
        if (!$result) {
            throw new UpgradeToOmekaS_Exception(
                __('Unable to execute install queries for default vocabularies.'));
        }
        $this->_log('[' . __FUNCTION__ . ']: ' . __('Installer Task "%s" ended.', 'Install Default Vocabularies'),
            Zend_Log::DEBUG);

        // See Omeka S ['installer']['tasks']: InstallDefaultTemplatesTask.php
        // Same note than vocabularies above.
        $script = dirname(dirname(dirname(dirname(__FILE__))))
            . DIRECTORY_SEPARATOR . 'libraries'
            . DIRECTORY_SEPARATOR . 'data'
            . DIRECTORY_SEPARATOR . 'default_templates.sql';
        $sql = file_get_contents($script);
        $result = $targetDb->prepare($sql)->execute();
        if (!$result) {
            throw new UpgradeToOmekaS_Exception(
                __('Unable to execute install queries for default templates.'));
        }
        $this->_log('[' . __FUNCTION__ . ']: ' . __('Installer Task "%s" ended.', 'Install Default Templates'),
            Zend_Log::DEBUG);

        // See Omeka S ['installer']['tasks']: CreateFirstUserTask.php
        $this->_log('[' . __FUNCTION__ . ']: ' . __('Installer Task "%s" skipped (processed with other users).', 'Create First User'),
            Zend_Log::DEBUG);

        // See Omeka S ['installer']['tasks']: AddDefaultSettingsTask.php
        $target->saveSetting('version', $this->module['version']);

        // Use the customized value for admin pages if modified.
        $value = get_option('per_page_admin');
        if ($value == 10) {
            // Default of Omeka S.
            $value = 25;
        } else {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('Omeka S doesn’t use a specific pagination value for public pages.'),
                Zend_Log::NOTICE);
        }
        $target->saveSetting('pagination_per_page', $value);

        $value = get_option('file_mime_type_whitelist');
        if ($value == Omeka_Validate_File_MimeType::DEFAULT_WHITELIST) {
            $value = $this->_omekaSMediaTypeWhitelist;
        } else {
            $value = explode(',', $value);
        }
        $target->saveSetting('media_type_whitelist', $value);
        $this->_log('[' . __FUNCTION__ . ']: ' . __('These three media types have been removed from the default white list of Omeka 2: "audio/x-m4a", "video/x-m4v" and "video/webm".'),
            Zend_Log::INFO);

        $value = get_option('file_extension_whitelist');
        if ($value == Omeka_Validate_File_Extension::DEFAULT_WHITELIST) {
            $value = $this->_omekaSExtensionWhitelist;
        } else {
            $value = explode(',', $value);
        }
        $target->saveSetting('extension_whitelist', $value);
        $this->_log('[' . __FUNCTION__ . ']: ' . __('These three extensions have been removed from the default white list of Omeka 2: "m4v", "opus" and "webm".'),
            Zend_Log::INFO);

        $user = $this->getParam('user');
        if (empty($user)) {
            throw new UpgradeToOmekaS_Exception(
                __('No user has been defined.'));
        }
        // Use the option "administrator_email" instead of the current user.
        $value = get_option('administrator_email') ?: $user->email;
        $target->saveSetting('administrator_email', $value);
        $target->saveSetting('installation_title', $this->getParam('installation_title'));
        $target->saveSetting('time_zone', $this->getParam('time_zone'));

        // Settings that are not set when the site is installed.

        // Even if the first site is not yet created.
        $target->saveSetting('default_site', (string) 1);
        $target->saveSetting('disable_file_validation', (string) get_option('disable_default_file_validation'));
        $target->saveSetting('property_label_information', 'none');
        $target->saveSetting('recaptcha_site_key', (string) get_option('recaptcha_public_key'));
        $target->saveSetting('recaptcha_secret_key', (string) get_option('recaptcha_private_key'));
        $target->saveSetting('use_htmlpurifier', (string) get_option('html_purifier_is_enabled'));

        $this->_log('[' . __FUNCTION__ . ']: ' . __('Installer Task "%s" ended.', 'Add Default Settings'),
            Zend_Log::DEBUG);

        $this->_log('[' . __FUNCTION__ . ']: ' . __('The main tables are created and default data inserted.'),
            Zend_Log::INFO);
    }

    protected function _upgradeLocalConfig()
    {
        // Convert config.ini into local.config.php with reasonable assertions.
        $config = Zend_Registry::get('bootstrap')->config;

        // Get default values of the local config of Omeka S.
        $targetConfig = $this->_omekaSLocalConfig;

        // Localization.

        // locale.name = ""
        $value = isset($config->locale->name) ? $config->locale->name : null;
        if ($value) {
            $targetConfig['translator']['locale'] = $value;
        }

        // Debugging.

        // Debug is not set in config.ini, but in the application environment..
        // debug.exceptions = false
        // debug.request = false
        // debug.profileDb = false
        // debug.email = ""
        // debug.emailLogPriority = Zend_Log::ERR
        $flag = false;
        foreach (array(
                'exceptions' => false,
                'request' => false,
                'profileDb' => false,
                'email' => '',
                'emailLogPriority' => 'Zend_Log::ERR',
            ) as $name => $defaultValue) {
            $value = isset($config->debug->$name) ? $config->debug->$name : null;
            if ($value != $defaultValue) {
                $flag = true;
                $this->_log('[' . __FUNCTION__ . ']: ' . __('The parameter "%s" is not supported by Omeka S currently.',
                    'debug.' . $name), Zend_Log::WARN);
            }
        }
        if ($flag) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('Use the integrated logging system or the environment to debug Omeka S.'),
                Zend_Log::NOTICE);
        }

        // Logging.

        // log.errors = false
        $value = isset($config->log->errors) ? $config->log->errors : null;
        if ($value) {
            $targetConfig['logger']['log'] = (boolean) $value;
        }
        // log.priority = Zend_Log::WARN
        // The priority is kept to NOTICE, except if has been modified.
        $value = isset($config->log->priority) ? $config->log->priority : null;
        if ($value && $value != 'Zend_Log::WARN') {
            $targetConfig['logger']['priority'] = '\Zend\Log\Logger::' . substr($value, 10);
        }
        // log.sql = false
        // This value is not used in Omeka S.
        $value = isset($config->log->sql) ? $config->log->sql : null;
        if ($value) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('The processor doesn’t convert the parameter "%s" currently.',
                'log.sql'), Zend_Log::WARN);
        }

        // Sessions.
        // TODO Manage the sessions config, but rarely modified.

        // session.name = ""
        $value = isset($config->session->name) ? $config->session->name : null;
        if ($value) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('The processor doesn’t convert the parameter "%s" currently.',
                'session.name'), Zend_Log::WARN);
        }
        // ; session.saveHandler = ""
        $value = isset($config->session->saveHandler) ? $config->session->saveHandler : null;
        if ($value) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('The processor doesn’t convert the parameter "%s" currently.',
                'session.saveHandler'), Zend_Log::WARN);
        }

        // Theme.

        // theme.useInternalAssets = false
        $value = isset($config->theme->useInternalAssets) ? $config->theme->useInternalAssets : null;
        if ($value) {
            $targetConfig['assets']['use_externals'] = false;
        }

        // Background Scripts.

        // background.php.path = ""
        $value = isset($config->background->php->path) ? $config->background->php->path : null;
        if ($value) {
            $targetConfig['cli']['phpcli_path'] = $value;
        }
        // jobs.dispatcher.default = "Omeka_Job_Dispatcher_Adapter_Synchronous"
        $value = isset($config->jobs->dispatcher->default) ? $config->jobs->dispatcher->default : null;
        if ($value != 'Omeka_Job_Dispatcher_Adapter_Synchronous') {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('The processor doesn’t convert the parameter "%s" currently.',
                'jobs.dispatcher.default'), Zend_Log::WARN);
        }
        // jobs.dispatcher.longRunning = "Omeka_Job_Dispatcher_Adapter_BackgroundProcess"
        $value = isset($config->jobs->dispatcher->longRunning) ? $config->jobs->dispatcher->longRunning : null;
        if ($value != 'Omeka_Job_Dispatcher_Adapter_BackgroundProcess') {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('The processor doesn’t convert the parameter "%s" currently.',
                'jobs.dispatcher.longRunning'), Zend_Log::WARN);
        }

        // Mail.

        // mail.transport.type = "Sendmail"
        // ; mail.transport.type = "Smtp"
        // ; mail.transport.host = ""
        // ; mail.transport.port = ###     ; Port number, if applicable.
        // ; mail.transport.name = ""      ; Local client hostname, e.g. "localhost"
        // ; mail.transport.auth = "login" ; For authentication, if required.
        // ; mail.transport.username = ""
        // ; mail.transport.password = ""
        // ; mail.transport.ssl = ""       ; For SSL support, set to "ssl" or "tls"
        $value = isset($config->mail->transport->type) ? $config->mail->transport->type : null;
        $values = isset($config->mail->transport) ? $config->mail->transport->toArray() : array();
        unset($values['type']);
        switch ($value) {
            case 'Sendmail':
                // Nothing to do: this is the default in Omeka S too.
                break;
            case 'Smtp':
            default:
                // TODO Check if options have the same keys.
                $targetConfig['mail']['transport']['type'] = strtolower($value);
                $targetConfig['mail']['transport']['options'] = $values;
                break;
        }
        if ($values) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('The config used to send mail should be checked.'),
                Zend_Log::NOTICE);
        }

        // Storage.

        // ; storage.adapter = "Omeka_Storage_Adapter_ZendS3"
        // ; storage.adapterOptions.accessKeyId =
        // ; storage.adapterOptions.secretAccessKey =
        // ; storage.adapterOptions.bucket =
        // ; storage.adapterOptions.expiration = 10 ; URL expiration time (in minutes)
        // ; storage.adapterOptions.endpoint = ; Custom S3 endpoint (optional)
        $value = isset($config->storage->adapter) ? $config->storage->adapter : null;
        $values = isset($config->storage->adapterOptions) ? $config->storage->adapterOptions->toArray() : array();
        if ($value || $values) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('The processor doesn’t convert the parameter "%s" currently.',
                'storage.adapter'), Zend_Log::WARN);
        }

        // Security.

        // ; ssl = "always"
        $value = isset($config->ssl) ? $config->ssl : null;
        if ($value) {
            $allowedValues = array('logins', 'sessions', 'always');
            $this->_log('[' . __FUNCTION__ . ']: ' . __('The processor doesn’t convert the parameter "%s" currently.',
                'ssl'), Zend_Log::WARN);
        }

        // Upload.

        // ;upload.maxFileSize = "10M"
        $value = isset($config->upload->maxFileSize) ? $config->upload->maxFileSize : null;
        if ($value && $value != '10M') {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('The parameter "%s" is not supported by Omeka S currently.',
                'upload.maxFileSize'), Zend_Log::WARN);
            $this->_log('[' . __FUNCTION__ . ']: ' . __('Update yourself the config of the server (php.ini), or set the value in ".htaccess".'),
                Zend_Log::NOTICE);
        }

        // Derivative Images.

        // ;fileDerivatives.strategy = "Omeka_File_Derivative_Strategy_ExternalImageMagick"
        $value = isset($config->fileDerivatives->strategy) ? $config->fileDerivatives->strategy : 'Omeka_File_Derivative_Strategy_ExternalImageMagick';
        switch ($value) {
            case 'Omeka_File_Derivative_Strategy_ExternalImageMagick':
                // Nothing to do: this is the default in Omeka S too.
                break;
            case 'Omeka_File_Derivative_Strategy_Imagick':
                $targetConfig['file_manager']['thumbnailer'] = 'Omeka\File\ImagickThumbnailer';
                break;
            case 'Omeka_File_Derivative_Strategy_GD':
                $targetConfig['file_manager']['thumbnailer'] = 'Omeka\File\GdThumbnailer';
                break;
            default:
                $this->_log('[' . __FUNCTION__ . ']: ' . __('The derivative strategy "%s" is not supported by Omeka S.',
                    $value), Zend_Log::WARN);
        }

        $values = isset($config->fileDerivativesy->strategyOptions) ? $config->fileDerivativesy->strategyOptions->toArray() : null;
        // ; fileDerivatives.strategyOptions.page = "0"
        if (isset($values['page']) && $values['page'] !== '0') {
            $targetConfig['file_manager']['thumbnail_options']['page'] = (integer) $values['page'];
        }
        // ; fileDerivatives.strategyOptions.gravity = "center"
        if (isset($values['gravity']) && $values['gravity'] !== 'center') {
            $targetConfig['file_manager']['thumbnail_types']['square']['options']['gravity'] = $values['gravity'];
        }
        // ; fileDerivatives.strategyOptions.autoOrient = false
        if (isset($values['autoOrient']) && $values['autoOrient']) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('The parameter "%s" is not supported by Omeka S currently.',
                'fileDerivatives.strategyOptions.autoOrient'), Zend_Log::WARN);
        }
        // ;fileDerivatives.typeWhitelist[] = "image/jpeg"
        $values = isset($config->fileDerivatives->typeWhitelist) ? $config->fileDerivatives->typeWhitelist->toArray() : null;
        if (!empty($values)) {
            // See "media_type_whitelist" and "extension_whitelist" too.
            $this->_log('[' . __FUNCTION__ . ']: ' . __('The parameter "%s" is not supported by Omeka S currently.',
                'fileDerivatives.typeWhitelist'), Zend_Log::WARN);
        }
        // ;fileDerivatives.typeBlacklist[] = "image/jpeg"
        $values = isset($config->fileDerivatives->typeBlacklist) ? $config->fileDerivatives->typeBlacklist->toArray() : null;
        if (!empty($values)) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('The parameter "%s" is not supported by Omeka S currently.',
                'fileDerivatives.typeBlacklist'), Zend_Log::WARN);
        }

        // Add some settings options that are moved inside the local config.
        $value = get_option('path_to_convert');
        if ($value) {
            $targetConfig['file_manager']['thumbnail_options']['imagemagick_dir'] = $value;
        }

        // Omeka 2 and Omeka S use the same sizes.
        $derivativeTypes = array(
            'fullsize' => array('type' => 'large', 'constraint' => 800, 'strategy' => 'default'),
            'thumbnail' => array('type' => 'medium', 'constraint' => 200, 'strategy' => 'default'),
            'square_thumbnail' => array('type' => 'square', 'constraint' => 200, 'strategy' => 'square'),
        );
        // This option is used in a fork and allows multiple thumbnail types.
        $values = get_option('derivative_types');
        if ($values) {
            $values = unserialize($values);
            foreach ($derivativeTypes as $derivativeType) {
                // Set default values except for default derivative types.
                if (!isset($derivativeTypes[$derivativeType])) {
                    $derivativeTypes[$derivativeType] = array(
                        'added' => true,
                        'type' => $derivativeType,
                        'constraint' => get_option($derivativeType . '_constraint'),
                        'stragegy' => strpos($derivativeType, 'square') !== false,
                    );
                }
            }
        }
        foreach ($derivativeTypes as $derivativeType => $options) {
            if (!empty($options['added']) && $options['strategy'] != 'default') {
                $targetConfig['file_manager']['thumbnail_types'][$options['type']]['strategy'] = $options['strategy'];
            }
            $value = get_option($derivativeType . '_constraint');
            if ($value != $options['constraint'] || !empty($options['added'])) {
                $targetConfig['file_manager']['thumbnail_types'][$options['type']]['constraint'] = $value;
            }
        }

        // Convert the array into an indented raw file.
        $localConfig = $this->_createRawArray($targetConfig);
        $localConfigPhp = $this->getFullPath('config/local.config.php');
        $result = file_put_contents($localConfigPhp, $localConfig);
        if (empty($result)) {
            throw new UpgradeToOmekaS_Exception(
                __('Unable to save file "%s".', 'local.config.php'));
        }

        // application.ini
        // routes.ini
        // .htaccess
        // errors.log!
        $this->_log('[' . __FUNCTION__ . ']: ' . __('The files "application.ini", "routes.ini", ".htaccess" and "errors.log" are not upgradable.')
            . ' ' . __('Check if you modified them.'), Zend_Log::NOTICE);
    }

    protected function _upgradeUsers()
    {
        $recordType = 'User';
        $db = $this->_db;
        $target = $this->getTarget();
        $targetDb = $target->getDb();

        $user = $this->getParam('user');

        $totalRecords = total_records($recordType);
        $this->_progress(0, $totalRecords);

        // Check if there are already records.
        $totalExisting = $target->totalRows('user');
        if ($totalExisting) {
            // TODO Allow to upgrade without ids (need a temp mapping of source and destination ids)?
            throw new UpgradeToOmekaS_Exception(
                __('Some users(%d) have been upgraded, so ids won’t be kept.',
                    $totalExisting)
                . ' ' . __('Check the processors of the plugins.'));
        }

        // This case is possible with an external identification (ldap...), but
        // not managed..
        if (empty($totalRecords)) {
            throw new UpgradeToOmekaS_Exception(
                __('There is no user in Omeka Classic: at least the super user should exist.'));
        }

        $mappingRoles = $this->getMappingRoles();

        // The process uses the regular queries of Omeka in order to keep
        // only good records.
        $table = $db->getTable('User');

        $totalSupers = 0;
        $totalAdmins = 0;
        $unmanagedRoles = array();

        $loops = floor(($totalRecords - 1) / $this->maxChunk) + 1;
        for ($page = 1; $page <= $loops; $page++) {
            $this->_progress(($page - 1) * $this->maxChunk);
            $records = $table->findBy(array(), $this->maxChunk, $page);

            $toInserts = array();
            foreach ($records as $record) {
                if (empty($mappingRoles[$record->role])) {
                    $unmanagedRoles[$record->role] = isset($unmanagedRoles[$record->role])
                        ? ++$unmanagedRoles[$record->role]
                        : 1;
                    continue;
                }

                if ($record->role == 'super') {
                    $totalSupers++;
                } elseif ($record->role == 'admin') {
                    $totalAdmins++;
                }

                $id = (integer) $record->id;

                // Give the role of global admin to the current user, whatever
                // the mapping.
                $role = $record->id == $user->id
                    ? 'global_admin'
                    : $mappingRoles[$record->role];

                $toInsert = array();
                $toInsert['id'] = $id;
                $toInsert['email'] = substr($record->email, 0, 190);
                $toInsert['name'] = substr($record->name, 0, 190);
                $toInsert['created'] = $this->getDatetime();
                $toInsert['modified'] = null;
                $toInsert['password_hash'] = null;
                $toInsert['role'] = $role;
                $toInsert['is_active'] = (integer) (boolean) $record->active;
                $toInserts[] = $target->cleanQuote($toInsert);
            }

            $target->insertRows('user', $toInserts);
        }

        $totalUnmanaged = array_sum($unmanagedRoles);
        $totalUpgraded = $totalRecords - $totalUnmanaged;
        if ($totalUnmanaged) {
            // Plural needs v2.3.1.
            $unknownRolesString = implode('", "', array_keys($unmanagedRoles));
            $message = function_exists('plural')
                ? __(plural('%d user not upgraded (role: "%s").', '%d users not upgraded (roles: "%s").',
                    $totalUnmanaged), $totalUnmanaged, $unknownRolesString)
                : __('%d users not upgraded (roles: "%s").', $totalUnmanaged, $unknownRolesString);
            $this->_log('[' . __FUNCTION__ . ']: ' . $message,
                Zend_Log::WARN);
        }

        $this->_log('[' . __FUNCTION__ . ']: ' . __('The current user has been made the global administrator.'),
            Zend_Log::NOTICE);
        if ($totalSupers > 1 && $totalAdmins) {
            $message = __('The other super users [%d] have been made site administrators, like the admins [%d].',
                $totalSupers <= 1 ? 0 : $totalSupers - 1, $totalAdmins);
            $this->_log('[' . __FUNCTION__ . ']: ' . $message,
                Zend_Log::NOTICE);
        }
        // Plural needs v2.3.1.
        $message = function_exists('plural')
            ? __(plural('%d user upgraded.', '%d users upgraded.', $totalUpgraded), $totalUpgraded)
            : __('%d users upgraded.', $totalUpgraded);
        $this->_log('[' . __FUNCTION__ . ']: ' . $message,
            Zend_Log::INFO);

        $settings = $this->getSecurityIni();
        if (!empty($settings->default->global_admin_password)) {
            $bind = array();
            $bind['password_hash'] = $settings->default->global_admin_password;
            $bind['modified'] = $this->getDatetime();
            $result = $targetDb->update('user', $bind, 'id = ' . $user->id);
        }

        $this->_log('[' . __FUNCTION__ . ']: ' . __('The username of users has been removed; the displayed name is unchanged.'),
            Zend_Log::NOTICE);
        $this->_log('[' . __FUNCTION__ . ']: ' . __('All users must request a new password in the login page.'),
            Zend_Log::WARN);
    }

    protected function _upgradeSite()
    {
        // Settings of Omeka Classic: create the first site.
        $db = $this->_db;
        $target = $this->getTarget();
        $targetDb = $target->getDb();
        $settings = $this->getSecurityIni();
        $user = $this->getParam('user');

        $title = $this->getSiteTitle();
        $slug = $this->getSiteSlug();
        $theme = !empty($settings->default->site->theme)
            ? $settings->default->site->theme
            : get_option('public_theme');

        $id = 1;

        $navigation = $this->_convertNavigation();

        $toInsert = array();
        $toInsert['id'] = $id;
        $toInsert['owner_id'] = $user->id;
        $toInsert['slug'] = $slug;
        $toInsert['theme'] = substr($theme ?: 'default', 0, 190);
        $toInsert['title'] = substr($title, 0, 190);
        $toInsert['navigation'] = $this->_toJson($navigation);
        $toInsert['item_pool'] = json_encode(array());
        $toInsert['created'] = $this->getDatetime();
        $toInsert['is_public'] = 1;
        $result = $targetDb->insert('site', $toInsert);
        if (!$result) {
            throw new UpgradeToOmekaS_Exception(
                __('Unable to create the first site.'));
        }

        $this->_siteId = $id;

        $target->saveSiteSetting('attachment_link_type', 'item');
        $target->saveSiteSetting('browse_attached_items', '0');

        // An item set for the site will be created later to keep original ids.

        if (!empty($navigation)) {
            $totalVisibleLinks = $this->_countNavigationPages(true);
            if ($totalVisibleLinks) {
                $this->_log('[' . __FUNCTION__ . ']: ' . __('%d navigation links have been upgraded.',
                    $totalVisibleLinks), Zend_Log::INFO);
            }
            $totalInvisibleLinks = $this->_countNavigationPages(false);
            if ($totalInvisibleLinks) {
                // Plural needs v2.3.1.
                $message = function_exists('plural')
                    ? __(plural(
                        'Omeka S doesn’t allow to hide/show navigation links, so %d link has not been upgraded.',
                        'Omeka S doesn’t allow to hide/show navigation links, so %d links have not been upgraded.',
                        $totalInvisibleLinks), $totalInvisibleLinks)
                    : __('Omeka S doesn’t allow to hide/show navigation links, so %d links have not been upgraded.',
                        $totalInvisibleLinks);
                $this->_log('[' . __FUNCTION__ . ']: ' . $message,
                    Zend_Log::NOTICE);
            }
            $this->_log('[' . __FUNCTION__ . ']: ' . __('The conversion of the navigation is currently partial and some false links may exist.',
                $totalInvisibleLinks), Zend_Log::INFO);
        }

        // Check if SimplePages is active to add the default page or not.
        $processors = $this->getProcessors();
        if (!isset($processors['SimplePages'])) {
            $title = __('Welcome');
            $slug = $this->_slugify($title);
            $toInsert = array();
            $toInsert['id'] = 1;
            $toInsert['site_id'] = 1;
            $toInsert['slug'] = substr($slug, 0, 190);
            $toInsert['title'] = substr($title, 0, 190);
            $toInsert['created'] = $this->getDatetime();
            $result = $targetDb->insert('site_page', $toInsert);

            $toInsert = array();
            $toInsert['page_id'] = 1;
            $toInsert['layout'] = 'pageTitle';
            $toInsert['data'] = $target->toJson(array());
            $toInsert['position'] = 1;
            $result = $targetDb->insert('site_page_block', $toInsert);

            $data = array(
                'html' => __('Welcome to your new site. This is an example page.')
                    . '<p>' . get_option('description') . '</p>'
                    . '<p>' . __('Author: %s', get_option('author')) . '</p>'
                    . '<p>' . __('Copyright: %s', get_option('copyright')) . '</p>',
            );
            $toInsert = array();
            $toInsert['page_id'] = 1;
            $toInsert['layout'] = 'html';
            $toInsert['data'] = $target->toJson($data);
            $toInsert['position'] = 2;
            $result = $targetDb->insert('site_page_block', $toInsert);
        }

        $siteId = $this->getSiteId();

        // Upgrade options required by the site.
        $searchRecordTypes = unserialize(get_option('search_record_types'));
        $searchResourceTypes = empty($searchRecordTypes) ? array() : array_flip(array_filter($searchRecordTypes));
        $target->saveSiteSetting('upgrade_search_resource_types', $searchResourceTypes);
        $target->saveSiteSetting('upgrade_show_empty_properties', (string) get_option('show_empty_elements'));
        $target->saveSiteSetting('upgrade_show_vocabulary_headings', (string) get_option('show_element_set_headings'));
        $target->saveSiteSetting('upgrade_tag_delimiter', (string) get_option('tag_delimiter'));
        $target->saveSiteSetting('upgrade_use_advanced_search', (string) get_theme_option('use_advanced_search'));
        $target->saveSiteSetting('upgrade_use_square_thumbnail', (string) get_option('use_square_thumbnail'));

        // Give all users access right to view the site.
        // This is a second mapping for the rights about the site.
        $mapping = array(
            'global_admin' => 'admin',
            'site_admin' => 'admin',
            'editor' => 'editor',
            'reviewer' => 'editor',
            'author' => 'editor',
            'researcher' => 'viewer',
        );

        $select = $targetDb->select()
            ->from('user', array('id', 'role'));
        $users = $targetDb->fetchAll($select);

        $toInserts = array();
        foreach ($users as $user) {
            $toInsert = array();
            $toInsert['id'] = null;
            $toInsert['site_id'] = $siteId;
            $toInsert['user_id'] = $user['id'];
            $toInsert['role'] = isset($mapping[$user['role']])
                ? $mapping[$user['role']]
                : 'viewer';
            $toInserts[] = $target->cleanQuote($toInsert);
        }
        $target->insertRows('site_permission', $toInserts);

        $this->_log('[' . __FUNCTION__ . ']: '
                . __('The "author", the "description" and the "copyright" of the site have been moved to the collection created for the site.'),
            Zend_Log::INFO);

        $this->_log('[' . __FUNCTION__ . ']: ' . __('The first site has been created.')
                . ' ' . __('Each user has a specific role in it.'),
            Zend_Log::INFO);
    }

    /**
     * Helper to convert an array into an indented raw file.
     *
     * @internal This quick and dirty tool is designed only for local.config.php.
     *
     * @param array $nestedArray
     * @return string
     */
    private function _createRawArray(array $nestedArray)
    {
        $indent = '    ';

        $output = array();
        $output[] = '<?php';
        $output[] = 'return [';

        $output = $this->_nestOutput($output, $nestedArray, 1, $indent);

        $output[] = '];';
        $result = implode(PHP_EOL, $output) . PHP_EOL;
        return $result;
    }

    private function _nestOutput($output, $array, $depth = 0, $indent = '    ')
    {
        $indentString = str_repeat($indent, $depth);
        foreach ($array as $key => $value) {
            // Manage an exception: no quote for a string.
            if ($key == 'priority') {
                $output[] = $indentString . "'" . $key . "' => " . $value . ',';
            }
            elseif (is_array($value)) {
                if (count($value) == 0) {
                    $output[] = $indentString . "'" . $key . "' => [],";
                } elseif (count($value) == 1 && $key == 'constraint') {
                    $v = reset($value);
                    $output[] = $indentString . "'" . $key . "' => ['" . key($value) . "' => " . $this->_printValue($v) . '],';
                } else {
                    $output[] = $indentString . "'" . $key . "' => [";
                    $output = $this->_nestOutput($output, $value, ($depth + 1), $indent);
                    $output[] = $indentString . '],';
                }
            }
            else {
                $output[] = $indentString . "'" . $key . "' => " . $this->_printValue($value) . ',';
            }
        }
        return $output;
    }

    private function _printValue($value)
    {
        $type = gettype($value);
        switch ($type) {
            case 'NULL':
                return 'null';
            case 'boolean':
                return $value ? 'true' : 'false';
            case 'integer':
                return $value;
            case 'string':
                return "'" . str_replace ("'", "\\'", $value) . "'";
            default:
                return (string) $value;
        }
    }

    protected function _convertNavigation()
    {
        $navigation = $this->_getMainNavigation();
        if (empty($navigation)) {
            return array();
        }

        // Process nav directly.
        if (!is_array($navigation)) {
            $navigation = $navigation->toArray();
        }
        foreach ($navigation as &$page) {
            $page['visible'] ? $this->_convertNavigationPage($page) : $page = null;
        }

        $navigation = array_filter($navigation);
        return $navigation;
    }

    protected function _convertNavigationPage(&$page)
    {
        if (is_array($page)) {
            $result = $this->_convertNavigationPageValues($page);
            if (isset($page['pages'])) {
                foreach ($page['pages'] as &$subpage) {
                    $subpage['visible'] ? $this->_convertNavigationPage($subpage) : $subpage = null;
                }
                $result['links'] = array_filter($page['pages']);
            } else {
                $result['links'] = array();
            }
            $page = $result;
        }
        // Else single value: no change and no return.
    }

    protected function _convertNavigationPageValues($page)
    {
        static $webRoot;
        static $baseRoot;
        static $omekaPath;
        static $omekaSPath;
        static $omekaSSitePath;

        if (is_null($webRoot)) {
            $webRoot = $this->getParam('WEB_ROOT');
            $parsed = parse_url($webRoot);
            $baseRoot = $parsed['scheme'] . '://' . $parsed['host'] . (!empty($parsed['port']) ? ':' . $parsed['port'] : '');
            $omekaPath = substr($webRoot, strlen($baseRoot));
            $omekaSPath = substr($this->getParam('url'), strlen($baseRoot));
            $omekaSSitePath = $omekaSPath . '/s/' . $this->getSiteSlug();
        }

        // The uri doesn't keep the fragment?
        $url = isset($page['uri']) ? $page['uri'] : $page['uid'];

        if ($url == $webRoot) {
            return array(
                'type' => 'url',
                'data' => array(
                    'label' => $page['label'],
                    'url' => $this->getParam('url'),
            ));
        }

        $parsed = parse_url($url);
        $isRemote = isset($parsed['scheme']) && in_array($parsed['scheme'], array('http', 'https'));
        // Check if this is an external url.
        if ($isRemote && strpos($url, $webRoot) !== 0) {
            return array(
                'type' => 'url',
                'data' => array(
                    'label' => $page['label'],
                    'url' => $url,
            ));
        }

        // Get the path without the Omeka 2 path, if any.
        $path = substr($parsed['path'], strlen($omekaPath));
        $parsed['url'] = $url;
        $parsed['fullpath'] = $parsed['path'];
        $parsed['path'] = $path;

        $processors = $this->getProcessors();
        foreach ($processors as $processor) {
            $result = $processor->_convertNavigationPageToLink(
                $page,
                $parsed,
                array(
                    'baseRoot' => $baseRoot,
                    'omekaPath' => $omekaPath,
                    'omekaSPath' => $omekaSPath,
                    'omekaSSitePath' => $omekaSSitePath,
            ));
            if ($result) {
                return $result;
            }
        }

        // Not found: return the full url.
        return array(
            'type' => 'url',
            'data' => array(
                'label' => $page['label'],
                'url' => $isRemote ? $url : $baseRoot . $url,
        ));
    }

    protected function _convertNavigationPageToLink($page, $parsed, $site)
    {
        $path = $parsed['path'];
        if (strlen($path) == 0) {
            return;
        }

        $omekaSPath = $site['omekaSPath'];
        $omekaSSitePath = $site['omekaSSitePath'];
        switch ($path) {
            case '/search':
            case '/items/search':
                return array(
                    'type' => 'url',
                    'data' => array(
                        'label' => $page['label'],
                        'url' => $omekaSSitePath . '/item/search',
                        // TODO Convert the query if any.
                        // 'query' => '',
                ));
            case '/items':
            case '/items/browse':
                return array(
                    'type' => 'browse',
                    'data' => array(
                        'label' => $page['label'],
                        // TODO Convert the query if any.
                        'query' => '',
                ));
            case '/collections':
            case '/collections/browse':
                return array(
                    'type' => 'browseItemSets',
                    'data' => array(
                        'label' => $page['label'],
                        // TODO Convert the query if any.
                        'query' => '',
                ));
            case strpos($path, '/items/show/') === 0:
                // Id of items are kept.
                $id = substr($path, strlen('/items/show/'));
                return array(
                    'type' => 'url',
                    'data' => array(
                        'label' => $page['label'],
                        'url' => $omekaSSitePath . '/item/' . $id,
                ));
            case strpos($path, '/collections/show/') === 0:
                // TODO Wrong path to collection (id changes).
                $id = substr($path, strlen('/collections/show/'));
                return array(
                    'type' => 'url',
                    'data' => array(
                        'label' => $page['label'],
                        'url' => $omekaSSitePath . '/item-set/' . $id,
                ));
            case strpos($path, '/files/show/') === 0:
                // TODO Wrong path to file (id changes).
                $id = substr($path, strlen('/files/show/'));
                return array(
                    'type' => 'url',
                    'data' => array(
                        'label' => $page['label'],
                        'url' => $omekaSSitePath . '/media/' . $id,
                ));
            case '/users':
            case '/users/login':
                return array(
                    'type' => 'url',
                    'data' => array(
                        'label' => $page['label'],
                        'url' => $omekaSPath . '/login',
                ));
            case '/users/logout':
                return array(
                    'type' => 'url',
                    'data' => array(
                        'label' => $page['label'],
                        'url' => $omekaSPath . '/logout',
                ));
            case '/exhibits':
            case '/exhibits/browse':
                return array(
                    'type' => 'url',
                    'data' => array(
                        'label' => $page['label'],
                        'url' => $omekaSPath,
                ));
            case strpos($path, '/exhibits/show/') === 0:
                $slug = substr($path, strlen('/exhibits/show/'));
                $pos = strpos($slug, '/');
                if ($pos === false) {
                    // The exhibit exists, because it's a visible internal link.
                    return array(
                        'type' => 'url',
                        'data' => array(
                            'label' => $page['label'],
                            // TODO The slug shouldn't be the same than the site (very rare).
                            'url' => $omekaSPath . '/s/' . $slug,
                    ));
                }
                // TODO Get the id of the page (the number of simple pages + the id of the exhibit page)..
                $id = 1;
                return array(
                    'type' => 'page',
                    'data' => array(
                        'label' => $page['label'],
                        'id' => $id,
                ));
        }
    }

    /**
     * Get main navigation pages.
     *
     * @return Omeka_Navigation|array
     */
    protected function _getMainNavigation()
    {
        static $navigation;

        if (is_null($navigation)) {
            $navigation = $this->getParam('navigation');
            if (is_null($navigation)) {
                try {
                    // From public_nav_main()
                    $navigation = new Omeka_Navigation;
                    $navigation->loadAsOption(Omeka_Navigation::PUBLIC_NAVIGATION_MAIN_OPTION_NAME);
                    $navigation->addPagesFromFilter(Omeka_Navigation::PUBLIC_NAVIGATION_MAIN_FILTER_NAME);
                } catch (Exception $e) {
                    $navigation = array();
                }
            }
            if (empty($navigation)) {
                $this->_log('[' . __FUNCTION__ . ']: ' . __('The main navigation menu is unavailable.'),
                    Zend_Log::WARN);
            }
        }

        return $navigation;
    }

    /**
     * Get all visible or invisible pages.
     *
     * @todo Check if a visible page is under an invisible page.
     *
     * @param string $visible
     * @return unknown
     */
    protected function _countNavigationPages($visible = true)
    {
        $navigation = $this->_getMainNavigation();
        if (empty($navigation)) {
            return 0;
        }
        // TODO Count the sub pages of the main navigation when array.
        if (is_array($navigation)) {
            return count($navigation);
        }
        $total = $navigation->findAllBy('visible', $visible);
        return count($total);
    }

    /**
     * Get an array containing all used roles with total.
     *
     * @return array.
     */
    public function getUsedRoles()
    {
        $db = $this->_db;
        $sql = "
        SELECT users.`role` AS role,
            COUNT(users.`id`) AS total_users
        FROM {$db->User} users
        GROUP BY users.`role`
        ORDER BY users.`role`
        ;";
        $roles = $db->fetchAll($sql);
        return $roles;
    }

    /**
     * Get the default mapping of roles from Omeka C to Omeka S.
     *
     * The name of each role is its id. The label is not used here.
     *
     * @return array
     */
    public function getDefaultMappingRoles()
    {
        static $mapping;

        if (empty($mapping)) {
            $mapping = $this->getMerged('mapping_roles');
        }

        return $mapping;
    }

    /**
     * Get the user mapping of roles from Omeka C to Omeka S.
     *
     * @return array
     */
    public function getMappingRoles()
    {
        $defaultMapping = $this->getDefaultMappingRoles();
        $mapping = $this->getParam('mapping_roles') ?: array();
        return $mapping + $defaultMapping;
    }
}
