<?php

namespace Tests\SimpleDbTests;

use Tests\MockNode;

class CreateDbTest extends AbstractSimpleDbTests
{
    /**
     * Test get new position, the `position` value will be use before `INSERT` the data to DB.
     */
    public function testGetNewPosition() : void
    {
        $this->dataRefill();
        $this->assertEquals(4, $this->nestedSet->getNewPosition(4));
        $this->assertEquals(6, $this->nestedSet->getNewPosition(2));
    }

    /**
     * Test get the data tree. This is usually for retrieve all the data with less condition.
     *
     * The `getTreeWithChildren()` method will be called automatically while run the `rebuild()` method.
     */
    public function testGetTreeWithChildren() : void
    {
        $this->dataRefill();
        $this->nestedSet->rebuild();
        $result = $this->nestedSet->getTreeWithChildren();
        $this->assertCount(21, $result);
    }

    /**
     * Test `rebuild()` method. This method must run after `INSERT`, `UPDATE`, or `DELETE` the data in database.<br>
     * It may have to run if the `level`, `left`, `right` data is incorrect.
     */
    public function testRebuild() : void
    {
        $this->dataRefill();
        $this->nestedSet->rebuild();
        // get the result of 3
        $row = $this->getRow($this->database, $this->settings, 3);
        // assert value must be matched.
        $this->assertEquals(40, $row[$this->settings->rightColumnName]);
        $this->assertEquals(1, $row[$this->settings->levelColumnName]);

        // get the result of 10
        $row = $this->getRow($this->database, $this->settings, 10);
        // assert value must be matched.
        $this->assertEquals(13, $row[$this->settings->leftColumnName]);
        $this->assertEquals(14, $row[$this->settings->rightColumnName]);
        $this->assertEquals(3, $row[$this->settings->levelColumnName]);
    }

    public function testAdd() : void
    {
        $this->dataRefill();
        $this->nestedSet->rebuild();
        $node = $this->nestedSet->add(MockNode::create(99, 0, 536, 412, 65, 58465, [], 'Added One'));
        $this->assertNotEmpty($node);
        $this->nestedSet->rebuild();
        $this->assertEquals(21, $node->id);

        $row = $this->getRow($this->database, $this->settings, $node->id);

        // recalculated
        $this->assertEquals(41, $row[$this->settings->leftColumnName]);
        $this->assertEquals(42, $row[$this->settings->rightColumnName]);
        $this->assertEquals(1, $row[$this->settings->levelColumnName]);
        $this->assertEquals(4, $row[$this->settings->positionColumnName]);
    }
}
