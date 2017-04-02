<?php

/**
 * Script to update the list of addons of Omeka with the last data.
 *
 * @internal To use, simply run in the terminal, from the root of Omeka:
 * ```
 * php -f plugins/UpgradeToOmekaS/docs/_scripts/update_data.php
 * ```
 *
 * @author Daniel Berthereau
 * @license Cecill v2.1
 */

$options = array(
    // Order addons.
    'order' => 'Name',
    // Update only one or more types of addon.
    'processOnlyType' => array(),
    // Update only one or more addons (set the addon name)..
    'processOnlyAddon' => array(),
    // Update data only for new urls (urls without name).
    'processOnlyNewUrls' => false,
    // Regenerate csv only (useful when edited in a spreadsheet).
    'processRegenerateCsvOnly' => false,
    // Allow to log (in terminal) the process of all the addons, else only
    // updated one will be displayed at the end, and errors.
    'logAllAddons' => true,
    // To debug and to add some logs.
    'debug' => false,
    // To debug only the specified number of addons.
    'debugMax' => 0,
    // To display diff between old and new data. May be "none", "diff", "whole".
    'debugDiff' => 'diff',
    // If true, the updated whole csv is saved in the destination after debug.
    'debugOutput' => false,
);

$basepath = realpath(dirname(__FILE__)
    . DIRECTORY_SEPARATOR . '..'
    . DIRECTORY_SEPARATOR . '_data'
    . DIRECTORY_SEPARATOR);

$types = array(
    'plugin' => array(
        'source' => $basepath . '/omeka_plugins.csv',
        'destination' => $basepath . '/omeka_plugins.csv',
    ),
    'module' => array(
        'source' => $basepath . '/omeka_s_modules.csv',
        'destination' => $basepath . '/omeka_s_modules.csv',
    ),
    'theme' => array(
        'source' => $basepath . '/omeka_themes.csv',
        'destination' => $basepath . '/omeka_themes.csv',
    ),
    'template' => array(
        'source' => $basepath . '/omeka_s_themes.csv',
        'destination' => $basepath . '/omeka_s_themes.csv',
    ),
);

foreach ($types as $type => $args) {
    if ($options['processOnlyType'] && !in_array($type, $options['processOnlyType'])) {
        continue;
    }
    $update = new UpdateDataExtensions($type, $args['source'], $args['destination'], $options);
    $result = $update->process();
}

return;

class UpdateDataExtensions
{
    protected $type = '';
    protected $source = '';
    protected $destination = '';
    protected $options = array();

    /**
     * Map of the keys of the plugin ini and the headers of the csv file.
     */
    protected $mappingToUpdate = array(
        'common' => array(
            'name' => 'Name',
            'author' => 'Author',
            'description' => 'Description',
            'license' => 'License',
            'link' => 'Link',
            'support_link' => 'Support Link',
            'version' => 'Last',
            'tags' => 'Tags',
        ),
        // Omeka 1 / 2.
        'plugin' => array(
            'required_plugins' => 'Required Plugins',
            'optional_plugins' => 'Optional Plugins',
            'omeka_minimum_version' => 'Omeka Min',
            // Omeka 1.
            'omeka_tested_up_to' => 'Omeka Target',
            // Omeka 2.
            'omeka_target_version=' => 'Omeka Target',
        ),
        'theme' => array(
            'title' => 'Name',
            'omeka_minimum_version' => 'Omeka Min',
            'omeka_tested_up_to' => 'Omeka Target',
            'omeka_target_version=' => 'Omeka Target',
        ),
        // Omeka 3 / S.
        'module' => array(
            'omeka_version_constraint' => 'Omeka Constraint',
            'module_link' => 'Link',
            'author_link' => 'Author Link',
        ),
        'template' => array(
            'theme_link' => 'Link',
            'author_link' => 'Author Link',
        ),
    );

    protected $headers;
    protected $omekaAddons;
    protected $updatedAddons;

    public function __construct($type, $source, $destination = '', array $options = array())
    {
        $this->type = $type;
        $this->source = $source;
        $this->destination = $destination ?: $source;
        $this->options = $options;
    }

