<?php

declare(strict_types=1);

/**
 * PHPUnit bootstrap file for UpgradeToOmekaS tests.
 */

// Define a constant to prevent HTML output during tests
define('PHPUNIT_RUNNING', true);

// Define OMEKA_PATH for tests (use a temp directory)
if (!defined('OMEKA_PATH')) {
    define('OMEKA_PATH', sys_get_temp_dir() . '/omeka-s-test');
}

// Create temp directory if needed
if (!is_dir(OMEKA_PATH)) {
    mkdir(OMEKA_PATH, 0755, true);
    mkdir(OMEKA_PATH . '/modules', 0755, true);
    mkdir(OMEKA_PATH . '/themes', 0755, true);
}

// Autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

/**
 * Helper function to extract and load only class definitions from a script.
 */
function loadScriptClasses(string $scriptPath, string $classPattern = null): void
{
    ob_start();

    $content = file_get_contents($scriptPath);

    // For install_omeka_s.php: extract classes before main script execution
    if ($classPattern === 'install') {
        if (preg_match('/^(.+?)(?=\n\/\/ La plupart des tests|\n\$[a-z]+ = new |\nif \(php_sapi_name)/s', $content, $matches)) {
            $classesOnly = $matches[1];
            $tempFile = sys_get_temp_dir() . '/temp_classes_' . md5($scriptPath) . '.php';
            file_put_contents($tempFile, $classesOnly);
            require_once $tempFile;
        }
    }
    // For update_data.php: extract only the class definition
    elseif ($classPattern === 'update') {
        if (preg_match('/(class UpdateDataExtensions.+)$/s', $content, $matches)) {
            $classCode = "<?php\ndeclare(strict_types=1);\n" . $matches[1];
            $tempFile = sys_get_temp_dir() . '/temp_update_class_' . md5($scriptPath) . '.php';
            file_put_contents($tempFile, $classCode);
            require_once $tempFile;
        }
    }

    ob_end_clean();
}

// Load classes from install_omeka_s.php
loadScriptClasses(dirname(__DIR__) . '/_scripts/install_omeka_s.php', 'install');

// Load classes from update_data.php
loadScriptClasses(dirname(__DIR__) . '/_scripts/update_data.php', 'update');
