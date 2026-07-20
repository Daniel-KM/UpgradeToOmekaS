<?php

declare(strict_types=1);

namespace UpgradeToOmekaS\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Addons;
use Utils;

/**
 * Functional tests for install_omeka_s.php workflow.
 *
 * These tests verify the end-to-end functionality of the installer.
 */
class InstallOmekaSTest extends TestCase
{
    private string $tempDir;
    private string $modulesDir;
    private string $themesDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/install_test_' . uniqid();
        $this->modulesDir = $this->tempDir . '/modules';
        $this->themesDir = $this->tempDir . '/themes';

        mkdir($this->tempDir, 0755, true);
        mkdir($this->modulesDir, 0755, true);
        mkdir($this->themesDir, 0755, true);
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

    // ==========================================================================
    // Addon List Fetching Tests
    // ==========================================================================

    public function testFetchAddonListsFromAllSources(): void
    {
        $utils = new Utils();
        $addons = new Addons($utils);

        $addonList = $addons->getAddons();

        // Should have all four types
        $this->assertArrayHasKey('omekamodule', $addonList);
        $this->assertArrayHasKey('omekatheme', $addonList);
        $this->assertArrayHasKey('module', $addonList);
        $this->assertArrayHasKey('theme', $addonList);

        // Each type should have some addons
        $this->assertNotEmpty($addonList['omekamodule'], 'Should have omeka.org modules');
        $this->assertNotEmpty($addonList['module'], 'Should have CSV modules');
    }

    public function testFetchSelectionsFromRemote(): void
    {
        $utils = new Utils();
        $addons = new Addons($utils);

        $selections = $addons->getSelections();

        $this->assertIsArray($selections);
        // Should have at least one selection
        $this->assertNotEmpty($selections, 'Should have at least one selection');

        // Each selection should have an array of module names
        foreach ($selections as $name => $modules) {
            $this->assertIsString($name);
            $this->assertIsArray($modules);
        }
    }

    // ==========================================================================
    // Addon Data Structure Validation Tests
    // ==========================================================================

    public function testAddonDataHasRequiredFields(): void
    {
        $utils = new Utils();
        $addons = new Addons($utils);

        $addonList = $addons->getAddons();
        $requiredFields = ['type', 'name', 'url', 'zip', 'dir'];

        foreach (['module', 'theme'] as $type) {
            if (empty($addonList[$type])) {
                continue;
            }

            // Check first few addons
            $count = 0;
            foreach ($addonList[$type] as $addon) {
                foreach ($requiredFields as $field) {
                    $this->assertArrayHasKey(
                        $field,
                        $addon,
                        "Addon '{$addon['name']}' missing required field '$field'"
                    );
                }
                if (++$count >= 5) {
                    break;
                }
            }
        }
    }

    public function testAddonDependenciesAreArrays(): void
    {
        $utils = new Utils();
        $addons = new Addons($utils);

        $addonList = $addons->getAddons();

        foreach (['module'] as $type) {
            if (empty($addonList[$type])) {
                continue;
            }

            foreach ($addonList[$type] as $addon) {
                if (isset($addon['dependencies'])) {
                    $this->assertIsArray(
                        $addon['dependencies'],
                        "Dependencies for '{$addon['name']}' should be an array"
                    );
                }
            }
        }
    }

    // ==========================================================================
    // Addon URL Validation Tests
    // ==========================================================================

    public function testAddonUrlsAreValid(): void
    {
        $utils = new Utils();
        $addons = new Addons($utils);

        $addonList = $addons->getAddons();

        foreach (['module', 'theme'] as $type) {
            if (empty($addonList[$type])) {
                continue;
            }

            $count = 0;
            foreach ($addonList[$type] as $addon) {
                $this->assertNotEmpty($addon['url'], "URL should not be empty for '{$addon['name']}'");
                $this->assertTrue(
                    filter_var($addon['url'], FILTER_VALIDATE_URL) !== false,
                    "Invalid URL for '{$addon['name']}': {$addon['url']}"
                );

                if (++$count >= 10) {
                    break;
                }
            }
        }
    }

    public function testAddonZipUrlsAreValid(): void
    {
        $utils = new Utils();
        $addons = new Addons($utils);

        $addonList = $addons->getAddons();

        foreach (['module'] as $type) {
            if (empty($addonList[$type])) {
                continue;
            }

            $count = 0;
            foreach ($addonList[$type] as $addon) {
                if (!empty($addon['zip'])) {
                    $this->assertTrue(
                        filter_var($addon['zip'], FILTER_VALIDATE_URL) !== false,
                        "Invalid zip URL for '{$addon['name']}': {$addon['zip']}"
                    );
                }

                if (++$count >= 10) {
                    break;
                }
            }
        }
    }

    // =========================================================================
    // = Version Compatibility Tests
    // =========================================================================
    // =

    public function testAddonsCarryVersions(): void
    {
        $utils = new Utils();
        $addons = new Addons($utils);

        $addonList = $addons->getAddons();

        // Find an addon exposing a versions list with omeka constraints.
        $addonWithVersions = null;
        foreach ($addonList['module'] as $addon) {
            if (!empty($addon['versions'])) {
                $addonWithVersions = $addon;
                break;
            }
        }

        $this->assertNotNull($addonWithVersions, 'Should find at least one addon with versions');
        $this->assertIsArray($addonWithVersions['versions']);
        $first = reset($addonWithVersions['versions']);
        $this->assertArrayHasKey('version', $first);
        $this->assertArrayHasKey('omeka_version_constraint', $first);
        $this->assertArrayHasKey('download_url', $first);
    }

