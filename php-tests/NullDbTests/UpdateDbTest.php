<?php

namespace Tests\NullDbTests;

class UpdateDbTest extends AbstractNullDbTests
{
    /**
     * Test check that selected parent is same level or under its children.
     *
     * This will be use to check before update the data.
     */
    public function testIsNodeParentInItsChildren() : void
    {
        $this->dataRefill();
        $this->nestedSet->rebuild();
        $this->assertFalse($this->nestedSet->isNewParentOutsideCurrentNodeTree(9, 12));
        $this->assertFalse($this->nestedSet->isNewParentOutsideCurrentNodeTree(9, 14));
        $this->assertTrue($this->nestedSet->isNewParentOutsideCurrentNodeTree(9, 4));
        $this->assertTrue($this->nestedSet->isNewParentOutsideCurrentNodeTree(9, 7));
        $this->assertTrue($this->nestedSet->isNewParentOutsideCurrentNodeTree(9, 20));
    }
}
