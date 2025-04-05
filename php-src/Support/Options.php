<?php

namespace kalanis\nested_tree\Support;

/**
 * Options for reading data from DB
 * Note: not everything is need in each used method
 */
class Options
{
    /**
     * @var int|null
     * The filter taxonomy ID
     */
    public ?int $currentId = null;

    /**
     * @var int|null
     * The filter parent ID.
     */
    public ?int $parentId = null;

    /**
     * @var Search|null
     * The search object
     */
    public ?Search $search = null;

    /**
     * @var array<string|int>
     * The taxonomy ID to look with `IN()` database function.
     * The array values must be integer, example `array(1,3,4,5)`.
     * This will flatten the result even when `listFlattened` was not set.
     */
    public array $filterIdBy = [];

    /**
     * @var Conditions|null
     * The custom where conditions.
     */
    public ?Conditions $where = null;

    /**
     * @var array<int, string>
     */
    public array $additionalColumns = [];

    /**
     * @var bool
     * Set to `true` to do not sort order the result.
     */
    public bool $noSortOrder = false;

    /**
     * @var bool
     * Set to `true` to do not limit the result.
     */
    public bool $unlimited = false;

    /**
     * @var int|null
     * The offset in the query
     */
    public ?int $offset = null;

    /**
     * @var int|null
     * The limit number in the query.
     */
    public ?int $limit = null;

    /**
     * @var bool
     * Set to `true` to list the result flatten.
     */
    public bool $listFlattened = false;

    /**
     * @var bool
     * Set to `true` to skip currently selected item.
     */
    public bool $skipCurrent = false;

    /**
     * @var bool
     * Set to `true` to explicit join of children
     */
    public bool $joinChild = false;

    public function __clone()
    {
        $this->search = is_null($this->search) ? null : clone $this->search;
        $this->where = is_null($this->where) ? null : clone $this->where;
    }
}
