<?php

/**
 * Mapping to replace strings in converted themes of Omeka S.
 *
 * @internal This file is merged during the init of processors of the plugins.
 *
 * @internal Unlike mapping of functions, the process uses str_replace().
 */

$mainNav = <<<'OUTPUT'
$this->upgrade()->currentSite()->publicNav()->menu()->renderMenu(null, ['maxDepth' => $this->themeSetting('nav_depth') - 1])
OUTPUT;

$bootstrapTwitterInput = <<<'INPUT'
$this->addHelperPath(
    __DIR__
        . DIRECTORY_SEPARATOR . 'libraries'
        . DIRECTORY_SEPARATOR . 'Twitter'
        . DIRECTORY_SEPARATOR . 'View'
        . DIRECTORY_SEPARATOR . 'Helper',
    'Twitter_View_Helper_');
INPUT;

$bootstrapTwitterOutput = <<<'OUTPUT'
/* // Need to be upgraded for Omeka S.
$thisView->addHelperPath(
    __DIR__
        . DIRECTORY_SEPARATOR . 'libraries'
        . DIRECTORY_SEPARATOR . 'Twitter'
        . DIRECTORY_SEPARATOR . 'View'
        . DIRECTORY_SEPARATOR . 'Helper',
    'Twitter_View_Helper_');
*/
OUTPUT;

return array(
    '$this->upgrade()->public_nav_main()' => $mainNav,
    $bootstrapTwitterInput => $bootstrapTwitterOutput,
);
