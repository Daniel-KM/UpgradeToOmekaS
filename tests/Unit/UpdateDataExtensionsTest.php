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
}
