<?php

class UpgradeToOmekaS_Processor_GeolocationTest extends UpgradeToOmekaS_Test_AppTestCase
{
    protected $_isAdminTest = true;

    protected $_processorName = 'UpgradeToOmekaS_Processor_Geolocation';

    public function setUp()
    {
        parent::setUp();

        $this->_setupPlugin();

        if (!class_exists('Location')) {
            $this->markTestSkipped(__('The plugin "%s" must be available to test it.',
                $this->_processor->pluginName));
        }
    }

    public function testUpgradeData()
    {
        $processor = new UpgradeToOmekaS_Processor_Geolocation();
        $processor->setParams($this->_defaultParams);
        $target = $processor->getTarget();
        $targetDb = $target->getDb();

        $item = insert_item();

        $location = new Location();
        $location->item_id = $item->id;
        $location->latitude = 48.8583;
        $location->longitude = 2.2944;
        $location->zoom_level = 10;
        $location->map_type = 'hybrid';
        $location->address = 'Tour Eiffel, Paris';
        $location->save();

        $totalRecords = total_records('Location');
        $this->assertEquals(1, $totalRecords);

        $result = $this->invokeMethod($processor, '_prepareModule');

        // The item is not imported (the process was not launched), so this is a
        // false location, so it is not imported.
        $result = $this->invokeMethod($processor, '_upgradeData');
        $result = $target->totalRows('mapping_marker');
        $this->assertEmpty(0, $result);

        $itemId = $this->_createItemViaDb($target);
        $itemId = $this->_createItemViaDb($target);

        $location->save();

        $result = $target->totalRows('item');
        $this->assertEquals(2, $result);

        $result = $this->invokeMethod($processor, '_upgradeData');
        $result = $target->totalRows('mapping_marker');
        $this->assertEquals(1, $result);
        $sql = 'SELECT * FROM mapping_marker;';
        $result = $targetDb->fetchRow($sql);

        $this->assertEquals(array(
            'id' => $location->id,
            'item_id' => $itemId,
            'media_id' => null,
            'lat' => $location->latitude,
            'lng' => $location->longitude,
            'label' => $location->address,
        ), $result);
    }
}
