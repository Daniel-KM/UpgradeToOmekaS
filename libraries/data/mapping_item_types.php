<?php

/**
 * Mapping of item types from Omeka C to classes of Omeka S. It can be adapted
 * and completed.
 *
 * @internal This file is merged during the init of processors of the plugins.
 */
return array(
    // Copied from Omeka2Importer.
    // See https://github.com/omeka-s-modules/Omeka2Importer/blob/develop/src/Controller/item_type_maps.php
    //
    // The mapping for "Hyperlink" to "bibo:Webpage" has been added.
    'Text'                  => array('dctype' => 'Text'),
    'Moving Image'          => array('dctype' => 'MovingImage'),
    'Oral History'          => array('bibo' => 'AudioDocument'),
    'Sound'                 => array('dctype' => 'Sound'),
    'Still Image'           => array('dctype' => 'StillImage'),
    'Website'               => array('bibo' => 'Website'),
    'Event'                 => array('dctype' => 'Event'),
    'Email'                 => array('bibo' => 'Email'),
    'Lesson Plan'           => array('bibo' => 'Workshop'),
    'Hyperlink'             => array('bibo' => 'Webpage'),
    'Person'                => array('foaf' => 'Person'),
    'Interactive Resource'  => array('dctype' => 'InteractiveResource'),
    'Dataset'               => array('dctype' => 'Dataset'),
    'Physical Object'       => array('dctype' => 'PhysicalObject'),
    'Service'               => array('dctype' => 'Service'),
    'Software'              => array('dctype' => 'Software'),
);
