<?php

namespace Tests\MultipleMenusTests;

use kalanis\nested_tree\Support;
use Tests\MockNode;

class CreateDbTest extends AbstractMultipleMenusTests
{
    /**
     * Test get the data tree. This is usually for retrieve all the data with less condition.
     *
     * The `getTreeWithChildren()` method will be called automatically while run the `rebuild()` method.
     */
    public function testGetTreeWithChildren() : void
    {
        $this->dataRefill();
        $this->rebuild(1);
        $this->rebuild(2);
        $result = $this->nestedSet->getTreeWithChildren();
        $this->assertCount(27, $result);

        $option1 = new Support\Options();
        $condition1 = new Support\Conditions();
        $condition1->query = 'menu_id = :menu_id';
        $condition1->bindValues = [':menu_id' => 1];
        $option1->where = $condition1;
        $result = $this->nestedSet->getTreeWithChildren($option1);
        $this->assertCount(17, $result);

        $option2 = new Support\Options();
        $condition2 = new Support\Conditions();
        $condition2->query = '(menu_id = :menu_id AND deleted = :deleted)';
        $condition2->bindValues = [':menu_id' => 1, ':deleted' => 0];
        $option2->where = $condition2;
        $result = $this->nestedSet->getTreeWithChildren($option2);
        $this->assertCount(17, $result);
    }

    /**
     * Test `rebuild()` method. This method must run after `INSERT`, `UPDATE`, or `DELETE` the data in database.<br>
     * It may have to run if the `level`, `left`, `right` data is incorrect.
     */
    public function testRebuild() : void
    {
        $this->dataRefill();
        $this->rebuild(1);

        // get the result of 3
        $row = $this->getRow($this->database, $this->settings, 3);
        // assert value must be matched.
        $this->assertEquals(21, $row[$this->settings->leftColumnName]);
        $this->assertEquals(32, $row[$this->settings->rightColumnName]);
        $this->assertEquals(1, $row[$this->settings->levelColumnName]);

        // get the result of 10
        $row = $this->getRow($this->database, $this->settings, 10);
        // assert value must be matched.
        $this->assertEquals(11, $row[$this->settings->leftColumnName]);
        $this->assertEquals(12, $row[$this->settings->rightColumnName]);
        $this->assertEquals(3, $row[$this->settings->levelColumnName]);

        // get the result of 29 (t_type = product_category and it did not yet rebuilt).
        $row = $this->getRow($this->database, $this->settings, 29);
        // assert value must be matched.
        $this->assertEquals(0, $row[$this->settings->leftColumnName]);
        $this->assertEquals(0, $row[$this->settings->rightColumnName]);
        $this->assertEquals(0, $row[$this->settings->levelColumnName]);
    }

    /**
     * Test get new position, the `position` value will be use before `INSERT` the data to DB.
     */
    public function testGetNewPosition() : void
    {
        $this->dataRefill();
        $this->rebuild(1);
        $this->rebuild(2);

        $condition = new Support\Conditions();
        $condition->query = 'menu_id = :menu_id';
        $condition->bindValues = [':menu_id' => 1];
        $this->assertEquals(4, $this->nestedSet->getNewPosition(4, $condition));
        $this->assertEquals(3, $this->nestedSet->getNewPosition(16, $condition)); // there is one deleted - will be skipped
        $this->assertEquals(4, $this->nestedSet->getNewPosition(777, $condition)); // not known - fails to root
        $this->assertEquals(4, $this->nestedSet->getNewPosition(null, $condition)); // root with unknown - fails to root

        $condition->bindValues = [':menu_id' => 2];
        $newPosition = $this->nestedSet->getNewPosition(21, $condition);
        $this->assertEquals(4, $newPosition);
    }

    public function testAdd() : void
    {
        $this->dataRefill();
        $this->rebuild(1);
        $this->rebuild(2);

        $options = new Support\Options();
        $optionsWhere = new Support\Conditions();
        $optionsWhere->query = '`menu_id` = :menu_id';
        $optionsWhere->bindValues = [':menu_id' => 1];
        $options->where = $optionsWhere;
        $addNode = MockNode::create(99, null, 536, 412, 65, 58465);
        $addNode->menu_id = 1;
        $node = $this->nestedSet->add($addNode, $options);

        $this->assertNotEmpty($node);
        $this->rebuild(1);
        $this->assertEquals(33, $node->id);

        $row = $this->getRow($this->database, $this->settings, $node->id);

        // recalculated
        $this->assertEquals(33, $row[$this->settings->leftColumnName]);
        $this->assertEquals(34, $row[$this->settings->rightColumnName]);
        $this->assertEquals(1, $row[$this->settings->levelColumnName]);
        $this->assertEquals(4, $row[$this->settings->positionColumnName]);
    }
}
