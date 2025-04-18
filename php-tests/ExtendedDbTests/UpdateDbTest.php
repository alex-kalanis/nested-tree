<?php

namespace Tests\ExtendedDbTests;

use kalanis\nested_tree\Support;

class UpdateDbTest extends AbstractExtendedDbTests
{
    /**
     * Test check that selected parent is same level or under its children.
     *
     * This will be use to check before update the data.
     *
     * @return void
     */
    public function testIsNodeParentInItsChildren() : void
    {
        $this->dataRefill();
        $categoryOption = new Support\Options();
        $categoryCondition = new Support\Conditions();
        $categoryCondition->query = '`t_type` = :type';
        $categoryCondition->bindValues = [':type' => 'category'];
        $categoryOption->additionalColumns = ['node.`t_type`'];
        $categoryOption->where = $categoryCondition;
        $this->nestedSet->rebuild($categoryOption);

        $categoryCondition->query = 'node.`t_type` = :type';
        $this->assertFalse($this->nestedSet->isNewParentOutsideCurrentNodeTree(
            9, // 2.1.1 (9)
            12, // shouldn't under 2.1.1.1 (12)
            $categoryOption
        ));
        $this->assertTrue($this->nestedSet->isNewParentOutsideCurrentNodeTree(
            9, // 2.1.1 (9)
            20, // is okay to be under 3.2.3 (20 - will be new parent)
            $categoryOption
        ));

        $this->assertTrue($this->nestedSet->isNewParentOutsideCurrentNodeTree(
            19, // 3.2.2
            16, // is under 3.2
            $categoryOption
        ));

        $categoryCondition->bindValues = [':type' => 'product-category'];
        // test search not found because incorrect `t_type` (must be return `true`).
        $this->assertFalse($this->nestedSet->isNewParentOutsideCurrentNodeTree(
            19,
            16,
            $categoryOption
        ));

        $this->assertFalse($this->nestedSet->isNewParentOutsideCurrentNodeTree(
            21, // camera (21)
            25, // shouldn't under nikon (25)
            $categoryOption
        ));
        // test multiple level children.
        $this->assertTrue($this->nestedSet->isNewParentOutsideCurrentNodeTree(
            30, // dell
            22, // is under desktop (28) > and desktop is under computer (22)
            $categoryOption
        ));
    }
}
