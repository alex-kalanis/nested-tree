<?php

namespace kalanis\nested_tree;

class Actions
{
    protected ?Support\Options $options = null;

    public function __construct(
        protected readonly NestedSet $nestedSet,
    ) {
    }

    public function setExtraOptions(?Support\Options $options) : void
    {
        $this->options = $options;
    }

    public function fixStructure() : void
    {
        $this->nestedSet->rebuild($this->getOptions());
    }

    /**
     * Create new node in connected table
     *
     * @param Support\Node $node
     * @return Support\Node
     */
    public function create(Support\Node $node) : Support\Node
    {
        $result = $this->nestedSet->add($node, $this->getOptions());
        $this->nestedSet->rebuild($this->getOptions());

        return $result;
    }

    /**
     * Update node's data
     *
     * @param Support\Node $node
     * @return bool
     */
    public function update(Support\Node $node) : bool
    {
        $result = $this->nestedSet->update($node);
        $this->nestedSet->rebuild($this->getOptions());

        return $result;
    }

    /**
     * Move node to the position
     *
     * @param int<1, max> $nodeId
     * @param int<1, max> $newPosition
     * @return bool
     */
    public function movePosition(int $nodeId, int $newPosition) : bool
    {
        $result = $this->nestedSet->move($nodeId, $newPosition);
        $this->nestedSet->rebuild($this->getOptions());

        return $result;
    }

    /**
     * Change node's parent
     *
     * @param int<1, max> $nodeId
     * @param int<0, max>|null $newParent
     * @return bool
     */
    public function changeParent(int $nodeId, ?int $newParent) : bool
    {
        $result = $this->nestedSet->changeParent($nodeId, $newParent);
        $this->nestedSet->rebuild($this->getOptions());

        return $result;
    }

    /**
     * Delete node, with or without its children
     *
     * @param int<1, max> $nodeId
     * @param bool $childrenUp children will be reconnected to upper entry or deleted too
     * @return bool
     */
    public function delete(int $nodeId, bool $childrenUp = false) : bool
    {
        $result = $childrenUp
            ? $this->nestedSet->deletePullUpChildren($nodeId, $this->getOptions())
            : !empty($this->nestedSet->deleteWithChildren($nodeId, $this->getOptions()))
        ;
        $this->nestedSet->rebuild($this->getOptions());

        return $result;
    }

    protected function getOptions() : Support\Options
    {
        return $this->options ?? new Support\Options();
    }
}
