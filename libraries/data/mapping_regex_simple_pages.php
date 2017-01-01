<?php

/**
 * Mapping to replace strings via regex in converted themes of Omeka S.
 * It can be completed.
 *
 * For Simple Pages.
 *
 * @internal This file is merged during the init of processors of the plugins.
 */
return array(
    '~\bsimple_pages_display_breadcrumbs\(~' => '$nav->breadcrumbs(',
);
