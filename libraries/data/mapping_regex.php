<?php

/**
 * Mapping to replace strings via regex in converted themes of Omeka S.
 * It can be completed.
 *
 * Regex are never perfect, of course. They are designed for normal themes and
 * not for plugins (get_view()). Parsing errors are generally related to
 * comments /¯* *_/ or multiline functions.
 *
 * @note When an error occurs, check the default view in application/view and
 * use directly the syntax and functions of Omeka S.
 *
 * @internal This file is merged during the init of processors of the plugins.
 */

// Check if a function has exactly one or more standard arguments and get them.
// Doesn't work with some nested arguments like `function(array(array(), 'value'))`
// but no false positive.
$regexFunctionArguments = array(
    // Check for simple words, space and punctuation and without anything else.
    'basic' => '~ ( (?: \$|\b) %s) (
        \(
            (\'[\w\s\.\?\,\;\:]+\' | \"[\w\s\.\?\,\;\:]+\")
        \)
        ) ~xi',
    // Check one argument exactly, of any form, with a group by argument.
    1 => '~ ( (?: \$|\b) %s) (
        (?!
            \(
            (?: null | \d+ | \'.*?\' | ".*?" | \[.*?\] | (?: \$|\b) [a-z_]\w*\s* (?: \-\>[a-z_]\w*\s* | \(.*?\) )*? )
            \s*,\s*
            (?: null | \d+ | \'.*\' | ".*" | \[.*\] | (?: \$|\b) [a-z_]\w*\s* (?: \-\>[a-z_]\w*\s* | \(.*\) )* )
            \)
        )
        (?= \(.+\))
        \(
            ( null | \d+ | \'.*?\' | ".*?" | \[.*?\] | (?: \$|\b) [a-z_]\w*\s* (?: \-\>[a-z_]\w*\s* | \(.*?\) )*? )
        \)
        ) ~xi',
    // Check two arguments exactly, of any form, with a group by argument.
    2 => '~ ( (?: \$|\b) %s) (
        (?!
            \(
            (?:
                (?: null | \d+ | \'.*?\' | ".*?" | \[.*?\] | (?: \$|\b) [a-z_]\w*\s* (?: \-\>[a-z_]\w*\s* | \(.*?\) )*? )
                \s*,\s*
            ) {2}
            (?: null | \d+ | \'.*\' | ".*" | \[.*\] | (?: \$|\b) [a-z_]\w*\s* (?: \-\>[a-z_]\w*\s* | \(.*\) )* )
            \)
        )
        (?= \(.+, .+\))
        \(
            ( null | \d+ | \'.*?\' | ".*?" | \[.*?\] | (?: \$|\b) [a-z_]\w*\s* (?: \-\>[a-z_]\w*\s* | \(.*?\) )*? )
            \s*\,\s*
            (?!
                (?: null | \d+ | \'.*?\' | ".*?" | \[.*?\] | (?: \$|\b) [a-z_]\w*\s* (?: \-\>[a-z_]\w*\s* | \(.*?\) )*? )
                \s*,\s*
                (?: null | \d+ | \'.*\' | ".*" | \[.*\] | (?: \$|\b) [a-z_]\w*\s* (?: \-\>[a-z_]\w*\s* | \(.*\) )* )
            )
            ( null | \d+ | \'.*?\' | ".*?" | \[.*?\] | (?: \$|\b) [a-z_]\w*\s* (?: \-\>[a-z_]\w*\s* | \(.*?\) )*? )
        \)
        ) ~xi',
    // Check three arguments exactly, of any form, with a group by argument.
    3 => '~ ( (?: \$|\b) %s) (
        (?!
            \(
            (?:
                (?: null | \d+ | \'.*?\' | ".*?" | \[.*?\] | (?: \$|\b) [a-z_]\w*\s* (?: \-\>[a-z_]\w*\s* | \(.*?\) )*? )
                \s*,\s*
            ) {3}
            (?: null | \d+ | \'.*\' | ".*" | \[.*\] | (?: \$|\b) [a-z_]\w*\s* (?: \-\>[a-z_]\w*\s* | \(.*\) )* )
            \)
        )
        (?= \(.+, .+\))
        \(
            ( null | \d+ | \'.*?\' | ".*?" | \[.*?\] | (?: \$|\b) [a-z_]\w*\s* (?: \-\>[a-z_]\w*\s* | \(.*?\) )*? )
            \s*\,\s*
            (?!
                (?:
                    (?: null | \d+ | \'.*?\' | ".*?" | \[.*?\] | (?: \$|\b) [a-z_]\w*\s* (?: \-\>[a-z_]\w*\s* | \(.*?\) )*? )
                    \s*,\s*
                ) {2}
                (?: null | \d+ | \'.*\' | ".*" | \[.*\] | (?: \$|\b) [a-z_]\w*\s* (?: \-\>[a-z_]\w*\s* | \(.*\) )* )
            )
            ( null | \d+ | \'.*?\' | ".*?" | \[.*?\] | (?: \$|\b) [a-z_]\w*\s* (?: \-\>[a-z_]\w*\s* | \(.*?\) )*? )
            \s*\,\s*
            (?!
                (?: null | \d+ | \'.*?\' | ".*?" | \[.*?\] | (?: \$|\b) [a-z_]\w*\s* (?: \-\>[a-z_]\w*\s* | \(.*?\) )*? )
                \s*,\s*
                (?: null | \d+ | \'.*\' | ".*" | \[.*\] | (?: \$|\b) [a-z_]\w*\s* (?: \-\>[a-z_]\w*\s* | \(.*\) )* )
            )
            ( null | \d+ | \'.*?\' | ".*?" | \[.*?\] | (?: \$|\b) [a-z_]\w*\s* (?: \-\>[a-z_]\w*\s* | \(.*?\) )*? )
        \)
        ) ~xi',
);

return array(
    // Non standard functions or functions used by old themes.
    '~\bdisplay_random_featured_item\(\)~'              => 'random_featured_items(1)',
    '~\bdisplay_random_featured_collection\(\)~'        => 'random_featured_collection()',
    '~\bdisplay_files_for_item\(\)~'                    => 'files_for_item()',

    '~\bset_items_for_loop\(recent_items\(~'            => 'set_loop_records(\'items\', get_recent_items(',
    '~\bset_items_for_loop\(~'                          => 'set_loop_records(\'items\', ',
    '~\bset_collections_for_loop\(~'                    => 'set_loop_records(\'item_sets\', ',
    '~\bset_files_for_loop\(~'                          => 'set_loop_records(\'medias\', ',
    '~\bhas_items_for_loop\(\)~'                        => 'has_loop_records(\'items\')',
    '~\bhas_collections_for_loop\(\)~'                  => 'has_loop_records(\'item_sets\')',
    '~\bhas_files_for_loop\(\)~'                        => 'has_loop_records(\'medias\')',
    '~\bloop_items\(\)~'                                => 'loop($items)',
    '~\bloop_collections\(\)~'                          => 'loop($itemSets)',
    '~\bloop_files\(\)~'                                => 'loop($medias)',
    '~\b(loop_files_for_item)\(\)~'                     => '$this->upgrade()->fallback(\'\1\', 5)',
    '~\b(loop_files_for_item)\((.+?)\)~'                => '$this->upgrade()->fallback(\'\1\', \2)',
    '~\b(loop_items_in_collection)\(\)~'                => '$this->upgrade()->fallback(\'\1\', 5)',
    '~\b(loop_items_in_collection)\((.+?)\)~'           => '$this->upgrade()->fallback(\'\1\', \2)',

    '~\bitem_has_thumbnail\(\)~'                        => 'metadata($item, \'has_thumbnail\')',
    '~\bitem_square_thumbnail\(\)~'                     => 'item_image(\'square\', array(), 0, $item)',
    '~\bitem_thumbnail\(\)~'                            => 'item_image(\'medium\', array(), 0, $item)',
    '~\buri\(~'                                         => 'url(',
    sprintf($regexFunctionArguments[1], 'item')         => 'metadata($item, \3)',
    sprintf($regexFunctionArguments[2], 'item')         => 'metadata($item, array(\3, \4))',
    sprintf($regexFunctionArguments[3], 'item')         => 'metadata($item, array(\3, \4), \5)',
    sprintf($regexFunctionArguments[1], 'collection')   => 'metadata($itemSet, \3)',
    sprintf($regexFunctionArguments[2], 'collection')   => 'metadata($itemSet, array(\3, \4))',
    sprintf($regexFunctionArguments[3], 'collection')   => 'metadata($itemSet, array(\3, \4), \5)',
    sprintf($regexFunctionArguments[1], 'file')         => 'metadata($media, \3)',
    sprintf($regexFunctionArguments[2], 'file')         => 'metadata($media, array(\3, \4))',
    sprintf($regexFunctionArguments[3], 'file')         => 'metadata($media, array(\3, \4), \5)',
    '~\b(nls2p)\((.*?)\)~'                              => 'implode(\'</p><p>\', explode(PHP_EOL, \2))',

    '~\bplugin_append_to_items_show\(\)~'               => 'fire_plugin_hook(\'public_items_show\', array(\'view\' => $this, \'item\' => $item))',
    '~\bplugin_append_to_collections_show\(\)~'         => 'fire_plugin_hook(\'public_collections_show\', array(\'view\' => $this, \'itemSet\' => $itemSet))',
    '~\bplugin_append_to_files_show\(\)~'               => 'fire_plugin_hook(\'public_files_show\', array(\'view\' => $this, \'media\' => $media))',
    '~\bplugin_append_to_items_browse\(\)~'             => 'fire_plugin_hook(\'public_items_browse\', array(\'view\' => $this))',
    '~\bplugin_append_to_items_browse_each\(\)~'        => 'fire_plugin_hook(\'public_items_browse_each\', array(\'view\' => $this, \'item\' => $item))',
    '~\bplugin_append_to_collections_browse\(\)~'       => 'fire_plugin_hook(\'public_collections_browse\', array(\'view\' => $this))',
    '~\bplugin_append_to_collections_browse_each\(\)~'  => 'fire_plugin_hook(\'public_collections_browse_each\', array(\'view\' => $this, \'itemSet\' => $itemSet))',
    '~\bplugin_header\(\)~'                             => 'fire_plugin_hook(\'public_head\', array(\'view\' => $this))',
    '~\bplugin_page_header\(\)~'                        => 'fire_plugin_hook(\'public_header\', array(\'view\' => $this))',
    '~\bsimple_search\(\)~'                             => 'fire_plugin_hook(\'public_header\', array(\'view\' => $this))',
    '~\bplugin_body\(\)~'                               => 'fire_plugin_hook(\'public_body\', array(\'view\' => $this))',
    '~\bplugin_page_content\(\)~'                       => 'fire_plugin_hook(\'public_content_top\', array(\'view\' => $this))',
    '~\bplugin_footer\(\)~'                             => 'fire_plugin_hook(\'public_footer\', array(\'view\' => $this))',

    '~\bsettings\((.+)\)~'                              => 'get_theme_option(\1)',
    '~\bqueue_css\((.+)\)~'                             => 'echo queue_css_file(\1)',
    '~\bqueue_js\((.+)\)~'                              => 'echo queue_js_file(\1)',
    '~\bdisplay_css\(\)~'                               => 'echo head_css()',
    '~\bdisplay_js\(\)~'                                => 'echo head_js()',
    '~\bsimple_search\(\)~'                             => 'search_form()',
    '~\b(link_to_advanced_search)\(\)~'                 => '$this->upgrade()->fallback(\'\1\')',
    '~\bcustom_display_logo\(\)~'                       => 'theme_logo()',
    '~\bcustom_public_nav_header\(\)~'                  => 'public_nav_main()',
    '~\bcustom_nav_items\(\)~'                          => 'public_nav_items()',
    '~\bcustom_header_image\(\)~'                       => 'theme_header_image()',
    '~\bcustom_header_background\(\)~'                  => 'theme_header_background()',

    '~\blink_to_previous_item\(\)~'                     => 'link_to_previous_item_show()',
    '~\blink_to_next_item\(\)~'                         => 'link_to_next_item_show()',
    '~\blink_to_browse_items\(\)~'                      => 'link_to_items_browse($this->translate(\'Browse Items\'))',
    '~\blink_to_browse_items\(~'                        => 'link_to_items_browse(',
    '~\bitem_belongs_to_collection\(\)~'                => '(boolean) count($item->itemSets())',
    '~\bitem_citation\(\)~'                             => '$this->upgrade()->getCitation($item)',
    '~\bitem_has_tags\(\)~'                             => '!empty($item->tags) /* Unmanaged in Omeka S. */',
    '~\bitem_has_type\(\)~'                             => '!empty($item->resourceClass())',
    '~\b(item_tags_as_string)\(\)~'                     => '$this->upgrade()->fallback(\'\1\')',
    '~\bcollection_has_collectors\(\)~'                 => '!empty($itemSet->value(\'dcterms:contributor\'))',

    '~^\s*head\(~'                                      => 'echo head(',
    '~^\s*foot\(~'                                      => 'echo foot(',
    '~' . preg_quote('<?php head(') . '~'               => '<?php echo head(',
    '~' . preg_quote('<?php foot(') . '~'               => '<?php echo foot(',

    // Fix default themes.
    // Neatscape.
    '~' . preg_quote('(array(\'class\'=>\'show\'))') . '~' => 'array(\'class\' => \'show\')',

    // These regex fix the record passed as a string in various places, and update the names.
    '~\b(metadata|all_element_texts|record_image|item_type_elements|link_to|record_url)\((?:\$|\'|")item(?:\'|")~i'                    => '\1($item',
    '~\b(metadata|all_element_texts|record_image|item_type_elements|link_to|record_url)\((?:\$|\'|")collection(?:\'|")~i'              => '\1($itemSet',
    '~\b(metadata|all_element_texts|record_image|item_type_elements|link_to|record_url)\((?:\$|\'|")file(?:\'|")~i'                    => '\1($media',
    '~\b(metadata|all_element_texts|record_image|item_type_elements|link_to|record_url)\((?:\$|\'|")exhibit(?:\'|")~i'                 => '\1($site',
    '~\b(metadata|all_element_texts|record_image|item_type_elements|link_to|record_url)\((?:\$|\'|")exhibit_?page(?:\'|")~i'           => '\1($page',
    '~\b(metadata|all_element_texts|record_image|item_type_elements|link_to|record_url)\((?:\$|\'|")simple_?pages?_?page(?:\'|")~i'    => '\1($page',
    '~\b(metadata|all_element_texts|record_image|item_type_elements|link_to|record_url)\((?:\$|\'|")simple_?page(?:\'|")~i'            => '\1($page',
    '~\b(metadata|all_element_texts|record_image|item_type_elements|link_to|record_url)\((?:\$|\'|")record(?:\'|")~i'                  => '\1($resource',
    '~\b(metadata|all_element_texts|record_image|item_type_elements|link_to|record_url)\((?:\$|\'|")(\w+)(?:\'|")~i'                   => '\1($\2',

    // Update names of records.
    '~\$record\b~'                                  => '$resource',
    '~\$records\b~'                                 => '$resources',
    '~\$recordType\b~'                              => '$resourceName',
    '~\$recordTypes\b~'                             => '$resourceNames',
    // '~\$item\b~'                                    => '$item',
    // '~\$items\b~'                                   => '$items',
    '~\$collection\b~'                              => '$itemSet',
    '~\$collections\b~'                             => '$itemSets',
    '~\$file\b~'                                    => '$media',
    '~\$files\b~'                                   => '$medias',
    '~\$item_type\b~'                               => '$resourceClass',
    '~\$item_types\b~'                              => '$resourceClasses',
    '~\$exhibit\b~'                                 => '$site',
    '~\$exhibits\b~'                                => '$sites',
    '~\$exhibit_page\b~'                            => '$page',
    '~\$exhibit_pages\b~'                           => '$pages',
    '~\$simple_pages_page\b~'                       => '$page',
    '~\$simple_page_page\b~'                        => '$page',
    '~\$simple_page\b~'                             => '$page',
    '~\$simple_pages_pages\b~'                      => '$pages',
    '~\$simple_page_pages\b~'                       => '$pages',
    '~\$simple_pages\b~'                            => '$pages',

    '~\$this\-\>record\b~'                          => '$resource',
    '~\$this\-\>records\b~'                         => '$resources',
    '~\$this\-\>item\b~'                            => '$item',
    '~\$this\-\>items\b~'                           => '$items',
    '~\$this\-\>collection\b~'                      => '$itemSet',
    '~\$this\-\>collections\b~'                     => '$itemSets',
    '~\$this\-\>file\b~'                            => '$media',
    '~\$this\-\>files\b~'                           => '$medias',
    '~\$this\-\>item_type\b~'                       => '$resourceClass',
    '~\$this\-\>item_types\b~'                      => '$resourceClasses',
    '~\$this\-\>exhibit\b~'                         => '$site',
    '~\$this\-\>exhibits\b~'                        => '$sites',
    '~\$this\-\>exhibit_page\b~'                    => '$page',
    '~\$this\-\>exhibit_pages\b~'                   => '$pages',
    '~\$this\-\>simple_pages_page\b~'               => '$page',
    '~\$this\-\>simple_page_page\b~'                => '$page',
    '~\$this\-\>simple_page\b~'                     => '$page',
    '~\$this\-\>simple_pages_pages\b~'              => '$pages',
    '~\$this\-\>simple_page_pages\b~'               => '$pages',
    '~\$this\-\>simple_pages\b~'                    => '$pages',

    // List of all functions of "globals.php" of Omeka 2 to methods of Omekas S.
    '~\b(get_option)\(~'                            => '$this->setting(',
    '~\b(set_option)\(~'                            => '$this->upgrade()->\1(',
    '~\b(delete_option)\(~'                         => '$this->upgrade()->\1(',
    '~\b(pluck)\(~'                                 => '$this->upgrade()->\1(',
    '~\b(current_user)\(~'                          => '$this->upgrade()->\1(',
    '~\b(get_db)\(~'                                => '$this->upgrade()->\1(',
    '~\b(debug)\(~'                                 => '$this->upgrade()->\1(',
    '~\b(_log)\(~'                                  => '$this->upgrade()->\1(',
    '~\b(add_plugin_hook)\(~'                       => '$this->upgrade()->\1(',
    '~\b(fire_plugin_hook)\(~'                      => '$this->upgrade()->\1(',
    '~\b(get_plugin_hook_output)\(~'                => '$this->upgrade()->\1(',
    '~\b(get_specific_plugin_hook_output)\(~'       => '$this->upgrade()->\1(',
    '~\b(get_plugin_broker)\(~'                     => '$this->upgrade()->\1(',
    '~\b(get_plugin_ini)\(~'                        => '$this->upgrade()->\1(',
    '~\b(add_file_display_callback)\(~'             => '$this->upgrade()->\1(',
    '~\b(add_file_fallback_image)\(~'               => '$this->upgrade()->\1(',
    '~\b(apply_filters)\(~'                         => '$this->upgrade()->\1(',
    '~\b(add_filter)\(~'                            => '$this->upgrade()->\1(',
    '~\b(clear_filters)\(~'                         => '$this->upgrade()->\1(',
    '~\b(get_acl)\(~'                               => '$this->upgrade()->\1(',
    '~\b(is_admin_theme)\(~'                        => '$this->upgrade()->\1(',
    '~\b(get_search_record_types)\(~'               => '$this->upgrade()->\1(',
    '~\b(get_custom_search_record_types)\(~'        => '$this->upgrade()->\1(',
    '~\b(get_search_query_types)\(~'                => '$this->upgrade()->\1(',
    '~\b(insert_item)\(~'                           => '$this->upgrade()->\1(',
    '~\b(insert_files_for_item)\(~'                 => '$this->upgrade()->\1(',
    '~\b(update_item)\(~'                           => '$this->upgrade()->\1(',
    '~\b(update_collection)\(~'                     => '$this->upgrade()->\1(',
    '~\b(insert_item_type)\(~'                      => '$this->upgrade()->\1(',
    '~\b(insert_collection)\(~'                     => '$this->upgrade()->\1(',
    '~\b(insert_element_set)\(~'                    => '$this->upgrade()->\1(',
    '~\b(release_object)\(~'                        => '$this->upgrade()->\1(',
    '~\b(get_theme_option)\(~'                      => '$this->upgrade()->\1(',
    '~\b(set_theme_option)\(~'                      => '$this->upgrade()->\1(',
    '~\b(get_user_roles)\(~'                        => '$this->upgrade()->\1(',
    '~\b(element_exists)\(~'                        => '$this->upgrade()->\1(',
    '~\b(plugin_is_active)\(~'                      => '$this->upgrade()->\1(',
    sprintf($regexFunctionArguments['basic'], '__') => '$this->translate(\3)',
    sprintf($regexFunctionArguments[1], '__')       => '$this->translate(\3)',
    sprintf($regexFunctionArguments[2], '__')       => '$this->translate(new Omeka\Stdlib\Message\2)',
    sprintf($regexFunctionArguments[3], '__')       => '$this->translate(new Omeka\Stdlib\Message\2)',
    '~\b(__)(\(.+?\))\;~'                           => '$this->translate(new Omeka\Stdlib\Message\2);',
    '~\b(__)\(~'                                    => '$this->upgrade()->stranslate(',
    '~\b(plural)\(~'                                => '$this->upgrade()->\1(',
    '~\b(add_translation_source)\(~'                => '$this->upgrade()->\1(',
    '~\b(get_html_lang)\(~'                         => '$this->upgrade()->\1(',
    '~\b(format_date)\(~'                           => '$this->upgrade()->\1(',
    '~\b(queue_js_file)\(~'                         => '$this->upgrade()->\1(',
    '~\b(queue_js_url)\(~'                          => '$this->upgrade()->\1(',
    '~\b(queue_js_string)\(~'                       => '$this->upgrade()->\1(',
    '~\b(queue_css_file)\(~'                        => '$this->upgrade()->\1(',
    '~\b(queue_css_url)\(~'                         => '$this->upgrade()->\1(',
    '~\b(queue_css_string)\(~'                      => '$this->upgrade()->\1(',
    '~\b(head_js)\(~'                               => '$this->upgrade()->\1(',
    '~\b(head_css)\(~'                              => '$this->upgrade()->\1(',
    '~\b(css_src)\(~'                               => '$this->upgrade()->\1(',
    '~\b(img)\(~'                                   => '$this->upgrade()->\1(',
    '~\b(js_tag)\(~'                                => '$this->upgrade()->\1(',
    '~\b(src)\(~'                                   => '$this->upgrade()->\1(',
    '~\b(physical_path_to)\(~'                      => '$this->upgrade()->\1(',
    '~\b(web_path_to)\(~'                           => '$this->upgrade()->\1(',
    '~\b(random_featured_collection)\(~'            => '$this->upgrade()->\1(',

    // These regex fix the skipped record.
    '~\bget_collection_for_item\(\)~'               => 'reset($item->itemSets()) /* TODO Manage multiple item sets. */ ',
    '~\bget_collection_for_item\((.+?)\)~'          => 'reset(\1->itemSets()) /* TODO Manage multiple item sets. */ ',
    '~\b(get_collection_for_item)\(~'               => '$this->upgrade()->\1 /* TODO Manage multiple item sets. */ (',

    '~\b(get_recent_collections)\(~'                => '$this->upgrade()->\1(',
    '~\b(get_random_featured_collection)\(~'        => '$this->upgrade()->\1(',
    '~\b(latest_omeka_version)\(~'                  => '$this->upgrade()->\1(',
    '~\b(max_file_size)\(~'                         => '$this->uploadLimit(',
    '~\b(file_markup)\(~'                           => '$this->upgrade()->\1 /* TODO Replace by $media->render(). */ (',
    '~\b(file_id3_metadata)\(~'                     => '$this->upgrade()->\1(',
    '~\b(get_recent_files)\(~'                      => '$this->upgrade()->\1(',
    '~\b(tag_attributes)\(~'                        => '$this->upgrade()->\1(',
    '~\b(search_form)\(~'                           => '$this->upgrade()->\1(',
    '~\b(search_filters)\(~'                        => '$this->searchFilters(',
    '~\b(element_form)\(~'                          => '$this->upgrade()->\1(',
    '~\b(element_set_form)\(~'                      => '$this->upgrade()->\1(',
    '~\b(label_table_options)\(~'                   => '$this->upgrade()->\1(',
    '~\b(get_table_options)\(~'                     => '$this->upgrade()->\1(',
    '~\b(get_view)\(\)~'                            => '$this', // Only in themes, not in plugins.
    '~\b(get_view)\(~'                              => '$this->upgrade()->\1(',
    '~\b(auto_discovery_link_tags)\(~'              => '$this->upgrade()->\1(',
    '~\b(common)\(~'                                => '$this->upgrade()->\1(',
    '~\b(head)\(~'                                  => '$this->upgrade()->\1(',
    '~\b(foot)\(~'                                  => '$this->upgrade()->\1(',
    '~\b(flash)\(~'                                 => '$this->messages(',
    '~\b(option)\(~'                                => '$this->upgrade()->\1(',
    '~\b(get_records)\(~'                           => '$this->upgrade()->\1(',
    '~\b(get_record)\(~'                            => '$this->upgrade()->\1(',
    '~\b(total_records)\(~'                         => '$this->upgrade()->\1(',

    // These regex replace the loop by the default array.
    '~\bloop\((?:\'|")items?(?:\'|")\)~i'                   => '$items',
    '~\bloop\((?:\'|")collections?(?:\'|")\)~i'             => '$itemSets',
    '~\bloop\((?:\'|")files?(?:\'|")\)~i'                   => '$medias',
    '~\bloop\((?:\'|")exhibits?(?:\'|")\)~i'                => '$sites',
    '~\bloop\((?:\'|")exhibit_?pages?(?:\'|")\)~i'          => '$pages',
    '~\bloop\((?:\'|")simple_?pages?(?:\'|")\)~i'           => '$pages',
    '~\bloop\((?:\'|")simple_?pages?_?pages?(?:\'|")\)~i'   => '$pages',
    '~\bloop\((?:\'|")tags?(?:\'|")\)~i'                    => '$tags',
    '~\bloop\((?:\'|")([a-z_]\w*?)(?:\'|")\)~i'             => '$\1',
    '~\b(loop)\(~'                                  => '$this->upgrade()->\1 /* TODO Replace by the variable directly. */ (',
    '~\b(set_loop_records)\(~'                      => '$this->upgrade()->\1 /* TODO Replace by the variable directly. */ (',
    '~\b(get_loop_records)\(~'                      => '$this->upgrade()->\1 /* TODO Replace by the variable directly. */ (',
    '~\b(has_loop_records)\(~'                      => '$this->upgrade()->\1 /* TODO Replace by the variable directly. */ (',
    '~\b(set_current_record)\(~'                    => '$this->upgrade()->\1 /* TODO Replace by the variable directly. */ (',
    '~\b(get_current_record)\(~'                    => '$this->upgrade()->\1 /* TODO Replace by the variable directly. */ (',

    '~\b(get_record_by_id)\(~'                      => '$this->upgrade()->\1(',
    '~\b(get_current_action_contexts)\(~'           => '$this->upgrade()->\1(',
    '~\b(output_format_list)\(~'                    => '$this->upgrade()->\1(',
    '~\b(browse_sort_links)\(~'                     => '$this->upgrade()->\1(',
    '~\b(body_tag)\(~'                              => '$this->upgrade()->\1(',
    '~\b(item_search_filters)\(~'                   => '$this->searchFilters(',

    // For all records.
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*(?:\'|")id(?:\'|")\s*\)~i'                           => '\1->id()',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*(?:\'|")name(?:\'|")\s*\)~i'                         => '\1->name()',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*(?:\'|")display(?: |_)title(?:\'|")\s*\)~i'          => '\1->displayTitle()',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*(?:\'|")title(?:\'|")\s*\)~i'                        => '\1->title()',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*(?:\'|")description(?:\'|")\s*\)~i'                  => '\1->description()',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*(?:\'|")added(?:\'|")\s*\)~i'                        => '\1->created()',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*(?:\'|")modified(?:\'|")\s*\)~i'                     => '\1->modified()',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*(?:\'|")featured(?:\'|")\s*\)~i'                     => 'isset(\1->isFeatured /* Unmanaged in Omeka S. */ ? \1->isFeatured : null',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*(?:\'|")public(?:\'|")\s*\)~i'                       => '\1->isPublic()',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*(?:\'|")owner(?: |_)id(?:\'|")\s*\)~i'               => '\1->owner()',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*(?:\'|")permalink(?:\'|")\s*\)~i'                    => '\1->url(null, true)',

    // For items.
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*(?:\'|")collection(?: |_)id(?:\'|")\s*\)~i'          => '\1->itemSets() /* TODO Manage multiple item sets. */ ? key(\1->itemSets()) : null',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*(?:\'|")collection(?: |_)name(?:\'|")\s*\)~i'        => '\1->itemSets() /* TODO Manage multiple item sets. */ ? (\1->itemSets()[key(\1->itemSets())])->displayTitle() : null',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*(?:\'|")item(?: |_)type(?: |_)id(?:\'|")\s*\)~i'     => '\1->resourceClass() ? \1->resourceClass()->localName() : null',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*(?:\'|")item(?: |_)type(?: |_)name(?:\'|")\s*\)~i'   => '\1->resourceClass() ? \1->resourceClass()->label() : null',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*(?:\'|")has(?: |_)files(?:\'|")\s*\)~i'              => '(boolean) count(\1->media())',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*(?:\'|")has(?: |_)tags(?:\'|")\s*\)~i'               => '!empty(\1->tags) /* Unmanaged in Omeka S. */',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*(?:\'|")file(?: |_)count(?:\'|")\s*\)~i'             => 'count(\1->media())',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*(?:\'|")has(?: |_)thumbnail(?:\'|")\s*\)~i'          => '\1->primaryMedia() ? \1->primaryMedia()->hasThumbnails() : false',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*(?:\'|")citation(?:\'|")\s*\)~i'                     => '$this->upgrade()->getCitation(\1)',

    // For collections.
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*(?:\'|")total(?: |_)items(?:\'|")\s*\)~i'            => '\1->itemCount()',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*(?:\'|")collectors(?:\'|")\s*\)~i'                   => '\1->value(\'dcterms:contributor\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*(?:\'|")collectors(?:\'|"),(.+)\)~i'                 => 'metadata(\1, array(\'Dublin Core\', \'Contributor\'), \2)',

    // For files.
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*(?:\'|")uri(?:\'|")\s*\)~i'                          => '\1->originalUrl()',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*(?:\'|")fullsize(?: |_)uri(?:\'|")\s*\)~i'           => '\1->thumbnailUrl(\'large\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*(?:\'|")thumbnail(?: |_)uri(?:\'|")\s*\)~i'          => '\1->thumbnailUrl(\'medium\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*(?:\'|")square(?: |_)thumbnail(?: |_)uri(?:\'|")\s*\)~i' => '\1->thumbnailUrl(\'square\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*(?:\'|")item(?: |_)id(?:\'|")\s*\)~i'                => '\1->item()->id()',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*(?:\'|")order(?:\'|")\s*\)~i'                        => 'isset(\1->position) /* Managed in Omeka S via sql only. */ ? \1->position : null',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*(?:\'|")size(?:\'|")\s*\)~i'                         => '$this->upgrade()->mediaFilesize(\1) /* Unmanaged in Omeka S. */',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*(?:\'|")has(?: |_)derivative(?: |_)image(?:\'|")\s*\)~i' => '\1->hasThumbnails()',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*(?:\'|")authentication(?:\'|")\s*\)~i'               => '\1->sha256()',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*(?:\'|")mime(?: |_)type(?:\'|")\s*\)~i'              => '\1->mediaType()',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*(?:\'|")type(?: |_)os(?:\'|")\s*\)~i'                => '\1->mediaData() /* Type OS is not managed in Omeka S. */',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*(?:\'|")filename(?:\'|")\s*\)~i'                     => '\1->storage_id() . (\1->extension() ? \'.\' . \1->extension() : \'\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*(?:\'|")original(?: |_)filename(?:\'|")\s*\)~i'      => '\1->source()',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*(?:\'|")metadata(?:\'|")\s*\)~i'                     => '\1->mediaData()',

    // For exhibits.
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*(?:\'|")slug(?:\'|")\s*\)~i'                         => '\1->slug()',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*(?:\'|")credits(?:\'|")\s*\)~i'                      => '\1->owner()->name() /* TODO Replace credits(). */',

    // This is probably an array with an element set name and an element set, or
    // with multilines and/or options, or with variables, or unknown.
    // So convert the element to term. Only the Dublin Core is set.
    // Dublin Core 1.0.
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Title(?:\'|")\s*\)\)~i'                    => '\1->value(\'dcterms:title\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Subject(?:\'|")\s*\)\)~i'                  => '\1->value(\'dcterms:subject\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Description(?:\'|")\s*\)\)~i'              => '\1->value(\'dcterms:description\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Creator(?:\'|")\s*\)\)~i'                  => '\1->value(\'dcterms:creator\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Source(?:\'|")\s*\)\)~i'                   => '\1->value(\'dcterms:source\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Publisher(?:\'|")\s*\)\)~i'                => '\1->value(\'dcterms:publisher\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Date(?:\'|")\s*\)\)~i'                     => '\1->value(\'dcterms:date\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Contributor(?:\'|")\s*\)\)~i'              => '\1->value(\'dcterms:contributor\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Rights(?:\'|")\s*\)\)~i'                   => '\1->value(\'dcterms:rights\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Relation(?:\'|")\s*\)\)~i'                 => '\1->value(\'dcterms:relation\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Format(?:\'|")\s*\)\)~i'                   => '\1->value(\'dcterms:format\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Language(?:\'|")\s*\)\)~i'                 => '\1->value(\'dcterms:language\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Type(?:\'|")\s*\)\)~i'                     => '\1->value(\'dcterms:type\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Identifier(?:\'|")\s*\)\)~i'               => '\1->value(\'dcterms:identifier\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Coverage(?:\'|")\s*\)\)~i'                 => '\1->value(\'dcterms:coverage\')',
    // Dublin Core Terms.
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Audience(?:\'|")\s*\)\)~i'                 => '\1->value(\'dcterms:audience\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Alternative Title(?:\'|")\s*\)\)~i'        => '\1->value(\'dcterms:alternative\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Table Of Contents(?:\'|")\s*\)\)~i'        => '\1->value(\'dcterms:tableOfContents\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Abstract(?:\'|")\s*\)\)~i'                 => '\1->value(\'dcterms:abstract\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Date Created(?:\'|")\s*\)\)~i'             => '\1->value(\'dcterms:created\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Date Valid(?:\'|")\s*\)\)~i'               => '\1->value(\'dcterms:valid\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Date Available(?:\'|")\s*\)\)~i'           => '\1->value(\'dcterms:available\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Date Issued(?:\'|")\s*\)\)~i'              => '\1->value(\'dcterms:issued\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Date Modified(?:\'|")\s*\)\)~i'            => '\1->value(\'dcterms:modified\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Extent(?:\'|")\s*\)\)~i'                   => '\1->value(\'dcterms:extent\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Medium(?:\'|")\s*\)\)~i'                   => '\1->value(\'dcterms:medium\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Is Version Of(?:\'|")\s*\)\)~i'            => '\1->value(\'dcterms:isVersionOf\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Has Version(?:\'|")\s*\)\)~i'              => '\1->value(\'dcterms:hasVersion\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Is Replaced By(?:\'|")\s*\)\)~i'           => '\1->value(\'dcterms:isReplacedBy\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Replaces(?:\'|")\s*\)\)~i'                 => '\1->value(\'dcterms:replaces\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Is Required By(?:\'|")\s*\)\)~i'           => '\1->value(\'dcterms:isRequiredBy\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Requires(?:\'|")\s*\)\)~i'                 => '\1->value(\'dcterms:requires\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Is Part Of(?:\'|")\s*\)\)~i'               => '\1->value(\'dcterms:isPartOf\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Has Part(?:\'|")\s*\)\)~i'                 => '\1->value(\'dcterms:hasPart\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Is Referenced By(?:\'|")\s*\)\)~i'         => '\1->value(\'dcterms:isReferencedBy\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")References(?:\'|")\s*\)\)~i'               => '\1->value(\'dcterms:references\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Is Format Of(?:\'|")\s*\)\)~i'             => '\1->value(\'dcterms:isFormatOf\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Has Format(?:\'|")\s*\)\)~i'               => '\1->value(\'dcterms:hasFormat\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Conforms To(?:\'|")\s*\)\)~i'              => '\1->value(\'dcterms:conformsTo\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Spatial Coverage(?:\'|")\s*\)\)~i'         => '\1->value(\'dcterms:spatial\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Temporal Coverage(?:\'|")\s*\)\)~i'        => '\1->value(\'dcterms:temporal\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Mediator(?:\'|")\s*\)\)~i'                 => '\1->value(\'dcterms:mediator\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Date Accepted(?:\'|")\s*\)\)~i'            => '\1->value(\'dcterms:dateAccepted\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Date Copyrighted(?:\'|")\s*\)\)~i'         => '\1->value(\'dcterms:dateCopyrighted\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Date Submitted(?:\'|")\s*\)\)~i'           => '\1->value(\'dcterms:dateSubmitted\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Audience Education Level(?:\'|")\s*\)\)~i' => '\1->value(\'dcterms:educationLevel\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Access Rights(?:\'|")\s*\)\)~i'            => '\1->value(\'dcterms:accessRights\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Bibliographic Citation(?:\'|")\s*\)\)~i'   => '\1->value(\'dcterms:bibliographicCitation\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")License(?:\'|")\s*\)\)~i'                  => '\1->value(\'dcterms:license\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Rights Holder(?:\'|")\s*\)\)~i'            => '\1->value(\'dcterms:rightsHolder\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Provenance(?:\'|")\s*\)\)~i'               => '\1->value(\'dcterms:provenance\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Instructional Method(?:\'|")\s*\)\)~i'     => '\1->value(\'dcterms:instructionalMethod\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Accrual Method(?:\'|")\s*\)\)~i'           => '\1->value(\'dcterms:accrualMethod\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Accrual Periodicity(?:\'|")\s*\)\)~i'      => '\1->value(\'dcterms:accrualPeriodicity\')',
    '~\bmetadata\((\$[a-z_]\w*)\s*\,\s*array\((?:\'|")Dublin Core(?:\'|")\s*\,\s*(?:\'|")Accrual Policy(?:\'|")\s*\)\)~i'           => '\1->value(\'dcterms:accrualPolicy\')',

    // Something else.
    '~\b(metadata)\((\$[a-z_]\w*)\b~i'              => '$this->upgrade()->\1 /* TODO Use \2->value(). */ (\2',

    '~\b(all_element_texts)\(~'                     => '$this->upgrade()->\1 /* TODO Replace by $resource->displayValues(). */ (',

    // These regex fix the skipped item in files_for_item().
    '~\b(files_for_item)\(~'                                                    => '$this->upgrade()->\1(',
    '~\b(files_for_item)\(\)~'                                                  => '\1([], [\'class\' => \'item-file\'], $item)',
    sprintf($regexFunctionArguments[1], 'files_for_item')                       => '\1(\3, [\'class\' => \'item-file\'], $item)',
    sprintf($regexFunctionArguments[2], 'files_for_item')                       => '\1(\3, \4, $item)',

    // These regex fix the skipped item in get_next_item().
    '~\b(get_next_item)\(~'                         => '$this->upgrade()->\1(',
    '~\b(get_next_item)\(\)~'                       => '\1($item)',

    // These regex fix the skipped item in get_previous_item().
    '~\b(get_previous_item)\(~'                     => '$this->upgrade()->\1(',
    '~\b(get_previous_item)\(\)~'                   => '\1($item)',

    '~\b(record_image)\(~'                          => '$this->upgrade()->\1(',

    // These regex fix the skipped item in item_image().
    '~\b(item_image)\(~'                                                        => '$this->upgrade()->\1(',
    '~\b(item_image)\(\)~'                                                      => '\1(null, [], 0, $item)',
    sprintf($regexFunctionArguments[1], 'item_image')                           => '\1(\3, [], 0, $item)',
    sprintf($regexFunctionArguments[2], 'item_image')                           => '\1(\3, \4, 0, $item)',
    sprintf($regexFunctionArguments[3], 'item_image')                           => '\1(\3, \4, \5, $item)',

    // These regex fix the skipped file in file_image().
    '~\b(file_image)\(~'                                                        => '$this->upgrade()->\1(',
    '~\b(file_image)\(\)~'                                                      => '\1(\'square\', [], $media)',
    sprintf($regexFunctionArguments[1], 'file_image')                           => '\1(\3, [], $media)',
    sprintf($regexFunctionArguments[2], 'file_image')                           => '\1(\3, \4, $media)',

    // These regex fix the skipped item in item_image_gallery().
    '~\b(item_image_gallery)\(~'                                                => '$this->upgrade()->\1(',
    '~\b(item_image_gallery)\(\)~'                                              => '\1([], \'square\', false, $item)',
    sprintf($regexFunctionArguments[1], 'item_image_gallery')                   => '\1(\3, \'square\', false, $item)',
    sprintf($regexFunctionArguments[2], 'item_image_gallery')                   => '\1(\3, \4, false, $item)',
    sprintf($regexFunctionArguments[3], 'item_image_gallery')                   => '\1(\3, \4, \5, $item)',

    '~\b(items_search_form)\(~'                     => '$this->upgrade()->\1(',
    '~\b(get_recent_items)\(~'                      => '$this->upgrade()->\1(',
    '~\b(get_random_featured_items)\(~'             => '$this->upgrade()->\1(',
    '~\b(recent_items)\(~'                          => '$this->upgrade()->\1(',
    '~\b(random_featured_items)\(~'                 => '$this->upgrade()->\1(',

    // These regex fix the skipped item in item_type_elements().
    '~\b(item_type_elements)\(\)~'                  => '\1($item)',
    '~\b(item_type_elements)\(~'                    => '$this->upgrade()->\1(',

    '~\b(link_to)\(~'                               => '$this->upgrade()->\1(',
    '~\b(link_to_item_search)\(~'                   => '$this->upgrade()->\1(',
    '~\b(link_to_items_browse)\(~'                  => '$this->upgrade()->\1(',
    // NOTE No item passed.
    '~\b(link_to_collection_for_item)\(~'           => '$this->upgrade()->\1 /* TODO No item passed */ (',

    // These regex fix the skipped collection in link_to_items_in_collection().
    '~\b(link_to_items_in_collection)\(~'                                       => '$this->upgrade()->\1(',
    '~\b(link_to_items_in_collection)\(\)~'                                     => '\1(null, [], \'browse\', $itemSet)',
    sprintf($regexFunctionArguments[1], 'link_to_items_in_collection')          => '\1(\3, [], \'browse\', $itemSet)',
    sprintf($regexFunctionArguments[2], 'link_to_items_in_collection')          => '\1(\3, \4, \'browse\', $itemSet)',
    sprintf($regexFunctionArguments[3], 'link_to_items_in_collection')          => '\1(\3, \4, \5, $itemSet)',

    // These regex fix the skipped item type in link_to_items_with_item_type().
    '~\b(link_to_items_with_item_type)\(~'                                      => '$this->upgrade()->\1(',
    '~\b(link_to_items_with_item_type)\(\)~'                                    => '\1(null, [], \'browse\', $resourceClass)',
    sprintf($regexFunctionArguments[1], 'link_to_items_with_item_type')         => '\1(\3, [], \'browse\', $resourceClass)',
    sprintf($regexFunctionArguments[2], 'link_to_items_with_item_type')         => '\1(\3, \4, \'browse\', $resourceClass)',
    sprintf($regexFunctionArguments[3], 'link_to_items_with_item_type')         => '\1(\3, \4, \5, $resourceClass)',

    // These regex fix the skipped file in link_to_file_show().
    '~\b(link_to_file_show)\(~'                                                 => '$this->upgrade()->\1(',
    '~\b(link_to_file_show)\(\)~'                                               => '\1([], null, $media)',
    sprintf($regexFunctionArguments[1], 'link_to_file_show')                    => '\1(\3, null, $media)',
    sprintf($regexFunctionArguments[2], 'link_to_file_show')                    => '\1(\3, \4, $media)',

    // These regex fix the skipped item in link_to_item().
    '~\b(link_to_item)\(~'                                                      => '$this->upgrade()->\1(',
    '~\b(link_to_item)\(\)~'                                                    => '\1(null, [], \'show\', $item)',
    sprintf($regexFunctionArguments[1], 'link_to_item')                         => '\1(\3, [], \'show\', $item)',
    sprintf($regexFunctionArguments[2], 'link_to_item')                         => '\1(\3, \4, \'show\', $item)',
    sprintf($regexFunctionArguments[3], 'link_to_item')                         => '\1(\3, \4, \5, $item)',

    '~\b(link_to_items_rss)\(~'                     => '$this->upgrade()->\1(',

    // NOTE No item passed.
    '~\b(link_to_next_item_show)\s*\(~'             => '$this->upgrade()->\1(',
    '~\b(link_to_previous_item_show)\s*\(~'         => '$this->upgrade()->\1(',

    '~\b(bootstrap_browse_sort_links)\s*\(~'        => '$this->upgrade()->\1(',
    '~\b(' . preg_quote('public_nav_items()->setUiClass') . ')\(~'  => '$this->upgrade()->public_nav_items([], 0, ',

    // These regex fix the skipped item in link_to_collection().
    '~\b(link_to_collection)\(~'                                                => '$this->upgrade()->\1(',
    '~\b(link_to_collection)\(\)~'                                              => '\1(null, [], \'show\', $itemSet)',
    sprintf($regexFunctionArguments[1], 'link_to_collection')                   => '\1(\3, [], \'show\', $itemSet)',
    sprintf($regexFunctionArguments[2], 'link_to_collection')                   => '\1(\3, \4, \'show\', $itemSet)',
    sprintf($regexFunctionArguments[3], 'link_to_collection')                   => '\1(\3, \4, \5, $itemSet)',

    '~\b(link_to_home_page)\(~'                     => '$this->upgrade()->\1(',
    '~\b(link_to_admin_home_page)\(~'               => '$this->upgrade()->\1(',

    '~\b(nav)\(~'                                   => '$this->upgrade()->\1(',
    '~\bpagination_links\(\)~'                      => '$this->pagination()',
    '~\bpagination_links\(array\(\)\)~'             => '$this->pagination()',
    '~\b(pagination_links)\(~'                      => '$this->upgrade()->\1 /* TODO Replace array by list of args: @see pagination(). */ (',
    '~\b(public_nav_main)\(~'                       => '$this->upgrade()->\1(',
    '~\b(public_nav_items)\(~'                      => '$this->upgrade()->\1(',
    '~\b(html_escape)\(~'                           => '$this->escapeHtml /* TODO the plugin must be loaded. */ (',
    '~\b(js_escape)\(~'                             => '$this->escapeJs /* TODO the plugin must be loaded. */ (',
    '~\b(xml_escape)\(~'                            => '$this->upgrade()->\1(',
    '~\b(text_to_paragraphs)\(~'                    => '$this->upgrade()->\1(',
    '~\b(snippet)\(~'                               => '$this->upgrade()->\1(',
    '~\b(snippet_by_word_count)\(~'                 => '$this->upgrade()->\1(',
    '~\b(strip_formatting)\(~'                      => '$this->upgrade()->\1(',
    '~\b(text_to_id)\(~'                            => '$this->upgrade()->\1(',
    '~\b(url_to_link)\(~'                           => '$this->upgrade()->\1(',
    '~\b(url_to_link_callback)\(~'                  => '$this->upgrade()->\1(',
    '~\b(get_recent_tags)\(~'                       => '$this->upgrade()->\1(',
    '~\b(tag_cloud)\(~'                             => '$this->upgrade()->\1(',
    '~\b(tag_string)\(~'                            => '$this->upgrade()->\1(',

    // List of common urls.
    '~\burl\(\)~'                                   => '$this->basePath()',
    '~\b(url)\(~'                                   => '$this->upgrade()->\1(',
    '~(' . preg_quote('$this->upgrade()->url') . ')\((?:\'|")items(?:\'|")\)~'                    => '\1(\'item\')',
    '~(' . preg_quote('$this->upgrade()->url') . ')\((?:\'|")items/browse(?:\'|")\)~'             => '\1(\'item\')',
    '~(' . preg_quote('$this->upgrade()->url') . ')\((?:\'|")items/show/(\d+)(?:\'|")\)~'         => '\1(\'item/\2\')',
    '~(' . preg_quote('$this->upgrade()->url') . ')\((?:\'|")collections(?:\'|")\)~'              => '\1(\'item-set\')',
    '~(' . preg_quote('$this->upgrade()->url') . ')\((?:\'|")collections/browse(?:\'|")\)~'       => '\1(\'item-set\')',
    '~(' . preg_quote('$this->upgrade()->url') . ')\((?:\'|")collections/show/(\d+)(?:\'|")\)~'   => '\1(\'item-set/\2\')',
    '~(' . preg_quote('$this->upgrade()->url') . ')\((?:\'|")files(?:\'|")\)~'                    => '\1(\'media\')',
    '~(' . preg_quote('$this->upgrade()->url') . ')\((?:\'|")files/browse(?:\'|")\)~'             => '\1(\'media\')',
    '~(' . preg_quote('$this->upgrade()->url') . ')\((?:\'|")files/show/(\d+)(?:\'|")\)~'         => '\1(\'media/\2\')',
    '~(' . preg_quote('$this->upgrade()->url') . ')\((?:\'|")items/search(?:\'|")\)~'             => '\1(\'item/search\')',
    '~(' . preg_quote('$this->upgrade()->url') . ')\((?:\'|")search(?:\'|")\)~'                   => '\1(\'item/search\')',

    '~\b(absolute_url)\(~'                          => '$this->upgrade()->\1(',
    '~\b(current_url)\(~'                           => '$this->upgrade()->\1(',
    '~\b(is_current_url)\(~'                        => '$this->upgrade()->\1(',
    '~\b(record_url)\(~'                            => '$this->upgrade()->\1(',
    '~\b(items_output_url)\(~'                      => '$this->upgrade()->\1(',
    '~\b(file_display_url)\(~'                      => '$this->upgrade()->\1(',
    '~\b(public_url)\(~'                            => '$this->upgrade()->\1(',
    '~\b(admin_url)\(~'                             => '$this->upgrade()->\1(',
    '~\b(set_theme_base_url)\(~'                    => '$this->upgrade()->\1(',
    '~\b(revert_theme_base_url)\(~'                 => '$this->upgrade()->\1(',
    '~\b(theme_logo)\(~'                            => '$this->upgrade()->\1(',
    '~\b(theme_header_image)\(~'                    => '$this->upgrade()->\1(',
    '~\b(theme_header_background)\(~'               => '$this->upgrade()->\1(',
    '~\b(is_allowed)\(~'                            => '$this->userIsAllowed(',
    '~\b(add_shortcode)\(~'                         => '$this->upgrade()->\1(',

    // Inflector.
    '~' . preg_quote('$this->view->pluralize(') . '~'   => '\Doctrine\Common\Inflector\Inflector::pluralize(',
    '~' . preg_quote('$this->view->singularize(') . '~' => '\Doctrine\Common\Inflector\Inflector::singularize(',
    '~\b' . preg_quote('Inflector::pluralize(') . '~'   => '\Doctrine\Common\Inflector\Inflector::pluralize(',
    '~\b' . preg_quote('Inflector::singularize(') . '~' => '\Doctrine\Common\Inflector\Inflector::singularize(',
    '~\b' . preg_quote('Inflector::camelize(') . '~'    => '\Doctrine\Common\Inflector\Inflector::camelize(',
    '~\b' . preg_quote('Inflector::tableize(') . '~'    => '\Doctrine\Common\Inflector\Inflector::tableize(',
    '~\b' . preg_quote('Inflector::classify(') . '~'    => '\Doctrine\Common\Inflector\Inflector::classify(',
    '~\b' . preg_quote('Inflector::titleize(') . '~'    => '\UpgradeFromOmekaClassic\View\Helper\Inflector::titleize(',
    '~\b' . preg_quote('Inflector::underscore(') . '~'  => '\UpgradeFromOmekaClassic\View\Helper\Inflector::underscore(',
    '~\b' . preg_quote('Inflector::humanize(') . '~'    => '\UpgradeFromOmekaClassic\View\Helper\Inflector::humanize(',
    '~\b' . preg_quote('Inflector::variablize(') . '~'  => '\UpgradeFromOmekaClassic\View\Helper\Inflector::variablize(',
    '~\b' . preg_quote('Inflector::ordinalize(') . '~'  => '\UpgradeFromOmekaClassic\View\Helper\Inflector::ordinalize(',

    // Zend_Date really used in plugins and common themes.
    // Only formats commonly used in Omeka or simple are converted.
    // Locale is not managed. See Zend_Date().
    '~\b' . preg_quote('Zend_Date::ATOM') . '\b~'           => '\\DateTime::ATOM',
    '~\b' . preg_quote('Zend_Date::COOKIE') . '\b~'         => '\\DateTime::COOKIE',
    '~\b' . preg_quote('Zend_Date::ISO_8601') . '\b~'       => '\\DateTime::ISO8601',
    '~\b' . preg_quote('Zend_Date::RFC_822') . '\b~'        => '\\DateTime::RFC822',
    '~\b' . preg_quote('Zend_Date::RFC_850') . '\b~'        => '\\DATETIME::RFC850',
    '~\b' . preg_quote('Zend_Date::RFC_1036') . '\b~'       => '\\DATETIME::RFC1036',
    '~\b' . preg_quote('Zend_Date::RFC_1123') . '\b~'       => '\\DATETIME::RFC1123',
    '~\b' . preg_quote('Zend_Date::RFC_2822') . '\b~'       => '\\DATETIME::RFC2822',
    '~\b' . preg_quote('Zend_Date::RFC_3339') . '\b~'       => '\\DATETIME::RFC3339',
    '~\b' . preg_quote('Zend_Date::RSS') . '\b~'            => '\\DATETIME::RSS',
    '~\b' . preg_quote('Zend_Date::W3C') . '\b~'            => '\\DATETIME::W3C',
    '~\b' . preg_quote('Zend_Date::DAY') . '\b~'            => "'d'",
    '~\b' . preg_quote('Zend_Date::DAY_SHORT') . '\b~'      => "'j'",
    '~\b' . preg_quote('Zend_Date::WEEKDAY_8601') . '\b~'   => "'N'",
    '~\b' . preg_quote('Zend_Date::DAY_SUFFIX ') . '\b~'    => "'S'",
    '~\b' . preg_quote('Zend_Date::WEEKDAY_DIGIT') . '\b~'  => "'w'",
    '~\b' . preg_quote('Zend_Date::DAY_OF_YEAR') . '\b~'    => "'z'",
    '~\b' . preg_quote('Zend_Date::WEEK') . '\b~'           => "'W'",
    '~\b' . preg_quote('Zend_Date::MONTH') . '\b~'          => "'m'",
    '~\b' . preg_quote('Zend_Date::MONTH_SHORT') . '\b~'    => "'n'",
    '~\b' . preg_quote('Zend_Date::MONTH_DAYS') . '\b~'     => "'t'",
    '~\b' . preg_quote('Zend_Date::LEAPYEAR') . '\b~'       => "'L'",
    '~\b' . preg_quote('Zend_Date::YEAR_8601') . '\b~'      => "'o'",
    '~\b' . preg_quote('Zend_Date::YEAR') . '\b~'           => "'Y'",
    '~\b' . preg_quote('Zend_Date::YEAR_SHORT') . '\b~'     => "'y'",
    '~\b' . preg_quote('Zend_Date::SWATCH') . '\b~'         => "'B'",
    '~\b' . preg_quote('Zend_Date::HOUR_SHORT_AM') . '\b~'  => "'g'",
    '~\b' . preg_quote('Zend_Date::HOUR_SHORT') . '\b~'     => "'G'",
    '~\b' . preg_quote('Zend_Date::HOUR_AM') . '\b~'        => "'h'",
    '~\b' . preg_quote('Zend_Date::HOUR') . '\b~'           => "'H'",
    '~\b' . preg_quote('Zend_Date::MINUTE_SHORT') . '\b~'   => "'i'",
    '~\b' . preg_quote('Zend_Date::SECOND_SHORT') . '\b~'   => "'s'",
    '~\b' . preg_quote('Zend_Date::TIMEZONE_NAME') . '\b~'  => "'e'",
    '~\b' . preg_quote('Zend_Date::DAYLIGHT') . '\b~'       => "'I'",
    '~\b' . preg_quote('Zend_Date::GMT_DIFF') . '\b~'       => "'O'",
    '~\b' . preg_quote('Zend_Date::GMT_DIFF_SEP') . '\b~'   => "'P'",
    '~\b' . preg_quote('Zend_Date::TIMEZONE') . '\b~'       => "'T'",
    '~\b' . preg_quote('Zend_Date::TIMEZONE_SECS') . '\b~'  => "'Z'",
    '~\b' . preg_quote('Zend_Date::TIMESTAMP') . '\b~'      => "'U'",
    // The next formats are genericized.
    '~\b' . preg_quote('Zend_Date::DATE_FULL') . '\b~'      => "'l j F Y'",
    '~\b' . preg_quote('Zend_Date::DATE_LONG') . '\b~'      => "'j F Y'",
    '~\b' . preg_quote('Zend_Date::DATE_MEDIUM') . '\b~'    => "'j M Y'",
    '~\b' . preg_quote('Zend_Date::DATE_SHORT') . '\b~'     => "'Y/m/d'",
    '~\b' . preg_quote('Zend_Date::TIME_FULL') . '\b~'      => "'G:i:s e'",
    '~\b' . preg_quote('Zend_Date::TIME_LONG') . '\b~'      => "'G:i:s T'",
    '~\b' . preg_quote('Zend_Date::TIME_MEDIUM') . '\b~'    => "'G:i:s'",
    '~\b' . preg_quote('Zend_Date::TIME_SHORT') . '\b~'     => "'G:i'",
    '~\b' . preg_quote('Zend_Date::DATETIME_FULL') . '\b~'  => "'l j F Y \a\\t G:i:s e'",
    '~\b' . preg_quote('Zend_Date::DATETIME_LONG') . '\b~'  => "'j F Y \a\\t G:i:s T'",
    '~\b' . preg_quote('Zend_Date::DATETIME_MEDIUM') . '\b~' => "'j M Y, G:i:s'",
    '~\b' . preg_quote('Zend_Date::DATETIME_SHORT') . '\b~' => "'Y/m/d, G:i'",

    // Functions from the fork of Omeka.
    '~' . preg_quote('useInternalAssets()') . '~'    => '$this->upgrade()->useInternalAssets()',

    // Other functions.
    '~' . preg_quote('$this->shortcodes(') . '~'    => '$this->upgrade()->fallback(\'shortcodes\', ',
    '~\bgetFiles\(\)~'                              => 'media()',

    // Some fixes.
    '~' . preg_quote('$this->$this->') . '~'                => '$this->',
    '~' . preg_quote('$this->upgrade()->$this->') . '~'     => '$this->upgrade()->',
    '~\b' . preg_quote('upgrade()->upgrade()->') . '~'      => 'upgrade()->',
    '~\b' . preg_quote('echo echo') . '\b~'                 => 'echo',

    '~\<\?php\s*?\>~'                               => '',

    '~' . preg_quote('$this->partial(') . '\'(.*?)\.php\'~'                 => '$this->partial(\'\1.phtml\'',
    '~' . preg_quote('$this->partial(') . '\'items/(.*?)\'~'                => '$this->partial(\'item/\1\'',
    '~' . preg_quote('$this->partial(') . '\'collections/(.*?)\'~'          => '$this->partial(\'item-set/\1\'',
    '~' . preg_quote('$this->partial(') . '\'files/(.*?)\'~'                => '$this->partial(\'media/\1\'',
    '~' . preg_quote('$this->partial(') . '\'item/search\-form.phtml\'~'    => '$this->partial(\'common/advanced-search.phtml\'',

    // Various strings.
    '~\'favicon.ico\'~'                             => '\'img/favicon.ico\'',

    '~($[a-z_]\w*?)\-\>getCollection\(\)\b~i'       => '\1->itemSets() /* TODO Warning: Multiple collections. */',
    '~\>hasThumbnail\(~'                            => '>hasThumbnails(',
    '~\>getExtension\(~'                            => '>extension(',

    '~(\$[a-z]\w+)\-\>name\b(?!\(\))~i'             => '\1->name()',
    '~(\$[a-z]\w+)\-\>id\b(?!\(\))~i'               => '\1->id()',
    '~' . preg_quote('$itemSet->name()') . '~'      => '$itemSet->value(\'dcterms:title\')',
    '~' . preg_quote('collection(\'id\')') . '~'    => '$itemSet->id()',

    '~(\'public_items_browse_each\')~'              => '\1 /* Unmanaged in Omeka S. */',
    '~(\'public_collections_browse_each\')~'        => '\1 /* Unmanaged in Omeka S. */',

    '~\$current_collection~'                        => '$itemSet',
    '~(\$[a-z]\w+)\-\>hasContributor\(\)~'          => '(boolean) \1->value(\'dcterms:contributor\')',

    '~(\$[a-z_]\w*)\s*\-\>\s*Files\b~'              => '\1->media()',
    '~(\$[a-z_]\w*)\s*\-\>\s*Items\b~'              => '\1->items()',
    '~(\$[a-z_]\w*)\s*\-\>\s*Tags\b~'               => 'null /* tags are not managed \1->tags() */',

    '~WEB_VIEW_SCRIPTS~'                            => '$this->assetUrl(\'\')',
    // The web root may be converted into current site root or the main root.
    '~WEB_ROOT~'                                    => '$this->url(\'site\', array(\'site-slug\' => $site->slug()))',
    '~WEB_ROOT~'                                    => '$this->serverUrl() . $this->basePath()',

    '~(?:\'|")imageSize(?:\'|")\s*\=\>\s*(?:\'|")original(?:\'|")~i'            => "'imageSize' => 'original'",
    '~(?:\'|")imageSize(?:\'|")\s*\=\>\s*(?:\'|")fullsize(?:\'|")~i'            => "'imageSize' => 'large'",
    '~(?:\'|")imageSize(?:\'|")\s*\=\>\s*(?:\'|")thumbnail(?:\'|")~i'           => "'imageSize' => 'medium'",
    '~(?:\'|")imageSize(?:\'|")\s*\=\>\s*(?:\'|")square_thumbnail(?:\'|")~i'    => "'imageSize' => 'square'",

    // Fixes common double nested for metadata. To be cleaned.
    '~metadata(.*?)\((\$[a-z]\w+),\s*(array\(.*?\)),\s*(\'(?:bodyid|bodyclass)\'.*?)\)\)~i'                                                 => 'metadata\1(\2, \3), \4)',
    '~metadata(.*?)\((\$[a-z]\w+),\s*(array\(.*?\)),\s*(\'bodyid\'.*?)\),\s*(\'bodyclass\'.*?\))~i'                                         => 'metadata\1(\2, \3, \4))',
    '~metadata(.*?)\((\$[a-z]\w+),\s*(array\(.*?\)),\s*array\((\'(?:class)\'.*?\))\)(.*?)\)~i'                                              => 'metadata\1(\2, \3), array(\4\5)',
    '~metadata(.*?)\((\$[a-z]\w+),\s*(array\(.*?\))\),\s*(array\(\'(?:all|delimiter|index|no_escape|no_filter|snippet)\'.*?\)\))(.*?)\)~i'  => 'metadata\1(\2, \3, \4\5)',

    // Functions of plugins.
    '~\b(exhibit_builder_display_random_featured_exhibit)\(\)~' => '$this->upgrade()->fallback(\1)',
    '~neatline(?:-|_)time~'                             => 'timeline',
    '~Neatline_Time_Timeline~'                          => 'timeline',

    // Functions of themes.
    '~\b(custom_show_item_metadata)\(\)~'               => '$this->upgrade()->fallback(\'\1\')',
    '~\b(rhythm_display_date_added)\(\)~'               => '$this->upgrade()->fallback(\'\1\')',
    '~\b(santaFe_flash)\(\)~'                           => '$this->messages()',
    '~\b(custom_files_for_item)\(\)~'               => '$this->upgrade()->fallback(\'\1\')',

    '~if\s*' . preg_quote('($this->upgrade()->has_loop_records') . '.*?\(\'items\'\)\)\s*\:~'                       => '$items = $this->upgrade()->get_loop_records(\'items\', false); if ($items):',
    '~if\s*' . preg_quote('($this->upgrade()->has_loop_records') . '.*?\(\'(?:collections|item_sets)\'\)\)\s*\:~'   => '$itemSets = $this->upgrade()->get_loop_records(\'itemSets\', false); if ($itemSets):',
    '~if\s*' . preg_quote('($this->upgrade()->has_loop_records') . '.*?\(\'files\'\)\)\s*\:~'                       => '$medias = $this->upgrade()->get_loop_records(\'medias\', false); if ($medias):',
);
