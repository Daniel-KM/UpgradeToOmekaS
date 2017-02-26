<?php

/**
 * Upgrade Core Themes to Omeka S.
 *
 * @package UpgradeToOmekaS
 */
class UpgradeToOmekaS_Processor_CoreThemes extends UpgradeToOmekaS_Processor_Abstract
{

    public $pluginName = 'Core/Themes';
    public $minVersion = '2.3.1';
    public $maxVersion = '2.5';
    protected $_isCore = true;

    public $module = array(
        'type' => 'integrated',
    );

    public $processMethods = array(
        '_copyAssets',
        '_copyThemes',
        '_upgradeThemesParams',
        '_upgradeThemes',
        '_upgradeFunctionsAndVariables',
        '_upgradeHooks',
        '_upgradeFiles',
    );

    public $specificProcessMethods = array(
        'themes' => array(
            '_copyThemes',
            '_upgradeThemes',
            '_upgradeFunctionsAndVariables',
            '_upgradeHooks',
            '_upgradeFiles',
        ),
    );

    protected function _copyAssets()
    {
        $this->_copyAssetsFilesAndMetadata();
    }

    protected function _copyAssetsFiles()
    {
        $this->_copyAssetsFilesAndMetadata('files');
    }

    protected function _copyAssetsMetadata()
    {
        $this->_copyAssetsFilesAndMetadata('metadata');
    }

