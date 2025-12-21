<?php

namespace Tests\BasicTests;

use kalanis\nested_tree\Sources\SourceInterface;
use kalanis\nested_tree\Support;
use Tests\MockNode;

class MockDataSource implements SourceInterface
{
    /** @var MockNode[] */
    protected array $nodes = [];

    /**
     * @param MockNode[] $nodes
     */
    public function __construct(array $nodes = [])
    {
        $this->nodes = $nodes ?: [
            MockNode::create(1, 0, 1, 2, 1, 1),
            MockNode::create(2, 0, 3, 4, 1, 2),
            MockNode::create(3, 0, 5, 6, 1, 3),
            MockNode::create(4, 0, 7, 8, 1, 4),
        ];
    }

    public function selectLastPosition(?Support\Node $parentNode, ?Support\Conditions $where) : ?int
    {
        return empty($parentNode) || empty($parentNode->id) ? 0 : 3;
    }

    public function selectSimple(Support\Options $options) : array
    {
        $filtered = !is_null($options->currentId) ? array_filter($this->nodes, fn (MockNode $n) : bool => $options->currentId === $n->id) : $this->nodes;
        $filtered = !is_null($options->parentId) ? array_filter($filtered, fn (MockNode $n) : bool => $options->parentId === $n->parentId) : $filtered;

        return $filtered;
    }

    public function selectParent(Support\Node $node, Support\Options $options) : ?Support\Node
    {
        $filtered = !is_null($node->parentId) ? array_filter($this->nodes, fn (MockNode $n) : bool => $node->parentId === $n->parentId) : $this->nodes;

        return !empty($filtered) ? reset($filtered) : null;
    }

    public function selectCount(Support\Options $options) : int
    {
        $filtered = !is_null($options->currentId) ? array_filter($this->nodes, fn (MockNode $n) : bool => $options->currentId === $n->id) : $this->nodes;
        $filtered = !is_null($options->parentId) ? array_filter($filtered, fn (MockNode $n) : bool => $options->parentId === $n->parentId) : $filtered;

        return count($filtered);
    }

    public function selectLimited(Support\Options $options) : array
    {
        if ($options->skipCurrent) {
            return [];
        }

        $filtered = !is_null($options->currentId) ? array_filter($this->nodes, fn (MockNode $n) : bool => $options->currentId === $n->id) : $this->nodes;
        $filtered = !is_null($options->parentId) ? array_filter($filtered, fn (MockNode $n) : bool => $options->parentId === $n->parentId) : $filtered;

        return $filtered;
    }

    public function selectWithParents(Support\Options $options) : array
    {
        return $this->nodes;
    }

    public function add(Support\Node $node, ?Support\Conditions $where) : Support\Node
    {
        return $node;
    }

    public function updateData(Support\Node $node, ?Support\Conditions $where) : bool
    {
        return !empty($node->id);
    }

    public function updateNodeParent(Support\Node $node, ?Support\Node $parent, int $position, ?Support\Conditions $where) : bool
    {
        return !empty($node->id);
    }

    public function updateChildrenParent(Support\Node $node, ?Support\Node $parent, ?Support\Conditions $where) : bool
    {
        return !empty($parent) && !empty($parent->id);
    }

    public function updateLeftRightPos(Support\Node $row, ?Support\Conditions $where) : bool
    {
        return !empty($row->parentId);
    }

    public function makeHole(?Support\Node $parent, int $position, bool $moveUp, ?Support\Conditions $where = null) : bool
    {
        return !empty($parent) && !empty($parent->id);
    }

    public function deleteSolo(Support\Node $node, ?Support\Conditions $where) : bool
    {
        return !empty($node->id);
    }
}
