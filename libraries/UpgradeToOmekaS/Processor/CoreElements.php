<?php

/**
 * Upgrade Core Elements to Omeka S.
 *
 * @package UpgradeToOmekaS
 */
class UpgradeToOmekaS_Processor_CoreElements extends UpgradeToOmekaS_Processor_Abstract
{
    public $pluginName = 'Core / Elements';
    public $minVersion = '2.3.1';
    public $maxVersion = '2.5';

    public $module = array(
        'type' => 'integrated',
    );

    /**
     * List of methods to process for the upgrade.
     *
     * @var array
     */
    public $processMethods = array(
        '_upgradeItemTypes',
        '_upgradeElements',
    );

    /**
     * Initialized during init via libraries/data/mapping_item_types.php.
     *
     * @var array
     */
    // public $mapping_item_types = array();

    /**
     * Initialized during init via libraries/data/mapping_elements.php.
     *
     * @var array
     */
    // public $mapping_elements = array();

    /**
     * Flat list of classes as an associative array of prefix:names and ids.
     *
     * @var
     */
    protected $_classesIds = array();

    /**
     * Flat list of properties as an associative array of prefix:names and ids.
     *
     * @var
     */
    protected $_propertyIds = array();

    protected function _init()
    {
        $dataDir = dirname(dirname(dirname(dirname(__FILE__))))
            . DIRECTORY_SEPARATOR . 'libraries'
            . DIRECTORY_SEPARATOR . 'data';

        $script = $dataDir
            . DIRECTORY_SEPARATOR . 'mapping_item_types.php';
        $this->mapping_item_types = require $script;

        $script = $dataDir
            . DIRECTORY_SEPARATOR . 'mapping_elements.php';
        $this->mapping_elements = require $script;
    }

    /**
     * Check if the plugin is installed.
     *
     * @internal Always true for the Core.
     *
     * @return boolean
     */
    public function isPluginReady()
    {
        return true;
    }

    protected function _upgradeItemTypes()
    {
        $customItemTypes = $this->_getCustomItemTypes();
        $message = '[' . __FUNCTION__ . ']: '
            . __('In Omeka S, it’s possible to create one or more specific forms for each type of item.')
            . ' ';

        if ($customItemTypes) {
            $list = implode('", "', array_map(function ($v) { return $v->name; }, $customItemTypes));
            $this->_log($message . (count($customItemTypes) <= 1
                    ? __('Like one item type is customized ("%s"), you can check resource templates to recreate it.',
                        $list)
                    : __('Like %s items types are customized ("%s"), you can check resource templates to recreate them.',
                        count($customItemTypes), $list)),
                Zend_Log::INFO);
        }
        // Just an info.
        else {
            $this->_log($message
                . __('So, if you have customized your item types, you can check resource templates.'),
                Zend_Log::INFO);
        }

        $unmappedItemTypes = $this->_getUnmappedItemTypes();
        $message = '[' . __FUNCTION__ . ']: ';
        if ($unmappedItemTypes) {
            $list = implode('", "', array_map(function ($v) {
                return $v->name;
            }, $unmappedItemTypes));
            $this->_log($message . (count($unmappedItemTypes) <= 1
                ? __('One used item type ("%s") is not mapped and won’t be upgraded.',
                    $list)
                : __('%d used item types ("%s") are not mapped and won’t be upgraded.',
                    count($unmappedItemTypes), $list)),
                Zend_Log::WARN);
        }
        // Just an info.
        else {
            $this->_log($message
                . __('All used item types are mapped.'),
                Zend_Log::INFO);
        }

        // TODO Create resource templates for customized item types.
        // TODO The resource templates can contains only the truly used elements.
    }

