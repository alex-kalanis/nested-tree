<?php

namespace Tests;

use kalanis\nested_tree\Support;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Class CommonTestClass
 * The structure for mocking and configuration seems so complicated, but it's necessary to let it be totally idiot-proof
 * @requires extension PDO
 * @requires extension pdo_mysql
 */
class CommonTestClass extends TestCase
{
    public function getPdo() : PDO
    {
        $host = getenv('NESTED_TREE_MYSQL_DB_HOST');
        $host = false !== $host ? strval($host) : '127.0.0.1';

        $port = getenv('NESTED_TREE_MYSQL_DB_PORT');
        $port = false !== $port ? intval($port) : 3306;

        $user = getenv('NESTED_TREE_MYSQL_DB_USER');
        $user = false !== $user ? strval($user) : 'testing';

        $pass = getenv('NESTED_TREE_MYSQL_DB_PASS');
        $pass = false !== $pass ? strval($pass) : 'there-is-nothing-available';

        $db = getenv('NESTED_TREE_MYSQL_DB_NAME');
        $db = false !== $db ? strval($db) : 'nested_tree';

        $connection = new PDO(
            sprintf(
                'mysql:host=%s;port=%d;dbname=%s',
                $host,
                $port,
                $db,
            ),
            $user,
            $pass,
        );

        $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $connection->exec('SET NAMES utf8;');

        return $connection;
    }

    protected function compareNodes(
        Support\Node $storedNode,
        Support\Node $mockNode,
        bool $alsoChildren = false,
        bool $checkLeftRight = false,
        bool $checkPosition = false,
    ) : bool {
        return (
            $storedNode->id === $mockNode->id
            && $storedNode->parentId === $mockNode->parentId
            && $storedNode->name === $mockNode->name
            && ($alsoChildren ? ($this->sortIds($storedNode->childrenIds) === $this->sortIds($mockNode->childrenIds)) : true)
            && ($checkLeftRight ? ($storedNode->left === $mockNode->left) : true)
            && ($checkLeftRight ? ($storedNode->right === $mockNode->right) : true)
            && ($checkPosition ? ($storedNode->position === $mockNode->position) : true)
        );
    }

    protected function sortIds(array $data) : array
    {
        sort($data);

        return array_values($data);
    }

    protected function getRow(PDO $pdo, Support\TableSettings $settings, int $rowId): array
    {
        $sql = 'SELECT * FROM `' . $settings->tableName . '` WHERE `' . $settings->idColumnName . '` = :id';
        $Sth = $pdo->prepare($sql);
        $Sth->bindValue(':id', $rowId, \PDO::PARAM_INT);
        $Sth->execute();
        $row = $Sth->fetch();
        $Sth->closeCursor();
        unset($sql, $Sth);
        return $row;
    }
}
