<?php

/**
 * Check, prepare and manage all target database methods.
 *
 * @package UpgradeToOmekaS
 */
class UpgradeToOmekaS_Helper_TargetOmekaS extends UpgradeToOmekaS_Helper_Target
{

    /**
     * Wrapper to set a global setting.
     *
     * @param string $name The name of the value ("id" in the table).
     * @param var $value The value will be json_encoded().
     * @return void
     */
    public function saveSetting($name, $value)
    {
        $this->saveJsonSetting('setting', $name, $value);
    }

    /**
     * Wrapper to set a site setting.
     *
     * @param string $name The name of the value ("id" in the table).
     * @param var $value The value will be json_encoded().
     * @param integer $siteId The main site, or an exhibit.
     * @return void
     */
    public function saveSiteSetting($name, $value, $siteId = 1)
    {
        $this->saveJsonSetting('site_setting', $name, $value, $siteId);
    }

    /**
     * Set a setting in json.
     *
     * @param string $table "setting" or "site_setting"
     * @param string $name The name of the value ("id" in the table).
     * @param var $value The value will be json_encoded().
     * @param integer $siteId The main site, or an exhibit.
     * @return void
     */
    public function saveJsonSetting($table, $name, $value, $siteId = null)
    {
        if (empty($name)) {
            throw new UpgradeToOmekaS_Exception(
                __('The name of the setting is empty.'));
        }
        if (empty($table)) {
            throw new UpgradeToOmekaS_Exception(
                __('The table is not defined.'));
        }
        if (!in_array($table, array('setting', 'site_setting'))) {
            throw new UpgradeToOmekaS_Exception(
                __('The table is not managed.'));
        }
        if ($table == 'site_setting' && empty($siteId)) {
            throw new UpgradeToOmekaS_Exception(
                __('The id of the site is empty.'));
        }

        $value = $this->toJson($value);

        // Check if there is a value.
        $db = $this->getDb();
        $select = $db->select()
            ->from($table)
            ->where('id = ?', $name);
        if ($table == 'site_setting') {
            $select
                ->where('site_id = ?', $siteId);
        }
        $result = $db->fetchRow($select);

        // Update row.
        if ($result) {
            $where = array();
            $where[] = 'id = ' . $db->quote($name);
            if ($table == 'site_setting') {
                $where[] = 'site_id = ' . (integer) $siteId;
            }
            $result = $db->update($table, array('value' => $value), $where);
        }
        // Insert new row.
        else {
            $toInsert = array();
            $toInsert['id'] = $name;
            $toInsert['value'] = $value;
            if ($table == 'site_setting') {
                $toInsert['site_id'] = $siteId;
            }
            $result = $db->insert($table, $toInsert);
        }
    }

    /**
     * Wrapper to get a json_decoded global setting.
     *
     * @param string $name The name of the value ("id" in the table).
     * @return var
     */
    public function selectSetting($name)
    {
        return $this->selectJsonSetting('setting', $name);
    }

    /**
     * Wrapper to get a json_decoded site setting.
     *
     * @param string $name The name of the value ("id" in the table).
     * @param integer $siteId The main site, or an exhibit.
     * @return var
     */
    public function selectSiteSetting($name, $siteId = 1)
    {
        return $this->selectJsonSetting('site_setting', $name, $siteId);
    }

    /**
     * Get a json_decoded setting.
     *
     * @param string $table "setting" or "site_setting"
     * @param string $name The name of the value ("id" in the table).
     * @param integer $siteId The main site, or an exhibit.
     * @return var
     */
    public function selectJsonSetting($table, $name, $siteId = null)
    {
        if (empty($name)) {
            return;
        }
        if (empty($table)) {
            throw new UpgradeToOmekaS_Exception(
                __('The table is not defined.'));
        }
        if (!in_array($table, array('setting', 'site_setting'))) {
            throw new UpgradeToOmekaS_Exception(
                __('The table is not managed.'));
        }
        if ($table == 'site_setting' && empty($siteId)) {
            throw new UpgradeToOmekaS_Exception(
                __('The id of the site is empty.'));
        }

        $db = $this->getDb();
        $select = $db->select()
            ->from($table, array('value'))
            ->where('id = ?', $name);
        if ($table == 'site_setting') {
            $select
                ->where('site_id = ?', $siteId);
        }
        $result = $db->fetchOne($select);
        if ($result) {
            return json_decode($result, true);
        }
    }

    /**
     * Wrapper for json_encode() to get a clean json.
     *
     * @internal The database is Unicode and this is allowed since php 5.4.
     *
     * @param var $value
     * @return string
     */
    public function toJson($value)
    {
        return json_encode(
            $value,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