    protected function _upgradeElements()
    {
        $unmappedElements = $this->_getUnmappedElements();
        $message = '[' . __FUNCTION__ . ']: '
            . __('In Omeka S, it’s not possible currently to create a specific property (element) without a rdf vocabulary or a module.')
            . ' ';

        if ($unmappedElements) {
            $list = implode('", "', array_map(function ($v) {
                $elementSet = $v->getElementSet();
                return ($elementSet ? $elementSet->name : '[' . __('No Element Set') . ']')
                    . ':' . $v->name;
            }, $unmappedElements));
            $this->_log($message . (count($unmappedElements) <= 1
                    ? __('One used element ("%s") is not mapped and won’t be upgraded.',
                        $list)
                    : __('%d used elements ("%s") are not mapped and won’t be upgraded.',
                        count($unmappedElements), $list)),
                Zend_Log::WARN);
        }
        // Just an info.
        else {
            $this->_log($message
                . __('Anyway, all used elements are mapped, so all metadata will be available.'),
            Zend_Log::INFO);
        }

        // TODO Allow to create properties in a custom ontology, or list more than the default ones.
    }

    /**
     * Get an array containing all used item types with total.
     *
     * @return array.
     */
    public function getUsedItemTypes()
    {
        $db = $this->_db;
        $sql = "
        SELECT item_types.`id` AS item_type_id,
            item_types.`name` AS item_type_name,
            COUNT(items.`id`) AS total_items
        FROM {$db->ItemType} item_types
        JOIN {$db->Item} items
            ON items.`item_type_id` = item_types.`id`
        GROUP BY item_types.`id`
        ORDER BY item_types.`name`
        ;";
        $itemTypes = $db->fetchAll($sql);
        return $itemTypes;
    }

    /**
     * Get the customized item types (different from the standard ones).
     *
     * @todo Count the total of customized item types too (changed order, etc.).
     * @todo Check the specific element sets too.
     * @todo Manage the item types of the plugins too.
     *
     * @return array
     */
    protected function _getCustomItemTypes()
    {
        $itemTypes = get_records('ItemType', array(), 0);
        $defaultItemTypes = $this->mapping_item_types;

        $result = array();
        foreach ($itemTypes as $itemType) {
            if (!isset($defaultItemTypes[$itemType->name])) {
                $result[$itemType->id] = $itemType;
            }
        }
        return $result;
    }

    /**
     * Get an array containing all used elements with total by record type.
     *
     * @return array.
     */
    public function getUsedElements()
    {
        $db = $this->_db;
        $sql = "
        SELECT element_sets.`id` AS element_set_id,
            element_sets.`name` AS element_set_name,
            elements.`id` AS element_id,
            elements.`name` AS element_name,
            COUNT(collections.`id`) AS total_collections,
            COUNT(items.`id`) AS total_items,
            COUNT(files.`id`) AS total_files
        FROM {$db->ElementSet} element_sets
        JOIN {$db->Element} elements
            ON elements.`element_set_id` = element_sets.`id`
        JOIN {$db->ElementText} element_texts
            ON element_texts.`element_id` = elements.`id`
        LEFT JOIN {$db->Collection} collections
            ON collections.`id` = element_texts.`record_id`
                AND element_texts.`record_type` = 'Collection'
        LEFT JOIN {$db->Item} items
            ON items.`id` = element_texts.`record_id`
                AND element_texts.`record_type` = 'Item'
        LEFT JOIN {$db->File} files
            ON files.`id` = element_texts.`record_id`
                AND element_texts.`record_type` = 'File'
        GROUP BY elements.`id`
        ORDER BY element_sets.`name`, elements.`name`
        ;";
        $elements = $db->fetchAll($sql);
        return $elements;
    }

