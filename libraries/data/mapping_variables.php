<?php

/**
 * Mapping of global functions from Omeka C to Omeka S. It can be completed.
 *
 * @internal This file is merged during the init of processors of the plugins.
 *
 * @internal Unlike mapping of functions, the process uses str_replace().
 */
return array(
    // Of course, nothing is optimized...

    // view/error/index.phtml.
    'WEB_VIEW_SCRIPTS'                      => '$this->assetUrl(\'\')',
    '$displayError'                         => 'true',  // TODO Check if in production.
    '($e)'                                  => '($this->exception)', // The "()" avoids to catch exception.
    '($e->'                                 => '($this->exception->',

    // view/error/404.phtml.
    '$badUri'                               => '$this->serverUrl() . $_SERVER[\'REQUEST_URI\']',

    // Simple page.
    '$is_home_page'                         => 'false', // TODO Check if page is home page (useless).

);
