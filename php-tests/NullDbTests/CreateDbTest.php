<?php

namespace Tests\NullDbTests;

class CreateDbTest extends AbstractNullDbTests
{
    /**
     * Test get new position, the `position` value will be use before `INSERT` the data to DB.
     */
    public function testGetNewPosition() : void
    {
        $this->dataRefill();
        $this->assertEquals(4, $this->nestedSet->getNewPosition(4));
        $this->assertEquals(6, $this->nestedSet->getNewPosition(2));
    }

    /**
     * Test get the data tree. This is usually for retrieve all the data with less condition.
     *
     * The `getTreeWithChildren()` method will be called automatically while run the `rebuild()` method.
     */
    public function testGetTreeWithChildren() : void
    {
        $this->dataRefill();
        $this->nestedSet->rebuild();
        $result = $this->nestedSet->getTreeWithChildren();
        $this->assertCount(21, $result);
    }

    /**
     * Test `rebuild()` method. This method must run after `INSERT`, `UPDATE`, or `DELETE` the data in database.<br>
     * It may have to run if the `level`, `left`, `right` data is incorrect.
     */
    public function testRebuild() : void
    {
        $this->dataRefill();
        $this->nestedSet->rebuild();
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
    }
}