    /**
     * Get an array containing all item types by used element.
     *
     * @return array.
     */
    public function getItemTypesByUsedElement()
    {
        $db = $this->_db;
        $sql = "
        SELECT
            elements.`id` AS element_id,
            elements.`name` AS element_name,
            item_types.`id` AS item_type_id,
            item_types.`name` AS item_type_name,
            COUNT(items.`id`) AS total
        FROM {$db->Item} items
        JOIN {$db->ElementText} element_texts
            ON element_texts.`record_id` = items.`id`
                AND element_texts.`record_type` = 'Item'
        JOIN {$db->ItemTypesElement} item_types_elements
            ON item_types_elements.`element_id` = element_texts.`element_id`
        JOIN {$db->ItemType} item_types
            ON item_types_elements.`item_type_id` = item_types.`id`
                AND item_types.`id` = items.`item_type_id`
        JOIN {$db->Element} elements
            ON elements.`id` = element_texts.`element_id`
                AND elements.`id` = item_types_elements.`element_id`
        GROUP BY elements.`id`, item_types.`id`
        ORDER BY elements.`name`, item_types.`name`
        ;";
        $elements = $db->fetchAll($sql);
        $result = array();
        foreach ($elements as $element) {
            $result[$element['element_id']][$element['item_type_name']] = $element;
        }
        return $result;
    }

    /**
     * Get the unmapped item types.
     *
     * @return array
     */
    protected function _getUnmappedItemTypes()
    {
        $mapping = $this->getParam('mapping_item_types');
        $unmapped = array_filter($mapping, function ($v) {
            return empty($v);
        });
        $records = get_records('ItemType', array(), 0);
        foreach ($records as $record) {
            if (isset($unmapped[$record->id])) {
                $unmapped[$record->id] = $record;
            }
        }
        return $unmapped;
    }

    /**
     * Get the unmapped elements.
     *
     * @return array
     */
    protected function _getUnmappedElements()
    {
        $mapping = $this->getParam('mapping_elements');
        $unmapped = array_filter($mapping, function ($v) {
            return empty($v);
        });
        $records = get_records('Element', array(), 0);
        foreach ($records as $record) {
            if (isset($unmapped[$record->id])) {
                $unmapped[$record->id] = $record;
            }
        }
        return $unmapped;
    }

    /**
     * Get the default mapping from Omeka C item types to Omeka S classes.
     *
     * This method doesn't use the database of Omeka S, but the file "classes.php".
     *
     * @param string $itemTypeFormat "name" (default) or "id".
     * @param string $classFormat "prefix:name" (default), "label" or "id".
     * @return array
     */
    public function getDefaultMappingItemTypesToClasses($itemTypeFormat = 'name', $classFormat = 'prefix:name')
    {
        static $itemTypes;
        static $classes;
        static $mapping;

        if (empty($itemTypes)) {
            // Get the flat list of item types truly installed in Omeka.
            $select = $this->_db->getTable('ItemType')->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->from(array(), array(
                'id' => 'item_types.id',
                'name' => 'item_types.name',
            ))
            ->order('item_types.id');
            $itemTypes = $this->_db->fetchAll($select);

            // Get the flat list of classes ids and prefix:name by prefix:name.
            $classes = $this->getClasses();
            $result = array();
            foreach ($classes as $vocabularyLabel => $vocabulary) {
                // Use only the prefix of the vocabulary.
                $prefix = trim(substr($vocabularyLabel, strpos($vocabularyLabel, '[')), '[] ');
                // Preformat the class.
                foreach ($vocabulary as $classId => $class) {
                    $label = reset($class);
                    $name = key($class);
                    $result[$prefix . ':' . $name] = array(
                        'id' => $classId,
                        'name' => $name,
                        'prefix:name' => $prefix . ':' . $name,
                        'label' => $label,
                        'prefix' => $prefix,
                    );
                }
            }
            $classes = $result;

            // Get the mapping of item types with classes as prefix:name.
            $mapping = $this->getMerged('mapping_item_types');
            $mapping = array_map(function ($v) {
                return key($v) . ':' . reset($v);
            } , $mapping);
        }

        // Process the requested format.
        $result = array();
        foreach ($itemTypes as $itemType) {
            $mappedClass = null;
            // Get the map of the element if any.
            if (!empty($mapping[$itemType['name']])) {
                $map = $mapping[$itemType['name']];
                if (isset($classes[$map])) {
                    $mappedClass = $classes[$map][$classFormat];
                }
            }
            // Format the result.
            // Set the mapping, even if not mapped.
            switch ($itemTypeFormat) {
                case 'id':
                    $result[$itemType['id']] = $mappedClass;
                    break;
                case 'name':
                default:
                    $result[$itemType['name']] = $mappedClass;
                    break;
            }
        }

        return $result;
    }

