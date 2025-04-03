<?php

namespace Tests\ExtendedDbTests;

use kalanis\nested_tree\Support\Conditions;
use kalanis\nested_tree\Support\Options;
use Tests\Support\NamesAsArrayTrait;

class ReadDbTest extends AbstractExtendedDbTests
{
    use NamesAsArrayTrait;

    /**
     * Test get selected item (or start from selected item but skip it) and look up until root.
     */
    public function testGetTaxonomyWithParentsId() : void
    {
        $this->dataRefill();
        $this->rebuild();

        // test filter taxonomy id option.
        $options = new Options();
        $options->currentId = 13;
        $options->additionalColumns = ['parent.t_name'];
        $result = $this->nestedSet->getNodesWithParents($options);

        $resultNames = $this->getNamesAsArray($result, 't_name');
        $this->assertCount(4, $result);
        $this->assertEquals(['Root 2', '2.1', '2.1.1', '2.1.1.2'], $resultNames);
        $this->assertEquals(count($result), count($resultNames));
    }

    /**
     * Test get selected item (or start from selected item but skip it) and look up until root.
     */
    public function testGetTaxonomyWithParentsWhere() : void
    {
        $this->dataRefill();
        $this->rebuild();

        // test where option.
        $options = new Options();
        $optionsWhere = new Conditions();
        $optionsWhere->query = '`node`.`t_status` = :t_status AND `node`.`t_type` = :t_type';
        $optionsWhere->bindValues = [':t_status' => 0, ':t_type' => 'category'];
        $options->where = $optionsWhere;
        $options->joinChild = true;
        $options->additionalColumns = ['ANY_VALUE(node.t_name)', 'ANY_VALUE(parent.t_name) AS t_name'];
        $result = $this->nestedSet->getNodesWithParents($options);

        $resultNames = $this->getNamesAsArray($result, 't_name');
        $this->assertCount(9, $result);
        $this->assertEquals(['Root 2', '2.1', '2.1.1', '2.1.1.3', '2.3', '2.4', 'Root 3', '3.2', '3.2.3'], $resultNames);
        $this->assertEquals(count($result), count($resultNames));
    }

    /**
     * Test get selected item and retrieve its children.
     */
    public function testGetTaxonomyWithChildren() : void
    {
        $this->dataRefill();
        $this->rebuild();

        // test filter taxonomy id option.
        $options = new Options();
        $options->currentId = 4;
        $options->joinChild = true;
        $options->additionalColumns = ['ANY_VALUE(child.t_name) AS t_name', 'ANY_VALUE(parent.t_name)'];
        $optionsWhere = new Conditions();
        $optionsWhere->query = '`t_type` = :t_type';
        $optionsWhere->bindValues =  [':t_type' => 'category'];
        $options->where = $optionsWhere;
        $optionsWhere->query = '`child`.`t_type` = :t_type';
        $optionsWhere->bindValues =  [':t_type' => 'category'];
        $result = $this->nestedSet->getNodesWithChildren($options);

        $resultNames = $this->getNamesAsArray($result, 't_name');
        $this->assertCount(7, $result);
        $this->assertEmpty(array_diff(['2.1', '2.1.1', '2.1.1.1', '2.1.1.2', '2.1.1.3', '2.1.2', '2.1.3'], $resultNames));
        $this->assertEquals(count($result), count($resultNames));
    }
}
