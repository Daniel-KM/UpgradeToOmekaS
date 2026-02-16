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
// requests an hour. With a token, the limit is 5000 requests per hour.
$tokenGithubPath = $datapath . 'token_github.txt';
$tokenGithub = file_exists($tokenGithubPath) ? trim(file_get_contents($tokenGithubPath)) : '';
if (empty($tokenGithub)) {
    echo "Warning: No GitHub token found at {$tokenGithubPath}\n";
    echo "API rate limit will be 60 requests/hour. Create the file with a GitHub personal access token for 5000 requests/hour.\n\n";
}

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
    // Cache invalid URLs per type to avoid re-checking them.
    // Each type has its own cache file (e.g., invalid_urls_module.txt).
    'cacheInvalidUrls' => true,
    'cacheInvalidUrlsPath' => $datapath . 'cache/',
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
        // Additional search patterns to find more modules.
        'keywords_extra' => [
            'omeka-s-module',
            'omeka-s+module',
            'omekas+module',
        ],
        'ini' => 'config' . DIRECTORY_SEPARATOR . 'module.ini',
        // Known organizations that publish Omeka S modules.
        'organizations' => [
            'github.com' => [
                'Daniel-KM',
                'biblibre',
                'omeka-s-modules',
                'GhentCDH',
                'digihum',
                'chnm',
                'zerocrates',
                'samszo',
                'omeka-j',
                'ateeducacion',
                'ManOnDaMoon',
                'indic-archive',
                'Libnamic',
                'caprowsky',
                'pols12',
                'utarchives',
                'neshmi',
                'Fisk-University',
                'agile-humanities',
            ],
            'gitlab.com' => [
                'Daniel-KM',
                '6piTech',
            ],
        ],
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
        'keywords_extra' => [
            'omeka-s-theme',
            'omeka-s+theme',
            'omekas+theme',
        ],
        'ini' => 'config' . DIRECTORY_SEPARATOR . 'theme.ini',
        'organizations' => [
            'github.com' => [
                'Daniel-KM',
                'omeka-s-themes',
                'omeka',
                'GhentCDH',
                'biblibre',
                'agile-humanities',
                'ManOnDaMoon',
            ],
            'gitlab.com' => [
                'Daniel-KM',
            ],
        ],
    ],
];

// Pre-load all URLs from all CSV files to avoid cross-type checks during search.
$allUrlsByType = [];
foreach ($types as $type => $args) {
    $allUrlsByType[$type] = [];
    if (file_exists($args['source'])) {
        $rows = array_map('str_getcsv', file($args['source']));
        if (!empty($rows)) {
            $csvHeaders = array_flip($rows[0]);
            if (isset($csvHeaders['Url'])) {
                foreach ($rows as $key => $row) {
                    if ($key === 0) {
                        continue;
                    }
                    $url = trim($row[$csvHeaders['Url']] ?? '', '/ ');
                    if ($url) {
                        $allUrlsByType[$type][] = $url;
                    }
                }
            }
        }
    }
}

