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

return array(
    '$this->upgrade()->public_nav_main()' => $mainNav,
);
