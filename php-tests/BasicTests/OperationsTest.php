<?php

namespace Tests\BasicTests;

use kalanis\nested_tree\NestedSet;
use kalanis\nested_tree\Support\TableSettings;
use Tests\CommonTestClass;
use Tests\MockNode;
use Tests\NestedSetExtends;

class OperationsTest extends CommonTestClass
{
    protected ?NestedSet $nestedSet = null;

    public function setUp() : void
    {
        $this->nestedSet = new NestedSetExtends(new MockDataSource());
    }

    /**
     * Test rebuild `children` into the array result.
     *
     * The `getTreeRebuildChildren()` method will be called automatically while run the `getTreeWithChildren()` method.
     */
    public function testGetTreeRebuildChildren() : void
    {
        $array = [0 => MockNode::create(0)];
        // dummy data. ----------------------------
        $array[1] = MockNode::create(1, 0, 0, 0, 1, 0, [], 'Root 1');
        $array[2] = MockNode::create(2, 0, 0, 0, 1, 0, [], 'Root 2');
        $array[4] = MockNode::create(4, 2, 0, 0, 2, 0, [], '2.1');
        $array[6] = MockNode::create(6, 4, 0, 0, 3, 0, [], '2.1.1');
        // end dummy data. ------------------------

        $array = $this->nestedSet->getTreeRebuildChildren($array);

        $row = reset($array);
        $this->assertTrue($this->compareNodes($row, MockNode::create(0, children: [1, 2]), true));
        $row = next($array);
        $this->assertTrue($this->compareNodes($row, MockNode::create(1, level: 1, name: 'Root 1'), true));
        $row = next($array);
        $this->assertTrue($this->compareNodes($row, MockNode::create(2, level: 1, children: [4], name: 'Root 2'), true));
        $row = next($array);
        $this->assertTrue($this->compareNodes($row, MockNode::create(4, 2, level: 2, children: [6], name: '2.1'), true));
        $row = next($array);
        $this->assertTrue($this->compareNodes($row, MockNode::create(6, 4, level: 3, name: '2.1.1'), true));
        $row = next($array);
        $this->assertFalse($row);
    }

    /**
     * Test rebuild `level`, `left`, `right` data.
     *
     * This `rebuildGenerateTreeData()` method will be called automatically while run the `rebuild()` method.
     */
    public function testRebuildGenerateTreeData() : void
    {
        $array = [0 => MockNode::create(0)];
        // dummy data. ----------------------------
        $array[1] = MockNode::create(1, 0, 0, 0, 1, 0, [], 'Root 1');
        $array[2] = MockNode::create(2, 0, 0, 0, 1, 0, [], 'Root 2');
        $array[3] = MockNode::create(3, 0, 0, 0, 1, 0, [], 'Root 3');
        $array[4] = MockNode::create(4, 2, 0, 0, 2, 0, [], '2.1');
        $array[5] = MockNode::create(5, 2, 0, 0, 2, 0, [], '2.2');
        $array[6] = MockNode::create(6, 4, 0, 0, 3, 0, [], '2.1.1');
        $array[7] = MockNode::create(7, 4, 0, 0, 3, 0, [], '2.1.2');
        $array[8] = MockNode::create(8, 4, 0, 0, 3, 0, [], '2.1.3');

        $array = $this->nestedSet->getTreeRebuildChildren($array);
        // end dummy data. ------------------------

        $n = 0;
        $this->nestedSet->rebuildGenerateTreeData($array, 0, 0, $n);

        $row = reset($array);
        $this->assertTrue($this->compareNodes($row, MockNode::create(0, right: 17, children: [1, 2, 3]), true, true));
        $row = next($array);
        $this->assertTrue($this->compareNodes($row, MockNode::create(1, 0, 1, 2, 1, name: 'Root 1'), true, true));
        $row = next($array);
        $this->assertTrue($this->compareNodes($row, MockNode::create(2, 0, 3, 14, 1, children: [4, 5], name: 'Root 2'), true, true));
        $row = next($array);
        $this->assertTrue($this->compareNodes($row, MockNode::create(3, 0, 15, 16, 1, name: 'Root 3'), true, true));
        $row = next($array);
        $this->assertTrue($this->compareNodes($row, MockNode::create(4, 2, 4, 11, 2, children: [6, 7, 8], name: '2.1'), true, true));
        $row = next($array);
        $this->assertTrue($this->compareNodes($row, MockNode::create(5, 2, 12, 13, 2, name: '2.2'), true, true));
        $row = next($array);
        $this->assertTrue($this->compareNodes($row, MockNode::create(6, 4, 5, 6, 3, name: '2.1.1'), true, true));
        $row = next($array);
        $this->assertTrue($this->compareNodes($row, MockNode::create(7, 4, 7, 8, 3, name: '2.1.2'), true, true));
        $row = next($array);
        $this->assertTrue($this->compareNodes($row, MockNode::create(8, 4, 9, 10, 3, name: '2.1.3'), true, true));
        $row = next($array);
        $this->assertFalse($row);
    }

