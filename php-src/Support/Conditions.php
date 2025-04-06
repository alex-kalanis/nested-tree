<?php

namespace kalanis\nested_tree\Support;

class Conditions
{
    /**
     * @var string
     * <pre>
     *      '(`parent`.`columnName` = :value1 AND `parent`.`columnName2` = :value2)'
     * </pre>
     */
    public string $query = '';

    /**
     * @var array<int|string, int|float|string|null>
     * <pre>
     *      array(':value1' => 'lookup value 1', ':value2' => 'lookup value2')
     * </pre>
     */
    public array $bindValues = [];
}
