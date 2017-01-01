<?php
define('UPGRADETOOMEKAS_DIR', dirname(dirname(__FILE__)));
define('TEST_FILES_DIR', UPGRADETOOMEKAS_DIR
    . DIRECTORY_SEPARATOR . 'tests'
    . DIRECTORY_SEPARATOR . 'suite'
    . DIRECTORY_SEPARATOR . '_files');
require_once dirname(dirname(UPGRADETOOMEKAS_DIR)) . '/application/tests/bootstrap.php';
require_once 'UpgradeToOmekaS_Test_AppTestCase.php';
