<?php

namespace kalanis\nested_tree\Sources;

use kalanis\nested_tree\Support;

interface SourceInterface
{
    /**
     * @param int<0, max>|null $parentNodeId
     * @param Support\Conditions|null $where
     * @return int<1, max>|null
     */
    public function selectLastPosition(?int $parentNodeId, ?Support\Conditions $where) : ?int;

    /**
     * @return Support\Node[]
     */
    public function selectSimple(Support\Options $options) : array;

    /**
     * @param int<1, max> $nodeId
     * @param Support\Options $options
     * @return int<0, max>|null
     */
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

    /**
     * @param int<0, max> $nodeId
     * @param int<0, max>|null $parentId
     * @param int<0, max> $position
     * @param Support\Conditions|null $where
     * @return bool
     */
    public function updateNodeParent(int $nodeId, ?int $parentId, int $position, ?Support\Conditions $where) : bool;

    /**
     * @param int<0, max> $nodeId
     * @param int<0, max>|null $parentId
     * @param Support\Conditions|null $where
     * @return bool
     */
    public function updateChildrenParent(int $nodeId, ?int $parentId, ?Support\Conditions $where) : bool;

    public function updateLeftRightPos(Support\Node $row, ?Support\Conditions $where) : bool;

    /**
     * @param int<0, max>|null $parentId
     * @param int<0, max> $position
     * @param bool $moveUp
     * @param Support\Conditions|null $where
     * @return bool
     */
    public function makeHole(?int $parentId, int $position, bool $moveUp, ?Support\Conditions $where = null) : bool;

    /**
     * @param int<0, max> $nodeId
     * @param Support\Conditions|null $where
     * @return bool
     */
    public function deleteSolo(int $nodeId, ?Support\Conditions $where) : bool;

    public function deleteWithChildren(Support\Node $row, ?Support\Conditions $where) : bool;
}
