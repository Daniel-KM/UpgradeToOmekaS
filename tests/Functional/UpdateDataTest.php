<?php

declare(strict_types=1);

namespace UpgradeToOmekaS\Tests\Functional;

use PHPUnit\Framework\TestCase;
use UpdateDataExtensions;

/**
 * Functional tests for update_data.php workflow.
 *
 * These tests verify the end-to-end functionality of the data updater.
 */
class UpdateDataTest extends TestCase
{
    private string $tempDir;
    private string $dataDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/update_test_' . uniqid();
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

    // ==========================================================================
    // CSV Processing Tests
    // ==========================================================================

    public function testProcessCsvFormatOnly(): void
    {
        // Create test CSV
        $csvContent = "Name,Url,Last version,Description\n";
        $csvContent .= "TestModule,https://github.com/user/TestModule,1.0.0,\"A test module\"\n";
        $csvContent .= "AnotherModule,https://github.com/user/AnotherModule,2.0.0,\"Another module\"\n";

        $csvPath = $this->dataDir . '/test_modules.csv';
        file_put_contents($csvPath, $csvContent);

        $args = [
            'source' => $csvPath,
            'destination' => $csvPath,
            'topic' => 'omeka-s-module',
            'keywords' => '"Omeka%20S"+module',
            'ini' => 'config/module.ini',
        ];

        $options = $this->getDefaultOptions();
        $options['processFormatCsvOnly'] = true;

        ob_start();
        $updater = new UpdateDataExtensions('module', $args, $options);
        $result = $updater->process();
        ob_end_clean();

        $this->assertTrue($result);
        $this->assertFileExists($csvPath);

        // Verify content preserved
        $content = file_get_contents($csvPath);
        $this->assertStringContainsString('TestModule', $content);
        $this->assertStringContainsString('AnotherModule', $content);
    }

    public function testCsvOrderingByName(): void
    {
        // Create CSV with unordered entries
        $csvContent = "Name,Url,Last version\n";
        $csvContent .= "Zebra,https://github.com/user/Zebra,1.0.0\n";
        $csvContent .= "Alpha,https://github.com/user/Alpha,1.0.0\n";
        $csvContent .= "Middle,https://github.com/user/Middle,1.0.0\n";

        $csvPath = $this->dataDir . '/order_test.csv';
        file_put_contents($csvPath, $csvContent);

        $args = [
            'source' => $csvPath,
            'destination' => $csvPath,
            'topic' => 'omeka-s-module',
            'keywords' => '"Omeka%20S"+module',
            'ini' => 'config/module.ini',
        ];

        $options = $this->getDefaultOptions();
        $options['processFormatCsvOnly'] = true;
        $options['order'] = ['Name' => 'asc'];

        ob_start();
        $updater = new UpdateDataExtensions('module', $args, $options);
        $result = $updater->process();
        ob_end_clean();

        $this->assertTrue($result);

        // Read and verify order
        $lines = file($csvPath, FILE_IGNORE_NEW_LINES);
        // Line 0 is header, Line 1 should be Alpha
        $this->assertStringContainsString('Alpha', $lines[1]);
        $this->assertStringContainsString('Middle', $lines[2]);
        $this->assertStringContainsString('Zebra', $lines[3]);
    }

    // ==========================================================================
    // TSV Output Tests
    // ==========================================================================

    public function testSaveTsvFileMethod(): void
    {
        $args = [
            'source' => $this->dataDir . '/test.csv',
            'destination' => $this->dataDir . '/test.csv',
            'topic' => 'omeka-s-module',
            'keywords' => '"Omeka%20S"+module',
            'ini' => 'config/module.ini',
        ];

        $updater = new UpdateDataExtensions('module', $args, $this->getDefaultOptions());

        $reflection = new \ReflectionClass($updater);
        $method = $reflection->getMethod('saveToTsvFile');
        $method->setAccessible(true);

        $data = [
            ['Name', 'Url', 'Version'],
            ['Module1', 'https://example.com', '1.0.0'],
        ];

        $tsvPath = $this->dataDir . '/output.tsv';
        $result = $method->invoke($updater, $tsvPath, $data);

        $this->assertTrue($result);
        $this->assertFileExists($tsvPath);
    }

    // ==========================================================================
    // Namespace Extraction Tests
    // ==========================================================================

