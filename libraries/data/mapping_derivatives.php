<?php

/**
 * Mapping of files from Omeka C to Omeka S. It can be adapted and completed.
 *
 * @internal This file is merged during the init of processors of the plugins.
 */
return array(
    // Omeka C type, Omeka C filepath and Omeka S filepath.
    // The filepath for Omeka S should be the same than in the config.
    'original' => array('original' => 'original'),
    'fullsize' => array('fullsize' => 'large'),
    'thumbnail' => array('thumbnails' => 'medium'),
    'square_thumbnail' => array('square_thumbnails' => 'square'),
);
