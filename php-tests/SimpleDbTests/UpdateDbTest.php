<?php

namespace Tests\SimpleDbTests;

use kalanis\nested_tree\Support;
use Tests\MockNode;
use Tests\Support\DumpTrait;

class UpdateDbTest extends AbstractSimpleDbTests
{
    use DumpTrait;

    /**
     * Test check that selected parent is same level or under its children.
     *
     * This will be used to check before update the data.
     */
    public function testIsNodeParentInItsChildren() : void
    {
        $this->dataRefill();
        $this->nestedSet->rebuild();
        $this->assertFalse($this->nestedSet->isNewParentOutsideCurrentNodeTree(9, 12));
        $this->assertFalse($this->nestedSet->isNewParentOutsideCurrentNodeTree(9, 14));
        $this->assertTrue($this->nestedSet->isNewParentOutsideCurrentNodeTree(9, 4));
        $this->assertTrue($this->nestedSet->isNewParentOutsideCurrentNodeTree(9, 7));
        $this->assertTrue($this->nestedSet->isNewParentOutsideCurrentNodeTree(9, 20));
        $this->assertTrue($this->nestedSet->isNewParentOutsideCurrentNodeTree(9, 0)); // root always
    }

    public function testMoveNodeUp() : void
    {
        $this->dataRefill();
        $this->nestedSet->rebuild();
        $this->nestedSet->move(13, 3);
        $this->nestedSet->rebuild();

        $options = new Support\Options();
        $options->joinChild = true;
        $options->additionalColumns = ['ANY_VALUE(child.name) as name'];
        $where = new Support\Conditions();
        $where->query = 'child.name LIKE :my_name';
        $where->bindValues = [':my_name' => '2.1.1.%'];
        $options->where = $where;
        $nodes = $this->nestedSet->listNodesFlatten($options);

        $node = reset($nodes->items);
        $this->assertEquals('2.1.1.1', $node->name);
        $this->assertEquals(1, $node->position);
        $this->assertEquals(12, $node->id);
        $node = next($nodes->items);
        $this->assertEquals('2.1.1.3', $node->name);
        $this->assertEquals(2, $node->position);
        $this->assertEquals(14, $node->id);
        $node = next($nodes->items);
        $this->assertEquals('2.1.1.2', $node->name);
        $this->assertEquals(3, $node->position);
        $this->assertEquals(13, $node->id);

        $this->assertEmpty(next($nodes->items));
    }

    public function testMoveNodeDown() : void
    {
        $this->dataRefill();
        $this->nestedSet->rebuild();
        $this->nestedSet->move(14, 2);
        $this->nestedSet->rebuild();

        $options = new Support\Options();
        $options->joinChild = true;
        $options->additionalColumns = ['ANY_VALUE(child.name) as name'];
        $where = new Support\Conditions();
        $where->query = 'child.name LIKE :my_name';
        $where->bindValues = [':my_name' => '2.1.1.%'];
        $options->where = $where;
        $nodes = $this->nestedSet->listNodesFlatten($options);

        $node = reset($nodes->items);
        $this->assertEquals('2.1.1.1', $node->name);
        $this->assertEquals(1, $node->position);
        $this->assertEquals(12, $node->id);
        $node = next($nodes->items);
        $this->assertEquals('2.1.1.3', $node->name);
        $this->assertEquals(2, $node->position);
        $this->assertEquals(14, $node->id);
        $node = next($nodes->items);
        $this->assertEquals('2.1.1.2', $node->name);
        $this->assertEquals(3, $node->position);
        $this->assertEquals(13, $node->id);

        $this->assertEmpty(next($nodes->items));
    }

