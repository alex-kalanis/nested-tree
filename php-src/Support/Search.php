<?php

namespace kalanis\nested_tree\Support;

class Search
{
    /**
     * @var string search string
     */
    public string $value = '';

    /**
     * @var array<string, string|int>
     * <pre>
     *  array('name', 'column2', 'column3')
     * </pre>
     */
    public array $columns = [];
}
