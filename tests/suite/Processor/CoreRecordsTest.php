<?php

class UpgradeToOmekaS_Processor_CoreRecordsTest extends UpgradeToOmekaS_Test_AppTestCase
{
    protected $_isAdminTest = true;

    public function setUp()
    {
        parent::setUp();

        // Authenticate and set the current user.
        $this->user = $this->db->getTable('User')->find(1);
        $this->_authenticateUser($this->user);
    }

    public function testUpgradeItems()
    {
        $this->_checkDownloadedOmekaS();
        $processor = $this->_prepareProcessor(
            'Core / Records',
            array('user' => $this->user),
            array('_unzipOmekaS', '_configOmekaS', '_installOmekaS', '_upgradeUsers',
                '_upgradeItems'));
        $target = $processor->getTarget();
        $targetDb = $target->getDb();

        // There is one item by default.
        $totalRecords = total_records('Item');

        $result = $target->totalRows('item');
        $this->assertEquals($totalRecords, $result);
        $result = $target->totalRows('resource');
        $this->assertEquals($totalRecords, $result);
    }

    public function testCreateItemSetForSite()
    {
        $this->_checkDownloadedOmekaS();
        $processor = $this->_prepareProcessor(
            'Core / Records',
            array('user' => $this->user),
            array('_unzipOmekaS', '_configOmekaS', '_installOmekaS', '_upgradeUsers',
                '_createItemSetForSite'));
        $target = $processor->getTarget();
        $targetDb = $target->getDb();

        // There are no collection by default.
        $result = $target->totalRows('item_set');
        $this->assertEquals(1, $result);
        $sql = 'SELECT * FROM item_set;';
        $result = $targetDb->fetchRow($sql);
        $itemSetId = (integer) $result['id'];

        $result = $target->totalRows('resource');
        $this->assertEquals(1, $result);
        $sql = 'SELECT * FROM resource WHERE id = ' . $itemSetId;
        $result = $targetDb->fetchRow($sql);

        $itemSet = array(
            'id' => $itemSetId,
            'owner_id' => (integer) $this->user->id,
            'resource_template_id' => null,
            'is_public' => 0,
            'resource_type' => 'Omeka\Entity\ItemSet',
        );
        $result = array_intersect_key($result, $itemSet);
        $this->assertEquals($itemSet, $result);

        $result = $target->totalRows('value');
        $this->assertEquals(6, $result);
        $sql = 'SELECT * FROM value';
        $result = $targetDb->fetchAll($sql);
        $this->assertEquals(6, count($result));
        $sql = 'SELECT * FROM value WHERE resource_id = ' . $itemSetId;
        $result = $targetDb->fetchAll($sql);
        $this->assertEquals(6, count($result));

        $properties = array(
            'resource_id' => $itemSetId,
            'property_id' => 1,
            'value' => 'All items of the site "Automated Test Installation"',
        );
        $result[0] = array_intersect_key($result[0], $properties);
        $this->assertEquals($properties, $result[0]);

        $properties = array(
            'resource_id' => $itemSetId,
            'property_id' => 30,
            'value' => 'Digital library powered by Omeka Classic',
            'uri' => 'http://www.example.com',
        );
        $result[5] = array_intersect_key($result[5], $properties);
        $this->assertEquals($properties, $result[5]);
    }

    public function testUpgradeCollections()
    {
        $this->_checkDownloadedOmekaS();
        $processor = $this->_prepareProcessor(
            'Core / Records',
            array('user' => $this->user),
            array('_unzipOmekaS', '_configOmekaS', '_installOmekaS', '_upgradeUsers',
                '_upgradeCollections'));
        $target = $processor->getTarget();
        $targetDb = $target->getDb();

        // There are no collection by default.
        $totalRecords = total_records('Collection');
        $result = $target->totalRows('item_set');
        $this->assertEquals($totalRecords, $result);
        $result = $target->totalRows('resource');
        $this->assertEquals($totalRecords, $result);
    }

