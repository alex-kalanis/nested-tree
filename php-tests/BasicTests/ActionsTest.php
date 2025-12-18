<?php

namespace Tests\BasicTests;

use kalanis\nested_tree\Actions;
use kalanis\nested_tree\Support\Options;
use Tests\CommonTestClass;
use Tests\MockNode;
use Tests\NestedSetExtends;

class ActionsTest extends CommonTestClass
{
    protected ?Actions $actions = null;

    public function setUp() : void
    {
        $this->actions = new Actions(new NestedSetExtends(new MockDataSource()));
    }

    public function testCreate() : void
    {
        $node = MockNode::create(25);
        $this->assertNotEmpty($this->actions->create($node));
    }

    public function testUpdate() : void
    {
        $node = MockNode::create(25);
        $this->assertTrue($this->actions->update($node));
    }

    public function testMove() : void
    {
        $this->assertFalse($this->actions->movePosition(35, 400));
        $this->assertTrue($this->actions->movePosition(3, 2));
    }

    public function testChangeParent() : void
    {
        $this->assertFalse($this->actions->changeParent(35, null));
        $this->assertTrue($this->actions->changeParent(4, 0));
    }

    public function testDeletePull() : void
    {
        $this->assertFalse($this->actions->delete(35, true));
        $this->assertTrue($this->actions->delete(4, true));
    }

    public function testDeleteAll() : void
    {
        $this->assertFalse($this->actions->delete(35));
    }

    public function testOptions() : void
    {
        $opt = new Options();
        $this->actions->setExtraOptions($opt);
        $this->actions->fixStructure();
        $this->assertTrue(true);
    }

    public function testGet() : void
    {
        $node = $this->actions->getNode(1);
        $this->assertNotNull($node);
        $this->assertEquals(1, $node->id);
        $this->assertEquals(1, $node->level);
        $this->assertEquals(1, $node->left);
        $this->assertEquals(2, $node->right);
        $this->assertEquals(1, $node->position);
    }

    public function testGetNull() : void
    {
        $options = $this->actions->getOptions();
        $options->skipCurrent = true;
        $this->actions->setExtraOptions($options);
        $this->assertNull($this->actions->getNode(1));
    }
}
