<?php

namespace kalanis\nested_tree\Support;

class Node extends \stdClass
{
    /**
     * @var int<0, max> Current node ID
     */
    public int $id = 0;

    /**
     * @var int<0, max>|null Parent node ID
     */
    public ?int $parentId = null;

    /**
     * @var int<0, max> Level to get
     */
    public int $level = 0;

    /**
     * @var int<0, max> Left leaf value
     */
    public int $left = 0;

    /**
     * @var int<0, max> Right leaf value
     */
    public int $right = 0;

    /**
     * @var int<0, max> Position inside peers
     */
    public int $position = 0;

    /**
     * @var array<int<0, max>> Children of node - ids
     */
    public array $childrenIds = [];

    /**
     * @var array<int<0, max>, Node> Children of node - nodes
     */
    public array $childrenNodes = [];
}
