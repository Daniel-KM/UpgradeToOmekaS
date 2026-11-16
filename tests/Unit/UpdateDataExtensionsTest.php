<?php

declare(strict_types=1);

namespace UpgradeToOmekaS\Tests\Unit;

use PHPUnit\Framework\TestCase;
use UpdateDataExtensions;

/**
 * Unit tests for the UpdateDataExtensions class from update_data.php.
 */
class UpdateDataExtensionsTest extends TestCase
{
    private string $tempDir;
    private string $dataDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/update_data_test_' . uniqid();
        $this->dataDir = $this->tempDir . '/data';
        mkdir($this->dataDir, 0755, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    private function getDefaultOptions(): array
    {
        return [
            'token' => ['api.github.com' => ''],
            'order' => ['Name' => 'asc'],
            'filterDuplicates' => false,
            'filterFalseForks' => false,
            'keepUpdatedForks' => true,
            'filterFalseAddons' => false,
            'excludedUrlsPath' => $this->dataDir . '/excluded_urls.txt',
            'processOnlyType' => [],
            'processOnlyAddon' => [],
            'processOnlyNewUrls' => false,
            'processSearch' => false,
            'processUpdate' => false,
            'processFormatCsvOnly' => false,
            'logAllAddons' => false,
            'debug' => false,
            'debugMax' => 0,
            'debugDiff' => 'none',
            'debugOutput' => false,
        ];
    }

    private function getModuleArgs(): array
    {
        return [
            'source' => $this->dataDir . '/test_modules.csv',
            'destination' => $this->dataDir . '/test_modules.csv',
            'topic' => 'omeka-s-module',
            'keywords' => '"Omeka%20S"+module',
            'ini' => 'config/module.ini',
        ];
    }

    // ==========================================================================
    // Constructor Tests
    // ==========================================================================

    public function testConstructorWithValidParameters(): void
    {
        $args = $this->getModuleArgs();
        $options = $this->getDefaultOptions();

        $updater = new UpdateDataExtensions('module', $args, $options);

        $this->assertInstanceOf(UpdateDataExtensions::class, $updater);
    }

    // ==========================================================================
    // CleanAddonName Tests (via reflection)
    // ==========================================================================

    public function testCleanAddonNameReturnsLowercase(): void
    {
        $args = $this->getModuleArgs();
        $options = $this->getDefaultOptions();
        $updater = new UpdateDataExtensions('module', $args, $options);

        $reflection = new \ReflectionClass($updater);
        $method = $reflection->getMethod('cleanAddonName');
        $method->setAccessible(true);

        $result = $method->invoke($updater, 'MyModule');

        // Should return lowercase version
        $this->assertIsString($result);
        $this->assertEquals(strtolower($result), $result);
    }

    public function testCleanAddonNameIsConsistent(): void
    {
        $args = $this->getModuleArgs();
        $options = $this->getDefaultOptions();
        $updater = new UpdateDataExtensions('module', $args, $options);

        $reflection = new \ReflectionClass($updater);
        $method = $reflection->getMethod('cleanAddonName');
        $method->setAccessible(true);

        // Same input should produce same output
        $result1 = $method->invoke($updater, 'TestModule');
        $result2 = $method->invoke($updater, 'TestModule');

        $this->assertEquals($result1, $result2);
    }

    // ==========================================================================
    // HyphenatedToPascalCase Tests (via reflection)
    // ==========================================================================

    public function testHyphenatedToPascalCaseConvertsLowercase(): void
    {
        $args = $this->getModuleArgs();
        $options = $this->getDefaultOptions();
        $updater = new UpdateDataExtensions('module', $args, $options);

        $reflection = new \ReflectionClass($updater);
        $method = $reflection->getMethod('hyphenatedToPascalCase');
        $method->setAccessible(true);

        // Lowercase hyphenated should be converted to PascalCase
        $result = $method->invoke($updater, 'agile-theme-tools');
        $this->assertEquals('AgileThemeTools', $result);
    }

    public function testHyphenatedToPascalCasePreservesMixedCase(): void
    {
        $args = $this->getModuleArgs();
        $options = $this->getDefaultOptions();
        $updater = new UpdateDataExtensions('module', $args, $options);

        $reflection = new \ReflectionClass($updater);
        $method = $reflection->getMethod('hyphenatedToPascalCase');
        $method->setAccessible(true);

        // Mixed case should be preserved
        $result = $method->invoke($updater, 'Omeka-S-module-AdvancedSearch');
        $this->assertEquals('OmekaSModuleAdvancedSearch', $result);
    }

    public function testHyphenatedToPascalCaseHandlesUnderscores(): void
    {
        $args = $this->getModuleArgs();
        $options = $this->getDefaultOptions();
        $updater = new UpdateDataExtensions('module', $args, $options);

        $reflection = new \ReflectionClass($updater);
        $method = $reflection->getMethod('hyphenatedToPascalCase');
        $method->setAccessible(true);

        // Underscores should also be treated as separators
        $result = $method->invoke($updater, 'some_module_name');
        $this->assertEquals('SomeModuleName', $result);
    }

    public function testHyphenatedToPascalCaseHandlesNonHyphenated(): void
    {
        $args = $this->getModuleArgs();
        $options = $this->getDefaultOptions();
        $updater = new UpdateDataExtensions('module', $args, $options);

        $reflection = new \ReflectionClass($updater);
        $method = $reflection->getMethod('hyphenatedToPascalCase');
        $method->setAccessible(true);

        // Non-hyphenated PascalCase should be preserved
        $result = $method->invoke($updater, 'AgileThemeTools');
        $this->assertEquals('AgileThemeTools', $result);
    }

    // ==========================================================================
    // ExtractNamespaceFromProjectName Tests (via reflection)
    // ==========================================================================

    public function testExtractNamespaceFromProjectNameReturnsString(): void
    {
        $args = $this->getModuleArgs();
        $options = $this->getDefaultOptions();
        $updater = new UpdateDataExtensions('module', $args, $options);

        $reflection = new \ReflectionClass($updater);
        $method = $reflection->getMethod('extractNamespaceFromProjectName');
        $method->setAccessible(true);

        $result = $method->invoke($updater, 'some-module-name');

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testExtractNamespaceFromProjectNameWithException(): void
    {
        $args = $this->getModuleArgs();
        $options = $this->getDefaultOptions();
        $updater = new UpdateDataExtensions('module', $args, $options);

        $reflection = new \ReflectionClass($updater);
        $method = $reflection->getMethod('extractNamespaceFromProjectName');
        $method->setAccessible(true);

        // Test known exception
        $result = $method->invoke($updater, 'UpgradeToOmekaS');

        $this->assertEquals('UpgradeToOmekaS', $result);
    }

    public function testExtractNamespaceFromProjectNameWithEmptyString(): void
    {
        $args = $this->getModuleArgs();
        $options = $this->getDefaultOptions();
        $updater = new UpdateDataExtensions('module', $args, $options);

        $reflection = new \ReflectionClass($updater);
        $method = $reflection->getMethod('extractNamespaceFromProjectName');
        $method->setAccessible(true);

        $result = $method->invoke($updater, '');

        $this->assertEquals('', $result);
    }

    public function testExtractNamespaceFromProjectNameConvertsToPascalCase(): void
    {
        $args = $this->getModuleArgs();
        $options = $this->getDefaultOptions();
        $updater = new UpdateDataExtensions('module', $args, $options);

        $reflection = new \ReflectionClass($updater);
        $method = $reflection->getMethod('extractNamespaceFromProjectName');
        $method->setAccessible(true);

        // GitHub-style lowercase hyphenated names should be converted to PascalCase
        $result = $method->invoke($updater, 'agile-theme-tools-omeka');
        $this->assertEquals('AgileTools', $result);

        $result = $method->invoke($updater, 'omeka-s-all-in-one-accessibility');
        $this->assertEquals('AllInOneAccessibility', $result);

        $result = $method->invoke($updater, 'omeka-s-any-cloud');
        $this->assertEquals('AnyCloud', $result);
    }

    public function testExtractNamespaceFromProjectNamePreservesExistingCase(): void
    {
        $args = $this->getModuleArgs();
        $options = $this->getDefaultOptions();
        $updater = new UpdateDataExtensions('module', $args, $options);

        $reflection = new \ReflectionClass($updater);
        $method = $reflection->getMethod('extractNamespaceFromProjectName');
        $method->setAccessible(true);

        // Already PascalCase names should preserve their case
        $result = $method->invoke($updater, 'Omeka-S-module-AdvancedSearch');
        $this->assertEquals('AdvancedSearch', $result);

        $result = $method->invoke($updater, 'AgileThemeTools');
        $this->assertEquals('AgileTools', $result);
    }

    // ==========================================================================
    // FindData Tests (via reflection) - Tests GitLab and GitHub support
    // ==========================================================================

    public function testFindDataWithInvalidUrl(): void
    {
        $args = $this->getModuleArgs();
        $options = $this->getDefaultOptions();
        $updater = new UpdateDataExtensions('module', $args, $options);

        $reflection = new \ReflectionClass($updater);
        $method = $reflection->getMethod('findData');
        $method->setAccessible(true);

        $result = $method->invoke($updater, 'https://invalid.server.com/user/repo', 'creation date');

        $this->assertEquals('', $result);
    }

    public function testFindDataWithUnsupportedDataType(): void
    {
        $args = $this->getModuleArgs();
        $options = $this->getDefaultOptions();
        $updater = new UpdateDataExtensions('module', $args, $options);

        $reflection = new \ReflectionClass($updater);
        $method = $reflection->getMethod('findData');
        $method->setAccessible(true);

        // Capture output to prevent risky test warning
        ob_start();
        $result = $method->invoke($updater, 'https://github.com/user/repo', 'unsupported_data_type');
        ob_end_clean();

        $this->assertEquals('', $result);
    }

    // ==========================================================================
    // FetchAllGitHubPages Tests (via reflection)
    // ==========================================================================

    public function testFetchAllGitHubPagesWithEmptyResponse(): void
    {
        $args = $this->getModuleArgs();
        $options = $this->getDefaultOptions();
        $updater = new UpdateDataExtensions('module', $args, $options);

        $reflection = new \ReflectionClass($updater);
        $method = $reflection->getMethod('fetchAllGitHubPages');
        $method->setAccessible(true);

        // Test with invalid URL that returns empty
        $result = $method->invoke($updater, 'https://api.github.com/repos/nonexistent/nonexistent/releases?per_page=100');

        $this->assertIsArray($result);
    }

    // ==========================================================================
    // FetchAllGitLabPages Tests (via reflection)
    // ==========================================================================

    public function testFetchAllGitLabPagesWithEmptyResponse(): void
    {
        $args = $this->getModuleArgs();
        $options = $this->getDefaultOptions();
        $updater = new UpdateDataExtensions('module', $args, $options);

        $reflection = new \ReflectionClass($updater);
        $method = $reflection->getMethod('fetchAllGitLabPages');
        $method->setAccessible(true);

        // Test with invalid URL that returns empty
        $result = $method->invoke($updater, 'https://gitlab.com/api/v4/projects/nonexistent/releases?per_page=100');

        $this->assertIsArray($result);
    }

    // ==========================================================================
    // Log Tests (via reflection)
    // ==========================================================================

    public function testLogOutputsMessage(): void
    {
        $args = $this->getModuleArgs();
        $options = $this->getDefaultOptions();
        $updater = new UpdateDataExtensions('module', $args, $options);

        $reflection = new \ReflectionClass($updater);
        $method = $reflection->getMethod('log');
        $method->setAccessible(true);

        ob_start();
        $method->invoke($updater, 'Test log message');
        $output = ob_get_clean();

        $this->assertStringContainsString('Test log message', $output);
    }

    // ==========================================================================
    // CSV Handling Tests
    // ==========================================================================

    public function testSaveToCsvFile(): void
    {
        $args = $this->getModuleArgs();
        $options = $this->getDefaultOptions();
        $updater = new UpdateDataExtensions('module', $args, $options);

        $reflection = new \ReflectionClass($updater);
        $method = $reflection->getMethod('saveToCsvFile');
        $method->setAccessible(true);

        $data = [
            ['Name', 'Url', 'Version'],
            ['Module1', 'https://github.com/user/module1', '1.0.0'],
            ['Module2', 'https://github.com/user/module2', '2.0.0'],
        ];

        $destination = $this->dataDir . '/output.csv';
        $result = $method->invoke($updater, $destination, $data);

        $this->assertTrue($result);
        $this->assertFileExists($destination);

        // Verify content
        $content = file_get_contents($destination);
        $this->assertStringContainsString('Module1', $content);
        $this->assertStringContainsString('Module2', $content);
    }

    public function testSaveToTsvFile(): void
    {
        $args = $this->getModuleArgs();
        $options = $this->getDefaultOptions();
        $updater = new UpdateDataExtensions('module', $args, $options);

        $reflection = new \ReflectionClass($updater);
        $method = $reflection->getMethod('saveToTsvFile');
        $method->setAccessible(true);

        $data = [
            ['Name', 'Url', 'Version'],
            ['Module1', 'https://github.com/user/module1', '1.0.0'],
        ];

        $destination = $this->dataDir . '/output.tsv';
        $result = $method->invoke($updater, $destination, $data);

        $this->assertTrue($result);
        $this->assertFileExists($destination);
    }

    // ==========================================================================
    // Curl Tests (via reflection)
    // ==========================================================================

    public function testCurlWithInvalidUrl(): void
    {
        $args = $this->getModuleArgs();
        $options = $this->getDefaultOptions();
        $updater = new UpdateDataExtensions('module', $args, $options);

        $reflection = new \ReflectionClass($updater);
        $method = $reflection->getMethod('curl');
        $method->setAccessible(true);

        ob_start();
        $result = $method->invoke($updater, 'https://invalid-url-that-does-not-exist.example.com/api');
        ob_end_clean();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testCurlCachesResults(): void
    {
        $args = $this->getModuleArgs();
        $options = $this->getDefaultOptions();
        $updater = new UpdateDataExtensions('module', $args, $options);

        $reflection = new \ReflectionClass($updater);
        $method = $reflection->getMethod('curl');
        $method->setAccessible(true);

        // Same URL should return cached result
        ob_start();
        $result1 = $method->invoke($updater, 'https://api.github.com/repos/omeka/omeka-s');
        $result2 = $method->invoke($updater, 'https://api.github.com/repos/omeka/omeka-s');
        ob_end_clean();

        // Both should be equal (cached)
        $this->assertEquals($result1, $result2);
    }

    // ==========================================================================
    // Order Tests (via reflection)
    // ==========================================================================

    public function testOrderWithHeaderOnly(): void
    {
        $args = $this->getModuleArgs();
        $options = $this->getDefaultOptions();
        $options['order'] = ['Name' => 'asc'];
        $updater = new UpdateDataExtensions('module', $args, $options);

        $reflection = new \ReflectionClass($updater);
        $method = $reflection->getMethod('order');
        $method->setAccessible(true);

        // Test with header row only
        $result = $method->invoke($updater, [['Name', 'Url']]);

        $this->assertIsArray($result);
    }

    public function testOrderSortsAddons(): void
    {
        $args = $this->getModuleArgs();
        $options = $this->getDefaultOptions();
        $options['order'] = ['Name' => 'asc'];
        $updater = new UpdateDataExtensions('module', $args, $options);

        $reflection = new \ReflectionClass($updater);
        $method = $reflection->getMethod('order');
        $method->setAccessible(true);

        $addons = [
            ['Name', 'Url'],
            ['Zebra', 'https://example.com/zebra'],
            ['Alpha', 'https://example.com/alpha'],
            ['Middle', 'https://example.com/middle'],
        ];

        $result = $method->invoke($updater, $addons);

        // Should be sorted by Name ascending
        $this->assertEquals('Alpha', $result[1][0]);
        $this->assertEquals('Middle', $result[2][0]);
        $this->assertEquals('Zebra', $result[3][0]);
    }

    // ==========================================================================
    // Integration Test - Process with Format CSV Only
    // ==========================================================================

    public function testProcessFormatCsvOnly(): void
    {
        // Create a test CSV file
        $csvContent = "Name,Url,Last version\n";
        $csvContent .= "TestModule,https://github.com/user/test,1.0.0\n";
        file_put_contents($this->dataDir . '/test_modules.csv', $csvContent);

        $args = $this->getModuleArgs();
        $options = $this->getDefaultOptions();
        $options['processFormatCsvOnly'] = true;

        ob_start();
        $updater = new UpdateDataExtensions('module', $args, $options);
        $result = $updater->process();
        ob_end_clean();

        $this->assertTrue($result);
        $this->assertFileExists($this->dataDir . '/test_modules.csv');
    }

    public function testIsUrlForVersion(): void
    {
        $github = 'https://github.com/o/r/releases/download/v3.4.15/Shortcode-3.4.15.zip';
        $gitlab = 'https://gitlab.com/api/v4/projects/o%2Fr/packages/generic/Shortcode/3.4.16/Shortcode-3.4.16.zip';
        $archive = 'https://gitlab.com/o/r/-/archive/3.4.12/r-3.4.12.zip';
        $this->assertTrue(UpdateDataExtensions::isUrlForVersion($github, '3.4.15'));
        $this->assertTrue(UpdateDataExtensions::isUrlForVersion($github, 'v3.4.15'));
        $this->assertTrue(UpdateDataExtensions::isUrlForVersion($gitlab, '3.4.16'));
        $this->assertFalse(UpdateDataExtensions::isUrlForVersion($archive, '3.4.15'));
        $this->assertFalse(UpdateDataExtensions::isUrlForVersion($archive, '3.4.1'));
        $this->assertFalse(UpdateDataExtensions::isUrlForVersion($archive, '3.4'));
        $this->assertFalse(UpdateDataExtensions::isUrlForVersion($archive, ''));
        $this->assertFalse(UpdateDataExtensions::isUrlForVersion('', '3.4.12'));
    }

    public function testDefaultBranchArchiveUrl(): void
    {
        $this->assertSame(
            'https://github.com/o/Repo/archive/HEAD.zip',
            UpdateDataExtensions::defaultBranchArchiveUrl('https://github.com/o/Repo', 'Repo')
        );
        $this->assertSame(
            'https://gitlab.com/o/Repo/-/archive/HEAD/Repo-HEAD.zip',
            UpdateDataExtensions::defaultBranchArchiveUrl('https://gitlab.com/o/Repo/', 'Repo')
        );
        $this->assertSame('', UpdateDataExtensions::defaultBranchArchiveUrl('https://example.org/o/Repo', 'Repo'));
        $this->assertSame('', UpdateDataExtensions::defaultBranchArchiveUrl('https://github.com/o/Repo', ''));
    }

    public function testRetryAfter(): void
    {
        $this->assertSame(30, UpdateDataExtensions::retryAfter(["HTTP/2 429\r\n", "Retry-After: 30\r\n"]));
        $date = gmdate('D, d M Y H:i:s', time() + 120) . ' GMT';
        $delay = UpdateDataExtensions::retryAfter(['retry-after: ' . $date]);
        $this->assertGreaterThanOrEqual(118, $delay);
        $this->assertLessThanOrEqual(120, $delay);
        $reset = (string) (time() + 60);
        $this->assertGreaterThanOrEqual(59, UpdateDataExtensions::retryAfter(['x-ratelimit-remaining: 0', 'x-ratelimit-reset: ' . $reset]));
        $this->assertGreaterThanOrEqual(59, UpdateDataExtensions::retryAfter(['RateLimit-Remaining: 0', 'RateLimit-Reset: ' . $reset]));
        $this->assertNull(UpdateDataExtensions::retryAfter(['x-ratelimit-remaining: 10', 'x-ratelimit-reset: ' . $reset]));
        $this->assertNull(UpdateDataExtensions::retryAfter(['Content-Type: text/plain']));
    }

    public function testRateLimitScope(): void
    {
        $this->assertSame('gitlab.com/api', UpdateDataExtensions::rateLimitScope('https://gitlab.com/api/v4/projects/a%2Fb'));
        $this->assertSame('gitlab.com', UpdateDataExtensions::rateLimitScope('https://gitlab.com/a/b/-/raw/master/config/module.ini'));
        $this->assertSame('raw.githubusercontent.com', UpdateDataExtensions::rateLimitScope('https://raw.githubusercontent.com/a/b/master/plugin.ini'));
    }

    public function testIsMirrorUrl(): void
    {
        $this->assertTrue(UpdateDataExtensions::isMirrorUrl('https://gitlab.com/Daniel-KM/Omeka-S-module-A', 'https://github.com/daniel-km/Omeka-S-module-A'));
        $this->assertFalse(UpdateDataExtensions::isMirrorUrl('https://gitlab.com/Daniel-KM/Omeka-S-module-CSVImport', 'https://github.com/omeka-s-modules/CSVImport'));
        $this->assertFalse(UpdateDataExtensions::isMirrorUrl('https://github.com/Daniel-KM/A', 'https://github.com/Daniel-KM/B'));
    }

    private function invokeProtected(UpdateDataExtensions $updater, string $name, array $addons): array
    {
        $method = new \ReflectionMethod($updater, $name);
        $method->setAccessible(true);
        ob_start();
        $result = $method->invoke($updater, $addons);
        ob_end_clean();
        return $result;
    }

    public function testFilterDuplicatesKeepsForksWithNewerVersionOrEnoughOwnCommits(): void
    {
        $options = $this->getDefaultOptions();
        $options['filterDuplicates'] = true;
        $options['filterFalseForks'] = true;
        $updater = new UpdateDataExtensions('module', $this->getModuleArgs(), $options);

        $header = ['Name', 'Url', 'Alternate urls', 'Fork source', 'Fork commits', 'Creation date', 'Last update', 'Last version'];
        $source = 'https://github.com/omeka-s-modules/A';
        $addons = [
            $header,
            ['A', $source, '', '', '', '2015-01-01', '2026-01-01', '1.0.0'],
            ['A', 'https://github.com/u1/A', '', $source, '10', '2020-01-01', '2026-02-01', '1.0.0'],
            ['A', 'https://github.com/u2/A', '', $source, '2', '2020-01-01', '2026-03-01', '1.0.0'],
            ['A', 'https://github.com/u3/A', '', $source, '1', '2020-01-01', '2026-04-01', '2.0.0'],
            ['A', 'https://github.com/u4/A', '', $source, '0', '2020-01-01', '2026-05-01', '1.0.0'],
        ];

        $result = $this->invokeProtected($updater, 'filterDuplicates', $addons);
        $urls = array_column(array_slice($result, 1), 1);

        // The original, the fork with 10 commits, the fork with a newer version.
        $this->assertSame([$source, 'https://github.com/u1/A', 'https://github.com/u3/A'], $urls);
        // The fork with 2 commits is stored as alternate of the previous row.
        $this->assertStringContainsString('https://github.com/u2/A', $result[2][2]);
        $this->assertStringContainsString('https://github.com/u4/A', $result[3][2]);
    }

    public function testMergeSyncedReposMergesOnlyMirrorsOfTheSameOwner(): void
    {
        $updater = new UpdateDataExtensions('module', $this->getModuleArgs(), $this->getDefaultOptions());
        $header = ['Name', 'Url', 'Alternate urls', 'Last update', 'Last version', 'Stars'];
        $headers = new \ReflectionProperty($updater, 'headers');
        $headers->setAccessible(true);
        $headers->setValue($updater, array_flip($header));

        $addons = [
            $header,
            ['A', 'https://github.com/omeka-s-modules/A', '', '2026-01-01', '1.0.0', '4'],
            ['A', 'https://gitlab.com/Daniel-KM/A', '', '2026-03-01', '1.1.0', '2'],
            ['A', 'https://github.com/Daniel-KM/A', '', '2026-02-01', '1.1.0', '1'],
        ];

        $result = $this->invokeProtected($updater, 'mergeSyncedRepos', $addons);
        $urls = array_column(array_slice($result, 1), 1);

        // The original is kept; the mirrors of Daniel-KM are merged.
        $this->assertSame(['https://github.com/omeka-s-modules/A', 'https://gitlab.com/Daniel-KM/A'], $urls);
        $this->assertSame('https://github.com/Daniel-KM/A', $result[2][2]);
    }

    public function testFilterReleaseZips(): void
    {
        $names = fn (array $list) => array_map(fn ($o) => $o->name, UpdateDataExtensions::filterReleaseZips(...$list));
        $asset = fn (string $name) => (object) ['name' => $name];

        // Tag with "v", unlike the file name.
        $this->assertSame(['ActivityLog-1.0.1.zip'], $names(['ActivityLog', 'v1.0.1', [$asset('ActivityLog-1.0.1.zip')]]));
        // Dependencies zipped in the release are skipped.
        $this->assertSame(['Mirador-3.4.1.zip'], $names(['Mirador', '3.4.1', [$asset('Mirador-3.4.1.zip'), $asset('mirador-plugins.zip'), $asset('readme.md')]]));
        // Files for some versions of php.
        $this->assertSame(['Log-3.4.20.zip', 'Log-3.4.20-php74.zip'], $names(['Log', '3.4.20', [$asset('Log-3.4.20.zip'), $asset('Log-3.4.20-php74.zip')]]));
        // A single zip with another name is used.
        $this->assertSame(['AllinOneAccessibility.zip'], $names(['omeka-s-all-in-one-accessibility', '1.0.0', [$asset('AllinOneAccessibility.zip')]]));
        // No zip.
        $this->assertSame([], $names(['A', '1.0', [$asset('A-1.0.tar.gz')]]));
    }
}
