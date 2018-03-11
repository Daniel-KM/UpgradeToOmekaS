<?php

/**
 * Upgrade Reference to Omeka S.
 *
 * @package UpgradeToOmekaS
 */
class UpgradeToOmekaS_Processor_Reference extends UpgradeToOmekaS_Processor_Abstract
{

    public $pluginName = 'Reference';
    public $minVersion = '2.0';
    public $maxVersion = '';

    public $module = array(
        'name' => 'Reference',
        'version' => null,
        'url' => 'https://github.com/Daniel-KM/Omeka-S-module-Reference/archive/master.zip',
        'size' => null,
        'sha1' => null,
        'type' => 'port',
        'note' => '',
        'install' => array(
            // Copied from the original module.config.php.
            'config' => array(
                'reference_resource_name' => 'items',
                'reference_query_type' => 'eq',
                'reference_link_to_single' => true,
                'reference_total' => true,
                'reference_slugs' => [
                    'dcterms:subject' => [
                        'type' => 'properties',
                        'term' => 3,
                        'label' => 'Subject',
                        'active' => true,
                    ],
                ],
                'reference_list_skiplinks' => true,
                'reference_list_headings' => true,
                'reference_tree_enabled' => false,
                'reference_tree_term' => 'dcterms:subject',
                'reference_tree_hierarchy' => [],
                'reference_tree_branch' => false,
                'reference_tree_expanded' => true,
            ),
        ),
    );

    public $processMethods = array(
        '_installModule',
    );

    protected function _upgradeSettings()
    {
        $target = $this->getTarget();

        // Set default settings, that will be overridden by current Omeka ones.
        foreach ($this->module['install']['config'] as $setting => $value) {
            $target->saveSetting($setting, $value);
        }

        $mapping = [];
        $mapping['properties'] = $this->getProcessor('Core/Elements')
            ->getMappingElementsToPropertiesIds();
        $mapping['resource_classes'] = $this->getProcessor('Core/Elements')
            ->getMappingItemTypesToClasses();

        $mapOptions = array(
            'reference_query_type' => 'reference_query_type',
            'reference_link_to_single' => 'reference_link_to_single',
            'reference_slugs' => 'reference_slugs',
            'reference_list_skiplinks' => 'reference_list_skiplinks',
            'reference_list_headings' => 'reference_list_headings',
            'reference_tree_enabled' => 'reference_tree_enabled',
            'reference_tree_hierarchy' => 'reference_tree_hierarchy',
            'reference_tree_expanded' => 'reference_tree_expanded',
        );
        foreach ($mapOptions as $option => $setting) {
            if (empty($setting)) {
                continue;
            }
            $value = get_option($option);
            // Manage exceptions.
            switch ($option) {
                case 'reference_query_type':
                    $value = $value === 'contains' ? 'in' : 'eq';
                    break;
                case 'reference_slugs':
                    $value = json_decode($value, true);
                    foreach ($value as $slug => &$slugData) {
                        $slugData['type'] = $slugData['type'] === 'ItemType' ? 'resource_classes' : 'properties';
                        if (isset($mapping[$slugData['type']][$slugData['id']])) {
                            $slugData['id'] = (integer) $mapping[$slugData['type']][$slugData['id']];
                        } else {
                            $slugData['id'] = 0;
                            $this->_log('[' . __FUNCTION__ . ']: ' . __('The reference "%s" cannot be upgraded.', $slugData['label']),
                                Zend_Log::WARN);
                        }
                    }
                    break;
            }
            $target->saveSetting($setting, $value);
        }

        $this->_log('[' . __FUNCTION__ . ']: ' . __('The urls are slightly modified, but they can be reverted to the Omeka Classic ones if needed. See readme.'),
            Zend_Log::NOTICE);
    }
}
