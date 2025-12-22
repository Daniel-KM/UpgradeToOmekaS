<?php

declare(strict_types=1);

namespace UpgradeToOmekaS\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Addons;
use Utils;

/**
 * Unit tests for the Addons class from install_omeka_s.php.
 */
class AddonsTest extends TestCase
{
    private Addons $addons;
    private Utils $utils;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->utils = new Utils();
        $this->addons = new Addons($this->utils);
        $this->tempDir = sys_get_temp_dir() . '/addons_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        mkdir($this->tempDir . '/modules', 0755, true);
        mkdir($this->tempDir . '/themes', 0755, true);
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
    // Constants Tests
    // ==========================================================================

    public function testAddonListRepoConstant(): void
    {
        $this->assertNotEmpty(Addons::ADDON_LIST_REPO);
        $this->assertStringContainsString('/', Addons::ADDON_LIST_REPO);
    }

    public function testAddonListBranchConstant(): void
    {
        $this->assertNotEmpty(Addons::ADDON_LIST_BRANCH);
    }

    // ==========================================================================
    // Constructor Tests
    // ==========================================================================

    public function testConstructorWithUtils(): void
    {
        $utils = new Utils();
        $addons = new Addons($utils);

        $this->assertInstanceOf(Addons::class, $addons);
    }

    public function testConstructorWithoutUtils(): void
    {
        $addons = new Addons();

        $this->assertInstanceOf(Addons::class, $addons);
    }

    // ==========================================================================
    // Types Tests
    // ==========================================================================

    public function testTypesReturnsArray(): void
    {
        $types = $this->addons->types();

        $this->assertIsArray($types);
        $this->assertNotEmpty($types);
    }

    public function testTypesContainsExpectedTypes(): void
    {
        $types = $this->addons->types();

        $this->assertContains('module', $types);
        $this->assertContains('theme', $types);
        $this->assertContains('omekamodule', $types);
        $this->assertContains('omekatheme', $types);
    }

    // ==========================================================================
    // GetAddons Tests
    // ==========================================================================

    public function testGetAddonsReturnsArray(): void
    {
        $addons = $this->addons->getAddons();

        $this->assertIsArray($addons);
    }

    public function testGetAddonsHasAllTypes(): void
    {
        $addons = $this->addons->getAddons();
        $types = $this->addons->types();

        foreach ($types as $type) {
            $this->assertArrayHasKey($type, $addons);
        }
    }

    public function testGetAddonsRefreshParameter(): void
    {
        // First call
        $addons1 = $this->addons->getAddons(false);

        // Second call with refresh
        $addons2 = $this->addons->getAddons(true);

        // Both should return valid arrays
        $this->assertIsArray($addons1);
        $this->assertIsArray($addons2);
    }

    // ==========================================================================
    // GetSelections Tests
    // ==========================================================================

    public function testGetSelectionsReturnsArray(): void
    {
        $selections = $this->addons->getSelections();

        $this->assertIsArray($selections);
    }

    public function testGetSelectionsHasValidStructure(): void
    {
        $selections = $this->addons->getSelections();

        foreach ($selections as $name => $modules) {
            $this->assertIsString($name);
            $this->assertIsArray($modules);
        }
    }

    // ==========================================================================
    // GetMissingDependencies Tests (via reflection)
    // ==========================================================================

