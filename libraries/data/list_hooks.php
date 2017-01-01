<?php

/**
 * List of hooks used in themes in Omeka C. It can be completed.
 *
 * @internal This file is merged during the init of processors of the plugins.
 */
return array(
    // Default list of public hooks for a standard installation.
    // @link https://omeka.readthedocs.io/en/latest/Reference/hooks/

    // Most of these hooks have an empty output, because only the output of
    // upgraded plugins is managed and, when managed, the modules use events.
    // So only "public_head" is kept and is used mainly to add the css and js.

    // 'public_body',
    // 'public_collections_browse',
    // 'public_collections_browse_each',
    // 'public_collections_show',
    // 'public_content_top',
    // 'public_footer',
    'public_head',
    // 'public_header',
    // 'public_home',
    // 'public_items_browse',
    // 'public_items_browse_each',
    // 'public_items_search',
    // 'public_items_show',

    // These ones are not hooks, but are managed the same for technical reasons.
    'head_css',
    'head_js',
);
