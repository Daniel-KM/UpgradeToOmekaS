<?php

/**
 * Mapping of theme folders from Omeka C to Omeka S. It can be completed.
 *
 * @internal This file is merged during the init of processors of the plugins.
 */
return array(
    // Remove the api folder, since this is managed above the site level and
    // anyway, the api keys are not upgraded.
    'api' => '',

    // Assets.
    'css' => 'asset/css',
    '_sass' => 'asset/sass',
    'sass' => 'asset/sass',
    'font' => 'asset/fonts',
    'fonts' => 'asset/fonts',
    'javascript' => 'asset/js',
    'javascripts' => 'asset/js',
    'js' => 'asset/js',
    'images' => 'asset/img',
    'img' => 'asset/img',

    // Various tools (pagination, search...).
    'common' => 'view/common',
    'error' => 'view/error',

    // The level above the site.
    'index' => 'view/omeka/index',
    'install' => 'view/omeka/install',
    'users' => 'view/omeka/login',
    'maintenance' => 'view/omeka/maintenance',
    'migrate' => 'view/omeka/migrate',

    // The upgraded Omeka Classic site.
    'index' => 'view/omeka/site/index',
    'items' => 'view/omeka/site/item',
    'collections' => 'view/omeka/site/item-set',
    'files' => 'view/omeka/site/media',

    // Removed in Omeka S, but kept for a possible global "resource" search.
    'search' => 'view/omeka/site/resource',

    // If not merged, the other plugins folders will move in "view/omeka/site".

    // Other folders to keep in place.
    '.git' => '.git',
    '.tx' => '.tx',
);
