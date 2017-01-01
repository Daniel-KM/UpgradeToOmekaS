<?php

/**
 * Mapping of global functions from Omeka C to Omeka S. It can be completed.
 *
 * For Simple Pages.
 *
 * @internal This file is merged during the init of processors of the plugins.
 *
 * @internal Unlike the variables, the process uses preg_replace(), so keys and
 * replacements are regex.
 */
return array(
    '~\bsimple_pages_display_breadcrumbs\(~' => '$nav->breadcrumbs(',
    '~' . preg_quote("\$this->upgrade()->metadata('simple_pages_page', 'title')") . '~' => '$page->title()',
    '~' . preg_quote("\$this->upgrade()->metadata('simple_pages_page', 'slug')") . '~' => '$page->slug()',
);