    public function testNamespaceExtractionReturnsNonEmptyString(): void
    {
        $args = [
            'source' => $this->dataDir . '/test.csv',
            'destination' => $this->dataDir . '/test.csv',
            'topic' => 'omeka-s-module',
            'keywords' => '"Omeka%20S"+module',
            'ini' => 'config/module.ini',
        ];

        $updater = new UpdateDataExtensions('module', $args, $this->getDefaultOptions());

        $reflection = new \ReflectionClass($updater);
        $method = $reflection->getMethod('extractNamespaceFromProjectName');
        $method->setAccessible(true);

        // Test various inputs return non-empty strings
        $inputs = ['some-module', 'AnotherModule', 'test'];

        foreach ($inputs as $input) {
            $result = $method->invoke($updater, $input);
            $this->assertIsString($result, "Result for '$input' should be string");
            $this->assertNotEmpty($result, "Result for '$input' should not be empty");
        }
    }

    public function testNamespaceExtractionKnownException(): void
    {
        $args = [
            'source' => $this->dataDir . '/test.csv',
            'destination' => $this->dataDir . '/test.csv',
            'topic' => 'omeka-s-module',
            'keywords' => '"Omeka%20S"+module',
            'ini' => 'config/module.ini',
        ];

        $updater = new UpdateDataExtensions('module', $args, $this->getDefaultOptions());

        $reflection = new \ReflectionClass($updater);
        $method = $reflection->getMethod('extractNamespaceFromProjectName');
        $method->setAccessible(true);

        // Test known exception from the exceptions array
        $result = $method->invoke($updater, 'UpgradeToOmekaS');
        $this->assertEquals('UpgradeToOmekaS', $result);
    }

    // ==========================================================================
    // GitHub API Integration Tests
    // ==========================================================================

