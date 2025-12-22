<?php

declare(strict_types=1);

namespace UpgradeToOmekaS\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Utils;

/**
 * Unit tests for the Utils class from install_omeka_s.php.
 */
class UtilsTest extends TestCase
{
    private Utils $utils;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->utils = new Utils();
        $this->tempDir = sys_get_temp_dir() . '/utils_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        // Clean up temp directory
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

    // ==========================================================================
    // Constants Tests
    // ==========================================================================

    public function testOmekaVersionConstant(): void
    {
        $this->assertNotEmpty(Utils::OMEKA_VERSION);
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', Utils::OMEKA_VERSION);
    }

    public function testPhpMinimumVersionConstant(): void
    {
        $this->assertNotEmpty(Utils::PHP_MINIMUM_VERSION);
        $this->assertTrue(version_compare(Utils::PHP_MINIMUM_VERSION, '8.0.0', '>='));
    }

    public function testPhpMinimumVersionIdConstant(): void
    {
        $this->assertIsInt(Utils::PHP_MINIMUM_VERSION_ID);
        $this->assertGreaterThanOrEqual(80000, Utils::PHP_MINIMUM_VERSION_ID);
    }

    public function testMysqlMinimumVersionConstant(): void
    {
        $this->assertNotEmpty(Utils::MYSQL_MINIMUM_VERSION);
    }

    public function testMariaDbMinimumVersionConstant(): void
    {
        $this->assertNotEmpty(Utils::MARIADB_MINIMUM_VERSION);
    }

    public function testPhpRequiredExtensionsConstant(): void
    {
        $this->assertIsArray(Utils::PHP_REQUIRED_EXTENSIONS);
        $this->assertContains('fileinfo', Utils::PHP_REQUIRED_EXTENSIONS);
        $this->assertContains('pdo_mysql', Utils::PHP_REQUIRED_EXTENSIONS);
    }

    // ==========================================================================
    // Log Tests
    // ==========================================================================

    public function testLogAddsMessage(): void
    {
        // Create fresh Utils instance to have clean log
        $utils = new Utils();
        $utils->log('Test message');
        $logs = $utils->log();  // Call without args to get logs

        $this->assertIsArray($logs);
        $this->assertContains('Test message', $logs);
    }

    public function testLogReturnsArray(): void
    {
        $logs = $this->utils->log();
        $this->assertIsArray($logs);
    }

    // ==========================================================================
    // PsrLog Tests
    // ==========================================================================

    public function testPsrLogWithPlaceholders(): void
    {
        $utils = new Utils();
        $result = $utils->psrLog('Hello {name}', ['name' => 'World']);

        $this->assertIsArray($result);
        $this->assertContains('Hello World', $result);
    }

    public function testPsrLogWithMultiplePlaceholders(): void
    {
        $utils = new Utils();
        $result = $utils->psrLog('User {user} did {action}', ['user' => 'John', 'action' => 'login']);

        $this->assertContains('User John did login', $result);
    }

    public function testPsrLogWithMissingPlaceholder(): void
    {
        $utils = new Utils();
        $result = $utils->psrLog('Hello {name}', []);

        $this->assertContains('Hello {name}', $result);
    }

    // ==========================================================================
    // Execute Tests
    // ==========================================================================

    public function testExecuteSimpleCommand(): void
    {
        $result = $this->utils->execute('echo "test"');

        $this->assertIsString($result);
        $this->assertStringContainsString('test', $result);
    }

    public function testExecuteWithInvalidCommand(): void
    {
        $result = $this->utils->execute('nonexistent_command_xyz 2>/dev/null');

        // Should return empty or false for invalid command
        $this->assertTrue($result === '' || $result === false || $result === null);
    }

    // ==========================================================================
    // GetCommandPath Tests
    // ==========================================================================

    public function testGetCommandPathForExistingCommand(): void
    {
        $path = $this->utils->getCommandPath('ls');

        $this->assertNotFalse($path);
        $this->assertStringContainsString('ls', $path);
    }

    public function testGetCommandPathForNonExistingCommand(): void
    {
        $path = $this->utils->getCommandPath('nonexistent_command_xyz');

        $this->assertFalse($path);
    }

    // ==========================================================================
    // DownloadFile Tests
    // ==========================================================================

    public function testDownloadFileWithValidUrl(): void
    {
        $destination = $this->tempDir . '/downloaded.txt';
        $result = $this->utils->downloadFile(
            'https://raw.githubusercontent.com/omeka/omeka-s/develop/README.md',
            $destination
        );

        $this->assertTrue($result);
        $this->assertFileExists($destination);
        $this->assertGreaterThan(0, filesize($destination));
    }

