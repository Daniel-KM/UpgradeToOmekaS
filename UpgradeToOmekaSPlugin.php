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
        'upgrade_to_omeka_s_process_status' => null,
        'upgrade_to_omeka_s_process_logs' => '[]',
        'upgrade_to_omeka_s_process_url' => '',
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
        echo '<p>' . __('Nothing will be changed when this plugin will be uninstalled.') . '</p>';
        echo '<p>' . __('You may need to clean the database and the files or to restore a backup.') . '</p>';
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
        $baseProcessors['Core'] = 'UpgradeToOmekaS_Processor_Core';

        // Integrated plugins.
        $baseProcessors['DublinCoreExtended'] = 'UpgradeToOmekaS_Processor_DublinCoreExtended';
        // The first plugin to convert in order to keep ids and navigation.
        $baseProcessors['SimplePages'] = 'UpgradeToOmekaS_Processor_SimplePages';
        // $processors['Dropbox'] = 'UpgradeToOmekaS_Processor_Dropbox';
        // $processors['ExhibitBuilder'] = 'UpgradeToOmekaS_Processor_ExhibitBuilder';
        // $processors['ItemRelations'] = 'UpgradeToOmekaS_Processor_ItemRelations';
        $processors['MoreUserRoles'] = 'UpgradeToOmekaS_Processor_MoreUserRoles';

        // Upgraded or equivalent plugins in Omeka S.
        // $processors['CsvImport'] = 'UpgradeToOmekaS_Processor_CsvImport';
        $processors['EmbedCodes'] = 'UpgradeToOmekaS_Processor_EmbedCodes';
        // $processors['Geolocation'] = 'UpgradeToOmekaS_Processor_Geolocation';
        // $processors['SimpleVocab'] = 'UpgradeToOmekaS_Processor_SimpleVocab';
        $processors['SocialBookmarking'] = 'UpgradeToOmekaS_Processor_SocialBookmarking';
        // $processors['ZoteroImport'] = 'UpgradeToOmekaS_Processor_ZoteroImport';

        // Specific plugins not yet upgraded under Omeka S.
        // $processors['ArchiveFolder'] = 'UpgradeToOmekaS_Processor_ArchiveFolder';
        // $processors['Ark'] = 'UpgradeToOmekaS_Processor_Ark';
        // $processors['BeamMeUpToInternetArchive'] = 'UpgradeToOmekaS_Processor_BeamMeUpToInternerArchive';
        // $processors['BeamMeUpToSoundcloud'] = 'UpgradeToOmekaS_Processor_BeamMeUpToSoundCloud';
        // $processors['CleanUrl'] = 'UpgradeToOmekaS_Processor_CleanUrl';
        // $processors['Coins'] = 'UpgradeToOmekaS_Processor_CollectionTree';
        // $processors['CollectionTree'] = 'UpgradeToOmekaS_Processor_CollectionTree';
        // $processors['Commenting'] = 'UpgradeToOmekaS_Processor_Commenting';
        // $processors['Contribution'] = 'UpgradeToOmekaS_Processor_Contribution';
        // $processors['CsvImportPlus'] = 'UpgradeToOmekaS_Processor_CsvImportPlus';
        // $processors['GuestUser'] = 'UpgradeToOmekaS_Processor_GuestUser';
        // $processors['HistoryLog'] = 'UpgradeToOmekaS_Processor_HistoryLog';
        // $processors['MultiCollections'] = 'UpgradeToOmekaS_Processor_MultiCollections';
        // $processors['NeatlineTime'] = 'UpgradeToOmekaS_Processor_NeatlineTime';
        // $processors['OpenlayersZoom'] = 'UpgradeToOmekaS_Processor_OpenLayersZoom';
        // $processors['Rating'] = 'UpgradeToOmekaS_Processor_Rating';
        // $processors['Scripto'] = 'UpgradeToOmekaS_Processor_Scripto';
        // $processors['SimpleContact'] = 'UpgradeToOmekaS_Processor_SimpleContact';
        // $processors['SimpleVocabPlus'] = 'UpgradeToOmekaS_Processor_SimpleVocabPlus';
        // $processors['Stats'] = 'UpgradeToOmekaS_Processor_Stats';
        // $processors['Tagging'] = 'UpgradeToOmekaS_Processor_Tagging';
        // $processors['Taxonomy'] = 'UpgradeToOmekaS_Processor_Taxonomy';
        // $processors['UniversalViewer'] = 'UpgradeToOmekaS_Processor_UniversalViewer';

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
