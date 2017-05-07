<?php
/**
 * UpgradeToOmekaS
 *
 * Upgrade automatically your archive from Omeka 2 to Omeka-S (metadata, files,
 * parameters, exhibits, common plugins, and compatibility layer for themes).
 *
 * @copyright Copyright Daniel Berthereau, 2017
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt
 * @package UpgradeToOmekaS
 */

/**
 * The UpgradeToOmekaS plugin.
 */
class UpgradeToOmekaSPlugin extends Omeka_Plugin_AbstractPlugin
{
    /**
     * @var array This plugin's hooks.
     */
    protected $_hooks = array(
        'initialize',
        'install',
        'uninstall',
        'uninstall_message',
        'define_acl',
    );

    /**
     * @var array This plugin's filters.
     */
    protected $_filters = array(
        'admin_navigation_global',
        'upgrade_omekas',
    );

    /**
     * @var array This plugin's options.
     */
    protected $_options = array(
        'upgrade_to_omeka_s_document_root' => BASE_DIR,
        'upgrade_to_omeka_s_service_down' => false,
        'upgrade_to_omeka_s_process_params' => '[]',
        'upgrade_to_omeka_s_process_status' => null,
        'upgrade_to_omeka_s_process_logs' => '[]',
        'upgrade_to_omeka_s_process_progress' => '[]',
    );

    /**
     * Initialize the plugin.
     */
    public function hookInitialize()
    {
        add_translation_source(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'languages');

        if (get_option('upgrade_to_omeka_s_service_down')) {
            $front = Zend_Controller_Front::getInstance();
            $front->registerPlugin(new UpgradeToOmekaS_Controller_Plugin_Down);
        }
    }

    /**
     * Installs the plugin.
     */
    public function hookInstall()
    {
        $this->_installOptions();

        // Simplify some check between the submission of the form and the launch
        // of the background process via command line, where the document root
        // may not be set.
        set_option('upgrade_to_omeka_s_document_root', $this->_getDocumentRoot());
    }

    /**
     * Uninstalls the plugin.
     */
    public function hookUninstall()
    {
        $this->_uninstallOptions();
    }

    /**
     * Add a message to the confirm form for uninstallation of the plugin.
     */
    public function hookUninstallMessage()
    {
        $processor = new UpgradeToOmekaS_Processor_Base();
        $status = $processor->getStatus();

        $isReset = $status == UpgradeToOmekaS_Processor_Abstract::STATUS_RESET;
        $previousParams = json_decode(get_option('upgrade_to_omeka_s_process_params'), true);
        $hasPreviousUpgrade = !empty($previousParams);

        echo '<p>' . __('Nothing will be changed when this plugin will be uninstalled.') . '</p>';

        if ($hasPreviousUpgrade) {
            echo '<p>';
            echo __('The remove of created tables and copied files can be safely and automatically done before uninstall, if wished.');
            echo '</p>';
            echo '<p>';
            echo __('The database itself won’t be removed.');
            echo '</p>';
            echo '<p>';
            if (!$isReset || empty($status)) {
                echo ' ' . __('%sReset the status%s, then click the button "Remove" that will appear.',
                    '<a class="medium blue button" href="' . url('/upgrade-to-omeka-s/index/reset') . '">', '</a>');
            }
            // The reset is already done.
            else {
                echo '<a class="medium red button" href="' . url('/upgrade-to-omeka-s/index/remove') . '" onclick="return confirm(' . "'" . __('Are you sure to remove all tables and files of Omeka S?') . "'" . ');">'
                    . __('Remove Tables and Files of Omeka Semantic')
                    . '</a>';
            }
            echo '</p>';
        }

        echo '<p>';
        echo  __('You can check the database (%s) and the directory (%s), or restore a backup.',
            $previousParams['database']['type'] == 'share'
                ? __('shared database')
                : $previousParams['database']['host'] . (empty($previousParams['database']['port']) ? '' : ':' . $previousParams['database']['port']) . ' / ' . $previousParams['database']['dbname'],
            $previousParams['base_dir']);
        echo '</p>';
    }

