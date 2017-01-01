<?php

/**
 * Upgrade Core Themes to Omeka S.
 *
 * @package UpgradeToOmekaS
 */
class UpgradeToOmekaS_Processor_CoreThemes extends UpgradeToOmekaS_Processor_Abstract
{
    public $pluginName = 'Core / Themes';
    public $minVersion = '2.3.1';
    public $maxVersion = '2.5';

    public $module = array(
        'type' => 'integrated',
    );

    /**
     * This is a module installed via the core.
     *
     * @var array
     */
    protected $_module = array(
        'name' => 'Compatibility Layer For Omeka',
        'version' => '3.0.1',
        'url' => 'https://github.com/Daniel-KM/CompatibilityLayer4Omeka/releases/download/v%s/CompatibilityLayer4Omeka.zip',
        'size' => 10000,
        'md5' => '2b1919eabef364f14cbe9cdc71eb4467',
        'type' => 'upgrade',
        'partial' => true,
        'install' => array(),
    );

    /**
     * List of methods to process for the upgrade.
     *
     * @var array
     */
    public $processMethods = array(
        '_installCompatibiltyLayer',
        '_copyAssets',
        '_copyThemes',
        '_upgradeThemesParams',
        '_upgradeThemes',
        // TODO Remove empty folders (simple-pages...).
    );

    /**
     * Initialized during init via libraries/data/mapping_theme_folders.php.
     *
     * @var array
     */
    // public $mapping_theme_folders = array();

    /**
     * Initialized during init via libraries/data/mapping_theme_files.php.
     *
     * @var array
     */
    // public $mapping_theme_files = array();

    protected function _init()
    {
        $dataDir = dirname(dirname(dirname(dirname(__FILE__))))
            . DIRECTORY_SEPARATOR . 'libraries'
            . DIRECTORY_SEPARATOR . 'data';

            $script = $dataDir
                . DIRECTORY_SEPARATOR . 'mapping_theme_folders.php';
            $this->mapping_theme_folders = require $script;

            $script = $dataDir
                . DIRECTORY_SEPARATOR . 'mapping_theme_files.php';
            $this->mapping_theme_files = require $script;
        }

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

    protected function _installCompatibiltyLayer()
    {
        // TODO To be removed.
        return;

        $module = $this->module;
        $this->module = $this->_module;

        $this->_installModule();

        $this->_module = $module;

        $this->_log('[' . __FUNCTION__ . ']: ' . __('The compatibility layer translates only standard functions: check your theme if there are custom ones.'),
            Zend_Log::INFO);
    }

