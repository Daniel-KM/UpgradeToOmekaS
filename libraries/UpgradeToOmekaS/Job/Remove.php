<?php

/**
 * UpgradeToOmekaS_Job_Remove class
 *
 * @package UpgradeToOmekaS
 */
class UpgradeToOmekaS_Job_Remove extends UpgradeToOmekaS_Job_Abstract
{
    const QUEUE_NAME = 'upgrade_to_omeka_s_remove';

    /**
     * Performs the remove task.
     */
    public function perform()
    {
        // Set current user for this long running job.
//        Zend_Registry::get('bootstrap')->bootstrap('Acl');

        // Reset the status of the site.
        set_option('upgrade_to_omeka_s_process_status', Process::STATUS_IN_PROGRESS);
        set_option('upgrade_to_omeka_s_process_progress', json_encode(array()));

        $this->_log(__('Starting remove of Omeka Semantic.'),
            Zend_Log::WARN);

        $user = $this->getUser();
        $params = $this->_params;

        //Add the security ini params.
        $security = $this->_getSecurityIni()->toArray();
        $params['checks'] = $security['check'];

        // This constant may be needed in some scripts fetched from Omeka S.
        defined('OMEKA_PATH') or define('OMEKA_PATH', $params['base_dir']);

        $processor = new UpgradeToOmekaS_Processor_CoreSite();

        // The params should be set now, because there is the processing
        // parameter.
        $processor->setParams($params);

        //Check the database before.
        try {
            $target = $processor->getTarget();
            if (empty($target)) {
                throw new UpgradeToOmekaS_Exception(
                    __('Unable to access to the target database (%s).',
                        $params['database']['type'] == 'shared'
                            ? __('shared database')
                            : $params['database']['host'] . (empty($params['database']['port']) ? '' : ':' . $params['database']['port']) . ' / ' . $params['database']['dbname'],
                        $params['base_dir']));
            }
        } catch (UpgradeToOmekaS_Exception $e) {
            $this->_processError(__('An error occurred during remove: %s', $e->getMessage()));
            return;
        } catch (Exception $e) {
            $this->_processError(__('An unknown error occurred access to the database: %s.',
                $e->getMessage()));
            return;
        }

        // Check the dir of the files.
        $baseDir = $params['base_dir'];
        $result = UpgradeToOmekaS_Form_Validator::validateBaseDirToRemove($baseDir);
        if (empty($result)) {
            $this->_processError(__('The base dir %s cannot be deleted.'));
            return;
        }

        // Remove the tables of the database.
        try {
            $result = $target->removeTables();
        } catch (Exception $e) {
            $this->_processError(__('An unknown error occurred during the drop of tables of the database: %s.',
                $e->getMessage()));
            return;
        }
        if (!$result) {
            $this->_processError(__('Unable to remove the tables of the database.',
                $e->getMessage()));
            return;
        }
        $this->_log(__('The Omeka Semantic tables of the database have been removed successfully.'),
            Zend_Log::DEBUG);

        // Remove the dir.
        UpgradeToOmekaS_Common::removeDir($baseDir, true);

        $this->_log(__('The files of Omeka Semantic have been removed successfully.'),
            Zend_Log::DEBUG);

        // Clean the options.
        set_option('upgrade_to_omeka_s_process_params', '[]');
        set_option('upgrade_to_omeka_s_process_progress', json_encode(array()));
        set_option('upgrade_to_omeka_s_process_status', null);

        $this->_log(__('The remove process of Omeka Semantic ended successfully.'),
            Zend_Log::INFO);
    }
}
