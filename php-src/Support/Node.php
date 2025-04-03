<?php

namespace kalanis\nested_tree\Support;

class Node extends \stdClass
{
    /**
     * @var int Current node ID
     */
    public int $id = 0;

    /**
     * @var int|null Parent node ID
     */
    public ?int $parentId = null;

    /**
     * @var int Level to get
     */
    public int $level = 0;

    /**
     * @var int Left value
     */
    public int $left = 0;

    /**
     * @var int Right value
     */
    public int $right = 0;

    /**
     * @var int Position inside peers
     */
    public int $position = 0;

    /**
     * @var int[] Children of node - ids
     */
    public array $childrenIds = [];

    /**
     * @var Node[] Children of node - nodes
     */
    public array $childrenNodes = [];
}
