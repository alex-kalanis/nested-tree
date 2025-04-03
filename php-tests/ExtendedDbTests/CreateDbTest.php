<?php

namespace Tests\ExtendedDbTests;

use kalanis\nested_tree\Support;

class CreateDbTest extends AbstractExtendedDbTests
{
    /**
     * Test get the data tree. This is usually for retrieve all the data with less condition.
     *
     * The `getTreeWithChildren()` method will be called automatically while run the `rebuild()` method.
     */
    public function testGetTreeWithChildren() : void
    {
        $this->dataRefill();
        $result = $this->nestedSet->getTreeWithChildren();
        $this->assertCount(33, $result);

        $option1 = new Support\Options();
        $condition1 = new Support\Conditions();
        $condition1->query = 't_type = :t_type';
        $condition1->bindValues = [':t_type' => 'category'];
        $option1->where = $condition1;
        $result = $this->nestedSet->getTreeWithChildren($option1);
        $this->assertCount(21, $result);

        $option2 = new Support\Options();
        $condition2 = new Support\Conditions();
        $condition2->query = '(t_type = :t_type AND t_status = :t_status)';
        $condition2->bindValues = [':t_type' => 'category', ':t_status' => 1];
        $option2->where = $condition2;
        $result = $this->nestedSet->getTreeWithChildren($option2);
        $this->assertCount(17, $result);
    }

    /**
     * Test `rebuild()` method. This method must run after `INSERT`, `UPDATE`, or `DELETE` the data in database.<br>
     * It may have to run if the `level`, `left`, `right` data is incorrect.
     */
    public function testRebuild() : void
    {
        $this->dataRefill();
        // rebuild where t_type = category.
        $option1 = new Support\Options();
        $condition1 = new Support\Conditions();
        $condition1->query = 't_type = :t_type';
        $condition1->bindValues = [':t_type' => 'category'];
        $option1->where = $condition1;
        $this->nestedSet->rebuild($option1);

        // get the result of 3
        $sql = 'SELECT * FROM `' . $this->settings->tableName . '` WHERE `' . $this->settings->idColumnName . '` = :id';
        $Sth = $this->database->prepare($sql);
        $Sth->bindValue(':id', 3, \PDO::PARAM_INT);
        $Sth->execute();
        $row = $Sth->fetch();
        $Sth->closeCursor();
        unset($sql, $Sth);
        // assert value must be matched.
        $this->assertEquals(40, $row[$this->settings->rightColumnName]);
        $this->assertEquals(1, $row[$this->settings->levelColumnName]);

        // get the result of 10
        $sql = 'SELECT * FROM `' . $this->settings->tableName . '` WHERE `' . $this->settings->idColumnName . '` = :id';
        $Sth = $this->database->prepare($sql);
        $Sth->bindValue(':id', 10, \PDO::PARAM_INT);
        $Sth->execute();
        $row = $Sth->fetch();
        $Sth->closeCursor();
        unset($sql, $Sth);
        // assert value must be matched.
        $this->assertEquals(13, $row[$this->settings->leftColumnName]);
        $this->assertEquals(14, $row[$this->settings->rightColumnName]);
        $this->assertEquals(3, $row[$this->settings->levelColumnName]);

        // get the result of 29 (t_type = product_category and it did not yet rebuilt).
        $sql = 'SELECT * FROM `' . $this->settings->tableName . '` WHERE `' . $this->settings->idColumnName . '` = :id';
        $Sth = $this->database->prepare($sql);
        $Sth->bindValue(':id', 29, \PDO::PARAM_INT);
        $Sth->execute();
        $row = $Sth->fetch();
        $Sth->closeCursor();
        unset($sql, $Sth);
        // assert value must be matched.
        $this->assertEquals(0, $row[$this->settings->leftColumnName]);
        $this->assertEquals(0, $row[$this->settings->rightColumnName]);
        $this->assertEquals(0, $row[$this->settings->levelColumnName]);
    }

    /**
     * Test get new position, the `position` value will be use before `INSERT` the data to DB.
     */
    public function testGetNewPosition() : void
    {
        $this->dataRefill();

        $condition = new Support\Conditions();
        $condition->query = 't_type = :t_type';
        $condition->bindValues = [':t_type' => 'category'];
        $this->assertEquals(4, $this->nestedSet->getNewPosition(4, $condition));
        $this->assertEquals(4, $this->nestedSet->getNewPosition(16, $condition));
        $this->assertEquals(1, $this->nestedSet->getNewPosition(777, $condition)); // not known
        $this->assertEquals(1, $this->nestedSet->getNewPosition(null, $condition)); // root with unknown

        $condition->bindValues = [':t_type' => 'product-category'];
        $newPosition = $this->nestedSet->getNewPosition(21, $condition);
        $this->assertEquals(5, $newPosition);
    }
}