foreach ($types as $type => $args) {
    if ($options['processOnlyType'] && !in_array($type, $options['processOnlyType'])) {
        continue;
    }
    // Build the list of URLs from other types to skip during search.
    $otherTypesUrls = [];
    foreach ($allUrlsByType as $otherType => $urls) {
        if ($otherType !== $type) {
            $otherTypesUrls = array_merge($otherTypesUrls, $urls);
        }
    }
    $options['otherTypesUrls'] = array_flip($otherTypesUrls);
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

    /**
     * Progress tracking properties.
     */
    protected $startTime;
    protected $stepStartTime;
    protected $currentStep = '';
    protected $progressCurrent = 0;
    protected $progressTotal = 0;
    protected $lastProgressOutput = 0;

    /**
     * Cache of invalid URLs (URLs without valid ini file for this type).
     * @var array
     */
    protected $invalidUrlsCache = [];

    /**
     * New invalid URLs found during this run (to be saved at the end).
     * @var array
     */
    protected $newInvalidUrls = [];

    public function __construct($type, $args = [], $options = [])
    {
        $this->type = $type;
        if (empty($args['destination'])) {
            $args['destination'] = $args['source'];
        }
        $this->args = $args;
        $this->options = $options;

        // Load cached invalid URLs for this type.
        $this->loadInvalidUrlsCache();
    }

    /**
     * Do the full process.
     *
     * @return bool
     */
    public function process()
    {
        // May avoid an issue with Apple Mac (deprecated in PHP 8.1).
        if (PHP_VERSION_ID < 80100) {
            ini_set('auto_detect_line_endings', true);
        }

        $this->log('');
        $this->log(str_repeat('=', 70));
        $this->log(sprintf('Processing: %s', basename($this->args['source'])));
        $this->log(str_repeat('=', 70));

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

        $this->log('');
        if ($this->updatedAddons) {
            $this->log(sprintf('[Result] %d addons updated with new data.', count($this->updatedAddons)));
        } else {
            $this->log('[Result] No addons were updated.');
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

        // Save the cache of invalid URLs discovered during this run.
        $this->saveInvalidUrlsCache();

        $this->log('[Done] CSV file saved successfully.');
        $this->log(str_repeat('-', 70));

        return true;
    }

    /**
     * Create the TSV file with last versions from the CSV file.
     */
    public function processUpdateLastVersions()
    {
        $this->log(sprintf('[Versions] Generating %s...', basename(str_replace('.csv', '_versions.tsv', $this->args['destination']))));

        $destination = str_replace('.csv', '_versions.tsv', $this->args['destination']);
        if (!$this->checkFiles($this->args['source'], $destination)) {
            return false;
        }

        $addons = array_map('str_getcsv', file($this->args['source']));
        if (empty($addons)) {
            $this->log(sprintf('[Versions] Error: No content in "%s".', $this->args['source']));
            return false;
        }

        // Get headers by name.
        $headers = array_flip($addons[0]);
        $this->headers = $headers;
        unset($addons[0]);

        $addonsLastVersions = [];
        $processedCount = 0;
        $totalAddons = count($addons);

        foreach ($addons as $addon) {
            $addonUrl = trim($addon[$headers['Url']], '/ ');
            if (empty($addonUrl)) {
                continue;
            }

            // Use directory name from CSV if available and properly cased,
            // otherwise compute from URL (which has hyphenated words we can parse).
            $dirNameKey = $headers['Directory name'] ?? null;
            $csvDirName = $dirNameKey !== null ? trim($addon[$dirNameKey] ?? '') : '';
            // If CSV has a value with mixed case (like "AgileTools"), use it.
            // If CSV value is all lowercase (like "agiletools"), derive from URL instead.
            if (!empty($csvDirName) && $csvDirName !== strtolower($csvDirName)) {
                $cleanName = $csvDirName;
            } else {
                $cleanName = $this->extractNamespaceFromProjectName(basename($addonUrl));
            }

            if (empty($cleanName)) {
                continue;
            }

            $lastVersion = $addon[$headers['Last version'] ?? $headers['Last Version']] ?? '';

            if (empty($addonsLastVersions[$cleanName])) {
                $addonsLastVersions[$cleanName] = $lastVersion;
            } elseif (version_compare($addonsLastVersions[$cleanName], $lastVersion, '<=')) {
                $addonsLastVersions[$cleanName] = $lastVersion;
            }
            $processedCount++;
        }

        if ($this->options['debug'] && !$this->options['debugOutput']) {
            $this->log('[Versions] Debug mode: no output.');
        } else {
            $resultTsv = [];
            foreach ($addonsLastVersions as $addon => $version) {
                $resultTsv[] = [$addon, $version];
            }
            $result = $this->saveToTsvFile($destination, $resultTsv);
            if (!$result) {
                $this->log(sprintf('[Versions] Error saving "%s".', $destination));
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

        $this->log(sprintf('[Versions] Saved %d unique addons to TSV.', count($addonsLastVersions)));

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

        // Build multiple search queries to catch more modules.
        // The main query uses the standard keywords.
        $searches = [
            'keywords' => 'https://api.github.com/search/repositories?q=' . $this->args['keywords'] . '+fork%3Afalse' . '+in:topics,name,description,readme',
        ];

        // Add extra keyword searches if configured.
        if (!empty($this->args['keywords_extra'])) {
            foreach ($this->args['keywords_extra'] as $i => $extraKeywords) {
                $searches['extra_' . $i] = 'https://api.github.com/search/repositories?q=' . $extraKeywords . '+fork%3Afalse' . '+in:topics,name,description,readme';
            }
        }

        // Add topic-based search.
        if (!empty($this->args['topic'])) {
            $searches['topic'] = 'https://api.github.com/search/repositories?q=topic%3A' . $this->args['topic'] . '+fork%3Afalse';
        }

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
                $totalCount = $response->total_count;
                $this->log(sprintf(
                    '[Search:%s] Found %d repositories matching "%s"',
                    $searchType,
                    $totalCount,
                    $this->type
                ));

                if ($totalCount > 0) {
                    $totalProcessed = 0;
                    $page = 1;
                    $totalPages = (int) ceil($totalCount / 100);
                    do {
                        if ($page > 1) {
                            $urlPage = $url . '&page=' . $page;
                            $response = $this->curl($urlPage);
                            // A special check to avoid an infinite loop.
                            if (!$response || $response->total_count === 0 || count($response->items) === 0) {
                                break;
                            }
                        }
                        $resultNewUrls = $this->filterNewUrlsFromSearchResults($response, $urls);
                        $addons = array_merge($addons, $resultNewUrls['new_addons']);
                        $urls = array_merge($urls, $resultNewUrls['new_urls']);
                        $newUrls = array_merge($newUrls, $resultNewUrls['new_urls']);
                        $totalProcessed += count($response->items);

                        $this->log(sprintf(
                            '[Search:%s] Page %d/%d: processed %d/%d repos, found %d new URLs',
                            $searchType,
                            $page,
                            $totalPages,
                            $totalProcessed,
                            $totalCount,
                            count($resultNewUrls['new_urls'])
                        ));

                        ++$page;
                    } while ($totalProcessed < $totalCount);
                }
            } else {
                $this->log(sprintf('[Search:%s] No results from GitHub API.', $searchType));
            }
        }

        // Search known organizations on GitHub.
        $resultOrgs = $this->searchOrganizations($urls);
        $addons = array_merge($addons, $resultOrgs['new_addons']);
        $urls = array_merge($urls, $resultOrgs['new_urls']);
        $newUrls = array_merge($newUrls, $resultOrgs['new_urls']);

        // Search GitLab.
        $resultGitLab = $this->searchGitLab($urls);
        $addons = array_merge($addons, $resultGitLab['new_addons']);
        $urls = array_merge($urls, $resultGitLab['new_urls']);
        $newUrls = array_merge($newUrls, $resultGitLab['new_urls']);

        $this->log('');
        if ($newUrls) {
            $this->log(sprintf('[Search] Found %d new URLs for %s:', count($newUrls), $this->type));
            foreach ($newUrls as $url) {
                $this->log(sprintf('  + %s', $url));
            }
        } else {
            $this->log(sprintf('[Search] No new URLs found for %s.', $this->type));
        }

        return $addons;
    }

    /**
     * Search repositories in known organizations.
     *
     * @param array $urls Existing URLs to filter out.
     * @return array New addons and URLs found.
     */
    protected function searchOrganizations(array $urls)
    {
        $newAddons = [];
        $newUrls = [];

        if (empty($this->args['organizations'])) {
            return ['new_addons' => $newAddons, 'new_urls' => $newUrls];
        }

        $headers = $this->headers;
        $addonBase = array_fill(0, count($headers), null);
        $toExclude = !empty($this->options['filterFalseAddons']) && file_exists($this->options['excludedUrlsPath'])
            ? array_filter(array_map('trim', explode("\n", file_get_contents($this->options['excludedUrlsPath']))))
            : [];

        // Search GitHub organizations.
        if (!empty($this->args['organizations']['github.com'])) {
            $this->log('[Search:orgs] Searching known GitHub organizations...');
            foreach ($this->args['organizations']['github.com'] as $org) {
                $apiUrl = 'https://api.github.com/users/' . $org . '/repos?per_page=100&type=all';
                $page = 1;
                $foundInOrg = 0;

                do {
                    $url = $apiUrl . '&page=' . $page;
                    $response = $this->curl($url, [], false);

                    if (empty($response) || !is_array($response)) {
                        break;
                    }

                    foreach ($response as $repo) {
                        $repoUrl = $repo->html_url;

                        // Skip if already known or excluded.
                        if (in_array($repoUrl, $urls) || in_array($repoUrl, $newUrls)) {
                            continue;
                        }
                        if (in_array($repoUrl, $toExclude)) {
                            continue;
                        }
                        if ($repo->fork) {
                            continue;
                        }
                        // Skip URLs already known to belong to another type.
                        if ($this->isUrlInOtherType($repoUrl)) {
                            continue;
                        }

                        // Check if this looks like an Omeka S addon by checking for ini file.
                        $ini = $this->getIniForAddon($repoUrl);
                        if (empty($ini)) {
                            continue;
                        }

                        $addon = $addonBase;
                        $addon[$headers['Url']] = $repoUrl;
                        $newAddons[] = $addon;
                        $newUrls[] = $repoUrl;
                        $foundInOrg++;

                        if ($this->options['debug']) {
                            $this->log(sprintf('[Search:orgs] NEW from %s: %s', $org, $repoUrl));
                        }
                    }

                    // If less than 100 repos, we've reached the end.
                    if (count($response) < 100) {
                        break;
                    }
                    $page++;
                } while ($page <= 10); // Safety limit.

                if ($foundInOrg > 0) {
                    $this->log(sprintf('[Search:orgs] Found %d new repos in %s', $foundInOrg, $org));
                }
            }
        }

        // Search GitLab organizations/groups.
        if (!empty($this->args['organizations']['gitlab.com'])) {
            $this->log('[Search:orgs] Searching known GitLab groups...');
            foreach ($this->args['organizations']['gitlab.com'] as $group) {
                $encodedGroup = urlencode($group);
                $apiUrl = 'https://gitlab.com/api/v4/groups/' . $encodedGroup . '/projects?per_page=100&include_subgroups=true';
                $page = 1;
                $foundInOrg = 0;

                do {
                    $url = $apiUrl . '&page=' . $page;
                    $response = $this->curl($url, [], false);

                    if (empty($response) || !is_array($response)) {
                        break;
                    }

                    foreach ($response as $project) {
                        $repoUrl = $project->web_url;

                        // Skip if already known or excluded.
                        if (in_array($repoUrl, $urls) || in_array($repoUrl, $newUrls)) {
                            continue;
                        }
                        if (in_array($repoUrl, $toExclude)) {
                            continue;
                        }
                        // Skip URLs already known to belong to another type.
                        if ($this->isUrlInOtherType($repoUrl)) {
                            continue;
                        }

                        // Check if this looks like an Omeka S addon.
                        $ini = $this->getIniForAddon($repoUrl);
                        if (empty($ini)) {
                            continue;
                        }

                        $addon = $addonBase;
                        $addon[$headers['Url']] = $repoUrl;
                        $newAddons[] = $addon;
                        $newUrls[] = $repoUrl;
                        $foundInOrg++;

                        if ($this->options['debug']) {
                            $this->log(sprintf('[Search:orgs] NEW from %s: %s', $group, $repoUrl));
                        }
                    }

                    if (count($response) < 100) {
                        break;
                    }
                    $page++;
                } while ($page <= 10);

                if ($foundInOrg > 0) {
                    $this->log(sprintf('[Search:orgs] Found %d new repos in %s', $foundInOrg, $group));
                }
            }
        }

        $this->log(sprintf('[Search:orgs] Total: %d new URLs from organizations', count($newUrls)));

        return ['new_addons' => $newAddons, 'new_urls' => $newUrls];
    }

    /**
     * Search GitLab for Omeka S modules.
     *
     * @param array $urls Existing URLs to filter out.
     * @return array New addons and URLs found.
     */
    protected function searchGitLab(array $urls)
    {
        $newAddons = [];
        $newUrls = [];

        $headers = $this->headers;
        $addonBase = array_fill(0, count($headers), null);
        $toExclude = !empty($this->options['filterFalseAddons']) && file_exists($this->options['excludedUrlsPath'])
            ? array_filter(array_map('trim', explode("\n", file_get_contents($this->options['excludedUrlsPath']))))
            : [];

        // GitLab search queries.
        $searchTerms = ['omeka-s-module', 'omeka module', 'omeka-s'];
        if (!empty($this->args['topic'])) {
            $searchTerms[] = $this->args['topic'];
        }

        $this->log('[Search:gitlab] Searching GitLab...');

        foreach ($searchTerms as $term) {
            $apiUrl = 'https://gitlab.com/api/v4/projects?search=' . urlencode($term) . '&per_page=100';
            $page = 1;

            do {
                $url = $apiUrl . '&page=' . $page;
                $response = $this->curl($url, [], false);

                if (empty($response) || !is_array($response)) {
                    break;
                }

                foreach ($response as $project) {
                    $repoUrl = $project->web_url;

                    // Skip if already known or excluded.
                    if (in_array($repoUrl, $urls) || in_array($repoUrl, $newUrls)) {
                        continue;
                    }
                    if (in_array($repoUrl, $toExclude)) {
                        continue;
                    }
                    // Skip URLs already known to belong to another type.
                    if ($this->isUrlInOtherType($repoUrl)) {
                        continue;
                    }

                    // Check if this looks like an Omeka S addon.
                    $ini = $this->getIniForAddon($repoUrl);
                    if (empty($ini)) {
                        continue;
                    }

                    $addon = $addonBase;
                    $addon[$headers['Url']] = $repoUrl;
                    $newAddons[] = $addon;
                    $newUrls[] = $repoUrl;

                    if ($this->options['debug']) {
                        $this->log(sprintf('[Search:gitlab] NEW: %s', $repoUrl));
                    }
                }

                if (count($response) < 100) {
                    break;
                }
                $page++;
            } while ($page <= 5); // Safety limit for GitLab.
        }

        $this->log(sprintf('[Search:gitlab] Total: %d new URLs from GitLab', count($newUrls)));

        return ['new_addons' => $newAddons, 'new_urls' => $newUrls];
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
            // Skip URLs already known to belong to another type.
            if ($this->isUrlInOtherType($repo->html_url)) {
                if ($this->options['debug']) {
                    $this->log(sprintf('Other type: %s', $repo->html_url));
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

        $this->log('[Update] Fetching addon list from omeka.org...');
        $omekaAddons = $this->fetchOmekaAddons();
        $this->omekaAddons = $omekaAddons;
        $this->log(sprintf('[Update] Found %d addons on omeka.org', count($omekaAddons)));

        $this->updatedAddons = [];

        // Count total addons to process (excluding header)
        $totalAddons = count($addons) - 1;
        $processedCount = 0;
        $skippedCount = 0;
        $this->startTime = microtime(true);

        $this->log(sprintf('[Update] Processing %d addons...', $totalAddons));
        $this->log('');

        // Collect all addon URLs to process for batch prefetching
        $addonUrlsToProcess = [];
        foreach ($addons as $key => $addon) {
            if ($key === 0) {
                continue;
            }
            $addonUrl = trim($addon[$headers['Url']] ?? '', '/ ');
            if (empty($addonUrl)) {
                continue;
            }
            if ($this->options['processOnlyAddon'] && !in_array($addonUrl, $this->options['processOnlyAddon'])) {
                continue;
            }
            $addonName = $addon[$headers['Name']] ?? '';
            if ($addonName && $this->options['processOnlyNewUrls']) {
                continue;
            }
            if ($this->options['debug'] && $this->options['debugMax'] && $key > $this->options['debugMax']) {
                continue;
            }
            $addonUrlsToProcess[$key] = $addonUrl;
        }

        // Prefetch API data in batches of 10 for faster processing
        $batchSize = 10;
        $urlBatches = array_chunk($addonUrlsToProcess, $batchSize, true);
        $totalBatches = count($urlBatches);
        $this->log(sprintf('[Update] Prefetching data in %d batches of %d...', $totalBatches, $batchSize));

        foreach ($addons as $key => $addon) {
            if ($key === 0) {
                continue;
            }
            $addonUrl = trim($addon[$headers['Url']] ?? '', '/ ');
            if (empty($addonUrl)) {
                $skippedCount++;
                continue;
            }

            if ($this->options['processOnlyAddon'] && !in_array($addonUrl, $this->options['processOnlyAddon'])) {
                $skippedCount++;
                continue;
            }

            $addonName = $addon[$headers['Name']] ?? '';
            if ($addonName && $this->options['processOnlyNewUrls']) {
                $skippedCount++;
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
            }

            // Prefetch next batch when starting a new batch
            $batchIndex = (int) floor($processedCount / $batchSize);
            if ($processedCount % $batchSize === 0 && isset($urlBatches[$batchIndex])) {
                $this->prefetchAddonData(array_values($urlBatches[$batchIndex]));
            }

            $processedCount++;
            $elapsed = microtime(true) - $this->startTime;
            $eta = $processedCount > 0 ? ($elapsed / $processedCount) * ($totalAddons - $processedCount) : 0;
            $percent = round(($processedCount / $totalAddons) * 100);

            // Show progress every 5% or every 10 items (whichever is more frequent)
            $progressInterval = max(1, min(10, (int)($totalAddons / 20)));
            if ($processedCount % $progressInterval === 0 || $processedCount === $totalAddons) {
                $this->log(sprintf(
                    '[Update] %d/%d (%d%%) - %s [%s elapsed, ~%s remaining]',
                    $processedCount,
                    $totalAddons,
                    $percent,
                    $addonName,
                    $this->formatTime($elapsed),
                    $this->formatTime($eta)
                ));
            }

            $addons[$key] = $this->updateAddon($addon);
        }

        $totalElapsed = microtime(true) - $this->startTime;
        $this->log('');
        $this->log(sprintf(
            '[Update] Completed: %d processed, %d skipped [%s total]',
            $processedCount,
            $skippedCount,
            $this->formatTime($totalElapsed)
        ));

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

        // Skip HTML error pages received instead of INI content.
        if (preg_match('/^\s*</', $ini)) {
            $this->log('[No key in ini ]' . ' ' . $addonName);
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

    /**
     * Convert a hyphenated string to PascalCase, preserving existing mixed case.
     *
     * For example:
     * - "agile-theme-tools" becomes "AgileThemeTools"
     * - "Omeka-S-module-AdvancedSearch" becomes "OmekaSModuleAdvancedSearch"
     * - "AgileThemeTools" (no hyphens) stays "AgileThemeTools"
     *
     * @param string $name
     * @return string
     */
    protected function hyphenatedToPascalCase($name)
    {
        // Handle underscores too for consistency.
        $words = preg_split('/[-_]+/', $name);
        $result = '';
        foreach ($words as $word) {
            if ($word === '') {
                continue;
            }
            // If word is all lowercase, capitalize first letter.
            // If word already has mixed case (like "AdvancedSearch"), preserve it.
            if ($word === strtolower($word)) {
                $result .= ucfirst($word);
            } else {
                $result .= $word;
            }
        }
        return $result;
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

        // Convert hyphenated project name to PascalCase first to preserve proper casing.
        // GitHub project names are typically lowercase with hyphens.
        $pascalName = $this->hyphenatedToPascalCase($projectName);

        // Remove common Omeka prefixes/suffixes (case-insensitive).
        $cleanName = str_ireplace(
            ['OmekaS', 'Omeka'],
            ['', ''],
            $pascalName
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
                $projectParts = explode('/', $project, 2);
                $user = $projectParts[0];
                $projectName = $projectParts[1] ?? '';
                $url = 'https://api.github.com/repos/' . $user . '/' . $projectName;
                $response = $this->curl($url);
                break;
            case 'gitlab.com':
                // GitLab API requires URL-encoded project path.
                $encodedProject = urlencode($project);
                $url = 'https://gitlab.com/api/v4/projects/' . $encodedProject;
                $response = $this->curl($url);
                $projectName = basename($project);
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
                        // The directory name must be the namespace.
                        return $this->extractNamespaceFromProjectName($projectName);

                    case 'count versions':
                        // Paginate through all releases.
                        $baseUrl = 'https://api.github.com/repos/' . $user . '/' . $projectName . '/releases?per_page=100';
                        $allReleases = $this->fetchAllGitHubPages($baseUrl);
                        return count($allReleases);

                    case 'total downloads':
                        // Paginate through all releases.
                        $baseUrl = 'https://api.github.com/repos/' . $user . '/' . $projectName . '/releases?per_page=100';
                        $allReleases = $this->fetchAllGitHubPages($baseUrl);
                        $counts = [];
                        // Take the fact that some addons, like Mirador,
                        // include some dependencies zipped in some releases.
                        // Furthermore, some addons, like Log, have multiple
                        // files for different versions of php.
                        // So filter files in each release with the name of the
                        // addon + optional version number + optional extension
                        // + zip only.
                        $namespace = $this->extractNamespaceFromProjectName($projectName);
                        foreach ($allReleases as $release) {
                            // The function array_column() supports objects.
                            // $counts[$release->name] = array_sum(array_column($release->assets, 'download_count'));
                            $version = $release->tag_name;
                            $counts[$release->name] = array_sum(array_column(array_filter($release->assets, function ($as) use ($namespace, $version) {
                                return preg_match('~' . preg_quote($namespace, '~') . '[ ._-]?(?:(?:' . preg_quote($version, '~') . ')?|(?:' . preg_quote($version, '~') . ')?[ ._-]?php[\d ._-]?)\.zip$~', $as->name);
                            }), 'download_count'));
                        }
                        return array_sum($counts);

                    default:
                        return '';
                }
            case 'gitlab.com':
                $encodedProject = urlencode($project);
                switch ($dataToFind) {
                    case 'creation date':
                        return $response->created_at ?? '';

                    case 'last update':
                        // GitLab uses last_activity_at for the last update.
                        return $response->last_activity_at ?? '';

                    case 'fork source':
                        if (empty($response->forked_from_project)) {
                            return $isRecursive ? $addonUrl : '';
                        }
                        return $this->findData($response->forked_from_project->web_url, $dataToFind, true);

                    case 'last released zip':
                        $apiUrl = 'https://gitlab.com/api/v4/projects/' . $encodedProject . '/releases?per_page=100';
                        $content = $this->curl($apiUrl, [], false);
                        if (empty($content) || !is_array($content)) {
                            return '';
                        }
                        // The latest release is the first in the list.
                        $release = reset($content);
                        if (empty($release) || empty($release->assets) || empty($release->assets->sources)) {
                            return '';
                        }
                        // Find the zip source.
                        foreach ($release->assets->sources as $source) {
                            if ($source->format === 'zip') {
                                return $source->url;
                            }
                        }
                        return '';

                    case 'directory name':
                        return $this->extractNamespaceFromProjectName($projectName);

                    case 'count versions':
                        $apiUrl = 'https://gitlab.com/api/v4/projects/' . $encodedProject . '/releases?per_page=100';
                        $allReleases = $this->fetchAllGitLabPages($apiUrl);
                        return count($allReleases);

                    case 'total downloads':
                        // GitLab does not provide download counts via API.
                        // Return 0 or could try to get from project statistics.
                        return 0;

                    default:
                        return '';
                }

            default:
                return '';
        }
    }

    /**
     * Helper to get the response of a web service with retry logic.
     *
     * @param string $url
     * @param array $headers
     * @param bool $messageResponse
     * @param int $maxRetries Maximum number of retry attempts for network errors.
     * @return array The array may contain standard objects at any level.
     */
    protected function curl($url, $headers = [], $messageResponse = true, $maxRetries = 3)
    {
        static $data = [];

        if (isset($data[$url])) {
            return $data[$url];
        }

        // Avoid processing multiple times the same url with the same issue.
        $data[$url] = [];

        $server = strtolower(parse_url($url, PHP_URL_HOST));

        // Use GitHub-specific user agent for GitHub API, browser-like for others.
        if (strpos($server, 'github') !== false) {
            $userAgent = 'Daniel-KM/UpgradeToOmekaS';
            $headers[] = 'Accept: application/vnd.github+json';
            $headers[] = 'X-GitHub-Api-Version: 2022-11-28';
            if (!empty($this->options['token'][$server])) {
                $headers[] = 'Authorization: token ' . $this->options['token'][$server];
            }
        } else {
            $userAgent = 'Mozilla/5.0 (X11; Linux x86_64; rv:109.0) Gecko/20100101 Firefox/135.0';
        }
        $headers = array_unique($headers);

        // Retry loop with exponential backoff.
        $attempt = 0;
        $response = false;
        $curlError = '';
        $httpCode = 0;

        while ($attempt < $maxRetries) {
            $attempt++;

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_USERAGENT, $userAgent);
            // curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            // curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            // Timeout to avoid blocking on slow/unresponsive servers.
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($curl, CURLOPT_TIMEOUT, 30);

            if ($headers) {
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            }

            $response = curl_exec($curl);
            $curlError = curl_error($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            // Success - break out of retry loop.
            if ($response !== false && empty($curlError) && $httpCode >= 200 && $httpCode < 500) {
                break;
            }

            // Network error or server error (5xx) - retry with backoff.
            if ($attempt < $maxRetries) {
                $sleepTime = pow(2, $attempt); // Exponential backoff: 2, 4, 8 seconds.
                if ($this->options['debug'] || $attempt > 1) {
                    $this->log(sprintf(
                        '[Retry] Attempt %d/%d failed for %s (HTTP %d, error: %s). Retrying in %ds...',
                        $attempt,
                        $maxRetries,
                        $url,
                        $httpCode,
                        $curlError ?: 'none',
                        $sleepTime
                    ));
                }
                sleep($sleepTime);
            }
        }

        if ($curlError) {
            $this->log(sprintf('Curl error for url %s after %d attempts: %s', $url, $attempt, $curlError));
            return [];
        }

        if ($response === false) {
            $this->log(sprintf('No response from curl for url %s after %d attempts.', $url, $attempt));
            return [];
        }

        // All api are json.
        $output = json_decode($response);
        if (empty($output)) {
            // Don't log for 404 errors (common for non-existent files).
            if ($httpCode !== 404) {
                $this->log(sprintf('Empty response from curl for url %s (HTTP %d).', $url, $httpCode));
            }
            return [];
        }

        if (!(is_array($output) || is_object($output))) {
            $this->log(sprintf('Response from curl is not an array or an object for url %s.', $url));
            return [];
        }

        // Check for api error messages (GitHub and other use 'message' field).
        if (is_object($output) && !empty($output->message)) {
            // Check for GitHub rate limit error.
            if (strpos($output->message, 'API rate limit exceeded') !== false) {
                $this->log('GitHub API rate limit exceeded. Add a token to _data/token_github.txt for higher limits.');
                // Wait and retry for rate limit.
                if ($attempt < $maxRetries) {
                    $this->log('[Rate limit] Waiting 60 seconds before retry...');
                    sleep(60);
                    unset($data[$url]);
                    return $this->curl($url, $headers, $messageResponse, $maxRetries - $attempt);
                }
            } elseif (strpos($output->message, 'secondary rate limit') !== false) {
                // GitHub secondary rate limit - wait longer.
                $this->log('[Secondary rate limit] Waiting 120 seconds...');
                sleep(120);
                unset($data[$url]);
                return $this->curl($url, $headers, $messageResponse, $maxRetries - $attempt);
            } elseif ($messageResponse) {
                $this->log(sprintf('Error on url %1$s: %2$s.', $url, $output->message));
            }
            return [];
        } elseif (is_array($output) && !empty($output['message'])) {
            if (strpos($output['message'], 'API rate limit exceeded') !== false) {
                $this->log('GitHub API rate limit exceeded. Add a token to _data/token_github.txt for higher limits.');
            } elseif ($messageResponse) {
                $this->log(sprintf('Error on url %1$s: %2$s.', $url, $output['message']));
            }
            return [];
        }

        $data[$url] = $output;

        return $output;
    }

    /**
     * Fetch multiple URLs in parallel using curl_multi.
     *
     * @param array $urls List of URLs to fetch.
     * @param int $batchSize Number of concurrent requests (default 10).
     * @return array Associative array of URL => response.
     */
    protected function curlMultiBatch(array $urls, int $batchSize = 10): array
    {
        static $cache = [];
        $results = [];
        $toFetch = [];

        // Check cache first
        foreach ($urls as $url) {
            if (isset($cache[$url])) {
                $results[$url] = $cache[$url];
            } else {
                $toFetch[] = $url;
            }
        }

        if (empty($toFetch)) {
            return $results;
        }

        // Process in batches
        $batches = array_chunk($toFetch, $batchSize);

        foreach ($batches as $batch) {
            $multiHandle = curl_multi_init();
            $handles = [];

            foreach ($batch as $url) {
                $ch = curl_init();
                $server = strtolower(parse_url($url, PHP_URL_HOST));

                $headers = [];
                if (strpos($server, 'github') !== false) {
                    $userAgent = 'Daniel-KM/UpgradeToOmekaS';
                    $headers[] = 'Accept: application/vnd.github+json';
                    $headers[] = 'X-GitHub-Api-Version: 2022-11-28';
                    if (!empty($this->options['token'][$server])) {
                        $headers[] = 'Authorization: token ' . $this->options['token'][$server];
                    }
                } else {
                    $userAgent = 'Mozilla/5.0 (X11; Linux x86_64; rv:109.0) Gecko/20100101 Firefox/135.0';
                }

                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                if ($headers) {
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                }

                curl_multi_add_handle($multiHandle, $ch);
                $handles[$url] = $ch;
            }

            // Execute all requests
            $running = null;
            do {
                curl_multi_exec($multiHandle, $running);
                curl_multi_select($multiHandle);
            } while ($running > 0);

            // Collect results
            foreach ($handles as $url => $ch) {
                $response = curl_multi_getcontent($ch);
                $output = json_decode($response);

                if (!empty($output) && (is_array($output) || is_object($output))) {
                    // Check for error messages
                    if (is_object($output) && !empty($output->message)) {
                        $cache[$url] = [];
                    } elseif (is_array($output) && !empty($output['message'])) {
                        $cache[$url] = [];
                    } else {
                        $cache[$url] = $output;
                    }
                } else {
                    $cache[$url] = [];
                }

                $results[$url] = $cache[$url];
                curl_multi_remove_handle($multiHandle, $ch);
                curl_close($ch);
            }

            curl_multi_close($multiHandle);
        }

        return $results;
    }

    /**
     * Prefetch API data for multiple addons in parallel.
     *
     * @param array $addonUrls List of addon URLs.
     */
    protected function prefetchAddonData(array $addonUrls): void
    {
        $apiUrls = [];

        foreach ($addonUrls as $addonUrl) {
            $server = strtolower(parse_url($addonUrl, PHP_URL_HOST));
            $project = trim(parse_url($addonUrl, PHP_URL_PATH), '/');

            switch ($server) {
                case 'github.com':
                    $apiUrls[] = 'https://api.github.com/repos/' . $project;
                    break;
                case 'gitlab.com':
                    $encodedProject = urlencode($project);
                    $apiUrls[] = 'https://gitlab.com/api/v4/projects/' . $encodedProject;
                    break;
            }
        }

        if ($apiUrls) {
            $this->curlMultiBatch($apiUrls, 10);
        }
    }

    /**
     * Fetch all pages from a paginated GitHub API endpoint.
     *
     * @param string $baseUrl The base URL with per_page parameter.
     * @param int $maxPages Maximum pages to fetch (safety limit).
     * @return array All items from all pages.
     */
    protected function fetchAllGitHubPages(string $baseUrl, int $maxPages = 10): array
    {
        $allItems = [];
        $page = 1;

        while ($page <= $maxPages) {
            // Use base URL as-is for page 1 to benefit from curl cache
            // when the same URL was already fetched (e.g. last released zip).
            $url = $page === 1 ? $baseUrl : $baseUrl . '&page=' . $page;
            $content = $this->curl($url, [], false);

            if (empty($content) || !is_array($content)) {
                break;
            }

            $allItems = array_merge($allItems, $content);

            // If we got less than 100 items, we've reached the last page.
            if (count($content) < 100) {
                break;
            }

            $page++;
        }

        return $allItems;
    }

    /**
     * Fetch all pages from a paginated GitLab API endpoint.
     *
     * @param string $baseUrl The base URL with per_page parameter.
     * @param int $maxPages Maximum pages to fetch (safety limit).
     * @return array All items from all pages.
     */
    protected function fetchAllGitLabPages(string $baseUrl, int $maxPages = 10): array
    {
        $allItems = [];
        $page = 1;

        while ($page <= $maxPages) {
            // Use base URL as-is for page 1 to benefit from curl cache
            // when the same URL was already fetched (e.g. last released zip).
            $url = $page === 1 ? $baseUrl : $baseUrl . '&page=' . $page;
            $content = $this->curl($url, [], false);

            if (empty($content) || !is_array($content)) {
                break;
            }

            $allItems = array_merge($allItems, $content);

            // GitLab default per_page is 20, max is 100.
            // If we got less than 100 items, we've reached the last page.
            if (count($content) < 100) {
                break;
            }

            $page++;
        }

        return $allItems;
    }

    /**
     * Fetch a raw file from a URL with caching and retry logic.
     *
     * @param string $url The URL to fetch
     * @param int $maxRetries Maximum retry attempts for network errors.
     * @return string|false The file content or false on failure
     */
    protected function fetchRawFile($url, $maxRetries = 3)
    {
        static $cache = [];

        if (isset($cache[$url])) {
            return $cache[$url];
        }

        $content = false;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            $attempt++;

            // Use stream context for better control.
            $context = stream_context_create([
                'http' => [
                    'timeout' => 30,
                    'ignore_errors' => true,
                    'user_agent' => 'Daniel-KM/UpgradeToOmekaS',
                ],
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                ],
            ]);

            $content = @file_get_contents($url, false, $context);

            // Check HTTP response code from headers.
            $httpCode = 0;
            if (isset($http_response_header) && is_array($http_response_header)) {
                foreach ($http_response_header as $header) {
                    if (preg_match('/^HTTP\/\d+\.\d+\s+(\d+)/', $header, $matches)) {
                        $httpCode = (int) $matches[1];
                        break;
                    }
                }
            }

            // 404 (file doesn't exist) - discard error page body, don't retry.
            if ($httpCode === 404) {
                $content = false;
                break;
            }
            // Success - don't retry.
            if ($content !== false) {
                break;
            }

            // Network error or server error - retry with backoff.
            if ($attempt < $maxRetries) {
                $sleepTime = pow(2, $attempt);
                if ($this->options['debug']) {
                    $this->log(sprintf(
                        '[fetchRawFile] Attempt %d/%d failed for %s (HTTP %d). Retrying in %ds...',
                        $attempt,
                        $maxRetries,
                        $url,
                        $httpCode,
                        $sleepTime
                    ));
                }
                sleep($sleepTime);
            }
        }

        // Cache both successful and failed fetches to avoid retrying.
        $cache[$url] = $content;

        return $content;
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

        // If the URL was already identified as a different addon type, skip:
        // a module won't have a theme.ini and vice versa.
        if (isset($addonTypes[$keyAddon]) && $addonTypes[$keyAddon] !== $this->type) {
            return '';
        }

        // Check if URL is in the invalid cache for this type (skip fetching).
        // Only check cache when no custom iniPath is provided.
        if (empty($iniPath) && $this->isUrlInvalidCached($addonUrl)) {
            if ($this->options['debug']) {
                $this->log('[Cached invalid]' . ' ' . $addonUrl);
            }
            return '';
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
                // GitLab raw file URL format: /project/-/raw/branch/path
                $addonIniBase = $addonUrl . '/-/raw';
                break;
            default:
                $addonIniBase = $addonUrl;
                break;
        }

        $addonIni = $iniPath ?: ('master/' . $this->args['ini']);
        $addonIni = $addonIniBase . '/' . $addonIni;
        $ini = $this->fetchRawFile($addonIni);
        if (empty($ini)) {
            $addonIni = $iniPath ?: ('main/' . $this->args['ini']);
            $addonIni = $addonIniBase . '/' . $addonIni;
            $ini = $this->fetchRawFile($addonIni);
            if (empty($ini) && empty($iniPath)) {
                $this->log('[No config ini ]' . ' ' . $addonUrl);
                if ($this->options['debug']) {
                    $this->log(' Addon ini: ' . $addonIni);
                }
                // Add to invalid URLs cache for this type.
                $this->addInvalidUrl($addonUrl);
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

    /**
     * Start a new processing step with progress tracking.
     *
     * @param string $stepName Name of the step
     * @param int $total Total items to process (0 if unknown)
     */
    protected function startStep(string $stepName, int $total = 0): void
    {
        $this->currentStep = $stepName;
        $this->stepStartTime = microtime(true);
        $this->progressCurrent = 0;
        $this->progressTotal = $total;
        $this->lastProgressOutput = 0;

        if ($total > 0) {
            $this->log(sprintf('[%s] Starting: %d items to process...', $stepName, $total));
        } else {
            $this->log(sprintf('[%s] Starting...', $stepName));
        }
    }

    /**
     * Update progress for current step.
     *
     * @param int $current Current item number
     * @param string $itemName Optional name of current item
     * @param bool $force Force output even if not at interval
     */
    protected function updateProgress(int $current, string $itemName = '', bool $force = false): void
    {
        $this->progressCurrent = $current;

        // Only output every 10 items or when forced, to avoid too much output
        $outputInterval = max(1, (int)($this->progressTotal / 20)); // ~5% intervals
        if (!$force && $current !== $this->progressTotal && ($current - $this->lastProgressOutput) < $outputInterval) {
            return;
        }
        $this->lastProgressOutput = $current;

        $elapsed = microtime(true) - $this->stepStartTime;

        if ($this->progressTotal > 0) {
            $percent = round(($current / $this->progressTotal) * 100);
            $eta = $current > 0 ? ($elapsed / $current) * ($this->progressTotal - $current) : 0;

            if ($itemName) {
                $this->log(sprintf(
                    '[%s] %d/%d (%d%%) - %s [%.1fs elapsed, ~%.1fs remaining]',
                    $this->currentStep,
                    $current,
                    $this->progressTotal,
                    $percent,
                    $itemName,
                    $elapsed,
                    $eta
                ));
            } else {
                $this->log(sprintf(
                    '[%s] %d/%d (%d%%) [%.1fs elapsed, ~%.1fs remaining]',
                    $this->currentStep,
                    $current,
                    $this->progressTotal,
                    $percent,
                    $elapsed,
                    $eta
                ));
            }
        } else {
            if ($itemName) {
                $this->log(sprintf('[%s] %d processed - %s [%.1fs]', $this->currentStep, $current, $itemName, $elapsed));
            } else {
                $this->log(sprintf('[%s] %d processed [%.1fs]', $this->currentStep, $current, $elapsed));
            }
        }
    }

    /**
     * Complete current step.
     *
     * @param string $summary Optional summary message
     */
    protected function endStep(string $summary = ''): void
    {
        $elapsed = microtime(true) - $this->stepStartTime;

        if ($summary) {
            $this->log(sprintf('[%s] Completed: %s [%.1fs]', $this->currentStep, $summary, $elapsed));
        } else {
            $this->log(sprintf('[%s] Completed [%.1fs]', $this->currentStep, $elapsed));
        }
        $this->log(''); // Empty line for readability
    }

    /**
     * Format elapsed time as human readable string.
     *
     * @param float $seconds
     * @return string
     */
    protected function formatTime(float $seconds): string
    {
        $secs = (int) $seconds;
        if ($secs < 60) {
            return sprintf('%.1fs', $seconds);
        } elseif ($secs < 3600) {
            return sprintf('%dm %ds', (int)($secs / 60), $secs % 60);
        } else {
            return sprintf('%dh %dm', (int)($secs / 3600), (int)(($secs % 3600) / 60));
        }
    }

    /**
     * Load the cache of invalid URLs for this addon type.
     *
     * Invalid URLs are URLs that don't have a valid ini file for this type.
     * A URL may be invalid for "module" but valid for "theme", so each type
     * has its own cache file.
     *
     * Each line is stored as "URL\tYYYY-MM-DD". Entries older than one month
     * are expired so the URL can be re-checked. Lines without a date (old
     * format) are treated as expired.
     */
    protected function loadInvalidUrlsCache(): void
    {
        if (empty($this->options['cacheInvalidUrls'])) {
            return;
        }

        $cacheFile = $this->getInvalidUrlsCacheFile();
        if (!file_exists($cacheFile)) {
            $this->invalidUrlsCache = [];
            return;
        }

        $content = file_get_contents($cacheFile);
        if (empty($content)) {
            $this->invalidUrlsCache = [];
            return;
        }

        $lines = array_filter(array_map('trim', explode("\n", $content)));
        $expiry = strtotime('-1 month');
        $this->invalidUrlsCache = [];
        $expired = 0;

        foreach ($lines as $line) {
            $parts = explode("\t", $line, 2);
            $url = $parts[0];
            $date = $parts[1] ?? null;
            // Skip entries without date (old format) or older than one month.
            if (empty($date) || strtotime($date) < $expiry) {
                ++$expired;
                continue;
            }
            $this->invalidUrlsCache[$url] = $date;
        }

        // Rewrite the cache file if expired entries were removed.
        if ($expired) {
            $this->rewriteInvalidUrlsCache();
            $this->log(sprintf('[Cache] Expired %d invalid URLs for type "%s"', $expired, $this->type));
        }

        if ($this->options['debug']) {
            $this->log(sprintf('[Cache] Loaded %d invalid URLs for type "%s"', count($this->invalidUrlsCache), $this->type));
        }
    }

    /**
     * Save new invalid URLs to the cache file.
     *
     * Called at the end of processing to persist newly discovered invalid URLs.
     */
    protected function saveInvalidUrlsCache(): void
    {
        if (empty($this->options['cacheInvalidUrls'])) {
            return;
        }

        if (empty($this->newInvalidUrls)) {
            return;
        }

        $cacheFile = $this->getInvalidUrlsCacheFile();
        $cacheDir = dirname($cacheFile);

        // Create cache directory if it doesn't exist.
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        // Append new invalid URLs with date to the cache file.
        $date = date('Y-m-d');
        $lines = '';
        foreach ($this->newInvalidUrls as $url) {
            $lines .= $url . "\t" . $date . "\n";
        }
        file_put_contents($cacheFile, $lines, FILE_APPEND | LOCK_EX);

        $this->log(sprintf('[Cache] Saved %d new invalid URLs for type "%s"', count($this->newInvalidUrls), $this->type));
    }

    /**
     * Get the cache file path for invalid URLs of this type.
     *
     * @return string
     */
    protected function getInvalidUrlsCacheFile(): string
    {
        $cachePath = rtrim($this->options['cacheInvalidUrlsPath'] ?? '', '/\\');
        return $cachePath . DIRECTORY_SEPARATOR . 'invalid_urls_' . $this->type . '.txt';
    }

    /**
     * Check if a URL is in the invalid URLs cache for this type.
     *
     * @param string $url
     * @return bool
     */
    protected function isUrlInvalidCached(string $url): bool
    {
        if (empty($this->options['cacheInvalidUrls'])) {
            return false;
        }

        return isset($this->invalidUrlsCache[$url]);
    }

    /**
     * Check if a URL belongs to another addon type (plugin, module, theme, template).
     *
     * This avoids fetching ini files for URLs already known to be of a different type.
     *
     * @param string $url
     * @return bool
     */
    protected function isUrlInOtherType(string $url): bool
    {
        return isset($this->options['otherTypesUrls'][$url]);
    }

    /**
     * Add a URL to the invalid URLs cache for this type.
     *
     * @param string $url
     */
    protected function addInvalidUrl(string $url): void
    {
        if (empty($this->options['cacheInvalidUrls'])) {
            return;
        }

        // Don't add if already in cache.
        if (isset($this->invalidUrlsCache[$url])) {
            return;
        }

        $this->invalidUrlsCache[$url] = date('Y-m-d');
        $this->newInvalidUrls[] = $url;
    }

    /**
     * Rewrite the cache file with current in-memory entries (after expiry).
     */
    protected function rewriteInvalidUrlsCache(): void
    {
        $cacheFile = $this->getInvalidUrlsCacheFile();
        $cacheDir = dirname($cacheFile);

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $lines = '';
        foreach ($this->invalidUrlsCache as $url => $date) {
            $lines .= $url . "\t" . $date . "\n";
        }
        file_put_contents($cacheFile, $lines, LOCK_EX);
    }
}