    /**
     * Get the user mapping from Omeka C item types to Omeka S classes.
     *
     * @return array
     */
    public function getMappingItemTypesToClasses()
    {
        $defaultMapping = $this->getDefaultMappingItemTypesToClasses('id', 'id');
        // Convert the mapping params into Omeka S id.
        $mapping = $this->getParam('mapping_item_types') ?: array();
        $classesIds = $this->getClassesIds();
        foreach ($mapping as &$param) {
            $param = isset($classesIds[$param])
                ? $classesIds[$param]
                : null;
        }
        return $mapping + $defaultMapping;
    }

    /**
     * Get the list of classes of all vocabularies of Omeka S.
     *
     * This method doesn't use the database of Omeka S, but the file "classes.php".
     *
     * @todo Add an option to fetch the list from the database when possible.
     *
     * @return array
     */
    public function getClasses()
    {
        $script = dirname(dirname(dirname(dirname(__FILE__))))
            . DIRECTORY_SEPARATOR . 'libraries'
            . DIRECTORY_SEPARATOR . 'data'
            . DIRECTORY_SEPARATOR . 'classes.php';
        $classes = require $script;
        return $classes;
    }

    /**
     * Get flat list of classes of all vocabularies of Omeka S by "prefix:name".
     *
     * @return array
     */
    public function getClassesIds()
    {
        if (empty($this->_classesIds)) {
            $classes = $this->getClasses();
            $result = array();
            foreach ($classes as $vocabularyLabel => $vocabulary) {
                // Use only the prefix of the vocabulary.
                $prefix = trim(substr($vocabularyLabel, strpos($vocabularyLabel, '[')), '[] ');
                // Preformat the class.
                foreach ($vocabulary as $classId => $class) {
                    $label = reset($class);
                    $name = key($class);
                    $result[$prefix . ':' . $name] = $classId;
                }
            }
            $this->_classesIds = $result;
        }
        return $this->_classesIds;
    }