    public function testChangeNodeParent() : void
    {
        $this->dataRefill();
        $this->nestedSet->rebuild();
        $this->assertTrue($this->nestedSet->changeParent(13, 16));
        $this->nestedSet->rebuild();
        $this->assertFalse($this->nestedSet->changeParent(4, 12));

        $options = new Support\Options();
        $options->joinChild = true;
        $options->additionalColumns = ['ANY_VALUE(child.name) as name'];
        $where = new Support\Conditions();
        $where->query = 'child.parent_id = :my_id';
        $options->where = $where;

        // old group
        $where->bindValues = [':my_id' => 9];
        $nodes = $this->nestedSet->listNodesFlatten($options);

        $node = reset($nodes->items);
        $this->assertEquals('2.1.1.1', $node->name);
        $this->assertEquals(12, $node->id);
        $this->assertEquals(1, $node->position);
        $node = next($nodes->items);
        $this->assertEquals('2.1.1.3', $node->name);
        $this->assertEquals(14, $node->id);
        $this->assertEquals(2, $node->position);

        $this->assertEmpty(next($nodes->items));

        // new group
        $where->bindValues = [':my_id' => 16];
        $nodes = $this->nestedSet->listNodesFlatten($options);

        $node = reset($nodes->items);
        $this->assertEquals('3.2.1', $node->name);
        $this->assertEquals(18, $node->id);
        $this->assertEquals(1, $node->position);
        $node = next($nodes->items);
        $this->assertEquals('3.2.2', $node->name);
        $this->assertEquals(19, $node->id);
        $this->assertEquals(2, $node->position);
        $node = next($nodes->items);
        $this->assertEquals('3.2.3', $node->name);
        $this->assertEquals(20, $node->id);
        $this->assertEquals(3, $node->position);
        $node = next($nodes->items);
        $this->assertEquals('2.1.1.2', $node->name);
        $this->assertEquals(13, $node->id);
        $this->assertEquals(4, $node->position);

        $this->assertEmpty(next($nodes->items));
    }

    public function testMoveNoEntry() : void
    {
        $this->dataRefill();
        $this->nestedSet->rebuild();

        $extraOptions = new Support\Options();
        $extraOptions->joinChild = true;
        $extraOptions->additionalColumns = ['ANY_VALUE(child.name) as name'];
        $extraWhere = new Support\Conditions();
        $extraWhere->query = 'child.name LIKE :my_name';
        $extraWhere->bindValues = [':my_name' => '14.1.%'];
        $extraOptions->where = $extraWhere;

        $this->assertFalse($this->nestedSet->move(22, 2, $extraOptions));
    }

    /**
     * Must get true - result of move command
     * @return void
     */
    public function testMoveNoConditionsMet() : void
    {
        $this->dataRefill();
        $this->nestedSet->rebuild();

        $options = new Support\Options();
        $options->joinChild = true;
        $options->additionalColumns = ['ANY_VALUE(child.name) as name'];
        $conditions = new Support\Conditions();
        $conditions->query = 'child.name LIKE :my_name';
        $conditions->bindValues = [':my_name' => '14.1.%'];
        $options->where = $conditions;

        $this->assertFalse($this->nestedSet->move(15, 12, $options));
    }

    public function testUpdateData() : void
    {
        $this->dataRefill();
        $this->nestedSet->rebuild();

        $node = MockNode::create(14, name: 'Mop Update');
        $this->assertTrue($this->nestedSet->update($node));

        $row = $this->getRow($this->database, $this->settings, 14);

        // updated
        $this->assertEquals(14, $row[$this->settings->idColumnName]);
        $this->assertEquals(9, $row[$this->settings->parentIdColumnName]);
        $this->assertEquals(3, $row[$this->settings->positionColumnName]);
        $this->assertEquals('Mop Update', $row['name']);
    }

