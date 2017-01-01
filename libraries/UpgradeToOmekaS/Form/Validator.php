<?php

class UpgradeToOmekaS_Form_Validator extends Zend_Validate_Callback
{

    /**
     * Callback to check if a value is set and not empty.
     *
     * @param string $value The value to check.
     * @return boolean
     */
    static public function validateTrue($value)
    {
        return (boolean) $value;
    }

    static public function validateDateTimeZone($value)
    {
        $timeZones = DateTimeZone::listIdentifiers();
        return $value == 'UTC' || in_array($value, $timeZones);
    }

    /**
     * Callback to check the prefix.
     *
     * @param string $value The value to check.
     * @return boolean
     */
    static public function validatePrefix($value)
    {
        if (strlen($value) < 2) {
            return false;
        }
        // Should start with one letter and end with an underscore.
        return preg_match('/^[a-zA-Z0-9_]+_$/', $value);
    }

    /**
     * Callback to check the base dir (inside the server directory, different
     * from the Omeka Classic one, writable and empty).
     *
     * The checks are the same to create or to remove the dir, except that there
     * is an additionnal check to verify if it is empty.
     *
     * @internal Throw errors via flash.
     *
     * @param string $value The value to check.
     * @return boolean
     */
    static public function validateBaseDir($value)
    {
        $result = self::_validateBaseDir($value);

        if (!$result) {
            return false;
        }

        $flash = Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger');

        $path = rtrim($value, DIRECTORY_SEPARATOR);

        // Check if empty.
        // If the path doesn't exist, it can be created if needed, so no check.
        if (file_exists($path)) {
            $result = UpgradeToOmekaS_Common::isDirEmpty($path);
            if (!$result) {
                $flash->addMessage(__('The destination should be empty.'), 'error');
                return false;
            }
        }

        return true;
    }

    /**
     * Callback to check the base dir (inside the server directory, different
     * from the Omeka Classic one and writable).
     *
     * @internal Throw errors via flash.
     *
     * @param string $value The value to check.
     * @return boolean
     */
    static public function validateBaseDirToRemove($value)
    {
        $result = self::_validateBaseDir($value);
        return $result;
    }

    /**
     * Helper to check the base dir (inside the server directory, different from
     * the Omeka Classic one and writable).
     *
     * @internal Throw errors via flash.
     *
     * @param string $value The value to check.
     * @return boolean
     */
    static protected function _validateBaseDir($value)
    {
        $flash = Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger');

        if (strlen($value) < 2) {
            return false;
        }

        $path = rtrim($value, DIRECTORY_SEPARATOR);

        // Check if the path is inside the document root.
        $documentRoot = self::_getDocumentRoot();
        if (empty($documentRoot)) {
            $flash->addMessage(__('The document root of the server is unknown.') . ' ' . __('It can be defined in the file "security.ini" of the plugin.'), 'error');
            return false;
        }

        // Check if the directory is inside the document root.
        if (!self::_isInsideDir($documentRoot, $path)) {
            $flash->addMessage(__('The Omeka Semantic base dir should be inside the document root.'), 'error');
            return false;
        }

        // Check if the path is different from Omeka Classic.
        if ($path == BASE_DIR) {
            $flash->addMessage(__('The base dir of Omeka Semantic and Omeka Classic should be different.'), 'error');
            return false;
        }

        // Check if the file exists and if it is a dir.
        if (file_exists($path)) {
            if (!is_dir($path)) {
                $flash->addMessage(__('The base dir should be a directory.'), 'error');
                return false;
            }
        }
        // The path doesn't exist, so try to create it temporary.
        else {
            $result = UpgradeToOmekaS_Common::createDir($path);
            if (!$result) {
                $flash->addMessage(__('The base dir should be writable.'), 'error');
                return false;
            }
            // A security in case the user plays.
            if (realpath($path) == $path
                    && UpgradeToOmekaS_Common::isDirEmpty($path)
                ) {
                UpgradeToOmekaS_Common::removeDir($path, true);
            }
            // Next checks are useless since a dir can be created..
            return true;
        }

        // Check realpath (generally useless).
        $iniFile = dirname(dirname(dirname(dirname(__FILE__))))
            . DIRECTORY_SEPARATOR . 'security.ini';
        $settings = new Zend_Config_Ini($iniFile, 'upgrade-to-omeka-s');
        if ($settings->check->realpath == '1') {
            // Check if this is a true path.
            $absolutePath = realpath($path);
            if (empty($absolutePath) || $absolutePath != $path) {
                $flash->addMessage(__('The base dir should be an absolute real path.'), 'error');
                return false;
            }
        }
        // No check, so the absolute path is the path.
        else {
            $absolutePath = $path;
        }

        // Check rights.
        if (!is_writable($absolutePath)) {
            $flash->addMessage(__('The base dir should be writable.'), 'error');
            return false;
        }

        return true;
    }

    /**
     * Helper to get the document root of the server.
     *
     * @return string
     */
    static private function _getDocumentRoot()
    {
        return get_option('upgrade_to_omeka_s_document_root');
    }

    /**
     * Determine if a directory is a subdir of a base dir.
     *
     * @param string $baseDir
     * @param string $directory
     * @return boolean
     */
    static private function _isInsideDir($baseDir, $directory)
    {
        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $directory = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        return $baseDir != $directory && strpos($directory, $baseDir) === 0;
    }
}