    public function testUpgradeItemsCollections()
    {
        $this->_checkDownloadedOmekaS();

        // Prepare a list of records:
        // 2 collections and 5 items (3 with a collection).
        $itemDefaultId = 1;

        // Create a missing id.
        $item = new Item();
        $item->save();
        $item->delete();

        $item = new Item();
        $item->owner_id = 25;
        $item->save();
        $item3 = $item->id;

        $item = new Item();
        $item->save();
        $item->delete();

        $collection = new Collection();
        $collection->save();
        $collection1 = $collection->id;

        $item = new Item();
        $item->collection_id = $collection1;
        $item->public = 1;
        $item->save();
        $item5 = $item->id;

        $collection = new Collection();
        $collection->save();
        $collection->delete();

        $item = new Item();
        $item->collection_id = $collection1;
        $item->public = 1;
        $item->save();
        $item6 = $item->id;

        $collection = new Collection();
        $collection->save();
        $collection2 = $collection->id;

        $item = new Item();
        $item->collection_id = $collection2;
        $item->public = 1;
        $item->save();
        $item7 = $item->id;

        $totalItems = total_records('Item');
        $this->assertEquals(5, $totalItems);
        $totalCollections = total_records('Collection');
        $this->assertEquals(2, $totalCollections);

        $processor = $this->_prepareProcessor(
            'Core / Records',
            array('user' => $this->user),
            array('_unzipOmekaS', '_configOmekaS', '_installOmekaS', '_upgradeUsers',
                '_upgradeItems', '_upgradeCollections', '_setCollectionsOfItems'));
        $target = $processor->getTarget();
        $targetDb = $target->getDb();

        $result = $target->totalRows('item');
        $this->assertEquals($totalItems, $result);
        $result = $target->totalRows('item_set');
        $this->assertEquals($totalCollections, $result);
        $result = $target->totalRows('resource');
        $this->assertEquals($totalItems + $totalCollections, $result);

        $sql = 'SELECT * FROM resource;';
        $result = $targetDb->fetchAll($sql);
        $this->assertEquals('Omeka\Entity\Item', $result[1]['resource_type']);
        $this->assertEquals($item3, $result[1]['id']);
        $this->assertEmpty($result[1]['owner_id']);
        $this->assertEmpty($result[1]['is_public']);

        $this->assertEquals($item5, $result[2]['id']);
        $this->assertNotEmpty($result[2]['is_public']);

        $this->assertEquals('Omeka\Entity\ItemSet', $result[5]['resource_type']);
        $this->assertEquals(8, $result[5]['id']);
        $this->assertEmpty($result[5]['is_public']);
        $this->assertEquals($this->user->id, $result[5]['owner_id']);

        $result = $target->totalRows('item_item_set');
        $this->assertEquals(3, $result);

        $sql = 'SELECT MAX(id) FROM item_set;';
        $itemSet2 = $targetDb->fetchOne($sql);
        $sql = 'SELECT * FROM item_item_set WHERE item_id = ' . $item7;
        $result = $targetDb->fetchAll($sql);
        $this->assertEquals(1, count($result));
        $this->assertEquals($itemSet2, $result[0]['item_set_id']);
    }

    public function testUpgradeMetadata()
    {
        $this->_checkDownloadedOmekaS();

        // Prepare a list of records:
        // 1 collections and 2 items (one by default).
        // Create a missing id.
        $item = new Item();
        $item->save();
        $item->delete();

        $collection1 = insert_collection(array(), array(
            'Dublin Core' => array(
                'Title' => array(
                    array('text' => 'foo collection', 'html' => false),
                ),
                'Creator' => array(
                    array('text' => 'Foo Creator collection', 'html' => false),
                    array('text' => '<p>Yourself collection', 'html' => true),
                ),
                'Description' => array(
                    array('text' => 'Second description collection</p>', 'html' => true),
                ),
            ),
        ));

        // Create a missing id.
        $item = new Item();
        $item->save();
        $item->delete();

        $item2 = insert_item(
            array(
                'collection_id' => $collection1->id,
                'item_type_name' => 'Still Image',
                'tags' => 'Tag One, Tag Two, Tag Three',
            ),
            array(
                'Dublin Core' => array(
                    'Title' => array(
                        array('text' => 'bar text', 'html' => false),
                        array('text' => '<p>bar html</p>', 'html' => true),
                    ),
                    'Creator' => array(
                        array('text' => 'Myself', 'html' => false),
                        array('text' => '<p>Yourself', 'html' => true),
                    ),
                    'Description' => array(
                        array('text' => 'Bar description', 'html' => false),
                        array('text' => 'Bar description</p>', 'html' => true),
                    ),
                    'Date' => array(
                        array('text' => '2017', 'html' => false),
                    ),
                ),
            )
        );

        $totalItems = total_records('Item');
        // $this->assertEquals(2, $totalItems);
        $totalCollections = total_records('Collection');
        // $this->assertEquals(1, $totalCollections);

        $processor = $this->_prepareProcessor(
            'Core / Records',
            array('user' => $this->user),
            array('_unzipOmekaS', '_configOmekaS', '_installOmekaS', '_upgradeUsers',
                '_upgradeItems', '_upgradeCollections', '_setCollectionsOfItems', '_upgradeMetadata'));
        $target = $processor->getTarget();
        $targetDb = $target->getDb();

        $sql = 'SELECT MAX(id) FROM item_set;';
        $result = $targetDb->fetchOne($sql);
        $itemSetId = $result;

        $sql = 'SELECT * FROM value WHERE resource_id = ' . $itemSetId;
        $result = $targetDb->fetchAll($sql);
        $this->assertEquals(4, count($result));
        // 1: Dublin Core Title
        $this->assertEquals(1, $result[0]['property_id']);
        $this->assertEquals('<p>Yourself collection', $result[2]['value']);

        $sql = 'SELECT * FROM resource WHERE id = ' . $item2->id;
        $result = $targetDb->fetchRow($sql);
        // 33: Still Image
        $this->assertEquals(33, $result['resource_class_id']);

        $sql = 'SELECT * FROM value WHERE resource_id = ' . $item2->id;
        $result = $targetDb->fetchAll($sql);
        $this->assertEquals(7, count($result));
        // 2: Dublin Core Creator
        $this->assertEquals(2, $result[3]['property_id']);
        $this->assertEquals('Myself', $result[2]['value']);
    }
}
