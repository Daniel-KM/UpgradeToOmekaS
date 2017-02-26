<?php

/**
 * Script to update the list of addons of Omeka with the last data.
 *
 * @author Daniel Berthereau
 * @license Cecill v2.1
 */

$basepath = realpath(dirname(__FILE__) . '/../_data/');

$types = array(
    'plugin' => array(
        'source' => $basepath . '/omeka_plugins.csv',
        'destination' => $basepath . '/omeka_plugins.csv',
    ),
    'module' => array(
        'source' => $basepath . '/omeka_s_modules.csv',
        'destination' => $basepath . '/omeka_s_modules.csv',
    )
);

$options = array(
    // Allow to log (in terminal) the process of all the addons, else only
    // updated one will be displayed, and errors.
    'logAllAddons' => false,
    // To debug and to add some logs.
    'debug' => false,
    // The next two options can be used to update only one addon.
    // To debug only one type.
    'debugType' => '',
    // To debug an addon (set its name).
    'debugAddon' => '',
    // To debug only the specified number of addons.
    'debugMax' => 0,
    // If false, the result is not saved in the destination.
    'debugOutput' => true,
);

foreach ($types as $type => $args) {
    if ($options['debug'] && $options['debugType'] && $options['debugType'] != $type) {
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
        // Omeka 3 / S.
        'module' => array(
            'omeka_version_constraint' => 'Omeka Constraint',
            'module_link' => 'Link',
            'author_link' => 'Author Link',
        )
    );

    protected $updatedAddons = array();

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

        $addons = $this->update($addons);
        if (empty($addons)) {
            return false;
        }

        if ($this->updatedAddons) {
            $this->log(sprintf('%d lines updated.', count($this->updatedAddons)));
        } else {
            $this->log('No line updated.');
        }

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

    protected function update(array $addons)
    {
        // Get headers by name.
        $headers = array_flip($addons[0]);

        if (!isset($headers['Name'])) {
            $this->log(sprintf('No header "%s".', 'Name'));
            return false;
        }

        if (!isset($headers['Url'])) {
            $this->log(sprintf('No header "%s".', 'Url'));
            return false;
        }

        $omekaAddons = $this->fetchOmekaAddons();

        $updatedAddons = array();

        foreach ($addons as $key => &$addon) {
            if ($key == 0) {
                continue;
            }
            $currentAddon = $addon;

            $addonName = $addon[$headers['Name']];
            $addonUrl = trim($addon[$headers['Url']], '/ ');
            if (empty($addonName) || empty($addonUrl)) {
                continue;
            }

            if ($this->options['debug']) {
                if ($this->options['debugAddon']) {
                    if ($addonName != $this->options['debugAddon']) {
                        continue;
                    }
                }
                elseif ($this->options['debugMax'] && $key > $this->options['debugMax']) {
                    break;
                }
                $this->log($addonName . ' (' . $addonUrl . ')');
            }

            $server = strtolower(parse_url($addonUrl, PHP_URL_HOST));
            switch ($server) {
                case 'github.com':
                    $addonIniBase = str_ireplace('github.com', 'raw.githubusercontent.com', $addonUrl);
                    if ($addon[$headers['Plugin.ini Path']]) {
                        $addonIniBase = str_replace('/tree/master/', '/master/', $addonIniBase);
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
                case 'module':
                    $addonIni = $addon[$headers['Ini Path']] ?: 'master/config/module.ini';
                    break;
            }

            $addonIni = $addonIniBase . '/' . $addonIni;
            if ($this->options['debug']) {
                $this->log('Addon ini: ' . $addonIni);
            }

            $ini = file_get_contents($addonIni);
            if (empty($ini)) {
                $this->log('[No ini      ]' . ' ' . $addonName);
                continue;
            }

            $ini = parse_ini_string($ini);
            if (empty($ini)) {
                $this->log('[No ini keys ]' . ' ' . $addonName);
                continue;
            }

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
                        // Keep the name.
                        case 'name':
                            if (empty($iniValue)) {
                                $iniValue = $addon[$headers[$header]];
                            }
                            $iniValue = str_replace(array(
                                ' Plugin',
                                ' Widget',
                                ' public/admin',
                            ), '', $iniValue);
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
                    $this->log('Before');
                    $this->log($currentAddon);
                    $this->log('After');
                    $this->log($addon);
                }
            }
        }

        foreach ($omekaAddons as $omekaAddon) {
            if (empty($omekaAddon['checked'])) {
                $this->log('[Unreferenced]' . ' ' . $omekaAddon['name']);
            }
        }

        $this->updatedAddons = $updatedAddons;

        return $addons;
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
                $result = preg_match("/(.*)-([0-9\.]*)\.zip/", $filename, $matches);
                $addonName = $matches[1];
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

        $cleanName = str_replace('plugin', '', strtolower(preg_replace('~[^\da-z]~i', '', $addonName)));

        // Manage exception on Omeka.org.
        switch ($cleanName) {
            case 'neatlinewidgetsimiletimeline': return 'neatlinesimiletimeline';
            case 'neatlinewidgettext': return 'neatlinetext';
            case 'neatlinewidgetwaypoints': return 'neatlinewaypoints';
            case 'replacedctitleinallpublicadminviews': return 'replacedctitleinallviews';
            case 'sitemap2': return 'xmlsitemap';
            case 'vracore': return 'vracoreelementset';
            case 'pbcore': return 'pbcoreelementset';
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
