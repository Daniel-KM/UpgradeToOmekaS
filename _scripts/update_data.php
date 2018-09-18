<?php

/**
 * Script to update the list of addons of Omeka with the last data.
 *
 * To use, simply run in the terminal, from the root of the sources:
 * ```
 * php -f _scripts/update_data.php
 * ```
 *
 * @author Daniel Berthereau
 * @license Cecill v2.1
 */

// The token is required only to update dates. If empty, the limit will be 60
// requests an hour.
$tokenGithub = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'token_github.txt';
$tokenGithub = file_exists($tokenGithub) ? trim(file_get_contents($tokenGithub)) : '';

$options = [
    'token' => ['api.github.com' => $tokenGithub],
    // Order addons.
    'order' => ['Name', 'Url'],
    // Filter duplicate addons.
    'filterDuplicates' => true,
    // Filter forks only with name, so may remove extensions that are not
    // identified as fork by github.
    'filterFalseForks' => true,
    // Update only one or more types of addon ("plugin", "module", "theme", "template").
    'processOnlyType' => [],
    // Update only one or more addons (set the addon url).
    // 'processOnlyAddon' => array('https://github.com/Daniel-KM/UpgradeToOmekaS'),
    'processOnlyAddon' => [],
    // Update data only for new urls (urls without name).
    'processOnlyNewUrls' => false,
    // Process search for topics on github.
    'processSearch' => true,
    // Regenerate csv only (useful when edited in a spreadsheet).
    'processRegenerateCsvOnly' => false,
    // Allow to log (in terminal) the process of all the addons, else only
    // updated one will be displayed, and errors.
    'logAllAddons' => true,
    // To debug and to add some logs.
    'debug' => false,
    // To debug only the specified number of addons.
    'debugMax' => 0,
    // To display diff between old and new data. May be "none", "diff", "whole".
    'debugDiff' => 'diff',
    // If true, the updated whole csv is saved in the destination after debug.
    'debugOutput' => false,
];

$basepath = realpath(dirname(__FILE__)
    . DIRECTORY_SEPARATOR . '..'
    . DIRECTORY_SEPARATOR . '_data'
    . DIRECTORY_SEPARATOR);

$types = [
    'plugin' => [
        'source' => $basepath . '/omeka_plugins.csv',
        'destination' => $basepath . '/omeka_plugins.csv',
        'topic' => 'omeka-plugin',
        'keywords' => 'Omeka+plugin',
        'ini' => 'plugin.ini',
    ],
    'module' => [
        'source' => $basepath . '/omeka_s_modules.csv',
        'destination' => $basepath . '/omeka_s_modules.csv',
        'topic' => 'omeka-s-module',
        'keywords' => '"Omeka%20S"+module',
        'ini' => 'config/module.ini',
    ],
    'theme' => [
        'source' => $basepath . '/omeka_themes.csv',
        'destination' => $basepath . '/omeka_themes.csv',
        'topic' => 'omeka-theme',
        'keywords' => 'Omeka+theme',
        'ini' => 'theme.ini',
    ],
    'template' => [
        'source' => $basepath . '/omeka_s_themes.csv',
        'destination' => $basepath . '/omeka_s_themes.csv',
        'topic' => 'omeka-s-theme',
        'keywords' => '"Omeka%20S"+theme',
        'ini' => 'config/theme.ini',
    ],
];

foreach ($types as $type => $args) {
    if ($options['processOnlyType'] && !in_array($type, $options['processOnlyType'])) {
        continue;
    }
    $update = new UpdateDataExtensions($type, $args, $options);
    $result = $update->process();
}

return $result;

class UpdateDataExtensions
{
    protected $type = '';
    protected $args = [];
    protected $options = [];

    /**
     * Map of the keys of the plugin ini and the headers of the csv file.
     */
    protected $mappingToUpdate = [
        'common' => [
            'name' => 'Name',
            'author' => 'Author',
            'description' => 'Description',
            'license' => 'License',
            'link' => 'Link',
            'support_link' => 'Support Link',
            'version' => 'Last Version',
            'tags' => 'Tags',
        ],
        // Omeka 1 / 2.
        'plugin' => [
            'required_plugins' => 'Required Plugins',
            'optional_plugins' => 'Optional Plugins',
            'omeka_minimum_version' => 'Omeka Min',
            // Omeka 1.
            'omeka_tested_up_to' => 'Omeka Target',
            // Omeka 2.
            'omeka_target_version=' => 'Omeka Target',
        ],
        'theme' => [
            'title' => 'Name',
            'omeka_minimum_version' => 'Omeka Min',
            'omeka_tested_up_to' => 'Omeka Target',
            'omeka_target_version=' => 'Omeka Target',
        ],
        // Omeka 3 / S.
        'module' => [
            'omeka_version_constraint' => 'Omeka Constraint',
            'module_link' => 'Link',
            'author_link' => 'Author Link',
        ],
        'template' => [
            'theme_link' => 'Link',
            'author_link' => 'Author Link',
        ],
    ];