    /**
     * Get the default mapping from Omeka C elements to Omeka S properties.
     *
     * This method doesn't use the database of Omeka S, but the file "properties.php".
     *
     * @param string $elementFormat "set name:name" (default) or "id".
     * @param string $propertyFormat "prefix:name" (default), "label" or "id".
     * @param boolean $bySet Return a one or a two levels associative array.
     * @return array
     */
    public function getDefaultMappingElementsToProperties($elementFormat = 'set_name:name', $propertyFormat = 'prefix:name', $bySet = false)
    {
        static $elements;
        static $properties;
        static $mapping;

        if (empty($elements)) {
            // Get the flat list of elements truly installed in Omeka.
            $select = $this->_db->getTable('Element')->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->from(array(), array(
                'id' => 'elements.id',
                'name' => 'elements.name',
                'set_name' => 'element_sets.name',
            ))
            ->order('elements.id');
            $elements = $this->_db->fetchAll($select);

            // Get the flat list of property ids and prefix:name by prefix:name.
            $properties = $this->getProperties();
            $result = array();
            foreach ($properties as $vocabularyLabel => $vocabulary) {
                // Use only the prefix of the vocabulary.
                $prefix = trim(substr($vocabularyLabel, strpos($vocabularyLabel, '[')), '[] ');
                // Preformat the property.
                foreach ($vocabulary as $propertyId => $property) {
                    $label = reset($property);
                    $name = key($property);
                    $result[$prefix . ':' . $name] = array(
                        'id' => $propertyId,
                        'name' => $name,
                        'prefix:name' => $prefix . ':' . $name,
                        'label' => $label,
                        'prefix' => $prefix,
                    );
                }
            }
            $properties = $result;

            // Get the mapping of elements with properties as prefix:name.
            $mapping = $this->getMerged('mapping_elements');
            foreach ($mapping as $setName => &$mappedElements) {
                $mappedElements = array_map(function ($v) {
                    return key($v) . ':' . reset($v);
                } , $mappedElements);
            }
        }

        // Process the requested format.
        $result = array();
        foreach ($elements as $element) {
            $mappedProperty = null;
            // Get the map of the element if any.
            if (!empty($mapping[$element['set_name']][$element['name']])) {
                $map = $mapping[$element['set_name']][$element['name']];
                if (isset($properties[$map])) {
                    $mappedProperty = $properties[$map][$propertyFormat];
                }
            }
            // Format the result.
            // Set the mapping, even if not mapped.
            if ($bySet) {
                switch ($elementFormat) {
                    case 'id':
                        $result[$element['set_name']][$element['id']] = $mappedProperty;
                        break;
                    case 'set_name:name':
                    default:
                        $result[$element['set_name']][$element['set_name'] . ':' . $element['name']] = $mappedProperty;
                        break;
                }
            } else {
                switch ($elementFormat) {
                    case 'id':
                        $result[$element['id']] = $mappedProperty;
                        break;
                    case 'set_name:name':
                    default:
                        $result[$element['set_name'] . ':' . $element['name']] = $mappedProperty;
                        break;
                }
            }
        }

        return $result;
    }

    /**
     * Get the mapping from Omeka C elements to Omeka S properties.
     *
     * @return array
     */
    public function getMappingElementsToProperties()
    {
        $defaultMapping = $this->getDefaultMappingElementsToProperties('id', 'id', false);
        // Convert the mapping params into Omeka S id.
        $mapping = $this->getParam('mapping_elements') ?: array();
        $propertiesIds = $this->getPropertyIds();
        foreach ($mapping as &$param) {
            $param = isset($propertiesIds[$param])
                ? $propertiesIds[$param]
                : null;
        }
        return $mapping + $defaultMapping;
    }

    /**
     * Get the list of properties of all vocabularies of Omeka S.
     *
     * This method doesn't use the database of Omeka S, but the file "properties.php".
     *
     * @todo Add an option to fetch the list from the database when possible.
     *
     * @return array
     */
    public function getProperties()
    {
        static $properties;

        if (empty($properties)) {
            $script = dirname(dirname(dirname(dirname(__FILE__))))
                . DIRECTORY_SEPARATOR . 'libraries'
                . DIRECTORY_SEPARATOR . 'data'
                . DIRECTORY_SEPARATOR . 'properties.php';
            $properties = require $script;
        }

        return $properties;
    }

    /**
     * Get flat list of properties of vocabularies of Omeka S by 'prefix:name".
     *
     * This method doesn't use the database of Omeka S, but the file "properties.php".
     *
     * @todo Add an option to fetch the list from the database when possible.
     *
     * @return array
     */
    public function getPropertyIds()
    {
        if (empty($this->_propertyIds)) {
            $properties = $this->getProperties();
            $result = array();
            foreach ($properties as $vocabularyLabel => $vocabulary) {
                // Use only the prefix of the vocabulary.
                $prefix = trim(substr($vocabularyLabel, strpos($vocabularyLabel, '[')), '[] ');
                foreach ($vocabulary as $propertyId => $property) {
                    $name = key($property);
                    $result[$prefix . ':' . $name] = $propertyId;
                }
            }
            $this->_propertyIds = $result;
        }
        return $this->_propertyIds;
    }
}
