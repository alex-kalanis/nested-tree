<?php

namespace kalanis\nested_tree\Support;

use IteratorAggregate;

/**
 * @implements IteratorAggregate<int, Node>
 */
class Result implements \Countable, \IteratorAggregate
{
    public int $count = 0;

    /**
     * @var Node[]
     */
    public array $items = [];

    public function count() : int
    {
        return $this->count;
    }

    public function getIterator() : \Traversable
    {
        return new \ArrayIterator($this->items);
    }
}
