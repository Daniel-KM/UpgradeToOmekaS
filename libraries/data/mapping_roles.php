<?php

/**
 * Mapping of roles from Omeka C to Omeka S. It can be adapted and completed.
 *
 * @internal This file is merged during the init of processors of the plugins.
 */
return array(
    // By default, only the current super user will be the "global_admin".
    'super'             => 'site_admin',
    'admin'             => 'site_admin',
    'contributor'       => 'author',
    'researcher'        => 'researcher',
    // Default roles of Omeka S, if needed.
    'global_admin'      => 'global_admin',
    'site_admin'        => 'site_admin',
    'editor'            => 'editor',
    'reviewer'          => 'reviewer',
    'author'            => 'author',
    'researcher'        => 'researcher',
    // TODO Currently not managed automatically.
    // Plugin Guest User.
    // 'guest'                     => 'guest',
    // Plugin Contribution.
    // 'contribution-anonymous'    => 'anonymous',
    // 'contribution_anonymous'    => 'anonymous',
);
