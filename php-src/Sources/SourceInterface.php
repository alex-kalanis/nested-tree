<?php

namespace kalanis\nested_tree\Sources;

use kalanis\nested_tree\Support;

interface SourceInterface
{
    /**
     * @param Support\Node|null $parentNode
     * @param Support\Conditions|null $where
     * @return int<1, max>|null
     */
    public function selectLastPosition(?Support\Node $parentNode, ?Support\Conditions $where) : ?int;

    /**
     * @return Support\Node[]
     */
    public function selectSimple(Support\Options $options) : array;

    /**
     * @param Support\Node $node
     * @param Support\Options $options
     * @return Support\Node|null
     */
    public function selectParent(Support\Node $node, Support\Options $options) : ?Support\Node;

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
     * @param Support\Node $node
     * @param Support\Node|null $parent
     * @param int<0, max> $position
     * @param Support\Conditions|null $where
     * @return bool
     */
    public function updateNodeParent(Support\Node $node, ?Support\Node $parent, int $position, ?Support\Conditions $where) : bool;

    /**
     * @param Support\Node $node
     * @param Support\Node|null $parent
     * @param Support\Conditions|null $where
     * @return bool
     */
    public function updateChildrenParent(Support\Node $node, ?Support\Node $parent, ?Support\Conditions $where) : bool;

    public function updateLeftRightPos(Support\Node $row, ?Support\Conditions $where) : bool;

    /**
     * @param Support\Node|null $parent
     * @param int<0, max> $position
     * @param bool $moveUp
     * @param Support\Conditions|null $where
     * @return bool
     */
    public function makeHole(?Support\Node $parent, int $position, bool $moveUp, ?Support\Conditions $where = null) : bool;

    /**
     * @param Support\Node $node
     * @param Support\Conditions|null $where
     * @return bool
     */
    public function deleteSolo(Support\Node $node, ?Support\Conditions $where) : bool;

    public function deleteWithChildren(Support\Node $row, ?Support\Conditions $where) : bool;
}