    public function testMoveWithoutChildren(): void
    {
        $this->dataRefill();
        $this->nestedSet->rebuild();

        $this->assertTrue($this->nestedSet->move(12, 3));
        $this->nestedSet->rebuild();

        // check nodes
        $options = new Support\Options();
        $options->joinChild = true;
        $options->additionalColumns = ['ANY_VALUE(child.name) as name'];
        $nodes = $this->nestedSet->listNodesFlatten($options);
        usort($nodes->items, fn(Support\Node $node1, Support\Node $node2): int => $node1 <=> $node2);

        $node = reset($nodes->items);
        $this->assertEquals('Root 1', $node->name);
        $this->assertEquals(1, $node->left);
        $this->assertEquals(2, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('Root 2', $node->name);
        $this->assertEquals(3, $node->left);
        $this->assertEquals(26, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('Root 3', $node->name);
        $this->assertEquals(27, $node->left);
        $this->assertEquals(40, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('2.1', $node->name);
        $this->assertEquals(4, $node->left);
        $this->assertEquals(17, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('2.2', $node->name);
        $this->assertEquals(18, $node->left);
        $this->assertEquals(19, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('2.3', $node->name);
        $this->assertEquals(20, $node->left);
        $this->assertEquals(21, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('2.4', $node->name);
        $this->assertEquals(22, $node->left);
        $this->assertEquals(23, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('2.5', $node->name);
        $this->assertEquals(24, $node->left);
        $this->assertEquals(25, $node->right);
        // base
        $node = next($nodes->items);
        $this->assertEquals('2.1.1', $node->name);
        $this->assertEquals(5, $node->left);
        $this->assertEquals(12, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('2.1.2', $node->name);
        $this->assertEquals(13, $node->left);
        $this->assertEquals(14, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('2.1.3', $node->name);
        $this->assertEquals(15, $node->left);
        $this->assertEquals(16, $node->right);
        // moved items
        $node = next($nodes->items);
        $this->assertEquals('2.1.1.1', $node->name);
        $this->assertEquals(3, $node->position);
        $this->assertEquals(10, $node->left);
        $this->assertEquals(11, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('2.1.1.2', $node->name);
        $this->assertEquals(1, $node->position);
        $this->assertEquals(6, $node->left);
        $this->assertEquals(7, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('2.1.1.3', $node->name);
        $this->assertEquals(2, $node->position);
        $this->assertEquals(8, $node->left);
        $this->assertEquals(9, $node->right);
        // no change
        $node = next($nodes->items);
        $this->assertEquals('3.1', $node->name);
        $this->assertEquals(28, $node->left);
        $this->assertEquals(29, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('3.2', $node->name);
        $this->assertEquals(30, $node->left);
        $this->assertEquals(37, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('3.3', $node->name);
        $this->assertEquals(38, $node->left);
        $this->assertEquals(39, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('3.2.1', $node->name);
        $this->assertEquals(31, $node->left);
        $this->assertEquals(32, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('3.2.2', $node->name);
        $this->assertEquals(33, $node->left);
        $this->assertEquals(34, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('3.2.3', $node->name);
        $this->assertEquals(35, $node->left);
        $this->assertEquals(36, $node->right);
        $node = next($nodes->items);
        $this->assertEmpty($node);
    }

    public function testMoveWithChildrenNoAnotherChildren(): void
    {
        $this->dataRefill();
        $this->nestedSet->rebuild();

        $this->assertTrue($this->nestedSet->move(9, 3));
        $this->nestedSet->rebuild();

        // check nodes
        $options = new Support\Options();
        $options->joinChild = true;
        $options->additionalColumns = ['ANY_VALUE(child.name) as name'];
        $nodes = $this->nestedSet->listNodesFlatten($options);
        usort($nodes->items, fn(Support\Node $node1, Support\Node $node2): int => $node1 <=> $node2);

        $node = reset($nodes->items);
        $this->assertEquals('Root 1', $node->name);
        $this->assertEquals(1, $node->left);
        $this->assertEquals(2, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('Root 2', $node->name);
        $this->assertEquals(3, $node->left);
        $this->assertEquals(26, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('Root 3', $node->name);
        $this->assertEquals(27, $node->left);
        $this->assertEquals(40, $node->right);
        $node = next($nodes->items);
        // base
        $this->assertEquals('2.1', $node->name);
        $this->assertEquals(4, $node->left);
        $this->assertEquals(17, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('2.2', $node->name);
        $this->assertEquals(18, $node->left);
        $this->assertEquals(19, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('2.3', $node->name);
        $this->assertEquals(20, $node->left);
        $this->assertEquals(21, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('2.4', $node->name);
        $this->assertEquals(22, $node->left);
        $this->assertEquals(23, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('2.5', $node->name);
        $this->assertEquals(24, $node->left);
        $this->assertEquals(25, $node->right);
        $node = next($nodes->items);
        // moved items
        $this->assertEquals('2.1.1', $node->name);
        $this->assertEquals(3, $node->position);
        $this->assertEquals(9, $node->left);
        $this->assertEquals(16, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('2.1.2', $node->name);
        $this->assertEquals(1, $node->position);
        $this->assertEquals(5, $node->left);
        $this->assertEquals(6, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('2.1.3', $node->name);
        $this->assertEquals(2, $node->position);
        $this->assertEquals(7, $node->left);
        $this->assertEquals(8, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('2.1.1.1', $node->name);
        $this->assertEquals(10, $node->left);
        $this->assertEquals(11, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('2.1.1.2', $node->name);
        $this->assertEquals(12, $node->left);
        $this->assertEquals(13, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('2.1.1.3', $node->name);
        $this->assertEquals(14, $node->left);
        $this->assertEquals(15, $node->right);
        // no change in next
        $node = next($nodes->items);
        $this->assertEquals('3.1', $node->name);
        $this->assertEquals(28, $node->left);
        $this->assertEquals(29, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('3.2', $node->name);
        $this->assertEquals(30, $node->left);
        $this->assertEquals(37, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('3.3', $node->name);
        $this->assertEquals(38, $node->left);
        $this->assertEquals(39, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('3.2.1', $node->name);
        $this->assertEquals(31, $node->left);
        $this->assertEquals(32, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('3.2.2', $node->name);
        $this->assertEquals(33, $node->left);
        $this->assertEquals(34, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('3.2.3', $node->name);
        $this->assertEquals(35, $node->left);
        $this->assertEquals(36, $node->right);
        $node = next($nodes->items);
        $this->assertEmpty($node);
    }

    public function testMoveWithAnotherChildren(): void
    {
        $this->dataRefill();
        $this->nestedSet->rebuild();

        $this->assertTrue($this->nestedSet->move(8, 1));
        $this->nestedSet->rebuild();

        // check nodes
        $options = new Support\Options();
        $options->joinChild = true;
        $options->additionalColumns = ['ANY_VALUE(child.name) as name'];
        $nodes = $this->nestedSet->listNodesFlatten($options);
        usort($nodes->items, fn(Support\Node $node1, Support\Node $node2): int => $node1 <=> $node2);

        $node = reset($nodes->items);
        $this->assertEquals('Root 1', $node->name);
        $this->assertEquals(1, $node->left);
        $this->assertEquals(2, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('Root 2', $node->name);
        $this->assertEquals(3, $node->left);
        $this->assertEquals(26, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('Root 3', $node->name);
        $this->assertEquals(27, $node->left);
        $this->assertEquals(40, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('2.1', $node->name);
        $this->assertEquals(6, $node->left);
        $this->assertEquals(19, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('2.2', $node->name);
        $this->assertEquals(20, $node->left);
        $this->assertEquals(21, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('2.3', $node->name);
        $this->assertEquals(22, $node->left);
        $this->assertEquals(23, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('2.4', $node->name);
        $this->assertEquals(24, $node->left);
        $this->assertEquals(25, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('2.5', $node->name);
        $this->assertEquals(4, $node->left);
        $this->assertEquals(5, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('2.1.1', $node->name);
        $this->assertEquals(7, $node->left);
        $this->assertEquals(14, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('2.1.2', $node->name);
        $this->assertEquals(15, $node->left);
        $this->assertEquals(16, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('2.1.3', $node->name);
        $this->assertEquals(17, $node->left);
        $this->assertEquals(18, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('2.1.1.1', $node->name);
        $this->assertEquals(8, $node->left);
        $this->assertEquals(9, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('2.1.1.2', $node->name);
        $this->assertEquals(10, $node->left);
        $this->assertEquals(11, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('2.1.1.3', $node->name);
        $this->assertEquals(12, $node->left);
        $this->assertEquals(13, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('3.1', $node->name);
        $this->assertEquals(28, $node->left);
        $this->assertEquals(29, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('3.2', $node->name);
        $this->assertEquals(30, $node->left);
        $this->assertEquals(37, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('3.3', $node->name);
        $this->assertEquals(38, $node->left);
        $this->assertEquals(39, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('3.2.1', $node->name);
        $this->assertEquals(31, $node->left);
        $this->assertEquals(32, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('3.2.2', $node->name);
        $this->assertEquals(33, $node->left);
        $this->assertEquals(34, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('3.2.3', $node->name);
        $this->assertEquals(35, $node->left);
        $this->assertEquals(36, $node->right);
        $node = next($nodes->items);
        $this->assertEmpty($node);
    }

    public function testMoveWithBothChildren(): void
    {
        $this->dataRefill();
        $this->nestedSet->rebuild();

        $this->assertTrue($this->nestedSet->move(3, 2));
        $this->nestedSet->rebuild();

        // check nodes
        $options = new Support\Options();
        $options->joinChild = true;
        $options->additionalColumns = ['ANY_VALUE(child.name) as name'];
        $nodes = $this->nestedSet->listNodesFlatten($options);
        usort($nodes->items, fn(Support\Node $node1, Support\Node $node2): int => $node1 <=> $node2);

        $node = reset($nodes->items);
        $this->assertEquals('Root 1', $node->name);
        $this->assertEquals(1, $node->left);
        $this->assertEquals(2, $node->right);
        $this->assertEquals(1, $node->position);
        $node = next($nodes->items);
        $this->assertEquals('Root 2', $node->name);
        $this->assertEquals(17, $node->left);
        $this->assertEquals(40, $node->right);
        $this->assertEquals(3, $node->position);
        $node = next($nodes->items);
        $this->assertEquals('Root 3', $node->name);
        $this->assertEquals(3, $node->left);
        $this->assertEquals(16, $node->right);
        $this->assertEquals(2, $node->position);
        $node = next($nodes->items);
        $this->assertEquals('2.1', $node->name);
        $this->assertEquals(18, $node->left);
        $this->assertEquals(31, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('2.2', $node->name);
        $this->assertEquals(32, $node->left);
        $this->assertEquals(33, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('2.3', $node->name);
        $this->assertEquals(34, $node->left);
        $this->assertEquals(35, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('2.4', $node->name);
        $this->assertEquals(36, $node->left);
        $this->assertEquals(37, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('2.5', $node->name);
        $this->assertEquals(38, $node->left);
        $this->assertEquals(39, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('2.1.1', $node->name);
        $this->assertEquals(19, $node->left);
        $this->assertEquals(26, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('2.1.2', $node->name);
        $this->assertEquals(27, $node->left);
        $this->assertEquals(28, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('2.1.3', $node->name);
        $this->assertEquals(29, $node->left);
        $this->assertEquals(30, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('2.1.1.1', $node->name);
        $this->assertEquals(20, $node->left);
        $this->assertEquals(21, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('2.1.1.2', $node->name);
        $this->assertEquals(22, $node->left);
        $this->assertEquals(23, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('2.1.1.3', $node->name);
        $this->assertEquals(24, $node->left);
        $this->assertEquals(25, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('3.1', $node->name);
        $this->assertEquals(4, $node->left);
        $this->assertEquals(5, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('3.2', $node->name);
        $this->assertEquals(6, $node->left);
        $this->assertEquals(13, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('3.3', $node->name);
        $this->assertEquals(14, $node->left);
        $this->assertEquals(15, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('3.2.1', $node->name);
        $this->assertEquals(7, $node->left);
        $this->assertEquals(8, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('3.2.2', $node->name);
        $this->assertEquals(9, $node->left);
        $this->assertEquals(10, $node->right);
        $node = next($nodes->items);
        $this->assertEquals('3.2.3', $node->name);
        $this->assertEquals(11, $node->left);
        $this->assertEquals(12, $node->right);
        $node = next($nodes->items);
        $this->assertEmpty($node);
    }
}
