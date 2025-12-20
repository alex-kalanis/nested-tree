<?php

namespace kalanis\nested_tree\Support;

class SoftDelete
{
    /**
     * Name of column which represents soft deletion
     * @var string
     */
    public string $columnName = '';

    /**
     * Value which represents allowed state
     * @var string|int
     */
    public string|int $canUse = 0;

    /**
     * Value which represents deleted state
     * @var string|int
     */
    public string|int $isDeleted = 1;

    /**
     * Value which represents bind key
     * @var string
     */
    public string $bindAsKey = ':soft_delete';

}
