<?php

namespace kalanis\nested_tree\Support;

/**
 * Trait to work with names of properties within Node class which shall not be added / updated via this package.
 */
trait ColumnsTrait
{
    protected function translateColumn(TableSettings $settings, string $name) : string
    {
        return match ($name) {
            'id' => $settings->idColumnName,
            'parentId' => $settings->parentIdColumnName,
            'level' => $settings->levelColumnName,
            'left' => $settings->leftColumnName,
            'right' => $settings->rightColumnName,
            'position' => $settings->positionColumnName,
            default => $name,
        };
    }

    protected function isColumnNameFromBasic(string $name) : bool
    {
        return in_array($name, [
            'id',
            'childrenIds',
            'childrenNodes',
        ]);
    }

    protected function isColumnNameFromTree(string $name) : bool
    {
        return in_array($name, [
            'parentId',
            'left',
            'right',
            'level',
            'position',
        ]);
    }
}
