<?php

declare(strict_types=1);

/**
 * Script to update the list of addons of Omeka with the last data.
 *
 * To use, simply run in the terminal, from the root of the sources:
 * ```sh
 * php -f _scripts/update_data.php
 * ```
 *
 * @author Daniel Berthereau
 * @copyright 2017-2024 Daniel Berthereau
 * @license Cecill v2.1
 */

$datapath = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . '_data') . DIRECTORY_SEPARATOR;

// The token is required only to update dates. If empty, the limit will be 60
// requests an hour.
$tokenGithub = $datapath . 'token_github.txt';
$tokenGithub = file_exists($tokenGithub) ? trim(file_get_contents($tokenGithub)) : '';

$options = [
    'token' => ['api.github.com' => $tokenGithub],
    // Set options to order addons: order by alphabtic name, then the one that
    // is not a fork first, then the oldest created one, then the alphabetic
    // url.
    // This order avoids the issue with CSV Import, where the upstream master is
    // not updated since a while (tags and release are set on dev branch), and
    // where there are some forks that are not marked as fork, but more recent
    // than the upstream master branch.
    'order' => [
        'Name' => 'asc',
        // Fork is an exception: presence or not, without checking content.
        'Fork source' => 'desc',
        'Creation date' => 'asc',
        // The update may not be the last commit. The same for version.
        // 'Last update' => 'desc',
        'Url' => 'asc',
    ],
    // Filter duplicate addons. A duplicate has the same name and date or version.
    'filterDuplicates' => true,
    // Filter forks only with name, so may remove extensions that are not
    // identified as fork by github.
    'filterFalseForks' => true,
    // Keep forks that are new versions of the source,
    'keepUpdatedForks' => true,
    // Filter false addons (students testing, etc.). See file excluded_urls.txt.
    'filterFalseAddons' => true,
    'excludedUrlsPath' => $datapath . 'excluded_urls.txt',
    // Update only one or more types of addon ("plugin", "module", "theme", "template").
    'processOnlyType' => [
    ],
    // Update only one or more addons (set the addon url).
    // 'processOnlyAddon' => array('https://github.com/Daniel-KM/Omeka-plugin-UpgradeToOmekaS'),
    'processOnlyAddon' => [
    ],
    // Update data only for new urls (urls without name).
    'processOnlyNewUrls' => false,
    // Process search for topics on github.
    'processSearch' => true,
    // Process update of existing addons and new ones (if search is enabled).
    'processUpdate' => true,
    // Regenerate csv directly, so reformat the csv without useless enclosures.
    // It will skip the search and update processes.
    // This is useful when the csv is edited in a spreadsheet to avoid to change
    // unedited rows.
    'processFormatCsvOnly' => false,
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

$types = [
    'plugin' => [
        'source' => $datapath . 'omeka_plugins.csv',
        'destination' => $datapath . 'omeka_plugins.csv',
        'topic' => 'omeka-plugin',
        'keywords' => 'Omeka+plugin',
        'ini' => 'plugin.ini',
    ],
    'module' => [
        'source' => $datapath . 'omeka_s_modules.csv',
        'destination' => $datapath . 'omeka_s_modules.csv',
        'topic' => 'omeka-s-module',
        'keywords' => '"Omeka%20S"+module',
        'ini' => 'config' . DIRECTORY_SEPARATOR . 'module.ini',
    ],
    'theme' => [
        'source' => $datapath . 'omeka_themes.csv',
        'destination' => $datapath . 'omeka_themes.csv',
        'topic' => 'omeka-theme',
        'keywords' => 'Omeka+theme',
        'ini' => 'theme.ini',
    ],
    'template' => [
        'source' => $datapath . 'omeka_s_themes.csv',
        'destination' => $datapath . 'omeka_s_themes.csv',
        'topic' => 'omeka-s-theme',
        'keywords' => '"Omeka%20S"+theme',
        'ini' => 'config' . DIRECTORY_SEPARATOR . 'theme.ini',
    ],
];

foreach ($types as $type => $args) {
    if ($options['processOnlyType'] && !in_array($type, $options['processOnlyType'])) {
        continue;
    }
    $update = new UpdateDataExtensions($type, $args, $options);
    $result = $update->process();
    $update->processUpdateLastVersions();
}

exit($result ? 0 : 'An error occurred.');

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
            'support_link' => 'Support link',
            'version' => 'Last version',
            'tags' => 'Tags',
        ],
        // Omeka 1 / 2 / Classic.
        'plugin' => [
            'required_plugins' => 'Required plugins',
            'optional_plugins' => 'Optional plugins',
            'omeka_minimum_version' => 'Omeka min',
            // Omeka 1.
            'omeka_tested_up_to' => 'Omeka target',
            // Omeka 2.
            'omeka_target_version=' => 'Omeka target',
        ],
        'theme' => [
            'title' => 'Name',
            'omeka_minimum_version' => 'Omeka min',
            'omeka_tested_up_to' => 'Omeka target',
            'omeka_target_version=' => 'Omeka target',
        ],
        // Omeka 3 / S.
        'module' => [
            'omeka_version_constraint' => 'Omeka constraint',
            'module_link' => 'Link',
            'author_link' => 'Author link',
            'dependencies' => 'Dependencies',
        ],
        'template' => [
            'theme_link' => 'Link',
            'author_link' => 'Author link',
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

        if ($this->options['processFormatCsvOnly']) {
            $addons = $this->order($addons);
            $result = $this->saveToCsvFile($this->args['destination'], $addons);
            if (!$result) {
                $this->log(sprintf('An error occurred during saving the csv into the file "%s".', $this->args['destination']));
                return false;
            }
            return true;
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

        if ($this->options['processUpdate']) {
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

        $addons = $this->filterFalseAddons($addons);

        $addons = $this->order($addons);

        $addons = $this->filterDuplicates($addons);

        // Re-order after deduplicating may fixes order issues.
        $addons = $this->order($addons);

        if ($this->options['debug'] && !$this->options['debugOutput']) {
            $this->log('Required no output with debug.');
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
     * Create the json for last versions from the csv file.
     */
    public function processUpdateLastVersions()
    {
        $this->log(sprintf('Start update of file of last versions for "%s".', $this->args['topic']));

        if ($this->options['debug']) {
            $this->log('Debug mode enabled.');
        }

        $destination = str_replace('.csv', '_versions.tsv', $this->args['destination']);
        if (!$this->checkFiles($this->args['source'], $destination)) {
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
        unset($addons[0]);

        $replaceForName = [
            'plugin',
            'module',
            'theme',
            'widget',
            'omeka s',
            'omeka-s',
            'omeka',
        ];

        $addonsLastVersions = [];
        foreach ($addons as $addon) {
            // TODO Add the directory name in the csv file.
            // TODO Use extractNamespaceFromProjectName(). See findData().
            // Set the name or a temp addon name.
            $addonUrl = trim($addon[$headers['Url']], '/ ');
            $addonName = basename($addonUrl);
            $addonName = str_ireplace($replaceForName, '', $addonName);
            if (empty($addonName)) {
                continue;
            }

            $cleanName = $this->cleanAddonNameWithCase($addonName);
            $addonFullName = $addon[$headers['Name']];

            // To improve correct name as long as the directory name is not stored,
            // compare it to the name and clean name.
            if (strtolower($addonName) === preg_replace('~[^0-9a-zA-Z]~i', '', strtolower($addonFullName))
                || strtolower($cleanName) === preg_replace('~[^0-9a-zA-Z]~i', '', strtolower($addonFullName))
            ) {
                $cleanName = preg_replace('~[^0-9a-zA-Z]~i', '', $addonFullName);
            }

            $lastVersion = $addon[$headers['Last version'] ?? $headers['Last Version']] ?? '';

            if (empty($addonsLastVersions[$cleanName])) {
                $addonsLastVersions[$cleanName] = $lastVersion;
            } elseif (version_compare($addonsLastVersions[$cleanName], $lastVersion, '<=')) {
                $addonsLastVersions[$cleanName] = $lastVersion;
            }
            $this->log("$lastVersion : $cleanName ($addonFullName / $addonUrl)");
        }

        if ($this->options['debug'] && !$this->options['debugOutput']) {
            $this->log('Required no output with debug.');
        } else {
            $resultTsv = [];
            foreach ($addonsLastVersions as $addon => $version) {
                $resultTsv[] = [$addon, $version];
            }
            $result = $this->saveToTsvFile($destination, $resultTsv);
            if (!$result) {
                $this->log(sprintf('An error occurred during saving the tsv into the file "%s".', $destination));
                return false;
            }
            /*
            $result = $this->saveToJsonFile($destination, $addonsLastVersions);
            if (!$result) {
                $this->log(sprintf('An error occurred during saving the json into the file "%s".', $destination));
                return false;
            }
            */
        }

        $this->log(sprintf('Process ended successfully for "%s".', $this->args['topic']));
        $this->log('');

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

        // Topic is now included in default query, so there is only one search
        // query.
        $searches = [
            'keywords' => 'https://api.github.com/search/repositories?q=' . $this->args['keywords'] . '+fork%3Afalse' . '+in:topics,name,description,readme',
        ];

        foreach ($searches as $searchType => $url) {
            // // Limit the search to the last month.
            // $date = (new DateTime('first day of previous month'))->format('Y-m-d');
            // $url .= '++pushed%3A%3E' . $date;
            // Sort by last updated to get the new plugins first.
            $url .= '&s=updated';

            $url .= '&per_page=100';

            if ($this->options['debug']) {
                $this->log(sprintf('curl %s', $url));
                $time = time();
            }
            $response = $this->curl($url);
            if ($this->options['debug']) {
                $this->log(sprintf('    %d seconds', (time() - $time)));
            }
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
                            $response = $this->curl($urlPage);
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
                $this->log('No search results on github.');
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
        $toExclude = !empty($this->options['filterFalseAddons']) && file_exists($this->options['excludedUrlsPath'])
          ? array_filter(array_map('trim', explode("\n", file_get_contents($this->options['excludedUrlsPath']))))
          : [];

        foreach ($response->items as $repo) {
            if (in_array($repo->html_url, $urls)) {
                if ($this->options['debug']) {
                    $this->log(sprintf('Exists    : %s', $repo->html_url));
                }
                continue;
            }
            if (in_array($repo->html_url, $toExclude)) {
                if ($this->options['debug']) {
                    $this->log(sprintf('Excluded  : %s', $repo->html_url));
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
            $ini = $this->getIniForAddon($repo->html_url);
            if (empty($ini)) {
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
                    $this->log('[Unreferenced  ]' . ' ' . $unref);
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
        $currentAddon = $addon;

        // Set the name or a temp addon name.
        $addonUrl = trim($addon[$headers['Url']], '/ ');
        $addonName = $addon[$headers['Name']] ?: basename($addonUrl);

        // Set the date of the creation and the last update, that doesn’t depend
        // on ini, and don’t update if empty.
        $value = $this->findData($addonUrl, 'creation date');
        if ($value) {
            $addon[$headers['Creation date']] = $value;
        }

        $value = $this->findData($addonUrl, 'last update');
        if ($value) {
            $addon[$headers['Last update']] = $value;
        }

        $value = $this->findData($addonUrl, 'fork source');
        if ($value) {
            $addon[$headers['Fork source']] = $value;
        }

        $value = $this->findData($addonUrl, 'last released zip');
        if ($value) {
            $addon[$headers['Last released zip']] = $value;
        }

        $value = $this->findData($addonUrl, 'directory name');
        if ($value) {
            $addon[$headers['Directory name']] = $value;
        }

        $value = $this->findData($addonUrl, 'count versions');
        if ($value) {
            $addon[$headers['Count versions']] = $value;
        }

        $value = $this->findData($addonUrl, 'total downloads');
        if ($value) {
            $addon[$headers['Total downloads']] = $value;
        }

        $ini = $this->getIniForAddon($addonUrl, $addon[$headers['Ini path']] ?? null);
        if (empty($ini) && empty($addon[$headers['Ini path']])) {
            return $addon;
        }

        if (!is_string($ini)) {
            $this->log('[Invalid ini   ]' . ' ' . $addonName);
            return $addon;
        }

        $ini = parse_ini_string($ini);
        if (empty($ini)) {
            $this->log('[No key in ini ]' . ' ' . $addonName);
            return $addon;
        }

        // Update each configured keys in the ini file.
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
                            $iniValue = ($addon[$headers[$header]] ?? null) ?: $addonName;
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
                    case 'dependencies':
                        $iniValue = implode(', ', array_filter(array_map('trim', explode(',', $iniValue))));
                        break;
                }

                $addon[$headers[$header]] = $iniValue;
            }
        }

        // Manage some specificities.
        switch ($this->type) {
            case 'plugin':
                // Set if the plugin is upgradable.
                if (!empty($addon[$headers['Module']]) && empty($addon[$headers['Upgradable']])) {
                    $addon[$headers['Upgradable']] = 'Yes';
                }
                break;
            default:
                break;
        }

        $cleanName = $this->cleanAddonName($addonName);
        if (isset($omekaAddons[$cleanName])) {
            $addon[$headers['Omeka.org']] = $omekaAddons[$cleanName]['version'];
            $omekaAddons[$cleanName]['checked'] = true;
        }

        if ($currentAddon == $addon) {
            if ($this->options['logAllAddons']) {
                $this->log('[No update     ]' . ' ' . $addonName) . PHP_EOL;
                if ($this->options['debug']) {
                    $this->log('No update for addon');
                    $this->log($currentAddon);
                }
            }
        } else {
            $this->updatedAddons[] = $addonName;
            echo '[Updated       ]' . ' ' . $addonName . PHP_EOL;
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

        $orders = is_array($this->options['order']) ? $this->options['order'] : [$this->options['order'] => 'asc'];

        // Check orders.
        foreach ($orders as $order => $sort) {
            if (!isset($headers[$order])) {
                $this->log(sprintf('ERROR: Order %s not found in headers.', $order));
                unset($orders[$order]);
            } else {
                $orders[$order] = strtolower($sort) === 'desc' ? -1 : 1;
            }
        }

        if (empty($this->options['order'])) {
            return $addons;
        }

        $compareAddons = function (array $addonA, array $addonB) use ($orders, $headers): int {
            foreach ($orders as $order => $sort) {
                $dataA = $addonA[$headers[$order]] ?? '';
                $dataB = $addonB[$headers[$order]] ?? '';
                if (($dataA && !$dataB)
                    || (!$dataA && $dataB)
                ) {
                    return $dataA ? -1 * $sort : 1 * $sort;
                } elseif ($order === 'Fork source') {
                    // Exception for fork: don't check url order, but presence
                    // or not (done above).
                    continue;
                } else {
                    $result = strnatcasecmp((string) $dataA, (string) $dataB);
                    if ($result) {
                        return $result * $sort;
                    }
                }
            }
            return 0;
        };

        unset($addons[0]);
        usort($addons, $compareAddons);

        array_unshift($addons, null);
        $addons[0] = array_keys($headers);

        return $addons;
    }

    /**
     * Filter false addons (students works, etc.).
     *
     * @param array $addons
     * @return array
     */
    protected function filterFalseAddons(array $addons)
    {
        if (empty($this->options['filterFalseAddons'])) {
            return $addons;
        }

        if (!file_exists($this->options['excludedUrlsPath'])) {
            return $addons;
        }

        $toExclude = array_filter(array_map('trim', explode("\n", file_get_contents($this->options['excludedUrlsPath']))));
        if (!count($toExclude)) {
            return $addons;
        }

        // Get headers by name.
        $headers = array_flip($addons[0]);
        $totalExcluded = 0;
        foreach ($addons as $key => $addon) {
            $url = $addon[$headers['Url']];
            if (in_array($url, $toExclude)) {
                unset($addons[$key]);
                ++$totalExcluded;
            }
        }

        if ($totalExcluded) {
            $this->log(sprintf('%d urls were excluded on a general list of %d urls.', $totalExcluded, count($toExclude)));
        } else {
            $this->log(sprintf('No url was excluded on a general list of %d urls.', count($toExclude)));
        }

        return array_values($addons);
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

        // Because the addons are ordered as we want (see option "order"), the
        // previous row is always kept when duplicate.

        // TODO So this comparison can be removed.
        // Keep the addon that is not a fork, else the oldest created.
        /*
        $compareAddons = function (array $addonA, array $addonB) use ($headers): int {
            $forkSourceA = $addonA[$headers['Fork source']] ?? '';
            $forkSourceB = $addonB[$headers['Fork source']] ?? '';
            if (($forkSourceA && !$forkSourceB)
                || (!$forkSourceA && $forkSourceB)
            ) {
                return $forkSourceA ? 1 : -1;
            }
            $creationDateA = $addonA[$headers['Creation date']] ?? '';
            $creationDateB = $addonB[$headers['Creation date']] ?? '';
            if (!$creationDateA || !$creationDateB) {
                return $creationDateA ? -1 : 1;
            }
            return $creationDateA <=> $creationDateB;
        };
        */

        $duplicates = 0;
        $unidentifiedForks = 0;
        $updatedForks = 0;

        // Store previous row for comparison.
        $previousKey = -1;
        $previousAddon = [];
        $previousUrl = '';
        $previousName = '';
        $previousCreationDate = '';
        $previousLastUpdate = '';
        $previousForkSource = '';
        $previousVersion = '';

        foreach ($addons as $key => $addon) {
            if ($key === 0) {
                continue;
            }

            $url = $addon[$headers['Url']];
            $name = $addon[$headers['Name']];
            $creationDate = $addon[$headers['Creation date']] ?? '';
            $lastUpdate = $addon[$headers['Last update']] ?? '';
            $version = $addon[$headers['Last version']] ?? '';
            $forkSource = $addon[$headers['Fork source']] ?? '';

            if ($name === $previousName) {
                if ($lastUpdate === $previousLastUpdate || $version === $previousVersion) {
                    ++$duplicates;
                    $this->log(sprintf('Duplicate fork removed (identical): %1$s (%2$s)', $name, $url));
                    // Already ordered, so keep first.
                    unset($addons[$key]);
                    continue;
                } elseif (!empty($this->options['filterFalseForks'])) {
                    if (version_compare($previousVersion, $version, '<') && !empty($this->options['keepUpdatedForks'])) {
                        ++$updatedForks;
                        $this->log(sprintf('Duplicate fork kept (different): %1$s (%2$s)', $name, $url));
                    } else {
                        ++$unidentifiedForks;
                        $this->log(sprintf('Duplicate fork removed (older): %1$s (%2$s)', $name, $url));
                        // Already ordered, so keep first.
                        unset($addons[$key]);
                        continue;
                    }
                }
            }

            $previousKey = $key;
            $previousAddon = $addon;
            $previousUrl = $url;
            $previousName = $name;
            $previousCreationDate = $creationDate;
            $previousLastUpdate = $lastUpdate;
            $previousVersion = $version;
            $previousForkSource = $forkSource;
        }

        if ($duplicates) {
            $this->log(sprintf('%d duplicate rows were removed (identical).', $duplicates));
        }
        if ($unidentifiedForks) {
            $this->log(sprintf('%d duplicate rows were removed (unidentified forks with same name).', $unidentifiedForks));
        }
        if ($updatedForks) {
            $this->log(sprintf('%d duplicate rows were kept (updated forks).', $updatedForks));
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
            ['', '', ''],
            preg_replace('~[^0-9a-z]~i', '', strtolower($addonName))
        );

        // Manage exception on Omeka.org.
        $exceptions = [
            'neatlinewidgetsimiletimeline' => 'neatlinesimiletimeline',
            'neatlinewidgettext' => 'neatlinetext',
            'neatlinewidgetwaypoints' => 'neatlinewaypoints',
            'replacedctitleinallpublicadminviews' => 'replacedctitleinallviews',
            'sitemap2' => 'xmlsitemap',
            'vracore' => 'vracoreelementset',
            'pbcore' => 'pbcoreelementset',
            'media' => 'html5media',
        ];

        return $exceptions[$cleanName] ?? $cleanName;
    }

    /**
     * Clean an addon name keeping case to simplify matching.
     *
     * @param string $name
     * @return string
     */
    protected function cleanAddonNameWithCase($name)
    {
        // Manage exceptions with non standard characters (avoid duplicates).
        $addonName = str_replace(['+'], ['Plus'], $name);

        $cleanName = str_ireplace(
            ['plugin', 'module', 'theme'],
            ['', '', ''],
            preg_replace('~[^0-9a-zA-Z]~i', '', $addonName)
        );

        // Manage exception on Omeka.org.
        $exceptions = [
            'neatlinewidgetsimiletimeline' => 'NeatlineSimileTimeline',
            'neatlinewidgettext' => 'NeatlineText',
            'neatlinewidgetwaypoints' => 'NeatlineWaypoints',
            'replacedctitleinallpublicadminviews' => 'ReplacedcTitleInAllViews',
            'sitemap2' => 'XmlSitemap',
            'vracore' => 'VraCoreElementSet',
            'pbcore' => 'PBCore-Element-Set',
            'media' => 'Html5Media',
        ];

        return $exceptions[strtolower($cleanName)] ?? $cleanName;
    }

    protected function extractNamespaceFromProjectName($projectName)
    {
        if (!$projectName) {
            return (string) $projectName;
        }

        // Exceptions.
        $exceptions = [
            'UpgradeToOmekaS' => 'UpgradeToOmekaS',
            'Omeka-plugin-UpgradeToOmekaS' => 'UpgradeToOmekaS',
            'UpgradeFromOmekaClassic' => 'UpgradeFromOmekaClassic',
            'Omeka-S-module-UpgradeFromOmekaClassic' => 'UpgradeFromOmekaClassic',
            'OmekaSModuleBootstrap' => 'OmekaSModuleBootstrap',
            'omeka-s-theme-omekalia' => 'omekalia',
            'omekalia' => 'omekalia',
        ];
        if (isset($exceptions[$projectName])) {
            return $exceptions[$projectName];
        }

        $cleanName = str_ireplace(
            ['omeka-s', 'omekas', 'omeka s', 'omeka'],
            ['', '', '', ''],
            $projectName
        );
        $name = $this->cleanAddonNameWithCase($cleanName);
        return str_replace('-', '', $name);
    }

    /**
     * Get a data from the repository.
     *
     * @param string $addonUrl
     * @param string $dataToFind
     * @param string $isRecursive
     * @return string
     */
    protected function findData($addonUrl, $dataToFind, $isRecursive = false)
    {
        // The cache is in curl().

        $project = trim(parse_url($addonUrl, PHP_URL_PATH), '/');
        $server = strtolower(parse_url($addonUrl, PHP_URL_HOST));
        switch ($server) {
            case 'github.com':
                $user = strtok($project, '/');
                $projectName = strtok('/');
                $url = 'https://api.github.com/repos/' . $user . '/' . $projectName;
                $response = $this->curl($url);
                break;
            default:
                $response = '';
                break;
        }

        if (empty($response)) {
            return '';
        }

        switch ($server) {
            case 'github.com':
                switch ($dataToFind) {
                    case 'creation date':
                        return $response->created_at;

                    // "updated_at" means the last update in metadata or local
                    // commit, whereas "pushed_at" means the last commit, but it
                    // may be a commit on a fork if there is a pull request.
                    case 'last update':
                        // $url = 'https://api.github.com/repos/' . $project . '/commits/HEAD';
                        // $date = $response->commit->committer->date;
                        return $response->fork
                            ? $response->updated_at
                            : max($response->updated_at, $response->pushed_at);

                    case 'fork source':
                        if (!$response->fork) {
                            return $isRecursive ? $addonUrl : '';
                        }
                        return $this->findData($response->parent->html_url, $dataToFind, true);

                    case 'last released zip':
                        $user = strtok($project, '/');
                        $projectName = strtok('/');
                        // Don't use url path with "latest" to avoid a request.
                        // $apiUrl = 'https://api.github.com/repos/' . $user . '/' . $projectName . '/releases/latest';
                        $apiUrl = 'https://api.github.com/repos/' . $user . '/' . $projectName . '/releases?per_page=100';
                        $content = $this->curl($apiUrl, [], false);
                        if (empty($content) || $content === 'Not Found') {
                            return '';
                        }
                        // The latest from the list is always the first result.
                        $content = reset($content);
                        if (empty($content) || empty($content->assets)) {
                            return '';
                        }
                        return $content->assets[0]->browser_download_url;

                    case 'directory name':
                        $user = strtok($project, '/');
                        $projectName = strtok('/');
                        // The directory name must be the namespace.
                        return $this->extractNamespaceFromProjectName($projectName);

                    case 'count versions':
                        // TODO Add a check of the headers to get the right count of versions.
                        $user = strtok($project, '/');
                        $projectName = strtok('/');
                        $apiUrl = 'https://api.github.com/repos/' . $user . '/' . $projectName . '/releases?per_page=100';
                        $content = $this->curl($apiUrl, [], false);
                        return count($content);

                    case 'total downloads':
                        // TODO Add a loop to get the right count of downloads.
                        $user = strtok($project, '/');
                        $projectName = strtok('/');
                        $apiUrl = 'https://api.github.com/repos/' . $user . '/' . $projectName . '/releases?per_page=100';
                        $content = $this->curl($apiUrl, [], false);
                        $counts = [];
                        // Take the fact that some addons, like Mirador,
                        // include some dependencies zipped in some releases.
                        // Furthermore, some addons, like Log, have multiple
                        // files for different versions of php.
                        // So filter files in each release with the name of the
                        // addon + optional version number + optional extension
                        // + zip only.
                        foreach ($content as $release) {
                            // The function array_column() supports objects.
                            // $counts[$release->name] = array_sum(array_column($release->assets, 'download_count'));
                            $namespace = $this->extractNamespaceFromProjectName($projectName);
                            $version = $release->tag_name;
                            $counts[$release->name] = array_sum(array_column(array_filter($release->assets, function ($as) use ($namespace, $version) {
                                return preg_match('~' . preg_quote($namespace, '~') . '[ ._-]?(?:(?:' . preg_quote($version, '~') . ')?|(?:' . preg_quote($version, '~') . ')?[ ._-]?php[\d ._-]?)\.zip$~', $as->name);
                            }), 'download_count'));
                        }
                        return array_sum($counts);

                    default:
                        return '';
                }
            default:
                return '';
        }
    }

    /**
     * Helper to get the response of a web service.
     *
     * @param string $url
     * @param array $headers
     * @param bool $messageResponse
     * @return array The array may contain standard objects at any level.
     */
    protected function curl($url, $headers = [], $messageResponse = true)
    {
        static $data = [];

        if (isset($data[$url])) {
            return $data[$url];
        }

        // Avoid processing multiple times the same url with the same issue.
        $data[$url] = [];

        $userAgent = 'Mozilla/5.0 (X11; Linux x86_64; rv:109.0) Gecko/20100101 Firefox/135.0';

        $curl = curl_init();

        $server = strtolower(parse_url($url, PHP_URL_HOST));
        if (!empty($this->options['token'][$server])) {
            switch ($server) {
                case 'api.github.com':
                    $headers[] = 'Authorization: token ' . $this->options['token'][$server];
                    break;
            }
        }

        // TODO Two user agents?
        $domain = parse_url($url, PHP_URL_HOST);
        if (strpos($domain, 'github') !== false) {
            $headers[] = 'Accept: application/vnd.github+json';
            $headers[] = 'X-GitHub-Api-Version: 2022-11-28';
            $headers[] = 'User-Agent: Daniel-KM/UpgradeToOmekaS';
        }
        $headers = array_unique($headers);

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
            $this->log(sprintf('No response from curl for url %s.', $url));
            return [];
        }

        // All api are json.
        $output = json_decode($response);
        if (empty($output)) {
            $this->log(sprintf('Empty response from curl for url %s.', $url));
            return [];
        }

        if (!(is_array($output) || is_object($output))) {
            $this->log(sprintf('Response from curl is not an array or an object for url %s.', $url));
            return [];
        }

        // TODO Here only github? Skip?
        if (is_object($output) && !empty($output->message)) {
            if ($messageResponse) {
                $this->log(sprintf('Error on url %1$s: %2$s.', $url, $output->message));
            }
            return [];
        } elseif (is_array($output) && !empty($output['message'])) {
            if ($messageResponse) {
                $this->log(sprintf('Error on url %1$s: %2$s.', $url, $output['message']));
            }
            return [];
        }

        $data[$url] = $output;

        return $output;
    }

    protected function getIniForAddon($addonUrl, $iniPath = null)
    {
        static $addons = [];
        static $addonTypes = [];

        // The ini path is used in some non-standard repositories.
        $keyAddon = $addonUrl . ($iniPath ? ' iniPath' : '');

        // The check on the addon type avoids to return a wrong ini.
        if (isset($addons[$keyAddon])
            && $addonTypes[$keyAddon] === $this->type
        ) {
            return $addons[$keyAddon];
        }

        $server = strtolower(parse_url($addonUrl, PHP_URL_HOST));
        switch ($server) {
            case 'github.com':
                $addonIniBase = str_ireplace('github.com', 'raw.githubusercontent.com', $addonUrl);
                if ($iniPath) {
                    $replacements = [
                        '/tree/main/' => '/main/',
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

        $addonIni = $iniPath ?: ('master/' . $this->args['ini']);
        $addonIni = $addonIniBase . '/' . $addonIni;
        $ini = @file_get_contents($addonIni);
        if (empty($ini)) {
            $addonIni = $iniPath ?: ('main/' . $this->args['ini']);
            $addonIni = $addonIniBase . '/' . $addonIni;
            $ini = @file_get_contents($addonIni);
            if (empty($ini) && empty($iniPath)) {
                $this->log('[No config ini ]' . ' ' . $addonUrl);
                if ($this->options['debug']) {
                    $this->log(' Addon ini: ' . $addonIni);
                }
                return '';
            }
        }

        $addons[$keyAddon] = $ini;
        $addonTypes[$keyAddon] = $this->type;

        return $ini;
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
     * Save an array into a tsv file.
     *
     * @param string $destination
     * @param array $array
     * @return bool
     */
    protected function saveToTsvFile($destination, array $array)
    {
        $handle = fopen($destination, 'w');
        if (empty($handle)) {
            return false;
        }
        foreach ($array as $row) {
            fputcsv($handle, $row, "\t", chr(0));
        }
        return fclose($handle);
    }

    /**
     * Save an array into a json file.
     *
     * @param string $destination
     * @param array $array
     * @return bool
     */
    protected function saveToJsonFile($destination, array $array)
    {
        $json = json_encode($array, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        return (bool) file_put_contents($destination, $json);
    }

    /**
     * Echo a message to the standard output.
     *
     * @param mixed $message
     */
    protected function log($message): void
    {
        if (is_array($message)) {
            print_r($message);
        } else {
            echo $message . PHP_EOL;
        }
    }
}
