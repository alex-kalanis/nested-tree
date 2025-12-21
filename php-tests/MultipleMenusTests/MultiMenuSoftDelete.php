<?php

namespace Tests\MultipleMenusTests;

use kalanis\nested_tree\Support;

class MultiMenuSoftDelete extends Support\SoftDelete
{
    public string $columnName = 'deleted';
}