    public function process()
    {
        // May avoid an issue with Apple Mac.
        ini_set('auto_detect_line_endings', true);

        $this->log(sprintf('Start update of "%s".', $this->source));

        if ($this->options['debug']) {
            $this->log('Debug mode enabled.');
        }

        if (!$this->checkFiles($this->source, $this->destination)) {
            return false;
        }

        $addons = array_map('str_getcsv', file($this->source));
        if (empty($addons)) {
            $this->log(sprintf('No content in the csv file "%s".', $this->source));
            return false;
        }

        if (!$this->options['processRegenerateCsvOnly']) {
            $addons = $this->update($addons);
        }
        if (empty($addons)) {
            return false;
        }

        if ($this->updatedAddons) {
            $this->log(sprintf('%d lines updated.', count($this->updatedAddons)));
        } else {
            $this->log('No line updated.');
        }

        $addons = $this->order($addons);

        if ($this->options['debug'] && !$this->options['debugOutput']) {
            $this->log('Required no output.');
        } else {
            $result = $this->saveToCsvFile($this->destination, $addons);
            if (!$result) {
                $this->log(sprintf('An error occurred during saving the csv into the file "%s".', $this->destination));
                return false;
            }
        }

        $this->log('Process ended successfully.');

        return true;
    }

    /**
     * Regenerate data.
     *
     * @param array $addons
     * @return array
     */
    protected function update(array $addons)
    {
        // Get headers by name.
        $headers = array_flip($addons[0]);
        $this->headers = $headers;

        if ($this->options['debug']) {
            $this->log($headers);
        }

        if (!isset($headers['Name'])) {
            $this->log(sprintf('No header "%s".', 'Name'));
            return false;
        }

        if (!isset($headers['Url'])) {
            $this->log(sprintf('No header "%s".', 'Url'));
            return false;
        }

        $omekaAddons = $this->fetchOmekaAddons();
        $this->omekaAddons = $omekaAddons;

        $updatedAddons = array();
        $this->updatedAddons = $updatedAddons;

        foreach ($addons as $key => &$addon) {
            if ($key == 0) {
                continue;
            }
            $addonUrl = trim($addon[$headers['Url']], '/ ');
            if (empty($addonUrl)) {
                continue;
            }

            $addonName = $addon[$headers['Name']];
            if (empty($addonName)) {
                // Set a temp addon name.
                $addonName = basename($addonUrl);
            }
            else {
                if ($this->options['processOnlyNewUrls']) {
                    continue;
                }
                if ($this->options['processOnlyAddon'] && !in_array($addonName, $this->options['processOnlyAddon'])) {
                    continue;
                }
            }

            if ($this->options['debug']) {
                if ($this->options['debugMax'] && $key > $this->options['debugMax']) {
                    break;
                }
                // $this->log($addonName . ' (' . $addonUrl . ')');
            }

            $addon = $this->updateAddon($addon);
        }

        if (!$this->options['processOnlyNewUrls']) {
            foreach ($omekaAddons as $omekaAddon) {
                if (empty($omekaAddon['checked'])) {
                    $unref = isset($omekaAddon['title'])
                        ? $omekaAddon['title']
                        : $omekaAddon['name'];
                    $this->log('[Unreferenced]' . ' ' . $unref);
                }
            }
        }

        return $addons;
    }

