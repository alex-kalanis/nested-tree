<?php

namespace Tests\Support;

use kalanis\nested_tree\Support\TableSettings;

/**
 * @property \PDO $database
 * @property TableSettings $settings
 */
trait DumpTrait
{
    /**
     * Dump trait to get data from DB
     */
    protected function getDbDump(string $conditions = '1=1', array $values = []) : array
    {
        $sql = 'SELECT * FROM `' . $this->settings->tableName . '` WHERE ' . $conditions;
        $Sth = $this->database->prepare($sql);
        foreach ($values as $key => $value) {
            $Sth->bindValue($key, $values);
        }

        $Sth->execute();
        $rows = $Sth->fetchAll(\PDO::FETCH_ASSOC);
        $Sth->closeCursor();

        return $rows;
    }
}
