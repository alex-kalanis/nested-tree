<?php

namespace Tests\ExtendedDbTests;

use kalanis\nested_tree\Support\Conditions;
use kalanis\nested_tree\Support\Options;

class ListingDbTest extends AbstractExtendedDbTests
{
    /**
     * Test listing the taxonomy data in hierarchy or flatten styles with many options.
     */
    public function testListTaxonomyFull() : void
    {
        $this->dataRefill();
        $this->rebuild();

        // full result test.
        $options = new Options();
        $options->unlimited = true;
        $options->joinChild = true;

        // full result test.
        $result = $this->nestedSet->listNodes($options);

        // assert
        $this->assertEquals(32, $result->count); // with children.
        $this->assertCount(6, $result->items); // only root items because not flatten.
    }

    /**
     * Test listing the taxonomy data in hierarchy or flatten styles with many options.
     */
    public function testListTaxonomyOptions1() : void
    {
        $this->dataRefill();
        $this->rebuild();

        // tests with options.
        $options = new Options();
        $options->unlimited = true;
        $options->joinChild = true;
        $optionsWhere = new Conditions();
        $optionsWhere->query = 'ANY_VALUE(`child`.`t_type`) = :t_type';
        $optionsWhere->bindValues = [':t_type' => 'category'];
        $options->where = $optionsWhere;

        $result = $this->nestedSet->listNodes($options);
        unset($options);
        // assert
        $this->assertEquals(20, $result->count); // with children.
        $this->assertCount(3, $result->items); // only root items because not flatten.
    }

    /**
     * Test listing the taxonomy data in hierarchy or flatten styles with many options.
     */
    public function testListTaxonomyOptions2() : void
    {
        $this->dataRefill();
        $this->rebuild('product-category');

        // tests with options.
        $options = new Options();
        $options->unlimited = true;
        $options->joinChild = true;
        $optionsWhere = new Conditions();
        $optionsWhere->query = 'ANY_VALUE(`child`.`t_type`) = :t_type';
        $optionsWhere->bindValues = [':t_type' => 'product-category'];
        $options->where = $optionsWhere;

        $result = $this->nestedSet->listNodes($options);
        unset($options);
        // assert
        $this->assertEquals(12, $result->count); // with children.
        $this->assertCount(3, $result->items); // only root items because not flatten.
    }

    /**
     * Test listing the taxonomy data in flatten style.
     */
    public function testListTaxonomyFlattenSimple() : void
    {
        $this->dataRefill();
        $this->rebuild();

        // full result test.
        $options = new Options();
        $options->unlimited = true;
        $options->joinChild = true;
        $result = $this->nestedSet->listNodesFlatten($options);
        unset($options);
        // assert
        $this->assertEquals(32, $result->count); // with children.
        $this->assertCount(32, $result->items); // was flatten.
    }

    /**
     * Test listing the taxonomy data in flatten style.
     */
    public function testListTaxonomyFlattenOptions() : void
    {
        $this->dataRefill();
        $this->rebuild('product-category');

        // full result test.
        $options = new Options();
        $options->unlimited = true;
        $options->joinChild = true;
        $optionsConditions = new Conditions();
        $optionsConditions->query = 'ANY_VALUE(`child`.`t_type`) = :t_type';
        $optionsConditions->bindValues = [':t_type' => 'product-category'];
        $options->where = $optionsConditions;

        $result = $this->nestedSet->listNodesFlatten($options);
        unset($options);
        // assert
        $this->assertEquals(12, $result->count); // with children.
        $this->assertCount(12, $result->items); // was flatten.
    }
}