    public function testDownloadFileWithInvalidUrl(): void
    {
        $destination = $this->tempDir . '/invalid.txt';
        $result = $this->utils->downloadFile(
            'https://invalid-url-that-does-not-exist.example.com/file.txt',
            $destination
        );

        $this->assertFalse($result);
    }

    public function testDownloadFileWithInvalidDestination(): void
    {
        $destination = '/nonexistent/path/file.txt';
        $result = $this->utils->downloadFile(
            'https://example.com/file.txt',
            $destination
        );

        $this->assertFalse($result);
    }

    // ==========================================================================
    // UnzipFile Tests
    // ==========================================================================

    public function testUnzipFileWithValidZip(): void
    {
        // Create a simple zip file for testing
        $zipPath = $this->tempDir . '/test.zip';
        $extractPath = $this->tempDir . '/extracted';
        mkdir($extractPath, 0755, true);

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE) === true) {
            $zip->addFromString('test.txt', 'Hello World');
            $zip->close();
        }

        $result = $this->utils->unzipFile($zipPath, $extractPath);

        $this->assertTrue($result);
        $this->assertFileExists($extractPath . '/test.txt');
    }

    public function testUnzipFileWithInvalidZip(): void
    {
        $invalidZip = $this->tempDir . '/invalid.zip';
        file_put_contents($invalidZip, 'not a zip file');

        $result = $this->utils->unzipFile($invalidZip, $this->tempDir);

        $this->assertFalse($result);
    }

    // ==========================================================================
    // GetZipRootDir Tests
    // ==========================================================================

    public function testGetZipRootDirWithValidZip(): void
    {
        $zipPath = $this->tempDir . '/test_root.zip';

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE) === true) {
            $zip->addFromString('MyModule-1.0.0/Module.php', '<?php');
            $zip->addFromString('MyModule-1.0.0/config/module.ini', 'name=MyModule');
            $zip->close();
        }

        $rootDir = $this->utils->getZipRootDir($zipPath);

        $this->assertEquals('MyModule-1.0.0', $rootDir);
    }

    public function testGetZipRootDirWithInvalidZip(): void
    {
        $invalidZip = $this->tempDir . '/invalid_root.zip';
        file_put_contents($invalidZip, 'not a zip');

        $rootDir = $this->utils->getZipRootDir($invalidZip);

        $this->assertNull($rootDir);
    }

    public function testGetZipRootDirWithNonExistentFile(): void
    {
        $rootDir = $this->utils->getZipRootDir('/nonexistent/file.zip');

        $this->assertNull($rootDir);
    }

    // ==========================================================================
    // RmDir Tests
    // ==========================================================================

    public function testRmDirRemovesDirectory(): void
    {
        $testDir = $this->tempDir . '/to_remove';
        mkdir($testDir, 0755, true);
        file_put_contents($testDir . '/file.txt', 'content');

        $result = $this->utils->rmDir($testDir);

        $this->assertTrue($result);
        $this->assertDirectoryDoesNotExist($testDir);
    }

    public function testRmDirWithNestedDirectories(): void
    {
        $testDir = $this->tempDir . '/nested';
        mkdir($testDir . '/level1/level2', 0755, true);
        file_put_contents($testDir . '/level1/level2/file.txt', 'content');

        $result = $this->utils->rmDir($testDir);

        $this->assertTrue($result);
        $this->assertDirectoryDoesNotExist($testDir);
    }

    public function testRmDirWithNonExistentDirectory(): void
    {
        // rmDir returns true if directory doesn't exist (already "removed")
        $result = $this->utils->rmDir('/nonexistent/directory');

        $this->assertTrue($result);
    }

    public function testRmDirWithRelativePath(): void
    {
        // Non-existent paths return true (already "removed")
        // Security check only applies to existing paths
        $result = $this->utils->rmDir('relative/path');
        $this->assertTrue($result);
    }

    public function testRmDirWithDotDotPath(): void
    {
        // Non-existent paths return true (already "removed")
        $result = $this->utils->rmDir('/some/path/../other');
        $this->assertTrue($result);
    }

    public function testRmDirWithRootPath(): void
    {
        // Root path is explicitly blocked even if it exists
        $result = $this->utils->rmDir('/');
        $this->assertFalse($result);
    }

    public function testRmDirSecurityWithExistingRelativePath(): void
    {
        // Create a relative path directory
        $relativeDir = 'test_relative_dir_' . uniqid();
        mkdir($relativeDir, 0755, true);

        // Should return false for existing relative paths (security)
        $result = $this->utils->rmDir($relativeDir);

        // Clean up
        if (is_dir($relativeDir)) {
            rmdir($relativeDir);
        }

        $this->assertFalse($result);
    }
}
