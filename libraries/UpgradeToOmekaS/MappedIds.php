<?php

/**
 * Singleton used to store mapped record ids.
 *
 * @internal This class is mainly used to get the map of files, because the ids
 * of item don't change between Omeka Classic and Omeka Semantic.
 */
class UpgradeToOmekaS_MappedIds
{
    /**
     * The reference to the singleton instance of this class.
     *
     * @var UpgradeToOmekaS_MappedIds
     */
    private static $_instance;

    /**
     * Store all mapping ids by record type.
     *
     * @var array
     */
    private $_mappedIds = array();

    /**
     * This class is a singleton.
     */
    static public function init()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new UpgradeToOmekaS_MappedIds();
        }
        return self::$_instance;
    }

    /**
     * Store the mapped ids for a record type.
     *
     * @param string recordType
     * @param array $mappedIds
     * @return void
     */
    public function store($recordType, array $mappedIds)
    {
        if (empty($recordType) || !is_string($recordType)) {
            return;
        }

        $this->_mappedIds[$recordType] = $mappedIds;
    }

    /**
     * Get the mapping ids for a record type or all mappings.
     *
     * @param string recordType
     * @return array
     */
    public function fetch($recordType = null)
    {
        if (empty($recordType)) {
            return $this->_mappedIds;
        }

        if (!isset($this->_mappedIds[$recordType])) {
            return array();
        }

        return $this->_mappedIds[$recordType];
    }
}