    /**
     * Regenerate data for one row.
     *
     * @param array $addon
     * @return array
     */
    protected function updateAddon(array $addon)
    {
        $headers = $this->headers;
        $omekaAddons = &$this->omekaAddons;
        $updatedAddons = &$this->updatedAddons;
        $currentAddon = $addon;

        // Set the name or a temp addon name.
        $addonUrl = trim($addon[$headers['Url']], '/ ');
        $addonName = $addon[$headers['Name']] ?: basename($addonUrl);

        $server = strtolower(parse_url($addonUrl, PHP_URL_HOST));
        switch ($server) {
            case 'github.com':
                $addonIniBase = str_ireplace('github.com', 'raw.githubusercontent.com', $addonUrl);
                if ($addon[$headers['Ini Path']]) {
                    $replacements = array(
                        '/tree/master/' => '/master/',
                        '/tree/develop/' => '/develop/',
                    );
                    $addonIniBase = str_replace(
                        array_keys($replacements),
                        array_values($replacements),
                        $addonIniBase);
                }
                break;
            case 'gitlab.com':
                $addonIniBase = $addonUrl . '/raw';
                break;
            default:
                $addonIniBase = $addonUrl;
                break;
        }

        switch ($this->type) {
            case 'plugin':
                $addonIni = $addon[$headers['Ini Path']] ?: 'master/plugin.ini';
                break;
            case 'theme':
                $addonIni = $addon[$headers['Ini Path']] ?: 'master/theme.ini';
                break;
            case 'module':
                $addonIni = $addon[$headers['Ini Path']] ?: 'master/config/module.ini';
                break;
            case 'template':
                $addonIni = $addon[$headers['Ini Path']] ?: 'master/config/theme.ini';
                break;
        }

        $addonIni = $addonIniBase . '/' . $addonIni;

        $ini = @file_get_contents($addonIni);
        if (empty($ini)) {
            $this->log('[No ini      ]' . ' ' . $addonName);
            if ($this->options['debug']) {
                $this->log(' Addon ini: ' . $addonIni);
            }
            return $addon;
        }

        $ini = parse_ini_string($ini);
        if (empty($ini)) {
            $this->log('[No ini keys ]' . ' ' . $addonName);
            return $addon;
        }

        // Update each keys of the ini file.
        foreach ($this->mappingToUpdate as $typeKey => $typeValues) {
            if ($typeKey != 'common' && $typeKey != $this->type) {
                continue;
            }
            foreach ($typeValues as $iniKey => $header) {
                if (empty($header) || !isset($ini[$iniKey])) {
                    continue;
                }

                $iniValue = trim($ini[$iniKey]);

                // Manage exceptions.
                switch ($iniKey) {
                    // Keep the name when empty, and clean it.
                    case 'name':
                    case 'title':
                        if (empty($iniValue)) {
                            $iniValue = $addon[$headers[$header]] ?: $addonName;
                        }
                        $iniValue = str_ireplace(array(
                            ' plugin',
                            'plugin ',
                            ' module',
                            'module ',
                            ' theme',
                            'theme ',
                            ' widget',
                            'widget ',
                            ' public/admin',
                        ), '', $iniValue);
                        $addonName = $iniValue;
                        break;
                    // Fill no version.
                    case 'version':
                        if ($iniValue == '') {
                            $iniValue = '-';
                        }
                        break;
                    // Clean lists.
                    case 'tags':
                    case 'required_plugins':
                    case 'optional_plugins':
                        $iniValue = implode(', ', array_map('trim', explode(',', $iniValue)));
                        break;
                }

                $addon[$headers[$header]] = $iniValue;
            }
        }

        // Set if the plugin is upgradable.
        if ($this->type == 'plugin') {
            if (!empty($addon[$headers['Module']]) && empty($addon[$headers['Upgradable']])) {
                $addon[$headers['Upgradable']] = 'Yes';
            }
        }

        $cleanName = $this->cleanAddonName($addonName);
        if (isset($omekaAddons[$cleanName])) {
            $addon[$headers['Omeka.org']] = $omekaAddons[$cleanName]['version'];
            $omekaAddons[$cleanName]['checked'] = true;
        }

        if ($currentAddon == $addon) {
            if ($this->options['logAllAddons']) {
                $this->log('[No update   ]' . ' ' . $addonName);
            }
        } else {
            $updatedAddons[] = $addonName;
            echo '[Updated     ]' . ' ' . $addonName . PHP_EOL;
            if ($this->options['debug']) {
                switch ($this->options['debugDiff']) {
                    case 'diff':
                        $this->log('Updated');
                        $this->log(array_diff_assoc($currentAddon, $addon));
                        $this->log(array_diff_assoc($addon, $currentAddon));
                        break;
                    case 'whole':
                        $this->log('Before');
                        $this->log($currentAddon);
                        $this->log('After');
                        $this->log($addon);
                        break;
                }
            }
        }

        return $addon;
    }

    /**
     * Reorder data.
     *
     * @param array $addons
     * @return array
     */
    protected function order(array $addons)
    {
        if (empty($this->options['order'])) {
            return $addons;
        }

        // Get headers by name.
        $headers = array_flip($addons[0]);

        if ($this->options['debug']) {
            $this->log($headers);
        }

        if (!isset($headers[$this->options['order']])) {
            $this->log(sprintf('Order %s not found in headers.', $this->options['order']));
            return $addons;
        }
        $order = $headers[$this->options['order']];
        unset($addons[0]);

        $addonsList = array();
        foreach ($addons as $key => &$addon) {
            $addonsList[$key] = $addon[$order];
        }
        natcasesort($addonsList);
        $addonsList = array_replace($addonsList, $addons);
        array_unshift($addonsList, null);
        $addonsList[0] = array_keys($headers);

        return $addonsList;
    }

