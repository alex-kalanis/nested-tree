<?php

namespace Tests\BasicTests;

use kalanis\nested_tree\Sources\SourceInterface;
use kalanis\nested_tree\Support;
use Tests\MockNode;

class MockDataSource implements SourceInterface
{
    public function selectLastPosition(?int $parentNodeId, ?Support\Conditions $where) : ?int
    {
        return empty($parentNodeId) ? 0 : 3;
    }

    public function selectSimple(Support\Options $options) : array
    {
        return [
            MockNode::create(1, 0, 1, 2, 1, 1),
            MockNode::create(2, 0, 3, 4, 1, 2),
            MockNode::create(3, 0, 5, 6, 1, 3),
            MockNode::create(4, 0, 7, 8, 1, 4),
        ];
    }

    public function selectParent(int $nodeId, Support\Options $options) : ?int
    {
        return !empty($nodeId) ? $nodeId : null;
    }

    public function selectCount(Support\Options $options) : int
    {
        return 75;
    }

    public function selectLimited(Support\Options $options) : array
    {
        if ($options->skipCurrent) {
            return [];
        }

        return [
            MockNode::create(1, 0, 1, 2, 1, 1),
            MockNode::create(2, 0, 3, 4, 1, 2),
            MockNode::create(3, 0, 5, 6, 1, 3),
            MockNode::create(4, 0, 7, 8, 1, 4),
        ];
    }

    public function selectWithParents(Support\Options $options) : array
    {
        return [
            MockNode::create(1, 0, 1, 2, 1, 1),
            MockNode::create(2, 0, 3, 4, 1, 2),
            MockNode::create(3, 0, 5, 6, 1, 3),
            MockNode::create(4, 0, 7, 8, 1, 4),
        ];
    }

    public function add(Support\Node $node, ?Support\Conditions $where) : Support\Node
    {
        return $node;
    }

    public function updateData(Support\Node $node, ?Support\Conditions $where) : bool
    {
        return !empty($node->id);
    }

    public function updateNodeParent(int $nodeId, ?int $parentId, int $position, ?Support\Conditions $where) : bool
    {
        return !empty($nodeId);
    }

    public function updateChildrenParent(int $nodeId, ?int $parentId, ?Support\Conditions $where) : bool
    {
        return !empty($parentId);
    }

    public function updateLeftRightPos(Support\Node $row, ?Support\Conditions $where) : bool
    {
        return !empty($row->parentId);
    }

    public function makeHole(?int $parentId, int $position, bool $moveUp, ?Support\Conditions $where = null) : bool
    {
        return !empty($parentId);
    }

    public function deleteSolo(int $nodeId, ?Support\Conditions $where) : bool
    {
        return !empty($nodeId);
    }

    public function deleteWithChildren(Support\Node $row, ?Support\Conditions $where) : bool
    {
        return empty($row->id);
    }
}
