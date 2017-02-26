<?php

/**
 * Helper to update the list of plugins with the last version.
 *
 * The content comes from an goes to the file _data/omeka_plugins.csv
 */

$source = realpath(dirname(__FILE__) . '/../_data/omeka_plugins.csv');
$destination = $source;

// Allow to log (in terminal) the process of all the plugins.
$logAllPlugins = false;

// May avoid an issue with Apple Mac.
ini_set('auto_detect_line_endings', true);

$debug = false;
$debugMax = 1;
$debugOnly = 'Digital Object Linker';

echo sprintf('Start update of "%s".', $source) . PHP_EOL;

if ($debug) {
    echo sprintf('Debug mode enabled.') . PHP_EOL;
}

if (!checkFiles($source, $destination)) {
    return;
}

$plugins = array_map('str_getcsv', file($source));
if (empty($plugins)) {
    echo sprintf('No content in the csv file "%s".', $source) . PHP_EOL;
    return;
}

// Get headers by name.
$headers = array_flip($plugins[0]);

if (!isset($headers['Plugin'])) {
    echo 'No header "Plugin".' . PHP_EOL;
    return;
}

if (!isset($headers['Plugin Url'])) {
    echo 'No header "Plugin Url".' . PHP_EOL;
    return;
}

$omekaPlugins = fetchOmekaPlugins();
if (empty($omekaPlugins)) {
    echo 'The site for Omeka plugins is unavailable.' . PHP_EOL;
    return;
}

// Map of the keys of the plugin ini and the headers of the csv file.
$mappingToUpdate = array(
    'name' => 'Plugin',
    'author' => 'Author',
    'description' => 'Description',
    'license' => 'License',
    'link' => 'Link',
    'support_link' => 'Support Link',
    'version' => 'Last',
    'omeka_minimum_version' => 'Omeka Min',
    'omeka_target_version=' => 'Omeka Target',
    'omeka_tested_up_to' => 'Omeka Target',
    'tags' => 'Tags',
    'required_plugins' => 'Required Plugins',
    'optional_plugins' => 'Optional Plugins',
);

$updatedPlugins = array();
foreach ($plugins as $key => &$plugin) {
    if ($key == 0) {
        continue;
    }
    $currentPlugin = $plugin;

    $pluginName = $plugin[$headers['Plugin']];
    $pluginUrl = trim($plugin[$headers['Plugin Url']], '/ ');
    if (empty($pluginName) || empty($pluginUrl)) {
        continue;
    }

    if ($debug) {
        if ($debugOnly) {
            if ($pluginName != $debugOnly) {
                continue;
            }
        }
        elseif ($key > $debugMax) {
            break;
        }
        echo $pluginName . ' (' . $pluginUrl . ')' . PHP_EOL;
    }

    $server = strtolower(parse_url($pluginUrl, PHP_URL_HOST));
    switch ($server) {
        case 'github.com':
            $pluginIniBase = str_ireplace('github.com', 'raw.githubusercontent.com', $pluginUrl);
            if ($plugin[$headers['Plugin.ini Path']]) {
                $pluginIniBase = str_replace('/tree/master/', '/master/', $pluginIniBase);
            }
            break;
        case 'gitlab.com':
            $pluginIniBase = $pluginUrl . '/raw';
            break;
        default:
            $pluginIniBase = $pluginUrl;
            break;
    }

    $pluginIni = $plugin[$headers['Plugin.ini Path']] ?: 'master/plugin.ini';
    $pluginIni = $pluginIniBase . '/' . $pluginIni;
    if ($debug) {
        echo 'Plugin ini: ' . $pluginIni . PHP_EOL;
    }

    $ini = file_get_contents($pluginIni);
    if (empty($ini)) {
        echo '[No ini      ]' . ' ' . $pluginName . PHP_EOL;
        continue;
    }

    $ini = parse_ini_string($ini);
    if (empty($ini)) {
        echo '[No ini keys ]' . ' ' . $pluginName . PHP_EOL;
        continue;
    }

    foreach ($mappingToUpdate as $iniKey => $header) {
        if (empty($header) || !isset($ini[$iniKey])) {
            continue;
        }

        $iniValue = trim($ini[$iniKey]);

        // Manage exceptions.
        switch ($iniKey) {
            // Keep the name.
            case 'name':
                if (empty($iniValue)) {
                    $iniValue = $plugin[$headers[$header]];
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
                $iniValue = implode(', ', explode(',', $iniValue));
                break;
        }

        $plugin[$headers[$header]] = $iniValue;
    }

    $cleanName = cleanPluginName($pluginName);
    if (isset($omekaPlugins[$cleanName])) {
        $plugin[$headers['Omeka.org']] = $omekaPlugins[$cleanName]['version'];
        $omekaPlugins[$cleanName]['checked'] = true;
    }

    if ($currentPlugin == $plugin) {
        if ($logAllPlugins) {
            echo '[No update   ]' . ' ' . $pluginName . PHP_EOL;
        }
    } else {
        $updatedPlugins[] = $pluginName;
        echo '[Updated     ]' . ' ' . $pluginName . PHP_EOL;
        if ($debug) {
            echo 'Before' . PHP_EOL;
            print_r($currentPlugin);
            echo 'After' . PHP_EOL;
            print_r($plugin);
        }
    }
}

foreach ($omekaPlugins as $omekaPlugin) {
    if (empty($omekaPlugin['checked'])) {
        echo '[Unreferenced]' . ' ' . $omekaPlugin['name'] . PHP_EOL;
    }
}

if ($updatedPlugins) {
    echo sprintf('%d lines updated.', count($updatedPlugins)) . PHP_EOL;
} else {
    echo 'No line updated.' . PHP_EOL;
}

$result = saveToCsvFile($destination, $plugins);
if (!$result) {
    echo sprintf('An error occurred during saving the csv into the file "%s".', $destination) . PHP_EOL;
    return;
}

echo 'Process ended successfully.' . PHP_EOL;

return;

/**
 * Helper to check files.
 *
 * @param string $source
 * @param string $destination
 * @return boolean
 */
function checkFiles($source, $destination)
{
    if (!is_file($source) || !is_readable($source)) {
        echo sprintf('The source file "%s" is not readable.', $source) . PHP_EOL;
        return false;
    }

    if (is_file($destination) && !is_writeable($destination)) {
        echo sprintf('The destination file "%s" is not writeable.', $destination) . PHP_EOL;
        return false;
    }

    if (!is_file($destination) && !is_writeable(dirname($destination))) {
        echo sprintf('The directory "%s" is not writeable.', dirname($destination)) . PHP_EOL;
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
function fetchOmekaPlugins()
{
    $source = 'https://omeka.org/add-ons/plugins/';
    $plugins = array();

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
            $pluginName = $matches[1];
            $cleanName = cleanPluginName($pluginName);
            $plugins[$cleanName] = array();
            $plugins[$cleanName]['name'] = $pluginName;
            $plugins[$cleanName]['url'] = $url;
            $plugins[$cleanName]['version'] = $matches[2];
        }
    }

    return $plugins;
}

/**
 * Clean a plugin name.
 *
 * @param string $name
 * @return string
 */
function cleanPluginName($pluginName)
{
    // Manage exceptions with non standard characters.
    $pluginName = str_replace(array('+'), array('Plus'), $pluginName);

    $cleanName = str_replace('plugin', '', strtolower(preg_replace('~[^\da-z]~i', '', $pluginName)));

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
 * @param array $data
 * @param boolean
 */
function saveToCsvFile($destination, array $array)
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
