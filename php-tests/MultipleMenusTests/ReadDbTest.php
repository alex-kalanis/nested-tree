<?php

namespace Tests\MultipleMenusTests;

use kalanis\nested_tree\Support\Conditions;
use kalanis\nested_tree\Support\Options;
use Tests\Support\NamesAsArrayTrait;

class ReadDbTest extends AbstractMultipleMenusTests
{
    use NamesAsArrayTrait;

    /**
     * Test get selected item (or start from selected item but skip it) and look up until root.
     */
    public function testGetTaxonomyWithParentsId() : void
    {
        $this->dataRefill();
        $this->rebuild(1);

        // test filter taxonomy id option.
        $options = new Options();
        $options->currentId = 13;
        $result = $this->nestedSet->getNodesWithParents($options);

        $this->assertCount(4, $result); // how deep is it
    }

    /**
     * Test get selected item (or start from selected item but skip it) and look up until root.
     */
    public function testGetTaxonomyWithParentsWhere() : void
    {
        $this->dataRefill();
        $this->rebuild(1);

        // test where option.
        $options = new Options();
        $optionsWhere = new Conditions();
        $optionsWhere->query = '`node`.`menu_id` = :menu_id';
        $optionsWhere->bindValues = [':menu_id' => 1];
        $options->where = $optionsWhere;
        $options->joinChild = true;
        $result = $this->nestedSet->getNodesWithParents($options);

        $this->assertCount(16, $result);
    }

    /**
     * Test get selected item and retrieve its children.
     */
    public function testGetTaxonomyWithChildren() : void
    {
        $this->dataRefill();
        $this->rebuild(1);

        // test filter taxonomy id option.
        $options = new Options();
        $options->currentId = 4;
        $options->joinChild = true;
        $optionsWhere = new Conditions();
        $optionsWhere->query = '`child`.`menu_id` = :menu_id';
        $optionsWhere->bindValues =  [':menu_id' => 1];
        $options->where = $optionsWhere;
        $result = $this->nestedSet->getNodesWithChildren($options);

        $this->assertCount(6, $result);
    }
}
