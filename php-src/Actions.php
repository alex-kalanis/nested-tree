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
        // Po tehle se kurevsky jde! Tahle vec tam dost chybi!
        $this->nestedSet->rebuild($this->getOptions());
    }

    public function create(Support\Node $node) : void
    {
        $this->nestedSet->add($node, $this->getOptions());
        $this->nestedSet->rebuild($this->getOptions());
    }

    public function update(Support\Node $node) : void
    {
        $this->nestedSet->update($node);
        $this->nestedSet->rebuild($this->getOptions());
    }

    public function movePosition(int $nodeId, ?int $newPosition) : void
    {
        $this->nestedSet->move($nodeId, $newPosition);
        $this->nestedSet->rebuild($this->getOptions());
    }

    public function changeParent(int $nodeId, ?int $newParent) : void
    {
        $this->nestedSet->changeParent($nodeId, $newParent);
        $this->nestedSet->rebuild($this->getOptions());
    }

    public function delete(int $nodeId, bool $childrenUp = false) : bool
    {
        $result = $childrenUp
            ? $this->nestedSet->deletePullUpChildren($nodeId, $this->getOptions())
            : $this->nestedSet->deleteWithChildren($nodeId, $this->getOptions())
        ;
        $this->nestedSet->rebuild($this->getOptions());

        return $result;
    }

    protected function getOptions() : Support\Options
    {
        return $this->options ?? new Support\Options();
    }
}
