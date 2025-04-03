<?php

namespace Tests\SimpleDbTests;

use kalanis\nested_tree\Support\Options;
use kalanis\nested_tree\Support\Search;
use Tests\Support\NamesAsArrayTrait;

class ReadDbTest extends AbstractSimpleDbTests
{
    use NamesAsArrayTrait;

    /**
     * Test get selected item (or start from selected item but skip it) and look up until root.
     */
    public function testGetTaxonomyWithParentsId() : void
    {
        $this->dataRefill();
        $this->nestedSet->rebuild();

        // test filter taxonomy id option.
        $options = new Options();
        $options->currentId = 13;
        $options->additionalColumns = ['parent.name'];
        $result = $this->nestedSet->getNodesWithParents($options);

        $resultNames = $this->getNamesAsArray($result);
        $this->assertCount(4, $result);
        $this->assertEquals(['Root 2', '2.1', '2.1.1', '2.1.1.2'], $resultNames);
        $this->assertEquals(count($result), count($resultNames));
    }

    /**
     * Test get selected item (or start from selected item but skip it) and look up until root.
     */
    public function testGetTaxonomyWithParentsIdSkip() : void
    {
        $this->dataRefill();
        $this->nestedSet->rebuild();

        // test filter taxonomy id option.
        $options = new Options();
        $options->currentId = 13;
        $options->skipCurrent = true;
        $options->additionalColumns = ['parent.name'];
        $result = $this->nestedSet->getNodesWithParents($options);

        $resultNames = $this->getNamesAsArray($result);
        $this->assertCount(3, $result);
        $this->assertEquals(['Root 2', '2.1', '2.1.1'], $resultNames);
        $this->assertEquals(count($result), count($resultNames));
    }

    /**
     * Test get selected item (or start from selected item but skip it) and look up until root.
     */
    public function testGetTaxonomyWithParentsSearch() : void
    {
        $this->dataRefill();
        $this->nestedSet->rebuild();

        // test filter taxonomy id option.
        $options = new Options();
        $options->additionalColumns = ['ANY_VALUE(parent.name) AS name'];
        $optionsSearch = new Search();
        $optionsSearch->value = '3.2';
        $optionsSearch->columns = ['name'];
        $options->search = $optionsSearch;
        $result = $this->nestedSet->getNodesWithParents($options);

        $resultNames = $this->getNamesAsArray($result);
        $this->assertCount(5, $result);
        $this->assertEmpty(array_diff(['Root 3', '3.2', '3.2.1', '3.2.2'], $resultNames));
        $this->assertEquals(count($result), count($resultNames));
    }

    /**
     * Test get selected item and retrieve its children.
     */
    public function testGetTaxonomyWithChildrenId() : void
    {
        $this->dataRefill();
        $this->nestedSet->rebuild();

        // test filter taxonomy id option.
        $options = new Options();
        $options->currentId = 3;
        $options->joinChild = true;
        $options->additionalColumns = ['ANY_VALUE(child.name) AS name'];
        $result = $this->nestedSet->getNodesWithChildren($options);

        $resultNames = $this->getNamesAsArray($result);
        $this->assertCount(7, $result);
        $this->assertEmpty(array_diff(['Root 3', '3.1', '3.2', '3.2.1', '3.2.2', '3.2.3', '3.3'], $resultNames));
        $this->assertEquals(count($result), count($resultNames));
    }

    /**
     * Test get selected item and retrieve its children.
     */
    public function testGetTaxonomyWithChildrenIdUnlimited() : void
    {
        $this->dataRefill();
        $this->nestedSet->rebuild();

        // test filter taxonomy id with options.
        $options = new Options();
        $options->currentId = 16;
        $options->skipCurrent = true;
        $options->joinChild = true;
        $options->additionalColumns = ['ANY_VALUE(child.name) AS name'];
        $result = $this->nestedSet->getNodesWithChildren($options);

        $resultNames = $this->getNamesAsArray($result);
        $this->assertCount(4, $result);
        $this->assertEmpty(array_diff(['3.2', '3.2.1', '3.2.2', '3.2.3'], $resultNames));
        $this->assertEquals(count($result), count($resultNames));
    }
}