    /**
     * Helper to check files.
     *
     * @param string $source
     * @param string $destination
     * @return boolean
     */
    protected function checkFiles($source, $destination)
    {
        if (!is_file($source) || !is_readable($source)) {
            $this->log(sprintf('The source file "%s" is not readable.', $source));
            return false;
        }

        if (is_file($destination) && !is_writeable($destination)) {
            $this->log(sprintf('The destination file "%s" is not writeable.', $destination));
            return false;
        }

        if (!is_file($destination) && !is_writeable(dirname($destination))) {
            $this->log( sprintf('The directory "%s" is not writeable.', dirname($destination)));
            return false;
        }

        return true;
    }

    /**
     * Helper to get the list of addons on Omeka.org.
     *
     * Adapted from the plugin Escher.
     * @link https://github.com/AcuGIS/Escher/blob/master/controllers/IndexController.php
     *
     * @return array
     */
    protected function fetchOmekaAddons()
    {
        switch ($this->type) {
            case 'plugin':
                $source = 'https://omeka.org/add-ons/plugins/';
                break;
            case 'theme':
                $source = 'https://omeka.org/add-ons/themes/';
                break;
            default:
                return array();
        }

        $addons = array();

        $html = file_get_contents($source);
        if (empty($html)) {
            return array();
        }

        libxml_use_internal_errors(true);
        $pokemon_doc = new DOMDocument();
        $pokemon_doc->loadHTML($html);
        $pokemon_xpath = new DOMXPath($pokemon_doc);
        $pokemon_row = $pokemon_xpath->query('//a[@class="omeka-addons-button"]/@href');
        if ($pokemon_row->length > 0) {
            foreach ($pokemon_row as $row) {
                $url = $row->nodeValue;
                $filename = basename(parse_url($url, PHP_URL_PATH));
                // Some addons have "-" in name; some have letters in version.
                $result = preg_match('~([^\d]+)\-(\d.*)\.zip~', $filename, $matches);
                // Manage for example "Select2".
                if (empty($matches)) {
                    $result = preg_match('~(.*?)\-(\d.*)\.zip~', $filename, $matches);
                }
                $addonName = $matches[1];
                // Manage a bug.
                if ($addonName == '-Media') {
                    $addonName = 'HTML5 Media';
                }
                if ($addonName == 'Sitemap') {
                    $addonName = 'Sitemap 2';
                }
                $cleanName = $this->cleanAddonName($addonName);
                $addons[$cleanName] = array();
                $addons[$cleanName]['name'] = $addonName;
                $addons[$cleanName]['url'] = $url;
                $addons[$cleanName]['version'] = $matches[2];
            }
        }

        return $addons;
    }

    /**
     * Clean an addon name to simplify matching.
     *
     * @param string $name
     * @return string
     */
    protected function cleanAddonName($name)
    {
        // Manage exceptions with non standard characters (avoid duplicates).
        $addonName = str_replace(array('+'), array('Plus'), $name);

        $cleanName = str_ireplace(
            array('plugin', 'module', 'theme'),
            '',
            preg_replace('~[^\da-z]~i', '', strtolower($addonName)));

        // Manage exception on Omeka.org.
        switch ($cleanName) {
            case 'neatlinewidgetsimiletimeline': return 'neatlinesimiletimeline';
            case 'neatlinewidgettext': return 'neatlinetext';
            case 'neatlinewidgetwaypoints': return 'neatlinewaypoints';
            case 'replacedctitleinallpublicadminviews': return 'replacedctitleinallviews';
            case 'sitemap2': return 'xmlsitemap';
            case 'vracore': return 'vracoreelementset';
            case 'pbcore': return 'pbcoreelementset';
            case 'media': return 'html5media';
        }

        return $cleanName;
    }

    /**
     * Save an array into a csv file.
     *
     * @param string $destination
     * @param array $array
     * @return boolean
     */
    protected function saveToCsvFile($destination, array $array)
    {
        $handle = fopen($destination, 'w');
        if (empty($handle)) {
            return false;
        }
        foreach($array as $row) {
            fputcsv($handle, $row);
        }
        return fclose($handle);
    }

    /**
     * Echo a message to the standard output.
     *
     * @param mixed $message
     * @return void
     */
    protected function log($message)
    {
        if (is_array($message)) {
            print_r($message);
        } else {
            echo $message . PHP_EOL;
        }
    }
}