    /**
     * Test rebuild `level`, `left`, `right`, `position` data.
     *
     * This `rebuildGeneratePositionData()` method will be called automatically while run the `rebuild()` method.
     */
    public function testRebuildGeneratePositionData() : void
    {
        $array = [0 => MockNode::create(0)];
        // dummy data. ----------------------------
        $array[1] = MockNode::create(1, 0, 0, 0, 0, 0, [], 'Root 1');
        $array[2] = MockNode::create(2, 0, 0, 0, 0, 0, [], 'Root 2');
        $array[3] = MockNode::create(3, 0, 0, 0, 0, 0, [], 'Root 3');
        $array[4] = MockNode::create(4, 2, 0, 0, 0, 0, [], '2.1');
        $array[5] = MockNode::create(5, 2, 0, 0, 0, 0, [], '2.2');
        $array[6] = MockNode::create(6, 4, 0, 0, 0, 0, [], '2.1.1');
        $array[7] = MockNode::create(7, 4, 0, 0, 0, 0, [], '2.1.2');
        $array[8] = MockNode::create(8, 4, 0, 0, 0, 0, [], '2.1.3');

        $array = $this->nestedSet->getTreeRebuildChildren($array);
        // end dummy data. ------------------------

        $n = 0;
        $this->nestedSet->rebuildGeneratePositionData($array, 0, $n);

        $row = reset($array);
        $this->assertTrue($this->compareNodes($row, MockNode::create(0, position: 1), checkPosition: true));
        $row = next($array);
        $this->assertTrue($this->compareNodes($row, MockNode::create(1, position: 1, name: 'Root 1'), checkPosition: true));
        $row = next($array);
        $this->assertTrue($this->compareNodes($row, MockNode::create(2, position: 2, name: 'Root 2'), checkPosition: true));
        $row = next($array);
        $this->assertTrue($this->compareNodes($row, MockNode::create(3, position: 3, name: 'Root 3'), checkPosition: true));
        $row = next($array);
        $this->assertTrue($this->compareNodes($row, MockNode::create(4, 2, position: 1, name: '2.1'), checkPosition: true));
        $row = next($array);
        $this->assertTrue($this->compareNodes($row, MockNode::create(5, 2, position: 2, name: '2.2'), checkPosition: true));
        $row = next($array);
        $this->assertTrue($this->compareNodes($row, MockNode::create(6, 4, position: 1, name: '2.1.1'), checkPosition: true));
        $row = next($array);
        $this->assertTrue($this->compareNodes($row, MockNode::create(7, 4, position: 2, name: '2.1.2'), checkPosition: true));
        $row = next($array);
        $this->assertTrue($this->compareNodes($row, MockNode::create(8, 4, position: 3, name: '2.1.3'), checkPosition: true));
        $row = next($array);
        $this->assertFalse($row);
    }

    public function testMoveNone() : void
    {
        $this->assertFalse($this->nestedSet->move(0, 99));
    }

    public function testMoveNoneKnown() : void
    {
        $nestedSet = new NestedSetExtends(new MockDataSource([
            MockNode::create(0, null, 0, 9, 0, 1),
            MockNode::create(1, 0, 1, 2, 1, 1),
        ]));
        $this->assertFalse($nestedSet->move(0, 99));
    }

    public function testChangeParentExists() : void
    {
        $nestedSet = new NestedSetExtends(new MockDataSource([
            MockNode::create(1, 0, 1, 2, 1, 1),
        ]));
        $this->assertTrue($nestedSet->changeParent(1, null));
    }

    public function testChangeParentNop() : void
    {
        $nestedSet = new NestedSetExtends(new MockDataSource([
            MockNode::create(1, 0, 1, 2, 1, 1),
        ]));
        $this->assertFalse($nestedSet->changeParent(1, 13));
    }

    public function testColumnTranslate() : void
    {
        $ts = new TableSettings();
        $l = new XColumn();
        $this->assertEquals('id', $l->translate($ts, 'id'));
        $this->assertEquals('parent_id', $l->translate($ts, 'parentId'));
    }
}
