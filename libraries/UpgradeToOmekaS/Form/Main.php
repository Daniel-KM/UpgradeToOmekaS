<?php

class UpgradeToOmekaS_Form_Main extends Omeka_Form
{
    protected $_isConfirmation = false;

    public function init()
    {
        parent::init();

        $this->setAttrib('id', 'upgrade-to-omeka-s');
        $this->setMethod('post');

        $allowHardLink = $this->_allowHardLink();
        $databasePrefix = get_db()->prefix;
        $sizeDatabase = $this->_getSizeDatabase();

        // TODO Add the confirmation checkboxes only in a second step.
        $this->_isConfirmation = true;

        // TODO Manual select to skip some plugins?

        $validateTrue = array(array(
            'Callback',
            true,
            array(
                'callback' => array('UpgradeToOmekaS_Form_Validator', 'validateTrue'),
            ),
        ));

        $this->addElement('checkbox', 'check_backup_metadata', array(
            'label' => __('Confirm backup of metadata'),
            'description' => __('Check this option to confirm that you just made a backup of your metadata manually.'),
            'required' => true,
            'value' => false,
            'validators' => $validateTrue,
            'errorMessages' => array(__('You should confirm that the database is saved.')),
        ));

        $this->addElement('checkbox', 'check_backup_files', array(
            'label' => __('Confirm backup of files'),
            'description' => __('Check this option to confirm that you just made a backup of your files manually.'),
            'required' => true,
            'value' => false,
            'validators' => $validateTrue,
            'errorMessages' => array(__('You should confirm that the files are saved.')),
        ));

        $this->addElement('checkbox', 'check_backup_check', array(
            'label' => __('Confirm check of backups'),
            'description' => __('Check this option to confirm that you checked your previous backups manually.'),
            'required' => true,
            'value' => false,
            'validators' => $validateTrue,
            'errorMessages' => array(__('You should confirm that the backups are checked.')),
        ));

        $this->addElement('text', 'base_dir', array(
            'label' => __('Base Directory'),
            'description' => __('The abolute real path of the directory on the web server where Omeka Semantic will be installed.')
                . ' ' . __('This directory may exist or not, but it should be writable and empty.')
                . ' ' . __('It can be a subdir of Omeka Classic.'),
            'required' => true,
            'value' => '',
            'filters' => array(
                array('StringTrim', '\s'),
                // Remove the ending trailing directory separator.
                array('PregReplace', array('match' =>'/[\/\\\\\\s]+$/', 'replace' => '')),
                array('Callback', array(
                    'callback' => array('UpgradeToOmekaS_Form_Filter', 'filterRemoveDotSegments'))),
            ),
            'validators' => array(
                array(
                    'Callback',
                    true,
                    array(
                        'callback' => array('UpgradeToOmekaS_Form_Validator', 'validateBaseDir'),
                    ),
                ),
            ),
            'errorMessages' => array(__('The directory should be writable and empty.')),
        ));

        $multiOptions = array();
        if ($allowHardLink) {
            $multiOptions['hard_link'] = __('Hard Link (recommended)');
        }
        $multiOptions['copy'] = __('Copy');
        $multiOptions['dummy'] = __('Dummy files');
        $this->addElement('radio', 'files_type', array(
            'label' => __('Files'),
            'description'   => __('Define what to do with files of the archive (original files, thumbnails, etc.).')
                . ' ' . __('It is recommended to hard link them to avoid to waste space and to speed copy.')
                . ' ' . __('The dummy files can be used for testing purposes for common formats only.')
                . ' ' . __('Original files are never modified or deleted.')
                . ' ' . ($allowHardLink
                    ? __('It seems the server allows hard links (a second check will be done to avoid issues with mounted volumes).')
                    : __('The server does not support hard linking.')),
            'multiOptions' => $multiOptions,
            'required' => true,
            'value' => $allowHardLink ? 'hard_link' : 'copy',
            'class' => 'offset two columns',
        ));

        $this->addElement('radio', 'database_type', array(
            'label' => __('Database'),
            'description'   => __('Define the database Omeka S will be using.'),
            'multiOptions' => array(
                'separate' => __('Use a separate database (recommended)'),
                // 'share' => __('Share the database with a different prefix'),
                'share' => __('Share the database'),
            ),
            'required' => true,
            'value' => 'separate',
            'class' => 'offset two columns',
        ));
        $this->addElement('note', 'database_type_note_separate', array(
            'description' => __('When the database is separated, it should be created before process, then the parameters should be set below.')
            // . ' ' . __('"Port" and "prefix" are optional.'),
            . ' ' . __('"Port" is optional.'),
        ));
        $this->addElement('text', 'database_host', array(
            'label' => __('Host'),
            'filters' => array(array('StringTrim', '/\\\s')),
        ));
        $this->addElement('text', 'database_port', array(
            'label' => __('Port'),
            'filters' => array(array('StringTrim', '/\\\s')),
        ));
        $this->addElement('text', 'database_name', array(
            'label' => __('Name'),
            'filters' => array(array('StringTrim', '/\\\s')),
        ));
        $this->addElement('text', 'database_username', array(
            'label' => __('Username'),
            'filters' => array(array('StringTrim', '/\\\s')),
        ));
        $this->addElement('password', 'database_password', array(
            'label' => __('Password'),
        ));
        // Currently, Omeka S doesn't allow a table prefix.
        /*
        $this->addElement('text', 'database_prefix', array(
            'label' => __('Table Prefix'),
            'description'   => __('When the database is shared, the prefix of the tables should be different from the existing one ("%s").',
                    $databasePrefix ?: __('none'))
                . ' ' . __('It can be empty for a separate database.'),
            'filters' => array(array('StringTrim', '/\\\s')),
            'validators' => array(
                array(
                    'Callback',
                    true,
                    array(
                        'callback' => array('UpgradeToOmekaS_Form_Validator', 'validatePrefix'),
                    ),
                ),
            ),
            'errorMessages' => array(__('A prefix should have only alphanumeric characters, no space, and end with an underscore "_".')),
        ));
        */
        // An hidden value is set, but it won't be used until Omeka S allows it.
        $this->addElement('hidden', 'database_prefix', array(
            'value' => $databasePrefix == 'omekas_' ? 'omekasemantic_' : 'omekas_',
        ));
        $this->addElement('note', 'database_prefix_note', array(
            'description' => __('Currently, Omeka S doesn’t allow to use a prefix.'),
        ));

        if ($this->_isConfirmation) {
            $this->addElement('checkbox', 'check_database_confirm', array(
                'label' => __('Check of database size'),
                'description' => __('I confirm that the file system where the database is can manage %dMB of new data (two times the Omeka Classic one).', ceil($sizeDatabase * 2 / 1024 / 1024)),
                // 'required' => true,
                'value' => false,
                // 'validators' => $validateTrue,
                'errorMessages' => array(__('This check is required to confirm that you understand that some checks cannot be done automatically with some configurations.')),
            ));

            $this->addElement('checkbox', 'check_backup_confirm', array(
                'label' => __('Confirm'),
                'description' => __('I read the license (see the readme), I agree to it, and, like for any proprietary software, I confirm that I am solely and entirely responsible of what I do.'),
                // 'required' => true,
                'value' => false,
                // 'validators' => $validateTrue,
                'errorMessages' => array(__('This checkbox must be checked if you understand what you do.')),
            ));
        }

        $this->addDisplayGroup(
            array(
                'check_backup_metadata',
                'check_backup_files',
                'check_backup_check',
            ),
            'check_backup',
            array(
                'legend' => __('Backup of Metadata and Files'),
                'description' => __('The only possible issues for Omeka Classic are related to the lack of disk space for the file system, the temp directory or the database directory.')
                    . ' ' . __('An automatic check will be done before the confirmation, except for the file system where the database and the logs are.'),
        ));

        $this->addDisplayGroup(
            array(
                'base_dir',
            ),
            'general',
            array(
                'legend' => __('General Settings of Omeka Semantic'),
        ));

        $this->addDisplayGroup(
            array(
                'database_type',
                'database_type_note_separate',
                'database_prefix_note',
                'database_host',
                'database_port',
                'database_name',
                'database_username',
                'database_password',
                'database_prefix',
            ),
            'database',
            array(
                'legend' => __('Database for Omeka Semantic'),
        ));

        $this->addDisplayGroup(
            array(
                'files_type',
            ),
            'files',
            array(
                'legend' => __('Files for Omeka Semantic'),
        ));

        if ($this->_isConfirmation) {
            $this->addDisplayGroup(
                array(
                    'check_database_confirm',
                    'check_backup_confirm',
                ),
                'confirm',
                array(
                    'legend' => __('Confirmation'),
            ));
        }

        $this->applyOmekaStyles();
        $this->setAutoApplyOmekaStyles(false);

        $this->addElement('sessionCsrfToken', 'csrf_token');

        if ($this->_isConfirmation) {
            $this->addElement('submit', 'submit', array(
                'label' => __('Submit'),
                'class' => 'submit submit-big red',
                'decorators' => (array(
                    'ViewHelper',
                    array('HtmlTag', array('tag' => 'div', 'class' => 'field')))),
            ));
        }
        // SImple check.
        else {
            $this->addElement('submit', 'check_params', array(
                'label' => __('Check Parameters'),
                'class' => 'submit submit-big',
                'decorators' => (array(
                    'ViewHelper',
                    array('HtmlTag', array('tag' => 'div', 'class' => 'field')))),
            ));
        }
    }

