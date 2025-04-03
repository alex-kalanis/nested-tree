<?php

namespace Tests;

use kalanis\nested_tree\Support;
use PHPUnit\Framework\TestCase;

/**
 * Class CommonTestClass
 * The structure for mocking and configuration seems so complicated, but it's necessary to let it be totally idiot-proof
 */
class CommonTestClass extends TestCase
{
    public function getPdo() : \PDO
    {
        $host = getenv('NESTED_TREE_MYSQL_DB_HOST') ?: 'localhost';
        $port = getenv('NESTED_TREE_MYSQL_DB_PORT') ?: 3306;
        $db = getenv('NESTED_TREE_MYSQL_DB_NAME') ?: 'nested_tree';
        $user = getenv('NESTED_TREE_MYSQL_DB_USER') ?: 'root';
        $pass = getenv('NESTED_TREE_MYSQL_DB_PASS') ?: 'there-is-nothing-available';

        $connection = new \PDO(
            sprintf(
                'mysql:host=%s;port=%d;dbname=%s',
                $host,
                $port,
                $db,
            ),
            $user,
            $pass,
        );

        $connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

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
}