    public function testSatisfiesConstraint(): void
    {
        $utils = new Utils();
        $addons = new Addons($utils);

        $reflection = new \ReflectionClass($addons);
        $method = $reflection->getMethod('satisfiesConstraint');
        $method->setAccessible(true);

        $version = Utils::OMEKA_VERSION;

        $this->assertTrue($method->invoke($addons, $version, ''));
        $this->assertTrue($method->invoke($addons, $version, '*'));
        $this->assertTrue($method->invoke($addons, $version, '^4.0.0'));
        $this->assertTrue($method->invoke($addons, $version, '>=4.0.0'));
        $this->assertTrue($method->invoke($addons, $version, '>=3.0.0 <5.0.0'));
        $this->assertTrue($method->invoke($addons, $version, '4.0.0 - 4.9.9'));
        $this->assertTrue($method->invoke($addons, $version, '^3.0 || ^4.0'));
        $this->assertTrue($method->invoke($addons, $version, '~4.2.0'));

        $this->assertFalse($method->invoke($addons, $version, '^5.0.0'));
        $this->assertFalse($method->invoke($addons, $version, '>=5.0.0'));
        $this->assertFalse($method->invoke($addons, $version, '~4.1.0'));
    }

    // ==========================================================================
    // File Download Tests
    // ==========================================================================

    public function testDownloadOmekaReadme(): void
    {
        $utils = new Utils();
        $destination = $this->tempDir . '/README.md';

        $result = $utils->downloadFile(
            'https://raw.githubusercontent.com/omeka/omeka-s/develop/README.md',
            $destination
        );

        $this->assertTrue($result);
        $this->assertFileExists($destination);
        $this->assertGreaterThan(100, filesize($destination));
    }

    // ==========================================================================
    // Zip Handling Tests
    // ==========================================================================

    public function testCreateAndExtractZip(): void
    {
        $utils = new Utils();

        // Create a test zip
        $zipPath = $this->tempDir . '/test_module.zip';
        $extractPath = $this->tempDir . '/extracted';
        mkdir($extractPath, 0755, true);

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE) === true) {
            $zip->addFromString('TestModule/Module.php', '<?php class Module {}');
            $zip->addFromString('TestModule/config/module.ini', '[info]' . "\n" . 'name="Test Module"');
            $zip->close();
        }

        // Get root directory
        $rootDir = $utils->getZipRootDir($zipPath);
        $this->assertEquals('TestModule', $rootDir);

        // Extract
        $result = $utils->unzipFile($zipPath, $extractPath);
        $this->assertTrue($result);
        $this->assertFileExists($extractPath . '/TestModule/Module.php');
    }

    // =========================================================================
    // = Integration Test - Compatible Version Selection
    // =========================================================================
    // =

    public function testPickCompatibleVersion(): void
    {
        $utils = new Utils();
        $addons = new Addons($utils);

        // Versions are sorted descending, latest first, as built by the parser.
        $mockAddon = [
            'type' => 'module',
            'server' => 'github.com',
            'name' => 'MockModule',
            'basename' => 'MockModule',
            'dir' => 'MockModule',
            'version' => '3.0.0',
            'url' => 'https://github.com/test/MockModule',
            'zip' => '',
            'versions' => [
                '3.0.0' => ['version' => '3.0.0', 'omeka_version_constraint' => '^5.0.0', 'download_url' => 'https://github.com/test/MockModule/releases/download/3.0.0/x.zip'],
                '2.0.0' => ['version' => '2.0.0', 'omeka_version_constraint' => '^4.0.0', 'download_url' => 'https://github.com/test/MockModule/releases/download/2.0.0/x.zip'],
            ],
            'dependencies' => [],
        ];

        $reflection = new \ReflectionClass($addons);
        $method = $reflection->getMethod('pickCompatibleVersion');
        $method->setAccessible(true);

        // Omeka 4.2.1: 3.0.0 requires ^5, so 2.0.0 (^4) must be picked.
        $picked = $method->invoke($addons, $mockAddon);
        $this->assertIsArray($picked);
        $this->assertEquals('2.0.0', $picked['version']);

        // No compatible version returns null.
        $incompatible = $mockAddon;
        $incompatible['versions'] = [
            '3.0.0' => ['version' => '3.0.0', 'omeka_version_constraint' => '^5.0.0', 'download_url' => ''],
        ];
        $this->assertNull($method->invoke($addons, $incompatible));
    }

    // ==========================================================================
    // Version and Requirements Tests
    // ==========================================================================

    public function testOmekaVersionFormat(): void
    {
        $this->assertMatchesRegularExpression(
            '/^\d+\.\d+\.\d+$/',
            Utils::OMEKA_VERSION,
            'Omeka version should be in semver format'
        );
    }

    public function testPhpVersionRequirement(): void
    {
        $this->assertTrue(
            version_compare(PHP_VERSION, Utils::PHP_MINIMUM_VERSION, '>='),
            'Current PHP version should meet minimum requirement'
        );
    }

    public function testRequiredExtensionsAvailable(): void
    {
        foreach (Utils::PHP_REQUIRED_EXTENSIONS as $ext) {
            $this->assertTrue(
                extension_loaded($ext),
                "Required PHP extension '$ext' should be loaded"
            );
        }
    }
}
