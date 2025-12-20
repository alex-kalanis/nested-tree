<?php

namespace kalanis\nested_tree\Support;

class TableSettings
{
    /**
     * @var string Table name.
     */
    public string $tableName = 'taxonomy';

    /**
     * @var string Identity column name in the table. This column should be primary key.
     */
    public string $idColumnName = 'id';

    /**
     * @var string Parent ID that refer to the ID column.
     */
    public string $parentIdColumnName = 'parent_id';

    /**
     * @var string Left column name in the table.
     */
    public string $leftColumnName = 'left';

    /**
     * @var string Right column name in the table.
     */
    public string $rightColumnName = 'right';

    /**
     * @var string Level column name in the table. The root item will be start at level 1, the sub items of the root will be increase their level.
     */
    public string $levelColumnName = 'level';

    /**
     * @var string Position column name in the table. The position will be start at 1 for each level, it means the different level always start at 1.
     */
    public string $positionColumnName = 'position';

    /**
     * @var SoftDelete|null Configuration of soft deletion. "Null" means not used.
     */
    public ?SoftDelete $softDelete = null;

    /**
     * @var bool Is root as null or integer zero
     */
    public bool $rootIsNull = false;
}