    protected function _copyAssets()
    {
        $this->_log('[' . __FUNCTION__ . ']: ' . __('In Omeka S, all assets used for the themes (logo...) are managed in one place.'),
            Zend_Log::INFO);

        // There are usually few files in the folder.
        $sourceDir = FILES_DIR . DIRECTORY_SEPARATOR . 'theme_uploads';
        $files = UpgradeToOmekaS_Common::listFilesInDir($sourceDir);

        // Remove the file "index.html" from the list of files.
        $key = array_search('index.html', $files);
        if ($key !== false) {
            unset($files[$key]);
        }

        $totalRecords = count($files);
        if (empty($totalRecords)) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('The current site has no asset.'),
                Zend_Log::INFO);
            return;
        }

        // Recheck for the symlinks, that can be bypassed.
        $result = UpgradeToOmekaS_Common::containsSymlinks($sourceDir);
        if ($result) {
            $checks = $this->getParam('checks');
            if (!empty($checks['symlinks'])) {
                throw new UpgradeToOmekaS_Exception(
                    __('There are symbolic links inside the directory "files/themes_uploads".')
                        . ' ' . __('Some errors may occur in some cases.')
                        . ' ' . __('This precheck may be bypassed via "security.ini".'));
            }
            // Warn about the copy.
            else {
                $this->_log('[' . __FUNCTION__ . ']: ' . __('The directory "files/themes_uploads" contain symbolic links.')
                        . ' ' . __('Some errors may occur in some cases.')
                        . ' ' . __('You need to check yourself if all files were copied.'),
                    Zend_Log::WARN);
            }
        }

        $this->_progress(0, $totalRecords);

        // Prepare the destination directory.
        $destinationDir = $this->getParam('base_dir')
            . DIRECTORY_SEPARATOR . 'files'
            . DIRECTORY_SEPARATOR . 'asset';
        UpgradeToOmekaS_Common::createDir($destinationDir);

        // Prepare the asset.
        $targetDb = $this->getTarget()->getDb();

        // Process copy.
        $i = 0;
        foreach ($files as $filename) {
            $this->_progress(++$i);
            // Do a true copy, because they are not an archive and may change.
            $source = $sourceDir . DIRECTORY_SEPARATOR . $filename;
            $destination = $destinationDir . DIRECTORY_SEPARATOR . $filename;
            $result = copy($source, $destination);
            if (!$result) {
                throw new UpgradeToOmekaS_Exception(
                    __('The copy of the file "%s" to the directory "%s" failed.',
                        $source, $destinationDir . DIRECTORY_SEPARATOR));
            }

            $detect = new Omeka_File_MimeType_Detect($source);
            $mimeType = $detect->detect();

            // Create the asset after each copy in case of an error.
            $toInsert = array();
            $toInsert['id'] = null;
            $toInsert['name'] = $filename;
            $toInsert['media_type'] = $mimeType;
            $toInsert['storage_id'] = pathinfo($filename, PATHINFO_FILENAME);
            $toInsert['extension'] = pathinfo($filename, PATHINFO_EXTENSION);
            $targetDb->insert('asset', $toInsert);
        }

        $this->_log('[' . __FUNCTION__ . ']: ' . ($totalRecords <= 1
                ? __('One asset has been upgraded.')
                : __('%d assets have been upgraded.', $totalRecords)),
            Zend_Log::INFO);
    }

    protected function _copyThemes()
    {
        $this->_log('[' . __FUNCTION__ . ']: ' . __('In Omeka S, the home page is the first navigation link.'),
            Zend_Log::INFO);

        $source = PUBLIC_THEME_DIR;
        $destination = $this->getParam('base_dir')
            . DIRECTORY_SEPARATOR . 'themes';

        // Recheck for the symlinks, that can be bypassed.
        $result = UpgradeToOmekaS_Common::containsSymlinks(PUBLIC_THEME_DIR);
        $result = $result && UpgradeToOmekaS_Common::containsSymlinks(PLUGIN_DIR);
        if ($result) {
            $checks = $this->getParam('checks');
            if (!empty($checks['symlinks'])) {
                throw new UpgradeToOmekaS_Exception(
                    __('There are symbolic links inside the directory of themes.')
                        . ' ' . __('Some errors may occur in some cases.')
                        . ' ' . __('This precheck may be bypassed via "security.ini".'));
            }
            // Warn about the copy.
            else {
                $this->_log('[' . __FUNCTION__ . ']: ' . __('The themes contains symbolic links.')
                        . ' ' . __('Some errors may occur in some cases.')
                        . ' ' . __('You need to check yourself if all files were copied.'),
                    Zend_Log::WARN);
            }
        }

        $sourceDefaultTheme = BASE_DIR
            . DIRECTORY_SEPARATOR . 'application'
            . DIRECTORY_SEPARATOR . 'views'
            . DIRECTORY_SEPARATOR . 'scripts';

        $specialSubPlugins = array('css', 'font', 'fonts', 'javascripts', 'images', 'common');

        $themes = UpgradeToOmekaS_Common::listDirsInDir($source);
        // Replace the default theme with the Omeka Classic default theme.
        $keyClassic = array_search('default', $themes);
        if ($keyClassic !== false) {
            $themes[$keyClassic] = 'classic';
        }

        $this->_progress(0, count($themes));

        $processors = $this->getProcessors();
        $i = 0;
        foreach ($themes as $theme) {
            $this->_progress(++$i);

            // First, copy the default scripts from Omeka in each theme, since
            // they are used when there is no script with the same name in the
            // theme.
            $destinationTheme = $destination . DIRECTORY_SEPARATOR . $theme;
            UpgradeToOmekaS_Common::copyDir(
                $sourceDefaultTheme,
                $destinationTheme,
                true,
                array('php' => 'phtml'));

            // Second, copy the default scripts from the shared and public views
            // of each plugin in each theme, for the same reason.
            foreach ($processors as $processor) {
                if ($processor->isCore()) {
                    continue;
                }
                $sourceDefaultPlugin = PLUGIN_DIR
                    . DIRECTORY_SEPARATOR . $processor->pluginName
                    . DIRECTORY_SEPARATOR . 'views';
                if (!file_exists($sourceDefaultPlugin) && !is_dir($sourceDefaultPlugin)) {
                    continue;
                }
                $viewPluginName = str_replace('_', '-', Inflector::underscore($processor->pluginName));
                foreach (array('shared', 'public') as $sub) {
                    $sourcePluginSub = $sourceDefaultPlugin
                        . DIRECTORY_SEPARATOR . $sub;
                    if (!file_exists($sourcePluginSub) || !is_dir($sourcePluginSub)) {
                        continue;
                    }
                    $subdirsPlugins = UpgradeToOmekaS_Common::listDirsInDir($sourcePluginSub);
                    // Manage special folders.
                    foreach ($subdirsPlugins as $subdirPlugin) {
                        $destinationSubdirPlugin = $destinationTheme;
                        if (!in_array($subdirPlugin, $specialSubPlugins)) {
                            $destinationSubdirPlugin .= DIRECTORY_SEPARATOR . $viewPluginName;
                        }
                        $destinationSubdirPlugin .= DIRECTORY_SEPARATOR . $subdirPlugin;
                        UpgradeToOmekaS_Common::copyDir(
                            $sourcePluginSub . DIRECTORY_SEPARATOR . $subdirPlugin,
                            $destinationSubdirPlugin,
                            true,
                            array('php' => 'phtml'));
                    }
                }
            }

            // Third, overwrite them with the true files of the theme.
            // The copy of the "classic" theme (no source) is silently bypassed.
            UpgradeToOmekaS_Common::copyDir(
                $source . DIRECTORY_SEPARATOR . $theme,
                $destinationTheme,
                true,
                array('php' => 'phtml'));

            // Add a default theme ini if needed.
            $destinationIni = $destinationTheme
                . DIRECTORY_SEPARATOR . 'theme.ini';
            if (!file_exists($destinationIni)) {
                $title = ucwords($theme);
                if ($theme == 'classic') {
                    $author = 'Roy Rosenzweig Center for History and New Media';
                    $description = __('Default public theme of Omeka Classic');
                    $license = 'GPLv3';
                }
                // Theme unknown.
                else {
                    $author = __('[Unknown]');
                    $description = __('[No description]');
                    $license = __('[Unknown]');
                }

                $output = <<<OUTPUT
;;;;;;;
; Theme Settings
;;;;;;;

[theme]
author = "$author"
title = "$title"
description = "$description"
license = "$license"
website = "http://omeka.org"
support_link = "http://omeka.org/forums/forum/themes-and-public-display"
omeka_minimum_version="2.0"
omeka_target_version="2.5"
version="2.0"

OUTPUT;

                $result = file_put_contents($destinationIni, $output);
            }
        }

        $this->_log('[' . __FUNCTION__ . ']: ' . __('All themes (%d) have been copied with default scripts into Omeka S.',
            count($themes))
                . ' ' . __('You can find new ones on %shttps://omeka.org/s%s.', '<a href="omeka.org/s" target="_blank">', '</a>'),
            Zend_Log::INFO);
    }

    protected function _upgradeThemesParams()
    {
        $siteId = $this->getSiteId();

        $db = $this->_db;
        $target = $this->getTarget();
        $targetDb = $target->getDb();

        $select = $db->getTable('Option')->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->from(array(), array('name', 'value'))
            ->where('`name` LIKE "theme_%_options"')
            ->order('name');
        $options = $db->fetchPairs($select);

        $select = $targetDb->select()
            ->from('asset', array(
                'name' => new Zend_Db_Expr('CONCAT(`storage_id`, ".", `extension`)'),
                'id' => 'id',
            ))
            ->order('name');
        $assets = $targetDb->fetchPairs($select);

        // Extract the name and the values.
        $toInserts = array();
        foreach ($options as $key => $values) {
            $name = substr($key, strlen('theme_'), strlen($key) - strlen('theme__options'));
            if (empty($name)) {
                continue;
            }

            $values = unserialize($values);
            if (empty($values)) {
                continue;
            }

            // Check if there is an asset to upgrade the logo, else remove.
            if (!empty($values['logo'])) {
                $values['logo'] = isset($assets[$values['logo']])
                    ? $assets[$values['logo']]
                    : '';
            }

            $nameSetting = 'theme_settings_' . $name;
            $target->saveSiteSetting($nameSetting, $values, $siteId);
        }

        $this->_log('[' . __FUNCTION__ . ']: ' . __('The options of the themes have been upgraded as site settings.'),
            Zend_Log::INFO);
    }

    protected function _upgradeThemes()
    {
        $source = PUBLIC_THEME_DIR;
        $destination = $this->getParam('base_dir')
            . DIRECTORY_SEPARATOR . 'themes';

        $themes = UpgradeToOmekaS_Common::listDirsInDir($destination);
        if (empty($themes)) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('No theme to upgrade.'),
                Zend_Log::INFO);
            return;
        }
        $this->_progress(0, count($themes));

        $i = 0;
        foreach ($themes as $theme) {
            $this->_progress(++$i);
            $this->_log('[' . __FUNCTION__ . ']: ' . __('Processing upgrade of theme "%s".',
                    $theme),
                Zend_Log::DEBUG);
            $this->_upgradeTheme($destination . DIRECTORY_SEPARATOR . $theme);
        }

        $this->_log('[' . __FUNCTION__ . ']: ' . __('All files have been moved and renamed according to Omeka S views.')
            . ' ' . __('There is now a main file, "layout.phtml".'),
            Zend_Log::INFO);

        $this->_log('[' . __FUNCTION__ . ']: ' . __('All themes (%d) were upgraded.', count($themes))
                . ' ' . __('It is important to check them.'),
            Zend_Log::NOTICE);
    }

    protected function _upgradeTheme($path)
    {
        $this->_upgradeConfigTheme($path);
        $this->_addStandardFiles($path);
        $this->_reorganizeFolders($path);
        $this->_renameFiles($path);
        $this->_replaceImagesByImgInCss($path);
    }

    /**
     * Helper to convert theme.ini and config.ini of a theme.
     *
     * @param string $path
     * @return void
     */
    protected function _upgradeConfigTheme($path)
    {
        $name = basename($path);
        unset($this->_versionTheme);

        // "theme.ini" and "config.ini" are merged into "config/theme.ini".

        // No process when the destination exists (default classic theme).
        $destination = $path
            . DIRECTORY_SEPARATOR . 'config'
            . DIRECTORY_SEPARATOR . 'theme.ini';
        if (file_exists($destination)) {
            $this->_versionTheme = '2.0';
            return;
        }

        $source = $path . DIRECTORY_SEPARATOR . 'theme.ini';
        $sourceIni = file_exists($source)
            ? new Zend_Config_Ini($source, 'theme')
            : new Zend_Config(array());

        // Kept for next method.
        $this->_versionTheme = $sourceIni->version;

        $output = <<<OUTPUT
;;;;;;;
; Theme Settings
;;;;;;;

;;;;;;;
; Upgraded from the theme "{$name}" of Omeka Classic on {$this->getDatetime()}.
; See https://github.com/Daniel-KM/UpgradeToOmekaS
;;;;;;;

[info]
name = "{$sourceIni->title} (upgraded)"
version = "{$sourceIni->version}-upgraded"
author = "{$sourceIni->author}"
description = "{$sourceIni->description} [upgraded the {$this->getDatetime()}]"
theme_link = "{$sourceIni->support_link}"
author_link = "{$sourceIni->website}"
license = "{$sourceIni->license}"
;omeka_minimum_version = "{$sourceIni->omeka_minimum_version}"
;omeka_target_version = "{$sourceIni->omeka_target_version}"
helpers[] = ThemeHelperOne
helpers[] = ThemeHelperTwo


OUTPUT;

        $source = $path . DIRECTORY_SEPARATOR . 'config.ini';
        if (file_exists($source)) {
            $sourceIni = new Zend_Config_Ini($source, null, true);

            $elements = array();
            $commented = array();
            $renamed = array();
            if (isset($sourceIni->config) && $sourceIni->config->count()) {
                $mappingFormElements = array(
                    'file' => 'Omeka\Form\Element\Asset',
                    'textarea' => 'Omeka\Form\Element\HtmlTextarea',
                    // Zend standard.
                    'checkbox' => 'Zend\Form\Element\Checkbox',
                    'radio' => 'Zend\Form\Element\Radio',
                    'select' => 'Zend\Form\Element\Select',
                    'text' => 'Zend\Form\Element\Text',
                );
                foreach ($sourceIni->config as $key => $element) {
                    // Add the name.
                    $element->name = $key;

                    // Convert the description into info (simpler via array).
                    if (isset($element->options->description)) {
                        $element = $element->toArray();
                        $element['options']['info'] = $element['options']['description'];
                        unset($element['options']['description']);
                        $element = new Zend_Config($element, true);
                    }

                    // Convert options values to attributes values.
                    if (isset($element->options->value)) {
                        $element = $element->toArray();
                        $element['attributes']['value'] = $element['options']['value'];
                        unset($element['options']['value']);
                        $element = new Zend_Config($element, true);
                    }

                    $type = $element->type;
                    // Update the class if possible.
                    if (isset($mappingFormElements[$type])) {
                        // Update type.
                        $element->type = $mappingFormElements[$type];
                        // Manage updated elements.
                        switch ($key) {
                            case 'logo':
                                $element->options->label = 'Logo';
                                unset($element->options->description);
                                unset($element->options->validators);
                                break;
                            case 'footer_text':
                                $renamed[$key] = 'footer';
                                $key = $renamed[$key];
                                $element->type = $mappingFormElements['textarea'];
                                $element->options->label = 'Footer Content';
                                $element->options->info = 'HTML content to appear in the footer';
                                break;
                        }
                        // TODO Check validators and constraints or comment them.
                        $elements[$key] = $element;
                    }
                    // Save as comment.
                    else {
                        $commented[$key] = $element;
                    }
                }

                if ($commented) {
                    $sourceIni->config = new Zend_Config(array(
                        'elements' => $elements,
                        ';elements' => $commented,
                    ));
                }
                // No commented.
                else {
                    $sourceIni->config = new Zend_Config(array(
                        'elements' => $elements,
                    ));
                }
            }

            // Manage next sections.
            if ($commented && isset($sourceIni->groups)) {
                foreach ($sourceIni->groups as $key => $element) {
                    foreach ($element->elements as $k => $value) {
                        if (isset($renamed[$value])) {
                            $element->elements->$k = $renamed[$value];
                        }
                        elseif (isset($commented[$value]) || !isset($elements[$value])) {
                            unset($element->elements->$k);
                        }
                    }
                }
            }

            if (isset($sourceIni->plugins)) {
                $sourceIni->modules = $sourceIni->plugins;
                unset($sourceIni->plugins);
            }

            $writer = new Zend_Config_Writer_Ini(array(
                'config' => $sourceIni,
                'renderWithoutSections' => false,
            ));
            $output .= $writer->render();
        }
        // Set a default section when there is no config.
        else {
            $output .= <<<OUTPUT
[config]
elements.nav_depth.name = "nav_depth"
elements.nav_depth.type = "Number"
elements.nav_depth.options.label = "Top Navigation Depth"
elements.nav_depth.options.info = "Maximum number of levels to show in the site's top navigation bar. Set to 0 to show all levels."
elements.nav_depth.attributes.min = 0
elements.nav_depth.attributes.value = 0

elements.use_advanced_search.type = "Zend\Form\Element\Checkbox"
elements.use_advanced_search.options.label = "Use Advanced Site-wide Search"
elements.use_advanced_search.options.info = "Check this box if you wish to allow users to search your whole site by record (i.e. item, item set, media)."
elements.use_advanced_search.value = "0"
elements.use_advanced_search.name = "use_advanced_search"

elements.logo.name = "logo"
elements.logo.type = "Omeka\Form\Element\Asset"
elements.logo.options.label = "Logo"

elements.footer.name = "footer"
elements.footer.type = "Omeka\Form\Element\HtmlTextarea"
elements.footer.options.label = "Footer Content"
elements.footer.options.info = "HTML content to appear in the footer"
elements.footer.attributes.value = "Powered by Omeka S"

OUTPUT;
        }

        $result = UpgradeToOmekaS_Common::createDir(dirname($destination));
        // Check if the output is parsable.
        try {
            $result = file_put_contents($destination, $output);
            $result = parse_ini_file($destination);
        } catch (Exception $e) {
            throw new UpgradeToOmekaS_Exception(
                __('The file themes/%s/config/theme.ini is not parsable (%s).',
                    $name, $e->getMessage()));
        }

        foreach (array('config.ini', 'theme.ini') as $filepath) {
            $source = $path . DIRECTORY_SEPARATOR . $filepath;
            if (file_exists($source) && is_writable($source)) {
                $result = unlink($source);
            }
        }
    }

    /**
     * Helper to add the standard files.
     *
     * @param string $path
     * @return void
     */
    protected function _addStandardFiles($path)
    {
        $name = basename($path);
        $version = isset($this->_versionTheme) ? $this->_versionTheme : '';
        $pathDefault = dirname($path) . DIRECTORY_SEPARATOR . 'default';

        // "composer.json".
        $destination = $path
            . DIRECTORY_SEPARATOR . 'composer.json';

        // No process is done when the destination exists.
        if (!file_exists($destination)) {
            $output = <<<OUTPUT
{
    "name": "omeka-s-themes/{$name}",
    "type": "omeka-s-theme",
    "require": {
        "omeka/omeka-s": "*"
    }
}

OUTPUT;

            $result = file_put_contents($destination, $output);
        }

        // "package.json".
        $destination = $path
            . DIRECTORY_SEPARATOR . 'package.json';
        // No process is done when the destination exists.
        if (!file_exists($destination)) {

            $output = <<<'OUTPUT'
{
  "name": "%1$s",
  "version": "%2$s",
  "description": "A theme for Omeka S upgraded with the plugin "Upgrade To Omeka S" on %3$s.",
  "main": "gulpfile.js",
  "scripts": {
    "test": "test"
  },
  "repository": {
    "type": "git",
    "url": "git+https://github.com/omeka-s-themes/%1$s-upgraded.git"
  },
  "keywords": [
    "omeka-s",
    "theme"
  ],
  "author": "RRCHNM",
  "license": "GPL-3.0",
  "bugs": {
    "url": "https://github.com/omeka-s-themes/%1$s-upgraded/issues"
  },
  "homepage": "https://github.com/omeka-s-themes/%1$s-upgraded#readme",
  "devDependencies": {
    "autoprefixer": "^6.4.0",
    "gulp": "^3.9.1",
    "gulp-postcss": "^6.1.1",
    "gulp-sass": "^2.3.2",
    "susy": "^2.2.12"
  }
}

OUTPUT;

            $output = sprintf($output, $name, $version . '-upgraded', $this->getDatetime());
            $result = file_put_contents($destination, $output);
        }

        // Copy some standard files.
        foreach (array(
                'gulpfile.js',
                // "helper/ThemeHelperOne.php" is an example.
                'helper' . DIRECTORY_SEPARATOR . 'ThemeHelperOne.php',
                'view' . DIRECTORY_SEPARATOR . 'layout'
                    . DIRECTORY_SEPARATOR . 'layout.phtml',
            ) as $filepath) {
            $destination = $path
                . DIRECTORY_SEPARATOR . $filepath;
            // No process is done when the destination exists.
            if (!file_exists($destination)) {
                $source = $pathDefault
                    . DIRECTORY_SEPARATOR . $filepath;
                $result = UpgradeToOmekaS_Common::createDir(dirname($destination));
                $result = copy($source, $destination);
            }
        }
    }

    /**
     * Helper to add the reorganize folders.
     *
     * @param string $path
     * @return void
     */
    protected function _reorganizeFolders($path)
    {
        $name = basename($path);
        $pathDefault = dirname($path) . DIRECTORY_SEPARATOR . 'default';

        // Get the default mapping folders.
        $mapping = $this->getMerged('mapping_theme_folders');

        // Move the non referenced folders into "view/omeka/site".
        $destinationSite = 'view'
            . DIRECTORY_SEPARATOR . 'omeka'
            . DIRECTORY_SEPARATOR . 'site';

        // Get the list of non referenced folders.
        $dirs = UpgradeToOmekaS_Common::listDirsInDir($path);
        $exclude = array('asset', 'config', 'helper', 'view');
        $dirs = array_diff($dirs, $exclude);
        $dirs = array_diff($dirs, array_keys($mapping));
        $dirDestinations = array_map(function ($dirpath) use ($destinationSite) {
            return $destinationSite . DIRECTORY_SEPARATOR . $dirpath;
        }, $dirs);
        $mappingDirs = array_combine($dirs, $dirDestinations);

        $mapping += $mappingDirs;
        foreach ($mapping as $dirpath => $dirpathNew) {
            if ($dirpath == $dirpathNew) {
                continue;
            }
            $source = $path . DIRECTORY_SEPARATOR . $dirpath;
            if (!file_exists($source) || !is_dir($source)) {
                continue;
            }
            if (empty($dirpathNew)) {
                UpgradeToOmekaS_Common::removeDir($source, true);
                continue;
            }
            $destination = $path . DIRECTORY_SEPARATOR . $dirpathNew;
            $result = UpgradeToOmekaS_Common::copyDir($source, $destination, true);
            if (!$result) {
                throw new UpgradeToOmekaS_Exception(
                    __('An error occurred during the copy of "%s" in themes/%s.',
                        basename($source), $name));
            }
            $result = UpgradeToOmekaS_Common::removeDir($source, true);
        }
    }

    /**
     * Helper to add the rename files.
     *
     * @param string $path
     * @return void
     */
    protected function _renameFiles($path)
    {
        $name = basename($path);
        $pathDefault = dirname($path) . DIRECTORY_SEPARATOR . 'default';

        $mapping = $this->getMerged('mapping_theme_files');

        foreach ($mapping as $filepath => $filepathNew) {
            if ($filepath == $filepathNew) {
                continue;
            }
            $source = $path . DIRECTORY_SEPARATOR . $filepath;
            if (!file_exists($source) || !is_file($source)) {
                continue;
            }
            if (empty($filepathNew)) {
                unlink($source);
                continue;
            }
            $destination = $path . DIRECTORY_SEPARATOR . $filepathNew;
            // No check if the destination exists, this is useless.
            UpgradeToOmekaS_Common::createDir(dirname($destination));
            $result = rename($source, $destination);
            if (!$result) {
                throw new UpgradeToOmekaS_Exception(
                    __('An error occurred during the copy of "%s" in themes/%s.',
                        basename($source), $name));
            }
        }
    }

    /**
     * Helper to replace ../images/ by ../img/ in the main css (style.css).
     *
     * @param string $path
     * @return void
     */
    protected function _replaceImagesByImgInCss($path)
    {
        $name = basename($path);

        $file = $path
            . DIRECTORY_SEPARATOR . 'asset'
            . DIRECTORY_SEPARATOR . 'css'
            . DIRECTORY_SEPARATOR . 'style.css';

        if (!file_exists($file) || !is_file($file)) {
            return;
        }

        $content = file_get_contents($file);
        $content = str_replace('/images/', '/img/', $content);
        $result = file_put_contents($file, $content);
    }
}