    /**
     * Set if the form is a confirmation one.
     */
    public function setConfirmation($value)
    {
        $this->_isConfirmation = (boolean) $value;
    }

    /**
     * Validate the form
     *
     * @todo Move checks from the Core.
     *
     * @param  array $data
     * @throws Zend_Form_Exception
     * @return bool
     */
    public function isValid($data)
    {
        $valid = parent::isValid($data);

        $databaseType = $this->getElement('database_type');
        switch ($databaseType->getValue()) {
            case 'separate':
                foreach (array(
                        'database_host' => __('host'),
                        'database_username' => __('user name'),
                        'database_name' => __('name'),
                    ) as $name => $text) {
                    $element = $this->getElement($name);
                    $value = $element->getValue();
                    if (empty($value)) {
                        $message = __('The database parameter "%s" should be filled when the database is separate.', $text);
                        $element->addError($message);
                        $valid = false;
                    }
                }
                break;

            case 'share':
                $databasePrefix = $this->getElement('database_prefix');
                if ($databasePrefix->getValue() == get_db()->prefix) {
                    $message = __('In a shared database, the prefix cannot be the same for Omeka Classic and Omeka Semantic.');
                    $databasePrefix->addError($message);
                    $valid = false;
                }
                break;

            default:
                $message = __('Value %s is not allowed as database type.');
                $databaseType->addError($message);
                $valid = false;
                break;
        }

        return $valid;
    }

