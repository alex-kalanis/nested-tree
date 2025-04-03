<?php

namespace kalanis\nested_tree\Sources;

use kalanis\nested_tree\Support;

interface SourceInterface
{
    public function selectLastPosition(?int $parentNodeId, ?Support\Conditions $where) : ?int;

    /**
     * @return Support\Node[]
     */
    public function selectSimple(Support\Options $options) : array;

    public function selectParent(int $nodeId, Support\Options $options) : ?int;

    /**
     * @param Support\Options $options
     * @return int<0, max>
     */
    public function selectCount(Support\Options $options) : int;

    /**
     * @return Support\Node[]
     */
    public function selectLimited(Support\Options $options) : array;

    /**
     * @return Support\Node[]
     */
    public function selectWithParents(Support\Options $options) : array;

    public function add(Support\Node $node, ?Support\Conditions $where) : Support\Node;

    public function updateData(Support\Node $node, ?Support\Conditions $where) : bool;

    public function updateChildrenParent(?int $parentId, int $nodeId, ?Support\Conditions $where) : bool;

    public function updateLeftRightPos(Support\Node $row, ?Support\Conditions $where) : bool;

    public function deleteSolo(int $nodeId, ?Support\Conditions $where) : bool;

    public function deleteWithChildren(Support\Node $row, ?Support\Conditions $where) : bool;
}