    protected $headers;
    protected $omekaAddons;
    protected $updatedAddons;

    public function __construct($type, $args = [], $options = [])
    {
        $this->type = $type;
        if (empty($args['destination'])) {
            $args['destination'] = $args['source'];
        }
        $this->args = $args;
        $this->options = $options;
    }

    /**
     * Do the full process.
     *
     * @return bool
     */
    public function process()
    {
        // May avoid an issue with Apple Mac.
        ini_set('auto_detect_line_endings', true);

        $this->log(sprintf('Start update of "%s".', $this->args['source']));

        if ($this->options['debug']) {
            $this->log('Debug mode enabled.');
        }

        if (!$this->checkFiles($this->args['source'], $this->args['destination'])) {
            return false;
        }

        $addons = array_map('str_getcsv', file($this->args['source']));
        if (empty($addons)) {
            $this->log(sprintf('No content in the csv file "%s".', $this->args['source']));
            return false;
        }

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

        if ($this->options['processSearch']) {
            $addons = $this->search($addons);
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

        $addons = $this->filterDuplicates($addons);

        // Re-order after deduplicating may fixes order issues.
        $addons = $this->order($addons);

        if ($this->options['debug'] && !$this->options['debugOutput']) {
            $this->log('Required no output.');
        } else {
            $result = $this->saveToCsvFile($this->args['destination'], $addons);
            if (!$result) {
                $this->log(sprintf('An error occurred during saving the csv into the file "%s".', $this->args['destination']));
                return false;
            }
        }

        $this->log('Process ended successfully.');

        return true;
    }

    /**
     * @param array $addons
     * @return array
     */
    protected function search(array $addons)
    {
        $headers = $this->headers;

        // Get the list of urls.
        $urls = array_map(function ($v) use ($headers) {
            return $v[$headers['Url']];
        }, $addons);

        $newUrls = [];

        // Search on github.
        // Search topics are only available in preview currently.
        $curlHeaders = ['Accept: application/vnd.github.mercy-preview+json'];
        $searches = [
            // Search via topic.
            'topic' => 'https://api.github.com/search/repositories?q=topic:' . $this->args['topic'] . '+fork%3Afalse',
            'keywords' => 'https://api.github.com/search/repositories?q=' . $this->args['keywords'] . '+fork%3Afalse' . '+in:name,description,readme',
        ];

        foreach ($searches as $searchType => $url) {
            // // Limit the search to the last month.
            // $date = (new DateTime('first day of previous month'))->format('Y-m-d');
            // $url .= '++pushed%3A%3E' . $date;
            // Sort by last updated to get the new plugins first.
            $url .= '&s=updated';

            $url .= '&per_page=100';

            $response = $this->curl($url, $curlHeaders);
            if ($response) {
                if ($this->options['debug']) {
                    $this->log(sprintf(
                        'The search for %s with %s gives %d results.',
                        $this->type,
                        $searchType,
                        $response->total_count
                    ));
                }
                $totalCount = $response->total_count;
                if ($totalCount > 0) {
                    $totalProcessed = 0;
                    $page = 1;
                    do {
                        // $this->log(sprintf('The search for %s on %s gives %d results. Wait for processing.' . $searchType, $this->type, $totalCount));
                        $this->log('.');
                        if ($page > 1) {
                            $urlPage = $url . '&page=' . $page;
                            $response = $this->curl($urlPage, $curlHeaders);
                            // A special check to avoid an infinite loop.
                            if (!$response || $response->total_count == 0 || count($response->items) == 0) {
                                break;
                            }
                        }
                        $resultNewUrls = $this->filterNewUrlsFromSearchResults($response, $urls);
                        $addons = array_merge($addons, $resultNewUrls['new_addons']);
                        $urls = array_merge($urls, $resultNewUrls['new_urls']);
                        $newUrls = array_merge($newUrls, $resultNewUrls['new_urls']);
                        $totalProcessed += count($response->items);
                        ++$page;
                    } while ($totalProcessed < $totalCount);
                }
            } else {
                $this->log('No search on github.');
                break;
            }
        }

        if ($newUrls) {
            $this->log(sprintf('%d new urls for %s.', count($newUrls), $this->type));
            $this->log($newUrls);
        } else {
            $this->log(sprintf('No new urls for %s.', $this->type));
        }

        return $addons;
    }

    /**
     * Helper to process results of a search.
     *
     * @param object $response
     * @param array $urls The list of existing urls to filter the response.
     * @result array A list of new addons and new urls.
     */
    protected function filterNewUrlsFromSearchResults($response, $urls)
    {
        $headers = $this->headers;
        $addonBase = array_fill(0, count($headers), null);
        $newAddons = [];
        $newUrls = [];

        foreach ($response->items as $repo) {
            if (in_array($repo->html_url, $urls)) {
                if ($this->options['debug']) {
                    $this->log(sprintf('Exists    : %s', $repo->html_url));
                }
                continue;
            }
            if ($repo->fork) {
                if ($this->options['debug']) {
                    $this->log(sprintf('Is fork   : %s', $repo->html_url));
                }
                continue;
            }
            // Check if there is a config file to avoid false result.
            $addonIni = str_ireplace('github.com', 'raw.githubusercontent.com', $repo->html_url)
                . '/master/' . $this->args['ini'];
            $ini = @file_get_contents($addonIni);
            if (empty($ini)) {
                if ($this->options['debug']) {
                    $this->log(sprintf('No config : %s', $repo->html_url));
                }
                continue;
            }

            if ($this->options['debug']) {
                $this->log(sprintf('NEW       : %s', $repo->html_url));
            }

            $addon = $addonBase;
            $addon[$headers['Url']] = $repo->html_url;
            $newAddons[] = $addon;
            $urls[] = $repo->html_url;
            $newUrls[] = $repo->html_url;
        }

        return [
            'new_addons' => $newAddons,
            'new_urls' => $newUrls,
        ];
    }

    /**
     * Regenerate data.
     *
     * @param array $addons
     * @return array
     */
    protected function update(array $addons)
    {
        $headers = $this->headers;

        $omekaAddons = $this->fetchOmekaAddons();
        $this->omekaAddons = $omekaAddons;

        $this->updatedAddons = [];

        foreach ($addons as $key => $addon) {
            if ($key == 0) {
                continue;
            }
            $addonUrl = trim($addon[$headers['Url']], '/ ');
            if (empty($addonUrl)) {
                continue;
            }

            if ($this->options['processOnlyAddon'] && !in_array($addonUrl, $this->options['processOnlyAddon'])) {
                continue;
            }

            $addonName = $addon[$headers['Name']];
            if ($addonName && $this->options['processOnlyNewUrls']) {
                continue;
            }

            // Set a temp addon name.
            if (empty($addonName)) {
                $addonName = basename($addonUrl);
            }

            if ($this->options['debug']) {
                if ($this->options['debugMax'] && $key > $this->options['debugMax']) {
                    break;
                }
                // $this->log($addonName . ' (' . $addonUrl . ')');
            }

            $addons[$key] = $this->updateAddon($addon);
        }

        if (!$this->options['processOnlyNewUrls']) {
            foreach ($this->omekaAddons as $omekaAddon) {
                if (empty($omekaAddon['checked'])) {
                    $unref = isset($omekaAddon['title'])
                        ? $omekaAddon['title']
                        : $omekaAddon['name'];
                    $url = '';
                    foreach ($addons as $addon) {
                        if ($addon[$headers['Name']] == $unref) {
                            $url = trim($addon[$headers['Url']], '/ ');
                            if ($addon[$headers['Name']]) {
                                $unref = $addon[$headers['Name']];
                            }
                            break;
                        }
                    }
                    if (empty($url)
                            || (
                                $this->options['processOnlyAddon']
                                && !in_array($url, $this->options['processOnlyAddon'])
                        )) {
                        continue;
                    }
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

        // Set the date of the creation and the last update, that doesn't depend
        // on ini, and don't update if empty.
        $date = $this->findDate($addonUrl, 'creation date');
        if ($date) {
            $addon[$headers['Creation Date']] = $date;
        }
        $date = $this->findDate($addonUrl, 'last update');
        if ($date) {
            $addon[$headers['Last Update']] = $date;
        }

        $server = strtolower(parse_url($addonUrl, PHP_URL_HOST));
        switch ($server) {
            case 'github.com':
                $addonIniBase = str_ireplace('github.com', 'raw.githubusercontent.com', $addonUrl);
                if ($addon[$headers['Ini Path']]) {
                    $replacements = [
                        '/tree/master/' => '/master/',
                        '/tree/develop/' => '/develop/',
                    ];
                    $addonIniBase = str_replace(
                        array_keys($replacements),
                        array_values($replacements),
                        $addonIniBase
                    );
                }
                break;
            case 'gitlab.com':
                $addonIniBase = $addonUrl . '/raw';
                break;
            default:
                $addonIniBase = $addonUrl;
                break;
        }

        $addonIni = $addon[$headers['Ini Path']] ?: ('master/' . $this->args['ini']);

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
                        $iniValue = str_ireplace([
                            ' plugin',
                            'plugin ',
                            ' module',
                            'module ',
                            ' theme',
                            'theme ',
                            ' widget',
                            'widget ',
                            ' public/admin',
                        ], '', $iniValue);
                        $addonName = $iniValue;
                        break;
                    // Remove the "v" and fill no version.
                    case 'version':
                        $iniValue = trim(ltrim($iniValue, 'vV'));
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
     * Reorder addons.
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

        $orders = is_array($this->options['order']) ? $this->options['order'] : [$this->options['order']];
        foreach ($orders as $key => $order) {
            if (!isset($headers[$order])) {
                $this->log(sprintf('Order %s not found in headers.', $order));
                return $addons;
            }
            $orders[$key] = $headers[$order];
        }

        unset($addons[0]);

        $addonsList = [];
        foreach ($addons as $key => &$addon) {
            $value = '';
            foreach ($orders as $order) {
                $value .= $addon[$order];
            }
            $addonsList[$key] = $value;
        }
        natcasesort($addonsList);
        $addonsList = array_replace($addonsList, $addons);
        array_unshift($addonsList, null);
        $addonsList[0] = array_keys($headers);

        return $addonsList;
    }

    /**
     * Filter duplicate addons.
     *
     * @param array $addons
     * @return array
     */
    protected function filterDuplicates(array $addons)
    {
        if (empty($this->options['filterDuplicates'])) {
            return $addons;
        }

        $total = count($addons);
        if ($total <= 1) {
            return $addons;
        }

        // Get headers by name.
        $headers = array_flip($addons[0]);

        $duplicates = 0;
        $unidentifiedForks = 0;
        foreach ($addons as $key => $addon) {
            if ($key == 0) {
                continue;
            }
            // Get the name and last update of the first row.
            elseif ($key == 1) {
                $previousName = $addon[$headers['Name']];
                $previousLastUpdate = $addon[$headers['Last Update']];
                $previousKey = $key;
                continue;
            }
            $name = $addon[$headers['Name']];
            $lastUpdate = $addon[$headers['Last Update']];
            if ($name === $previousName && $lastUpdate === $previousLastUpdate) {
                ++$duplicates;
                unset($addons[$key]);
            } elseif ($name === $previousName && !empty($this->options['filterFalseForks'])) {
                ++$unidentifiedForks;
                if ($previousLastUpdate < $lastUpdate) {
                    unset($addons[$previousKey]);
                } else {
                    unset($addons[$key]);
                    continue;
                }
            }
            $previousName = $name;
            $previousLastUpdate = $lastUpdate;
            $previousKey = $key;
        }

        if ($duplicates) {
            $this->log(sprintf('%d duplicate rows were removed.', $duplicates));
        }
        if ($unidentifiedForks) {
            $this->log(sprintf('%d duplicate rows (unidentified forks with same name) were removed.', $unidentifiedForks));
        }

        return array_values($addons);
    }

    /**
     * Helper to check files.
     *
     * @param string $source
     * @param string $destination
     * @return bool
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
            $this->log(sprintf('The directory "%s" is not writeable.', dirname($destination)));
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
                return [];
        }

        $addons = [];

        $html = file_get_contents($source);
        if (empty($html)) {
            return [];
        }

        libxml_use_internal_errors(true);
        $pokemon_doc = new DOMDocument();
        $pokemon_doc->loadHTML($html);
        $pokemon_xpath = new DOMXPath($pokemon_doc);
        $pokemon_row = $pokemon_xpath->query('//a[@class="omeka-addons-button"]/@href');
        if ($pokemon_row->length > 0) {
            $matches = [];
            foreach ($pokemon_row as $row) {
                $url = $row->nodeValue;
                $filename = basename(parse_url($url, PHP_URL_PATH));
                // Some addons have "-" in name; some have letters in version.
                preg_match('~([^\d]+)\-(\d.*)\.zip~', $filename, $matches);
                // Manage for example "Select2".
                if (empty($matches)) {
                    preg_match('~(.*?)\-(\d.*)\.zip~', $filename, $matches);
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
                $addons[$cleanName] = [];
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
        $addonName = str_replace(['+'], ['Plus'], $name);

        $cleanName = str_ireplace(
            ['plugin', 'module', 'theme'],
            '',
            preg_replace('~[^\da-z]~i', '', strtolower($addonName))
        );

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
     * Get the date and time of the creation of the repository.
     *
     * @param string $addonUrl
     * @param string $dateToFind
     * @return string
     */
    protected function findDate($addonUrl, $dateToFind)
    {
        static $data = [];

        $project = trim(parse_url($addonUrl, PHP_URL_PATH), '/');
        $server = strtolower(parse_url($addonUrl, PHP_URL_HOST));
        if (!isset($data[$addonUrl])) {
            switch ($server) {
                case 'github.com':
                    $user = strtok($project, '/');
                    $projectName = strtok('/');
                    $url = 'https://api.github.com/repos/' . $user . '/' . $projectName;
                    $data[$addonUrl] = $this->curl($url);
                    break;
                default:
                    $data[$addonUrl] = '';
                    return '';
            }
        }

        if (empty($data[$addonUrl])) {
            return '';
        }

        $response = $data[$addonUrl];
        switch ($server) {
            case 'github.com':
                switch ($dateToFind) {
                    case 'creation date':
                        return $response->created_at;
                    case 'last update':
                        // "updated_at" means the last update in metadata,
                        // whereas "pushed_at" means the last commit.
                        // $url = 'https://api.github.com/repos/' . $project . '/commits/HEAD';
                        // $date = $response->commit->committer->date;
                        return $response->pushed_at;
                }
        }
    }

    /**
     * Helper to get the response of a web service.
     *
     * @param string $url
     * @param array $headers
     * @return string
     */
    protected function curl($url, $headers = [])
    {
        static $flag;

        // Allows only one main error.
        if ($flag) {
            return '';
        }

        $userAgent = 'Mozilla/5.0 (X11; Linux x86_64; rv:57.0) Gecko/20100101 Firefox/57.0';

        $curl = curl_init();

        $server = strtolower(parse_url($url, PHP_URL_HOST));
        if (!empty($this->options['token'][$server])) {
            switch ($server) {
                case 'api.github.com':
                    $headers[] = 'Authorization: token ' . $this->options['token'][$server];
                    break;
            }
        }

        $headers[] = 'Accept: application/vnd.github.v3+json';

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_USERAGENT, $userAgent);
        // curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        // curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        if ($headers) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }

        $response = curl_exec($curl);
        curl_close($curl);

        if ($response === false) {
            if (empty($flag)) {
                $flag = true;
                $this->log(sprintf('No response from curl for url %s.', $url));
            }
            return '';
        }

        $response = json_decode($response);
        if (empty($response)) {
            if (empty($flag)) {
                $flag = true;
                $this->log(sprintf('Empty response from curl for url %s.', $url));
            }
            return '';
        }
        if (!empty($response->message)) {
            if (empty($flag)) {
                $this->log(sprintf('Error on url %s: %s.', $url, $response->message));
            }
            return '';
        }

        return $response;
    }

    /**
     * Save an array into a csv file.
     *
     * @param string $destination
     * @param array $array
     * @return bool
     */
    protected function saveToCsvFile($destination, array $array)
    {
        $handle = fopen($destination, 'w');
        if (empty($handle)) {
            return false;
        }
        foreach ($array as $row) {
            fputcsv($handle, $row);
        }
        return fclose($handle);
    }

    /**
     * Echo a message to the standard output.
     *
     * @param mixed $message
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