    /**
     * Define the plugin's access control list.
     *
     * @param array $args This array contains a reference to the zend ACL.
     */
    public function hookDefineAcl($args)
    {
        $acl = $args['acl'];
        $resource = 'UpgradeToOmekaS_ Index';

        // TODO This is currently needed for tests for an undetermined reason.
        if (!$acl->has($resource)) {
            $acl->addResource($resource);
        }

        // Hack to disable default actions.
        $acl->deny(null, $resource, array('show', 'add', 'edit', 'delete'));
        // Limit the upgrade to the super user only.
        $acl->deny('admin', $resource);
        $acl->allow('super', $resource);
        }

    /**
     * Add the plugin link to the admin bar.
     *
     * @param array $nav Navigation array.
     * @return array Filtered navigation array.
    */
    public function filterAdminNavigationGlobal($nav)
    {
        $link = array(
            'label' => __('Upgrade'),
            'uri' => url('upgrade-to-omeka-s'),
            'resource' => 'UpgradeToOmekaS_ Index',
            'privilege' => 'index',
        );
        $nav[] = $link;

        return $nav;
    }

    /**
     * Add the processors.
     *
     * @param array $processors Available processors.
     * @return array Filtered processors array.
     */
    public function filterUpgradeOmekas($processors)
    {
        // Keep the core at first place to keep order or processing.
        $baseProcessors = array();
        $baseProcessors['Core/Server'] = 'UpgradeToOmekaS_Processor_CoreServer';
        $baseProcessors['Core/Site'] = 'UpgradeToOmekaS_Processor_CoreSite';
        $baseProcessors['Core/Elements'] = 'UpgradeToOmekaS_Processor_CoreElements';
        $baseProcessors['Core/Records'] = 'UpgradeToOmekaS_Processor_CoreRecords';
        $baseProcessors['Core/Files'] = 'UpgradeToOmekaS_Processor_CoreFiles';
        $baseProcessors['Core/Tags'] = 'UpgradeToOmekaS_Processor_CoreTags';
        $baseProcessors['Core/Themes'] = 'UpgradeToOmekaS_Processor_CoreThemes';
        // This processor will be set last during process.
        $baseProcessors['Core/Checks'] = 'UpgradeToOmekaS_Processor_CoreChecks';

        // The compatibility layer.
        $baseProcessors['UpgradeToOmekaS'] = 'UpgradeToOmekaS_Processor_UpgradeToOmekaS';

        // Integrated plugins.
        $baseProcessors['DublinCoreExtended'] = 'UpgradeToOmekaS_Processor_DublinCoreExtended';
        $baseProcessors['MoreUserRoles'] = 'UpgradeToOmekaS_Processor_MoreUserRoles';
        // The first plugin to convert in order to keep ids and navigation.
        $baseProcessors['SimplePages'] = 'UpgradeToOmekaS_Processor_SimplePages';
        $baseProcessors['ExhibitBuilder'] = 'UpgradeToOmekaS_Processor_ExhibitBuilder';
        // $processors['ItemRelations'] = 'UpgradeToOmekaS_Processor_ItemRelations';

        // Replacement module.
        $baseProcessors['Tagging'] = 'UpgradeToOmekaS_Processor_Tagging';

        // Other plugins are dynamically added.
        $dir = dirname(__FILE__)
            . DIRECTORY_SEPARATOR . 'libraries'
            . DIRECTORY_SEPARATOR . 'UpgradeToOmekaS'
            . DIRECTORY_SEPARATOR . 'Processor';

        // From the plugin OAI-PMH Harvester.
        $filenames = UpgradeToOmekaS_Common::listFilesInDir($dir);
        $filenames = array_diff($filenames, array('Abstract.php', 'Base.php'));
        foreach ($filenames as $filename) {
            if (!preg_match('/^(.+)\.php$/', $filename, $matches)
                    || strpos($filename, 'Core') === 0
                ) {
                continue;
            }
            $processors[$matches[1]] = 'UpgradeToOmekaS_Processor_' . $matches[1];
        }

        return array_merge($baseProcessors, $processors);
    }

    /**
     * Helper to get the document root of the server.
     *
     * @return string
     */
    protected function _getDocumentRoot()
    {
        // Get the backend settings from the security.ini file.
        $iniFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'security.ini';
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
}
