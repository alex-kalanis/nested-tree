<?php

namespace Tests\SimpleDbTests;

use kalanis\nested_tree\Support;
use Tests\MockNode;

class UpdateDbTest extends AbstractSimpleDbTests
{
    /**
     * Test check that selected parent is same level or under its children.
     *
     * This will be use to check before update the data.
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

    public function testMoveNoConditions() : void
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

        $this->assertTrue($this->nestedSet->move(15, 12, $options));
    }

    public function testUpdateData() : void
    {
        $this->dataRefill();
        $this->nestedSet->rebuild();

        $node = MockNode::create(14, name: 'Mop Update');
        $this->assertTrue($this->nestedSet->update($node));

        $sql = 'SELECT * FROM `' . $this->settings->tableName . '` WHERE `' . $this->settings->idColumnName . '` = :id';
        $Sth = $this->database->prepare($sql);
        $Sth->bindValue(':id', 14, \PDO::PARAM_INT);
        $Sth->execute();
        $row = $Sth->fetch();
        $Sth->closeCursor();

        // updated
        $this->assertEquals(14, $row[$this->settings->idColumnName]);
        $this->assertEquals(9, $row[$this->settings->parentIdColumnName]);
        $this->assertEquals(3, $row[$this->settings->positionColumnName]);
        $this->assertEquals('Mop Update', $row['name']);
    }
}
