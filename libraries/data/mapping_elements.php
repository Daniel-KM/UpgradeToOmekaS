<?php

/**
 * Mapping of elements from Omeka C to properties of Omeka S. It can be adapted
 * and completed.
 *
 * @internal This file is merged during the init of processors of the plugins.
 * Warning: the merge is not recursive.
 */
return array(
    'Dublin Core' => array(
        'Title'                 => array('dcterms' => 'title'),
        'Creator'               => array('dcterms' => 'creator'),
        'Subject'               => array('dcterms' => 'subject'),
        'Description'           => array('dcterms' => 'description'),
        'Publisher'             => array('dcterms' => 'publisher'),
        'Contributor'           => array('dcterms' => 'contributor'),
        'Date'                  => array('dcterms' => 'date'),
        'Type'                  => array('dcterms' => 'type'),
        'Format'                => array('dcterms' => 'format'),
        'Identifier'            => array('dcterms' => 'identifier'),
        'Source'                => array('dcterms' => 'source'),
        'Language'              => array('dcterms' => 'language'),
        'Relation'              => array('dcterms' => 'relation'),
        'Coverage'              => array('dcterms' => 'coverage'),
        'Rights'                => array('dcterms' => 'rights'),
    ),

    // Copied from Omeka2Importer.
    // See https://github.com/omeka-s-modules/Omeka2Importer/blob/develop/src/Controller/item_type_maps.php
    'Item Type Metadata' => array(
        'Text' => array(),
        'Interviewer'           => array('bibo' => 'interviewer'),
        'Interviewee'           => array('bibo' => 'interviewee'),
        'Location'              => array(),
        'Transcription'         => array(),
        'Local URL'             => array(),
        'Original Format'       => array('dcterms' => 'format'),
        'Physical Dimensions'   => array('dcterms' => 'extent'),
        'Duration'              => array('dcterms' => 'extent'),
        'Compression'           => array(),
        'Producer'              => array('bibo' => 'producer'),
        'Director'              => array('bibo' => 'director'),
        'Bit Rate/Frequency'    => array(),
        'Time Summary'          => array(),
        'Email Body'            => array(),
        'Subject Line'          => array(),
        'From'                  => array('bibo' => 'producer'),
        'To'                    => array('bibo' => 'recipient'),
        'CC'                    => array('bibo' => 'recipient'),
        'BCC'                   => array(),
        'Number of Attachments' => array(),
        'Standards'             => array(),
        'Objectives'            => array(),
        'Materials'             => array(),
        'Lesson Plan Text'      => array(),
        'URL'                   => array(),
        'Event Type'            => array(),
        'Participants'          => array(),
        'Birth Date'            => array(),
        'Birthplace'            => array(),
        'Death Date'            => array(),
        'Occupation'            => array(),
        'Biographical Text'     => array(),
        'Bibliography'          => array(),
    ),
);
