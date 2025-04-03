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
        $this->actions->create($node);
        $this->assertTrue(true);
    }

    public function testUpdate() : void
    {
        $node = MockNode::create(25);
        $this->actions->update($node);
        $this->assertTrue(true);
    }

    public function testMove() : void
    {
        $this->actions->movePosition(35, 400);
        $this->assertTrue(true);
    }

    public function testChangeParent() : void
    {
        $this->actions->changeParent(35, 700);
        $this->assertTrue(true);
    }

    public function testDeletePull() : void
    {
        $this->actions->delete(35, true);
        $this->assertTrue(true);
    }

    public function testDeleteAll() : void
    {
        $this->actions->delete(35);
        $this->assertTrue(true);
    }

    public function testOptions() : void
    {
        $opt = new Options();
        $this->actions->setExtraOptions($opt);
        $this->actions->fixStructure();
        $this->assertTrue(true);
    }
}
