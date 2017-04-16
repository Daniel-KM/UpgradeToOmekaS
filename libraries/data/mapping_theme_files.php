<?php

/**
 * Mapping of theme files from Omeka C to Omeka S. It can be completed.
 *
 * @internal This file is merged during the init of processors of the plugins.
 */
return array(
    // The key is the filepath of the files already moved in the good Omeka S
    // folder according to the mapping of theme folders.
    // So this is the list of original files from Omeka C application/views/scripts
    // that were already moved according to the structure of themes of Omeka S.
    // Other files remain in place.
    // When a file exists in Omeka S (default files in application/view), but
    // not in Omeka C, it will be used. Conversely, some files are useless in
    // Omeka S.

    // Assets.
    'favicon.ico'                                       => 'asset/img/favicon.ico',
    // The same template can be used for item sets (collections) now.
    'asset/js/items-search.js'                          => 'asset/js/advanced-search.js',

    // TODO Convert custom.php and functions.php into Omeka S helpers if needed.
    'custom.phtml'                                      => 'helper/custom.php',
    'functions.phtml'                                   => 'helper/functions.php',

    // According to Omeka S, the home page is a normal page (or any page). So it
    // should be rebuild via page blocks, eventually via a special block that
    // use the template directly (need a new module).
    'index.phtml'                                       => 'view/omeka/site/page/homepage.phtml',
    // May be:
    // 'index.phtml'                                    => 'view/omeka/site/index/index.phtml',

    // No more admin bar currently, kept for memo.
    'view/common/admin-bar.phtml'                       => 'view/common/admin-bar.phtml',
    // Used only in the admin interface.
    'view/common/delete-confirm.phtml'                  => '_unused/common/delete-confirm.phtml',
    'view/common/footer.phtml'                          => 'view/layout/footer.phtml',
    'view/common/header.phtml'                          => 'view/layout/header.phtml',
    'view/common/output-format-list.phtml'              => 'view/common/output-format-list.phtml',
    'view/common/pagination_control.phtml'              => 'view/common/pagination.phtml',
    // This is the correct mapping, but the two templates are kept to render old themes.
    // 'view/common/record-metadata.phtml'                 => 'view/common/resource-values.phtml',
    'view/common/record-metadata.phtml'                 => 'view/common/record-metadata.phtml',

    'view/error/403.phtml'                              => '_unused/error/403.phtml',
    'view/error/404.rss2.phtml'                         => '_unused/error/404.rss2.phtml',
    'view/error/404.dc.phtml'                           => '_unused/error/404.dc.phtml',
    'view/error/404.json.phtml'                         => '_unused/error/404.json.phtml',
    'view/error/404.phtml'                              => 'view/error/404.phtml',
    'view/error/404.xml.phtml'                          => '_unused/error/404.xml.phtml',
    'view/error/405.phtml'                              => '_unused/error/405.phtml',
    'view/error/index.phtml'                            => 'view/error/index.phtml',

    'view/omeka/login/activate.phtml'                   => 'view/omeka/login/create-password.phtml',
    'view/omeka/login/forgot-password.phtml'            => 'view/omeka/login/forgot-password.phtml',
    'view/omeka/login/login.phtml'                      => 'view/omeka/login/login.phtml',

    // The browse and show.xxx.phtml are useless, except atom/rss. A module may be needed.
    'view/omeka/site/item/browse.atom.phtml'            => '_unused/item/browse.atom.phtml',
    'view/omeka/site/item/browse.dcmes-xml.phtml'       => '_unused/item/browse.dcmes-xml.phtml',
    'view/omeka/site/item/browse.omeka-json.phtml'      => '_unused/item/browse.omeka-json.phtml',
    'view/omeka/site/item/browse.omeka-xml.phtml'       => '_unused/item/browse.omeka-xml.phtml',
    'view/omeka/site/item/browse.rss2.phtml'            => '_unused/item/browse.rss2.phtml',
    'view/omeka/site/item/browse.phtml'                 => 'view/omeka/site/item/browse.phtml',
    'view/omeka/site/item/search.phtml'                 => 'view/omeka/site/item/search.phtml',
    'view/omeka/site/item/search-form.phtml'            => 'view/common/advanced-search.phtml',
    'view/omeka/site/item/show.atom.phtml'              => '_unused/item/show.atom.phtml',
    'view/omeka/site/item/show.omeka-json.phtml'        => '_unused/item/show.omeka-json.phtml',
    'view/omeka/site/item/show.omeka-xml.phtml'         => '_unused/item/show.omeka-xml.phtml',
    'view/omeka/site/item/show.phtml'                   => 'view/omeka/site/item/show.phtml',
    'view/omeka/site/item/show.dcmes-xml.phtml'         => '_unused/item/show.dcmes-xml.phtml',
    // A simple helper for lists.
    'view/omeka/site/item/single.phtml'                 => 'view/omeka/site/item/single.phtml',
    // No tags in Omeka, but kept for possible evolution or module.
    'view/omeka/site/item/tags.phtml'                   => '_unused/record/tags.phtml',

    'view/omeka/site/item-set/browse.phtml'             => 'view/omeka/site/item-set/browse.phtml',
    'view/omeka/site/item-set/show.omeka-json.phtml'    => '_unused/item-set/show.omeka-json.phtml',
    'view/omeka/site/item-set/show.omeka-xml.phtml'     => '_unused/item-set/show.omeka-xml.phtml',
    'view/omeka/site/item-set/show.phtml'               => 'view/omeka/site/item-set/show.phtml',
    // A simple helper for list, that can be kept.
    'view/omeka/site/item-set/single.phtml'             => 'view/omeka/site/item-set/single.phtml',

    'view/omeka/site/media/show.omeka-json.phtml'       => '_unused//media/show.omeka-json.phtml',
    'view/omeka/site/media/show.omeka-xml.phtml'        => '_unused//media/show.omeka-xml.phtml',
    'view/omeka/site/media/show.phtml'                  => 'view/omeka/site/media/show.phtml',

    'view/omeka/site/record/index.phtml'                => '_unused/record/browse.phtml',
    'view/omeka/site/record/search-filters.phtml'       => 'view/common/search-filters.phtml',
    // This may be merged in the header.
    'view/omeka/site/record/search-form.phtml'          => 'view/common/search-main.phtml',
);