    public function testGetMissingDependenciesWithNoDependencies(): void
    {
        $reflection = new \ReflectionClass($this->addons);
        $method = $reflection->getMethod('getMissingDependencies');
        $method->setAccessible(true);

        $result = $method->invoke($this->addons, []);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetMissingDependenciesWithMissingModules(): void
    {
        $reflection = new \ReflectionClass($this->addons);
        $method = $reflection->getMethod('getMissingDependencies');
        $method->setAccessible(true);

        $result = $method->invoke($this->addons, ['NonExistentModule1', 'NonExistentModule2']);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertContains('NonExistentModule1', $result);
        $this->assertContains('NonExistentModule2', $result);
    }

    public function testGetMissingDependenciesWithEmptyStrings(): void
    {
        $reflection = new \ReflectionClass($this->addons);
        $method = $reflection->getMethod('getMissingDependencies');
        $method->setAccessible(true);

        $result = $method->invoke($this->addons, ['', '  ', 'ValidModule']);

        $this->assertIsArray($result);
        // Should only contain ValidModule, empty strings should be filtered
        $this->assertContains('ValidModule', $result);
        $this->assertNotContains('', $result);
    }

    // ==========================================================================
    // FindAddonByName Tests (via reflection)
    // ==========================================================================

    public function testFindAddonByNameWithNonExistent(): void
    {
        $reflection = new \ReflectionClass($this->addons);
        $method = $reflection->getMethod('findAddonByName');
        $method->setAccessible(true);

        $result = $method->invoke($this->addons, 'NonExistentModuleXYZ');

        $this->assertNull($result);
    }

    public function testFindAddonByNameCaseInsensitive(): void
    {
        // This test ensures the method handles case-insensitivity
        $reflection = new \ReflectionClass($this->addons);
        $method = $reflection->getMethod('findAddonByName');
        $method->setAccessible(true);

        // Both should return the same result (or both null if not found)
        $result1 = $method->invoke($this->addons, 'common');
        $result2 = $method->invoke($this->addons, 'COMMON');
        $result3 = $method->invoke($this->addons, 'Common');

        // All three should be equal (either all found or all null)
        $this->assertEquals($result1, $result2);
        $this->assertEquals($result2, $result3);
    }

    // ==========================================================================
    // ListDirsInDir Tests (via reflection)
    // ==========================================================================

    public function testListDirsInDirWithValidDirectory(): void
    {
        // Create some test directories
        mkdir($this->tempDir . '/modules/TestModule1', 0755, true);
        mkdir($this->tempDir . '/modules/TestModule2', 0755, true);
        file_put_contents($this->tempDir . '/modules/file.txt', 'not a dir');

        $reflection = new \ReflectionClass($this->addons);
        $method = $reflection->getMethod('listDirsInDir');
        $method->setAccessible(true);

        $result = $method->invoke($this->addons, $this->tempDir . '/modules', true);

        $this->assertIsArray($result);
        $this->assertContains('TestModule1', $result);
        $this->assertContains('TestModule2', $result);
        $this->assertNotContains('file.txt', $result);
    }

    public function testListDirsInDirWithEmptyDirectory(): void
    {
        $emptyDir = $this->tempDir . '/empty';
        mkdir($emptyDir, 0755, true);

        $reflection = new \ReflectionClass($this->addons);
        $method = $reflection->getMethod('listDirsInDir');
        $method->setAccessible(true);

        $result = $method->invoke($this->addons, $emptyDir, true);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testListDirsInDirWithNonExistentDirectory(): void
    {
        $reflection = new \ReflectionClass($this->addons);
        $method = $reflection->getMethod('listDirsInDir');
        $method->setAccessible(true);

        $result = $method->invoke($this->addons, '/nonexistent/directory', true);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testListDirsInDirRefreshParameter(): void
    {
        mkdir($this->tempDir . '/refresh_test/Dir1', 0755, true);

        $reflection = new \ReflectionClass($this->addons);
        $method = $reflection->getMethod('listDirsInDir');
        $method->setAccessible(true);

        // First call
        $result1 = $method->invoke($this->addons, $this->tempDir . '/refresh_test', false);

        // Add another directory
        mkdir($this->tempDir . '/refresh_test/Dir2', 0755, true);

        // Call without refresh (should use cache)
        $result2 = $method->invoke($this->addons, $this->tempDir . '/refresh_test', false);

        // Call with refresh (should see new directory)
        $result3 = $method->invoke($this->addons, $this->tempDir . '/refresh_test', true);

        $this->assertContains('Dir1', $result1);
        $this->assertContains('Dir1', $result2);
        $this->assertContains('Dir1', $result3);
        $this->assertContains('Dir2', $result3);
    }

    // ==========================================================================
    // Addon Data Structure Tests
    // ==========================================================================

    public function testAddonDataStructure(): void
    {
        $addons = $this->addons->getAddons();

        // Check module type has expected structure
        if (!empty($addons['module'])) {
            $firstAddon = reset($addons['module']);

            $this->assertArrayHasKey('type', $firstAddon);
            $this->assertArrayHasKey('name', $firstAddon);
            $this->assertArrayHasKey('url', $firstAddon);
            $this->assertArrayHasKey('zip', $firstAddon);
            $this->assertArrayHasKey('dir', $firstAddon);
        }
    }

    public function testAddonDependenciesIsArray(): void
    {
        $addons = $this->addons->getAddons();

        if (!empty($addons['module'])) {
            foreach ($addons['module'] as $addon) {
                if (isset($addon['dependencies'])) {
                    $this->assertIsArray($addon['dependencies']);
                }
            }
        }
    }
}
