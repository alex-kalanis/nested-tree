<?php

namespace Tests\BasicTests;

use kalanis\nested_tree\Support\ColumnsTrait;
use kalanis\nested_tree\Support\TableSettings;

class XColumn
{
    use ColumnsTrait;

    public function translate(TableSettings $settings, string $s) : string
    {
        return $this->translateColumn($settings, $s);
    }
}