    /**
     * @group network
     */
    public function testGitHubApiDataRetrieval(): void
    {
        $args = [
            'source' => $this->dataDir . '/github_test.csv',
            'destination' => $this->dataDir . '/github_test.csv',
            'topic' => 'omeka-s-module',
            'keywords' => '"Omeka%20S"+module',
            'ini' => 'config/module.ini',
        ];

        $updater = new UpdateDataExtensions('module', $args, $this->getDefaultOptions());

        $reflection = new \ReflectionClass($updater);
        $method = $reflection->getMethod('findData');
        $method->setAccessible(true);

        // Test with known repository
        ob_start();
        $result = $method->invoke($updater, 'https://github.com/omeka/omeka-s', 'creation date');
        ob_end_clean();

        // Should return a date string if API is accessible
        if (!empty($result)) {
            $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}/', $result);
        }
    }

    /**
     * @group network
     */
    public function testGitLabApiDataRetrieval(): void
    {
        $args = [
            'source' => $this->dataDir . '/gitlab_test.csv',
            'destination' => $this->dataDir . '/gitlab_test.csv',
            'topic' => 'omeka-s-module',
            'keywords' => '"Omeka%20S"+module',
            'ini' => 'config/module.ini',
        ];

        $updater = new UpdateDataExtensions('module', $args, $this->getDefaultOptions());

        $reflection = new \ReflectionClass($updater);
        $method = $reflection->getMethod('findData');
        $method->setAccessible(true);

        // Test with a known GitLab repository
        ob_start();
        $result = $method->invoke(
            $updater,
            'https://gitlab.com/Daniel-KM/Omeka-S-module-EasyAdmin',
            'directory name'
        );
        ob_end_clean();

        // Should return a namespace
        $this->assertNotEmpty($result);
    }

    // ==========================================================================
    // Filtering Tests
    // ==========================================================================

    public function testFilterFalseAddonsMethod(): void
    {
        // Create excluded URLs file
        $excludedPath = $this->dataDir . '/excluded_urls.txt';
        file_put_contents($excludedPath, "https://github.com/user/ExcludedModule\n");

        $args = [
            'source' => $this->dataDir . '/test.csv',
            'destination' => $this->dataDir . '/test.csv',
            'topic' => 'omeka-s-module',
            'keywords' => '"Omeka%20S"+module',
            'ini' => 'config/module.ini',
        ];

        $options = $this->getDefaultOptions();
        $options['filterFalseAddons'] = true;
        $options['excludedUrlsPath'] = $excludedPath;

        $updater = new UpdateDataExtensions('module', $args, $options);

        $reflection = new \ReflectionClass($updater);
        $method = $reflection->getMethod('filterFalseAddons');
        $method->setAccessible(true);

        // Test data with header and two addons
        $addons = [
            ['Name', 'Url', 'Last version'],
            ['KeepModule', 'https://github.com/user/KeepModule', '1.0.0'],
            ['ExcludedModule', 'https://github.com/user/ExcludedModule', '1.0.0'],
        ];

        // Capture output to prevent risky test warning
        ob_start();
        $result = $method->invoke($updater, $addons);
        ob_end_clean();

        $this->assertIsArray($result);
        // Should have header + 1 addon (KeepModule) after filtering
        $this->assertCount(2, $result);
    }

    // ==========================================================================
    // Error Handling Tests
    // ==========================================================================

    public function testHandlesMissingSourceFile(): void
    {
        $args = [
            'source' => $this->dataDir . '/nonexistent.csv',
            'destination' => $this->dataDir . '/output.csv',
            'topic' => 'omeka-s-module',
            'keywords' => '"Omeka%20S"+module',
            'ini' => 'config/module.ini',
        ];

        $options = $this->getDefaultOptions();
        $options['processFormatCsvOnly'] = true;

        ob_start();
        $updater = new UpdateDataExtensions('module', $args, $options);
        $result = $updater->process();
        ob_end_clean();

        // Should handle gracefully (return false or create empty file)
        // The exact behavior depends on implementation
        $this->assertIsBool($result);
    }

    public function testHandlesInvalidCsvFormat(): void
    {
        // Create invalid CSV (malformed)
        $csvPath = $this->dataDir . '/invalid.csv';
        file_put_contents($csvPath, "This is not a valid CSV file\nwith inconsistent columns, here, there\n");

        $args = [
            'source' => $csvPath,
            'destination' => $csvPath,
            'topic' => 'omeka-s-module',
            'keywords' => '"Omeka%20S"+module',
            'ini' => 'config/module.ini',
        ];

        $options = $this->getDefaultOptions();
        $options['processFormatCsvOnly'] = true;

        ob_start();
        $updater = new UpdateDataExtensions('module', $args, $options);
        $result = $updater->process();
        ob_end_clean();

        // Should not crash
        $this->assertIsBool($result);
    }

    // ==========================================================================
    // Multi-Type Processing Tests
    // ==========================================================================

    public function testProcessMultipleTypes(): void
    {
        $types = ['module', 'template'];

        foreach ($types as $type) {
            $csvContent = "Name,Url,Last version\n";
            $csvContent .= "Test{$type},https://github.com/user/Test{$type},1.0.0\n";

            $csvPath = $this->dataDir . "/{$type}.csv";
            file_put_contents($csvPath, $csvContent);

            $args = [
                'source' => $csvPath,
                'destination' => $csvPath,
                'topic' => "omeka-s-{$type}",
                'keywords' => "\"Omeka%20S\"+{$type}",
                'ini' => 'config/' . ($type === 'module' ? 'module.ini' : 'theme.ini'),
            ];

            $options = $this->getDefaultOptions();
            $options['processFormatCsvOnly'] = true;

            ob_start();
            $updater = new UpdateDataExtensions($type, $args, $options);
            $result = $updater->process();
            ob_end_clean();

            $this->assertTrue($result, "Processing failed for type: $type");
            $this->assertFileExists($csvPath);
        }
    }

    // ==========================================================================
    // Pagination Tests
    // ==========================================================================

    public function testGitHubPaginationFunction(): void
    {
        $args = [
            'source' => $this->dataDir . '/test.csv',
            'destination' => $this->dataDir . '/test.csv',
            'topic' => 'omeka-s-module',
            'keywords' => '"Omeka%20S"+module',
            'ini' => 'config/module.ini',
        ];

        $updater = new UpdateDataExtensions('module', $args, $this->getDefaultOptions());

        $reflection = new \ReflectionClass($updater);
        $method = $reflection->getMethod('fetchAllGitHubPages');
        $method->setAccessible(true);

        // Test with a URL that has multiple pages (omeka-s releases)
        ob_start();
        $result = $method->invoke(
            $updater,
            'https://api.github.com/repos/omeka/omeka-s/releases?per_page=100'
        );
        ob_end_clean();

        $this->assertIsArray($result);
        // Omeka S should have multiple releases
        if (!empty($result)) {
            $this->assertGreaterThan(0, count($result));
        }
    }

    public function testGitLabPaginationFunction(): void
    {
        $args = [
            'source' => $this->dataDir . '/test.csv',
            'destination' => $this->dataDir . '/test.csv',
            'topic' => 'omeka-s-module',
            'keywords' => '"Omeka%20S"+module',
            'ini' => 'config/module.ini',
        ];

        $updater = new UpdateDataExtensions('module', $args, $this->getDefaultOptions());

        $reflection = new \ReflectionClass($updater);
        $method = $reflection->getMethod('fetchAllGitLabPages');
        $method->setAccessible(true);

        // Test pagination function exists and returns array
        $result = $method->invoke(
            $updater,
            'https://gitlab.com/api/v4/projects/12345/releases?per_page=100'
        );

        $this->assertIsArray($result);
    }
}
