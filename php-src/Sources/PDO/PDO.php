<?php

namespace kalanis\nested_tree\Sources\PDO;

use kalanis\nested_tree\Sources\SourceInterface;
use kalanis\nested_tree\Support;
use PDO as base;

abstract class PDO implements SourceInterface
{
    use Support\ColumnsTrait;
    use Support\RowsTrait;

    public function __construct(
        protected readonly base $pdo,
        protected readonly Support\Node $nodeBase,
        protected readonly Support\TableSettings $settings,
    ) {
    }
}
