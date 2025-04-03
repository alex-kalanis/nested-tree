# Nested Tree

![Build Status](https://github.com/alex-kalanis/nested-tree/actions/workflows/code_checks.yml/badge.svg)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/alex-kalanis/nested-tree/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/alex-kalanis/nested-tree/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/alex-kalanis/nested-tree/v/stable.svg?v=1)](https://packagist.org/packages/alex-kalanis/nested-tree)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.4-8892BF.svg)](https://php.net/)
[![Downloads](https://img.shields.io/packagist/dt/alex-kalanis/nested-tree.svg?v1)](https://packagist.org/packages/alex-kalanis/nested-tree)
[![License](https://poser.pugx.org/alex-kalanis/nested-tree/license.svg?v=1)](https://packagist.org/packages/alex-kalanis/nested-tree)
[![Code Coverage](https://scrutinizer-ci.com/g/alex-kalanis/nested-tree/badges/coverage.png?b=master&v=1)](https://scrutinizer-ci.com/g/alex-kalanis/nested-tree/?branch=master)

Library to work with Nested tree set. Rework of [Rundiz's library](https://github.com/Rundiz/nested-set).

## About

The PHP nested set model for create/read/update/delete the tree data structure (hierarchy).

It uses combination of nested and adjacency models.

## Requirements

* PHP version 8.1 or higher

## DB structure

This library need following columns or their equivalents on affected table:

- `id` - PK on table
- `parent_id` - FK to PK on the same table, can be null for top
- `left` - left leaf
- `right` - right leaf
- `level` - how deep is it
- `position` - where it is against others in the level group

Each column can be set to different name by change in `TableSettings` class.

## Running tests

The `master` branch includes unit tests.
If you just want to check that everything is working as expected, executing the unit tests is enough.

* `phpunit` - runs unit and functional tests
