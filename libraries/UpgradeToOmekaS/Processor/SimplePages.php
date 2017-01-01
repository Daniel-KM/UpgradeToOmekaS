<?php

/**
 * Upgrade Simple Pages to Omeka S.
 *
 * @package UpgradeToOmekaS
 */
class UpgradeToOmekaS_Processor_SimplePages extends UpgradeToOmekaS_Processor_Abstract
{

    public $pluginName = 'SimplePages';
    public $minVersion = '3.0.8';
    public $maxVersion = '3.0.8';

    public $module = array(
        'type' => 'integrated',
    );

    public function convertNavigationPageToLink($page, $args, $site)
    {
        // Check if this is a slug.
        $slug = ltrim($args['path'], '/');
        $simplePage = get_record('SimplePagesPage', array(
            'slug' => $slug,
        ));
        if ($simplePage) {
            return array(
                'type' => 'page',
                'data' => array(
                    'label' => $page['label'],
                    // The ids are kept between Omeka 2 and Omeka S.
                    'id' => $simplePage->id,
            ));
        }
    }
}
