<?php

namespace Tests\SimpleDbTests;

use kalanis\nested_tree\Support;
use Tests\Support\NamesAsArrayTrait;

class DeleteDbTest extends AbstractSimpleDbTests
{
    use NamesAsArrayTrait;

    /**
     * Test delete selected item with its children.
     */
    public function testDeleteWithChildren() : void
    {
        $this->dataRefill();
        $this->nestedSet->rebuild();

        // test to make sure that the data has been built correctly.
        $options = new Support\Options();
        $options->currentId = 16;
        $options->unlimited = true;
        $options->additionalColumns = ['ANY_VALUE(parent.name)', 'ANY_VALUE(child.name) as name'];
        $result = $this->nestedSet->getNodesWithChildren($options);

        $resultNames = $this->getNamesAsArray($result);
        $this->assertCount(4, $result);
        $this->assertEmpty(array_diff(['3.2', '3.2.1', '3.2.2', '3.2.3'], $resultNames));
        $this->assertEquals(count($result), count($resultNames));

        $options = new Support\Options();
        $options->unlimited = true;
        $resultBeforeDelete = $this->nestedSet->listNodesFlatten($options);
        $deleteResult = $this->nestedSet->deleteWithChildren(16);
        $this->nestedSet->rebuild();
        $resultAfterDelete = $this->nestedSet->listNodesFlatten($options);

        $this->assertCount(20, $resultBeforeDelete);
        $this->assertSame(4, $deleteResult);
        $this->assertCount(16, $resultAfterDelete);
    }

    public function testDeletePullUpChildren() : void
    {
        $this->dataRefill();
        $this->nestedSet->rebuild();

        $options = new Support\Options();
        $options->unlimited = true;
        $options->additionalColumns = ['parent.name'];
        $resultBeforeDelete = $this->nestedSet->listNodesFlatten($options);
        $deleteResult = $this->nestedSet->deletePullUpChildren(9);
        $this->nestedSet->rebuild();
        $resultAfterDelete = $this->nestedSet->listNodesFlatten($options);

        $this->assertCount(20, $resultBeforeDelete);
        $this->assertTrue($deleteResult);
        $this->assertCount(19, $resultAfterDelete);
    }

    public function testDeleteConditions() : void
    {
        $this->dataRefill();
        $this->nestedSet->rebuild();

        $options = new Support\Options();
        $options->unlimited = true;
        $options->additionalColumns = ['parent.name'];
        $conditions = new Support\Conditions();
        $conditions->query = 'parent.name LIKE :my_name';
        $conditions->bindValues = [':my_name' => '14.1.%'];
        $options->where = $conditions;
        $resultBeforeDelete = $this->nestedSet->listNodesFlatten($options);
        $deleteResult = $this->nestedSet->deletePullUpChildren(9, $options);
        $this->nestedSet->rebuild();
        $resultAfterDelete = $this->nestedSet->listNodesFlatten($options);

        $this->assertCount(0, $resultBeforeDelete);
        $this->assertTrue($deleteResult);
        $this->assertCount(0, $resultAfterDelete);
    }
}