    /**
     * Helper to copy files of metadata of assets.
     *
     * @param string $limitTo "full" (default), "files" or "metadata".
     * @throws UpgradeToOmekaS_Exception
     */
    protected function _copyAssetsFilesAndMetadata($limitTo = 'full')
    {
        $copyFiles = $limitTo != 'metadata';
        $copyMetadata = $limitTo != 'files';
        $copyFull = $copyFiles && $copyMetadata;

        if (!$copyFiles && !$copyMetadata) {
            throw new UpgradeToOmekaS_Exception(
                __('This function is used to copy assets files or metadata, but nothing is defined.'));
        }

        if ($copyFull) {
            $this->_log('[' . __FUNCTION__ . ']: ' . __('In Omeka S, all assets used for the themes (logo...) are managed in one place.'),
                Zend_Log::INFO);
        }

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
            if ($copyFull) {
                $this->_log('[' . __FUNCTION__ . ']: ' . __('The current site has no asset.'),
                    Zend_Log::INFO);
            }
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
                        . ' ' . __('This precheck may be bypassed via an option in "security.ini".'));
            }
            // Warn about the copy.
            else {
                if ($copyFiles) {
                    $this->_log('[' . __FUNCTION__ . ']: ' . __('The directory "files/themes_uploads" contain symbolic links.')
                            . ' ' . __('Some errors may occur in some cases.')
                            . ' ' . __('You need to check yourself if all files were copied.'),
                        Zend_Log::WARN);
                }
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

            if ($copyFiles) {
                // Do a true copy because they aren't an archive and may change.
                $source = $sourceDir . DIRECTORY_SEPARATOR . $filename;
                $destination = $destinationDir . DIRECTORY_SEPARATOR . $filename;
                $result = copy($source, $destination);
                if (!$result) {
                    throw new UpgradeToOmekaS_Exception(
                        __('The copy of the file "%s" to the directory "%s" failed.',
                            $source, $destinationDir . DIRECTORY_SEPARATOR));
                }
            }

            if ($copyMetadata) {
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
        }

        if ($copyFull) {
            $this->_log('[' . __FUNCTION__ . ']: ' . ($totalRecords <= 1
                    ? __('One asset has been upgraded.')
                    : __('%d assets have been upgraded.', $totalRecords)),
                Zend_Log::INFO);
        }
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
                        . ' ' . __('This precheck may be bypassed via an option in "security.ini".'));
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

        // TODO Add a check to not rename files inside assets (theme "the-audio").

        $processors = $this->getProcessors();
        $i = 0;
        foreach ($themes as $theme) {
            $this->_progress(++$i);

            $destinationTheme = $destination . DIRECTORY_SEPARATOR . $theme;

            // Remove the existing dir to avoid issues when copy of themes only.
            UpgradeToOmekaS_Common::removeDir($destinationTheme, true);

            // First, copy the default scripts from Omeka in each theme, since
            // they are used when there is no script with the same name in the
            // theme.
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
        $target = $this->getTarget();
        $defaultSettings = $this->prepareThemeSettings();
        foreach ($defaultSettings as $theme => $settings) {
            // Add the option for the homepage.
            $settings['use_homepage_template'] = '1';
            $nameSetting = 'theme_settings_' . $theme;
            $target->saveSiteSetting($nameSetting, $settings, $siteId);
        }
        $this->_log('[' . __FUNCTION__ . ']: ' . __('The options of the themes have been upgraded as site settings.'),
            Zend_Log::INFO);
    }

    /**
     * Helper to normalize the list of all theme settings.
     *
     * return @array
     */
    public function prepareThemeSettings()
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

        $result = array();
        foreach ($options as $key => $values) {
            $theme = substr($key, strlen('theme_'), strlen($key) - strlen('theme__options'));
            if (empty($theme)) {
                continue;
            }

            $values = unserialize($values) ?: array();
            unset($values['theme_config_csrf']);

            // Check if there is an asset to upgrade the logo, else remove.
            if (!empty($values['logo'])) {
                $values['logo'] = isset($assets[$values['logo']])
                    ? $assets[$values['logo']]
                    : '';
            }

            // Add the option for the homepage.
            $values['use_homepage_template'] = '0';

            $result[$theme] = $values;
        }
        return $result;
    }

    protected function _upgradeThemes()
    {
        $source = PUBLIC_THEME_DIR;
        $destination = $this->getParam('base_dir')
            . DIRECTORY_SEPARATOR . 'themes';

        // This is the list in the destination, not from the source.
        $themes = UpgradeToOmekaS_Common::listDirsInDir($destination);
        $defaultKey = array_search('default', $themes);
        if ($defaultKey !== false) {
            unset($themes[$defaultKey]);
        }
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

        // TODO Remove empty folders (simple-pages...).

        $this->_log('[' . __FUNCTION__ . ']: ' . __('All files have been moved and renamed according to Omeka S views.')
            . ' ' . __('The header and the footer have been merged into "layout.phtml".'),
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
;
; See https://github.com/Daniel-KM/UpgradeToOmekaS
; See https://github.com/Daniel-KM/Omeka-S-module-UpgradeFromOmekaClassic
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

                // Add the check box for home page before the other options.
                $element = array();
                $element['name'] = 'use_homepage_template';
                $element['type'] = 'Zend\Form\Element\Checkbox';
                $element['options']['label'] = 'Use Home Page Template';
                $element['options']['info'] = 'Check this box to use the specific template `view/omeka/site/page/homepage.phtml` for the home page, instead of a standard page.';
                $element['attributes']['value '] = '1';
                $element = new Zend_Config($element, true);
                $elements['use_homepage_template'] = $element;

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

                    // Convert multi options to values options.
                    if (isset($element->options->multiOptions)) {
                        $element = $element->toArray();
                        $element['options']['value_options'] = $element['options']['multiOptions'];
                        unset($element['options']['multiOptions']);
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
elements.use_homepage_template.name = "use_homepage_template"
elements.use_homepage_template.type = "Zend\Form\Element\Checkbox"
elements.use_homepage_template.options.label = "Use Home Page Template"
elements.use_homepage_template.options.info = "Check this box to use the specific template `view/omeka/site/page/homepage.phtml` for the home page, instead of a standard page."
elements.use_homepage_template.attributes.value = "1"

elements.nav_depth.name = "nav_depth"
elements.nav_depth.type = "Number"
elements.nav_depth.options.label = "Top Navigation Depth"
elements.nav_depth.options.info = "Maximum number of levels to show in the site's top navigation bar. Set to 0 to show all levels."
elements.nav_depth.attributes.min = 0
elements.nav_depth.attributes.value = 0

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
                // If any, the header and the footer will replace it.
                'view' . DIRECTORY_SEPARATOR . 'layout'
                    . DIRECTORY_SEPARATOR . 'layout.phtml',
            ) as $filepath) {
            $source = $pathDefault
                . DIRECTORY_SEPARATOR . $filepath;
            $destination = $path
                . DIRECTORY_SEPARATOR . $filepath;
            // No process is done when the destination exists.
            if (file_exists($source) && !file_exists($destination)) {
                $result = UpgradeToOmekaS_Common::createDir(dirname($destination));
                $result = copy($source, $destination);
            }
        }

        // Create a default "layout.phtml". It will be replaced by the merge
        // of the header and the footer, if any.
        $destination = $path
            . DIRECTORY_SEPARATOR . 'view'
            . DIRECTORY_SEPARATOR . 'layout'
            . DIRECTORY_SEPARATOR . 'layout.phtml';
        if (!file_exists($destination)) {
            $output = <<<OUTPUT
<?php
/**
 * Theme "{$name}" of Omeka Classic upgraded on {$this->getDatetime()}.
OUTPUT;
            $output .= <<<'OUTPUT'
 *
 * @link https://github.com/Daniel-KM/UpgradeToOmekaS
 * @link https://github.com/Daniel-KM/Omeka-S-module-UpgradeFromOmekaClassic
 */

// See the original "layout.phtml" files to rewrite this and to separate header,
// content and footer.
// - default theme: themes/default/view/layout/layout.phtml
// - shared theme: application/view-shared/layout/layout.phtml
// - admin theme: application/view-admin/layout/layout.phtml

// TODO Upgrade the custom functions into standard helpers for Omeka S.
$customFunctions = realpath(__DIR__ . '/../../helper/custom.php');
if (file_exists($customFunctions)) {
    include_once $customFunctions;
}
$customFunctions = realpath(__DIR__ . '/../../helper/functions.php');
if (file_exists($customFunctions)) {
    include_once $customFunctions;
}

// The content of the output of each managed hook is saved in each "view/hook/".

$this->trigger('view.layout');
echo $this->content;

?>

OUTPUT;

            UpgradeToOmekaS_Common::createDir(dirname($destination));
            $result = file_put_contents($destination, $output);
        }

        // Add a default template for pages, that will be replaced if the plugin
        // Simple Pages is enabled and a specific template has been created.
        $destination = $path
            . DIRECTORY_SEPARATOR . 'view'
            . DIRECTORY_SEPARATOR . 'omeka'
            . DIRECTORY_SEPARATOR . 'site'
            . DIRECTORY_SEPARATOR . 'page'
            . DIRECTORY_SEPARATOR . 'show.phtml';
        if (!file_exists($destination)) {
            // This is the standard template of Simple Pages (views/public/page/show.php).
            $output = <<<'OUTPUT'
<?php
$bodyclass = 'page simple-page';
if ($is_home_page):
    $bodyclass .= ' simple-page-home';
endif;

echo head(array(
    'title' => metadata('simple_pages_page', 'title'),
    'bodyclass' => $bodyclass,
    'bodyid' => metadata('simple_pages_page', 'slug')
));
?>
<div id="primary">
    <?php if (!$is_home_page): ?>
    <p id="simple-pages-breadcrumbs"><?php echo simple_pages_display_breadcrumbs(); ?></p>
    <h1><?php echo metadata('simple_pages_page', 'title'); ?></h1>
    <?php endif; ?>
    <?php
    $text = metadata('simple_pages_page', 'text', array('no_escape' => true));
    echo $this->shortcodes($text);
    ?>
</div>

<?php echo foot(); ?>

OUTPUT;

            UpgradeToOmekaS_Common::createDir(dirname($destination));
            $result = file_put_contents($destination, $output);
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
                UpgradeToOmekaS_Common::removeDir($source, false);
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

        // Merge header.phtml and footer.phtml in layout.phtml, with content.
        $pathLayout = $path
            . DIRECTORY_SEPARATOR . 'view'
            . DIRECTORY_SEPARATOR . 'layout';
        $pathHeader = $pathLayout . DIRECTORY_SEPARATOR . 'header.phtml';
        $pathFooter = $pathLayout . DIRECTORY_SEPARATOR . 'footer.phtml';
        $pathLayout = $pathLayout . DIRECTORY_SEPARATOR . 'layout.phtml';
        if (file_exists($pathHeader) && file_exists($pathFooter)) {
            $base = <<<OUTPUT
<?php
/**
 * Theme "{$name}" of Omeka Classic upgraded on {$this->getDatetime()}.
OUTPUT;
            $base .= <<<'OUTPUT'
 *
 * @link https://github.com/Daniel-KM/UpgradeToOmekaS
 * @link https://github.com/Daniel-KM/Omeka-S-module-UpgradeFromOmekaClassic
 */

// See the original "layout.phtml" files to rewrite this and to separate header,
// content and footer.
// - default theme: themes/default/view/layout/layout.phtml
// - shared theme: application/view-shared/layout/layout.phtml
// - admin theme: application/view-admin/layout/layout.phtml

// TODO Upgrade the custom functions into standard helpers for Omeka S.
$customFunctions = realpath(__DIR__ . '/../../helper/custom.php');
if (file_exists($customFunctions)) {
    include_once $customFunctions;
}
$customFunctions = realpath(__DIR__ . '/../../helper/functions.php');
if (file_exists($customFunctions)) {
    include_once $customFunctions;
}

// The content of the output of each managed hook is saved in each "view/hook/".

$this->trigger('view.layout');

?>

OUTPUT;

            $header = file_get_contents($pathHeader);

            // Add the possible missing "?_>" to the header to avoid issues.
            $header = rtrim($header);
            if (substr($header, -2) != '?>') {
                $header .= '?>';
            }

            $content = <<<'OUTPUT'
            <?php // echo $this->messages(); ?>
            <?php echo $this->content; ?>
OUTPUT;

            $footer = file_get_contents($pathFooter);

            $output = $base
                . $header . PHP_EOL
                . $content . PHP_EOL
                . rtrim($footer) . PHP_EOL;

            $result = file_put_contents($pathLayout, $output);
            if (!$result) {
                throw new UpgradeToOmekaS_Exception(
                    __('An error occurred when saving "%s" in themes/%s.',
                        'layout.phtml', $name));
            }

            unlink($pathHeader);
            unlink($pathFooter);
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

    protected function _upgradeFunctionsAndVariables()
    {
        $path = $this->getParam('base_dir')
            . DIRECTORY_SEPARATOR . 'themes';

        $mappingRegex = $this->getMerged('mapping_regex');
        $mappingReplace = $this->getMerged('mapping_replace');

        $files = UpgradeToOmekaS_Common::listFilesInFolder($path, array('php', 'phtml'));
        $this->_progress(0, count($files));

        $toExclude = 'default'
            . DIRECTORY_SEPARATOR . 'view'
            . DIRECTORY_SEPARATOR . 'layout'
            . DIRECTORY_SEPARATOR . 'layout.phtml';
        $toExcludeMatch = '~^' . preg_quote($path, '~') . '/(\w|\-)+/asset/~';

        $totalCalls = 0;
        $i = 0;
        $flag = false;
        foreach ($files as $file) {
            $this->_progress(++$i);
            // Exclude some files from Omeka S.
            if (strpos($file, $toExclude)) {
                continue;
            }
            // Don't process files that are in assets.
            if (preg_match($toExcludeMatch, $file)) {
                continue;
            }

            $input = file_get_contents($file);

            $output = preg_replace(array_keys($mappingRegex), array_values($mappingRegex), $input, -1, $count);
            $countF = $count;

            $output = str_replace(array_keys($mappingReplace), array_values($mappingReplace), $output, $count);
            $countV = $count;
            $totalCalls += $countF + $countV;

            if (!$flag && !empty($input) && empty($output)) {
                $flag = true;
                $this->_log('[' . __FUNCTION__ . ']: ' . __('There is probably an error in mapping functions regex, because there is no output.'),
                    Zend_Log::WARN);
            }

            $result = file_put_contents($file, $output);
        }

        $this->_log('[' . __FUNCTION__ . ']: ' . __('A total of %d calls to functions have been converted.',
            $totalCalls), Zend_Log::INFO);
    }

    protected function _upgradeHooks()
    {
        $hooks = $this->getMergedList('list_hooks');
        foreach ($hooks as $hook) {
            $method = '_upgradeHook' . inflector::camelize($hook);
            $name;
            if (!method_exists($this, $method)) {
                throw new UpgradeToOmekaS_Exception(
                    __('Method "%s" for hook "%s" doesn’t exist.', $method, $hook));
            }
            $this->$method();
        }

        $this->_log('[' . __FUNCTION__ . ']: ' . __('A total of %d hooks have been upgraded.',
            count($hooks)), Zend_Log::INFO);
    }

    protected function _upgradeHookPublicHead()
    {
        $hookName = 'public_head';
        $relativePath = 'view'
            . DIRECTORY_SEPARATOR . 'omeka'
            . DIRECTORY_SEPARATOR . 'hook'
            . DIRECTORY_SEPARATOR . $hookName . '.phtml';

        // $args = array('view' => null);
        // $output = $this->_upgradeGetOutputHook($hookName, $args);
        $output = '';

        // The default js are added here to avoid issues with the order of js.

        $output = <<<'OUTPUT'
<?php
// TODO Move "public_head" in the layout.
// TODO Use only css and js assets of Omeka S (jquery and jqueryUI).
?>
<?php $upgrade = $this->upgrade(); ?>

<?php // From Omeka Semantic. ?>
<?php $this->headLink()->prependStylesheet($this->assetUrl('css/style.css')); ?>
<?php $this->headLink()->prependStylesheet($this->assetUrl('css/iconfonts.css', 'Omeka')); ?>
<?php // $this->headLink()->prependStylesheet('//fonts.googleapis.com/css?family=Open+Sans:400,400italic,600,600italic,700italic,700'); ?>
<?php $this->headScript()->prependFile($this->assetUrl('js/jquery.js', 'Omeka')); ?>

<?php // From Omeka Classic.
$this->headScript()
    ->prependScript('jQuery.noConflict();')
    ->prependScript('window.jQuery.ui || document.write(' . json_encode($upgrade->js_tag('vendor/jquery-ui')) . ')')
    ->prependFile('//ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/jquery-ui.min.js')
    ->prependScript('window.jQuery || document.write(' . json_encode($upgrade->js_tag('vendor/jquery')) . ')')
    ->prependFile('//ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js');
?>

OUTPUT;

        set_theme_base_url('public');
        $helper = new Zend_View_Helper_HeadScript();
        $outputPublicHead = $helper->headScript();
        revert_theme_base_url();
        $this->_outputPublicHead = $outputPublicHead;
        $output .= $this->_outputPublicHead;

        $this->_upgradeSaveContentInThemes($relativePath, $output);
    }

    protected function _upgradeHookHeadCss()
    {
        $hookName = 'head_css';
        $relativePath = 'view'
            . DIRECTORY_SEPARATOR . 'omeka'
            . DIRECTORY_SEPARATOR . 'hook'
            . DIRECTORY_SEPARATOR . $hookName . '.phtml';

        // TODO Avoid to include the links of the plugins.
        $output = <<<OUTPUT
<?php
// TODO Remove links of the plugins.
?>

OUTPUT;

        // Add the output of head_css().
        set_theme_base_url('public');
        $helper = new Zend_View_Helper_HeadLink();
        $output .= $helper->headLink();
        $helper = new Zend_View_Helper_HeadStyle();
        $output .= $helper->headStyle();
        revert_theme_base_url();

        $this->_upgradeSaveContentInThemes($relativePath, $output);
    }

    protected function _upgradeHookHeadJs()
    {
        $hookName = 'head_js';
        $relativePath = 'view'
            . DIRECTORY_SEPARATOR . 'omeka'
            . DIRECTORY_SEPARATOR . 'hook'
            . DIRECTORY_SEPARATOR . $hookName . '.phtml';

        // TODO Avoid to include the links of the plugins.
        $output = <<<OUTPUT
<?php
// TODO Remove links of the plugins.
?>

OUTPUT;

        set_theme_base_url('public');
        $helper = new Zend_View_Helper_HeadScript();
        // Default js are included during the hook "public_head".
        $outputHeadJs = $helper->headScript();
        revert_theme_base_url();

        if (trim($outputHeadJs) == trim($this->_outputPublicHead)) {
            $outputHeadJs = '';
        }
        $this->_outputPublicHead = '';
        $output .= $outputHeadJs;

        $this->_upgradeSaveContentInThemes($relativePath, $output);
    }

    protected function _upgradeFiles()
    {
        $mapping = $this->getMerged('upgrade_files');
        $this->_progress(0, count($mapping));

        $i = 0;
        foreach ($mapping as $relativePath => $upgrade) {
            $this->_progress(++$i);
            $this->_upgradeFileInThemes($relativePath, $upgrade);
        }
    }
}
