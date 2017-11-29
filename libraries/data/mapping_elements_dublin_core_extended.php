<?php

/**
 * Mapping of elements from Omeka C to properties of Omeka S. It can be adapted
 * and completed.
 *
 * This file contains all terms used by the plugin Dublin Core Extended.
 *
 * @internal This file is merged during the init of processors of the plugins.
 * Warning: the merge is not recursive.
 * So, the set "Dublin core" will overwrite the original one of Omeka C. Of
 * course, other sets remain unchanged.
 */
return array(
    // Dublin Core properties and property refinements. See DCMI Metadata Terms:
    // http://dublincore.org/documents/dcmi-terms/
    // Order comes from http://dublincore.org/schemas/xmls/qdc/dcterms.xsd,
    // but extended terms are set just after the generic element they refine.
    'Dublin Core' => array(
        'Title'                     => array('dcterms' => 'title'),
        'Alternative Title'         => array('dcterms' => 'alternative'),
        'Creator'                   => array('dcterms' => 'creator'),
        'Subject'                   => array('dcterms' => 'subject'),
        'Description'               => array('dcterms' => 'description'),
        'Table Of Contents'         => array('dcterms' => 'tableOfContents'),
        'Abstract'                  => array('dcterms' => 'abstract'),
        'Publisher'                 => array('dcterms' => 'publisher'),
        'Contributor'               => array('dcterms' => 'contributor'),
        'Date'                      => array('dcterms' => 'date'),
        'Date Created'              => array('dcterms' => 'created'),
        'Date Valid'                => array('dcterms' => 'valid'),
        'Date Available'            => array('dcterms' => 'available'),
        'Date Issued'               => array('dcterms' => 'issued'),
        'Date Modified'             => array('dcterms' => 'modified'),
        'Date Accepted'             => array('dcterms' => 'dateAccepted'),
        'Date Copyrighted'          => array('dcterms' => 'dateCopyrighted'),
        'Date Submitted'            => array('dcterms' => 'dateSubmitted'),
        'Type'                      => array('dcterms' => 'type'),
        'Format'                    => array('dcterms' => 'format'),
        'Extent'                    => array('dcterms' => 'extent'),
        'Medium'                    => array('dcterms' => 'medium'),
        'Identifier'                => array('dcterms' => 'identifier'),
        'Bibliographic Citation'    => array('dcterms' => 'bibliographicCitation'),
        'Source'                    => array('dcterms' => 'source'),
        'Language'                  => array('dcterms' => 'language'),
        'Relation'                  => array('dcterms' => 'relation'),
        'Is Version Of'             => array('dcterms' => 'isVersionOf'),
        'Has Version'               => array('dcterms' => 'hasVersion'),
        'Is Replaced By'            => array('dcterms' => 'isReplacedBy'),
        'Replaces'                  => array('dcterms' => 'replaces'),
        'Is Required By'            => array('dcterms' => 'isRequiredBy'),
        'Requires'                  => array('dcterms' => 'requires'),
        'Is Part Of'                => array('dcterms' => 'isPartOf'),
        'Has Part'                  => array('dcterms' => 'hasPart'),
        'Is Referenced By'          => array('dcterms' => 'isReferencedBy'),
        'References'                => array('dcterms' => 'references'),
        'Is Format Of'              => array('dcterms' => 'isFormatOf'),
        'Has Format'                => array('dcterms' => 'hasFormat'),
        'Conforms To'               => array('dcterms' => 'conformsTo'),
        'Coverage'                  => array('dcterms' => 'coverage'),
        'Spatial Coverage'          => array('dcterms' => 'spatial'),
        'Temporal Coverage'         => array('dcterms' => 'temporal'),
        'Rights'                    => array('dcterms' => 'rights'),
        'Access Rights'             => array('dcterms' => 'accessRights'),
        'License'                   => array('dcterms' => 'license'),
        'Audience'                  => array('dcterms' => 'audience'),
        'Audience Education Level'  => array('dcterms' => 'educationLevel'),
        'Mediator'                  => array('dcterms' => 'mediator'),
        'Audience Education Level'  => array('dcterms' => 'educationLevel'),
        'Accrual Method'            => array('dcterms' => 'accrualMethod'),
        'Accrual Periodicity'       => array('dcterms' => 'accrualPeriodicity'),
        'Accrual Policy'            => array('dcterms' => 'accrualPolicy'),
        'Instructional Method'      => array('dcterms' => 'instructionalMethod'),
        'Provenance'                => array('dcterms' => 'provenance'),
        'Rights Holder'             => array('dcterms' => 'rightsHolder'),
    ),
);
