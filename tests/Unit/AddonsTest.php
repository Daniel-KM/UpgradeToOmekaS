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

    // =========================================================================
    // = SatisfiesConstraint Tests (via reflection)
    // =========================================================================
    // =

    private function invokeSatisfies(string $version, string $constraint): bool
    {
        $reflection = new \ReflectionClass($this->addons);
        $method = $reflection->getMethod('satisfiesConstraint');
        $method->setAccessible(true);
        return $method->invoke($this->addons, $version, $constraint);
    }

    public function testSatisfiesConstraintEmptyOrWildcard(): void
    {
        $this->assertTrue($this->invokeSatisfies('4.2.1', ''));
        $this->assertTrue($this->invokeSatisfies('4.2.1', '*'));
    }

    public function testSatisfiesConstraintOperators(): void
    {
        $this->assertTrue($this->invokeSatisfies('4.2.1', '^4.0.0'));
        $this->assertTrue($this->invokeSatisfies('4.2.1', '~4.2.0'));
        $this->assertTrue($this->invokeSatisfies('4.2.1', '>=4.0.0'));
        $this->assertTrue($this->invokeSatisfies('4.2.1', '>=3.0.0 <5.0.0'));
        $this->assertTrue($this->invokeSatisfies('4.2.1', '4.0.0 - 4.9.9'));
        $this->assertTrue($this->invokeSatisfies('4.2.1', '^3.0 || ^4.0'));
    }

    public function testSatisfiesConstraintIncompatible(): void
    {
        $this->assertFalse($this->invokeSatisfies('4.2.1', '^5.0.0'));
        $this->assertFalse($this->invokeSatisfies('4.2.1', '>=5.0.0'));
        $this->assertFalse($this->invokeSatisfies('4.2.1', '~4.1.0'));
    }

    // =========================================================================
    // = PickCompatibleVersion Tests (via reflection)
    // =========================================================================
    // =

    private function invokePick(array $addon): ?array
    {
        $reflection = new \ReflectionClass($this->addons);
        $method = $reflection->getMethod('pickCompatibleVersion');
        $method->setAccessible(true);
        return $method->invoke($this->addons, $addon);
    }

    public function testPickCompatibleVersionPicksNewestCompatible(): void
    {
        // Versions sorted descending, as built by the parser.
        $addon = ['versions' => [
            '3.0.0' => ['version' => '3.0.0', 'omeka_version_constraint' => '^5.0.0', 'download_url' => 'a'],
            '2.0.0' => ['version' => '2.0.0', 'omeka_version_constraint' => '^4.0.0', 'download_url' => 'b'],
        ]];
        $picked = $this->invokePick($addon);
        $this->assertIsArray($picked);
        $this->assertSame('2.0.0', $picked['version']);
    }

    public function testPickCompatibleVersionNullWhenNoVersions(): void
    {
        $this->assertNull($this->invokePick([]));
        $this->assertNull($this->invokePick(['versions' => []]));
    }

    public function testPickCompatibleVersionNullWhenNoneCompatible(): void
    {
        $addon = ['versions' => [
            '3.0.0' => ['version' => '3.0.0', 'omeka_version_constraint' => '^5.0.0', 'download_url' => 'a'],
        ]];
        $this->assertNull($this->invokePick($addon));
    }

    public function testPickCompatibleVersionEmptyConstraintIsCompatible(): void
    {
        $addon = ['versions' => [
            '1.0.0' => ['version' => '1.0.0', 'omeka_version_constraint' => '', 'download_url' => 'a'],
        ]];
        $picked = $this->invokePick($addon);
        $this->assertIsArray($picked);
        $this->assertSame('1.0.0', $picked['version']);
    }

    // =========================================================================
    // = FallbackArchiveUrl Tests (via reflection)
    // =========================================================================
    // =

    public function testFallbackArchiveUrlByHost(): void
    {
        $reflection = new \ReflectionClass($this->addons);
        $method = $reflection->getMethod('fallbackArchiveUrl');
        $method->setAccessible(true);

        $github = $method->invoke($this->addons, 'github.com', 'https://github.com/u/Repo', 'Repo', 'master');
        $this->assertSame('https://github.com/u/Repo/archive/refs/heads/master.zip', $github);

        $gitlab = $method->invoke($this->addons, 'gitlab.com', 'https://gitlab.com/u/Repo', 'Repo', 'main');
        $this->assertSame('https://gitlab.com/u/Repo/-/archive/main/Repo-main.zip', $gitlab);
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
