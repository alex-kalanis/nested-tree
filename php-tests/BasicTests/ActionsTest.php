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
        $this->assertTrue($this->actions->movePosition(35, 400));
    }

    public function testChangeParent() : void
    {
        $this->assertTrue($this->actions->changeParent(35, null));
    }

    public function testDeletePull() : void
    {
        $this->assertTrue($this->actions->delete(35, true));
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
}
