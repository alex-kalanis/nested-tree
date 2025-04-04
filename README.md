# Nested Tree

![Build Status](https://github.com/alex-kalanis/nested-tree/actions/workflows/code_checks.yml/badge.svg)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/alex-kalanis/nested-tree/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/alex-kalanis/nested-tree/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/alex-kalanis/nested-tree/v/stable.svg?v=1)](https://packagist.org/packages/alex-kalanis/nested-tree)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.1-8892BF.svg)](https://php.net/)
[![Downloads](https://img.shields.io/packagist/dt/alex-kalanis/nested-tree.svg?v1)](https://packagist.org/packages/alex-kalanis/nested-tree)
[![License](https://poser.pugx.org/alex-kalanis/nested-tree/license.svg?v=1)](https://packagist.org/packages/alex-kalanis/nested-tree)
[![Code Coverage](https://scrutinizer-ci.com/g/alex-kalanis/nested-tree/badges/coverage.png?b=master&v=1)](https://scrutinizer-ci.com/g/alex-kalanis/nested-tree/?branch=master)

Library to work with Nested tree set. Rework of [Rundiz's library](https://github.com/Rundiz/nested-set).

## About

The PHP nested set model for create/read/update/delete the tree data structure (hierarchy).

It uses combination of nested and adjacency models. Also uses primary and foreign keys
as simple integers, so you cannot use composed keys.

## Requirements

* PHP version 8.1 or higher

## Basic usage

Basic usage is about to set ```Support\TableSettings``` with corresponding columns in
your app and then pass it into the libraries. Next you need to extend ```Support\Node```
to describe your real table. For specific things like more columns you also need to set
```Support\Options``` class. The most code is then set via DI. You can see that in tests.

```php
class MyNodes extends \kalanis\nested_tree\Support\Node
{
    public ?string $my_column = null;
}

class MyTable extends \kalanis\nested_tree\Support\TableSettings
{
    public string $tableName = 'my_menu';
}

$myNodes = new MyNodes();
$myTable = new MyTable();

// this is usually set via DI
$actions = new \kalanis\nested_tree\Actions(
    new \kalanis\nested_tree\NestedSet(
        new \kalanis\nested_tree\Sources\PDO\MySql(
            $yourPDOconnection,
            $myNodes,
            $myTable,
        ),
        $myNodes,
        $myTable,
    ),
);

// now work:

// repair the whole structure
$actions->fixStructure();

// move node in row
$actions->movePosition(25, 3);

// change parent node for the one chosen
$actions->changeParent(13, 7);
```

## DB structure

This library need following columns or their equivalents on affected table:

- `id` - PK on table, cannot be zero, because that is the same as top/root node
- `parent_id` - FK to PK on the same table, can be null for top - depend on you DB schema,
  cannot be zero, because that is the same as top/root node
- `left` - left leaf of nested tree
- `right` - right leaf of nested tree
- `level` - how deep is it
- `position` - where it is against others in the level group

Each column can be set to different name by change in `TableSettings` class.

## Running tests

The `master` branch includes unit tests.
If you just want to check that everything is working as expected, executing the unit tests is enough.

* `phpunit` - runs unit and functional tests

## Caveats

You must choose if you go with MariaDB or MySQL, because default implementation uses
function [ANY_VALUE()](https://jira.mariadb.org/browse/MDEV-10426) to go around the
problem with non-standard ```GROUP_BY``` implementation. So you may either use MySQL 5.7+
or disable ```ONLY_FULL_GROUP_BY``` directive in MariaDB. Or write custom query source
which itself will go around this particular problem.