    /**
     * Helper to get the a value from db.ini.
     *
     * @return string
     */
    protected function _getDatabaseValue($name)
    {
        $db = get_db();
        $config = $db->getAdapter()->getConfig();
        return isset($config[$name]) ? $config[$name] : '';
    }

    /**
     * Check if a hard link can be created.
     *
     * @return string
     */
    protected function _allowHardLink()
    {
        // A test is done inside the thumbnails folder, always writable.
        $base = FILES_DIR . DIRECTORY_SEPARATOR . 'thumbnails';
        $target = $base . DIRECTORY_SEPARATOR . 'index.html';
        $link = $base . DIRECTORY_SEPARATOR . md5(rtrim(strtok(substr(microtime(), 2), ' '), '0'));
        $result = link($target, $link);
        if ($result) {
            unlink($link);
        }
        return $result;
    }

    /**
     * Get the current size of the Omeka Classic database.
     *
     * @return integer
     */
    protected function _getSizeDatabase()
    {
        try {
            $db = get_db();
            $config = $db->getAdapter()->getConfig();
            $dbName = $config['dbname'];
            if (empty($dbName)) {
                return 0;
            }

            $sql = 'SELECT SUM(data_length + index_length + data_free) AS "Size"
            FROM information_schema.TABLES
            WHERE table_schema = "' . $dbName . '";';
            return $db->fetchOne($sql);
        } catch (Exception $e) {
            return 0;
        }
    }
}
