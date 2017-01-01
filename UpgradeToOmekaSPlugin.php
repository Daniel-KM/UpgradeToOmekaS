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
        // Keep the core at first place to keep order.
        $baseProcessors = array();
        $baseProcessors['Core'] = array(
            'class' => 'UpgradeToOmekaS_Processor_Core',
            'description' => __('Omeka Core'),
        );

        // Integrated plugins.
//         $processors['Dropbox'] = array(
//             'class' => 'UpgradeToOmekaS_Processor_Dropbox',
//             'description' => __('Dropbox'),
//         );
//         $processors['Geolocation'] = array(
//             'class' => 'UpgradeToOmekaS_Processor_Geolocation',
//             'description' => __('Geolocation'),
//         );
//         $processors['ExhibitBuilder'] = array(
//             'class' => 'UpgradeToOmekaS_Processor_ExhibitBuilder',
//             'description' => __('Exhibit Builder'),
//         );
//         $processors['ItemRelations'] = array(
//             'class' => 'UpgradeToOmekaS_Processor_ItemRelations',
//             'description' => __('Item Relations'),
//         );
        $processors['MoreUserRoles'] = array(
            'class' => 'UpgradeToOmekaS_Processor_MoreUserRoles',
            'description' => __('More User Roles'),
        );
        $processors['SimplePages'] = array(
            'class' => 'UpgradeToOmekaS_Processor_SimplePages',
            'description' => __('Simple Pages'),
        );
        $processors['SocialBookmarking'] = array(
            'class' => 'UpgradeToOmekaS_Processor_SocialBookmarking',
            'description' => __('Social Bookmarking'),
        );

        // Upgraded or equivalent plugins in Omeka S.
//         $processors['CsvImport'] = array(
//             'class' => 'UpgradeToOmekaS_Processor_CsvImport',
//             'description' => __('Csv Import'),
//         );
//         $processors['SimpleVocab'] = array(
//             'class' => 'UpgradeToOmekaS_Processor_SimpleVocab',
//             'description' => __('Simple Vocab'),
//         );
//         $processors['ZoteroImport'] = array(
//             'class' => 'UpgradeToOmekaS_Processor_ZoteroImport',
//             'description' => __('Zotero Import'),
//         );

//         // Specific plugins not yet upgraded under Omeka S.
//         $processors['ArchiveFolder'] = array(
//             'class' => 'UpgradeToOmekaS_Processor_ArchiveFolder',
//             'description' => __('ArchiveFolder'),
//         );
//         $processors['Ark'] = array(
//             'class' => 'UpgradeToOmekaS_Processor_Ark',
//             'description' => __('Ark & Noid'),
//         );
//         $processors['BeamMeUpToInternetArchive'] = array(
//             'class' => 'UpgradeToOmekaS_Processor_BeamMeUpToInternerArchive',
//             'description' => __('Beam Me Up To Internet Archive'),
//         );
//         $processors['BeamMeUpToSoundcloud'] = array(
//             'class' => 'UpgradeToOmekaS_Processor_BeamMeUpToSoundCloud',
//             'description' => __('Beam Me Up To Sound Cloud'),
//         );
//         $processors['CleanUrl'] = array(
//             'class' => 'UpgradeToOmekaS_Processor_CleanUrl',
//             'description' => __('CleanUrl'),
//         );
//         $processors['Coins'] = array(
//             'class' => 'UpgradeToOmekaS_Processor_CollectionTree',
//             'description' => __('Collection Tree'),
//         );
//         $processors['CollectionTree'] = array(
//             'class' => 'UpgradeToOmekaS_Processor_CollectionTree',
//             'description' => __('Collection Tree'),
//         );
//         $processors['Commenting'] = array(
//             'class' => 'UpgradeToOmekaS_Processor_Commenting',
//             'description' => __('Commenting'),
//         );
//         $processors['Contribution'] = array(
//             'class' => 'UpgradeToOmekaS_Processor_Contribution',
//             'description' => __('Contribution'),
//         );
//         $processors['CsvImportPlus'] = array(
//             'class' => 'UpgradeToOmekaS_Processor_CsvImportPlus',
//             'description' => __('Csv Import Plus'),
//         );
//         $processors['GuestUser'] = array(
//             'class' => 'UpgradeToOmekaS_Processor_GuestUser',
//             'description' => __('Guest User'),
//         );
//         $processors['HistoryLog'] = array(
//             'class' => 'UpgradeToOmekaS_Processor_HistoryLog',
//             'description' => __('History Log'),
//         );
//         $processors['MultiCollections'] = array(
//             'class' => 'UpgradeToOmekaS_Processor_MultiCollections',
//             'description' => __('Multi Collections'),
//         );
//         $processors['NeatlineTime'] = array(
//             'class' => 'UpgradeToOmekaS_Processor_NeatlineTime',
//             'description' => __('Neatline Time'),
//         );
//         $processors['OpenlayersZoom'] = array(
//             'class' => 'UpgradeToOmekaS_Processor_OpenLayersZoom',
//             'description' => __('Open Layers Zoom'),
//         );
//         $processors['Rating'] = array(
//             'class' => 'UpgradeToOmekaS_Processor_Rating',
//             'description' => __('Rating'),
//         );
//         $processors['Scripto'] = array(
//             'class' => 'UpgradeToOmekaS_Processor_Scripto',
//             'description' => __('Scripto'),
//         );
//         $processors['SimpleContact'] = array(
//             'class' => 'UpgradeToOmekaS_Processor_SimpleContact',
//             'description' => __('Simple Contact'),
//         );
//         $processors['SimpleVocabPlus'] = array(
//             'class' => 'UpgradeToOmekaS_Processor_SimpleVocabPlus',
//             'description' => __('Simple Vocab Plus'),
//         );
//         $processors['Stats'] = array(
//             'class' => 'UpgradeToOmekaS_Processor_Stats',
//             'description' => __('Stats'),
//         );
//         $processors['Tagging'] = array(
//             'class' => 'UpgradeToOmekaS_Processor_Tagging',
//             'description' => __('Tagging'),
//         );
//         $processors['Taxonomy'] = array(
//             'class' => 'UpgradeToOmekaS_Processor_Taxonomy',
//             'description' => __('Taxonomy'),
//         );

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
