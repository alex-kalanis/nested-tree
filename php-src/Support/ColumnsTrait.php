<?php

namespace kalanis\nested_tree\Support;

/**
 * Trait to work with names of properties within Node class which shall not be added / updated via this package.
 */
trait ColumnsTrait
{
    protected function translateColumn(TableSettings $settings, string $name) : ?string
    {
        return match ($name) {
            'id' => $settings->idColumnName,
            'parentId' => $settings->parentIdColumnName,
            'level' => $settings->levelColumnName,
            'left' => $settings->leftColumnName,
            'right' => $settings->rightColumnName,
            'position' => $settings->positionColumnName,
            default => $this->getNameBasedOnExtraSettings($settings, $name),
        };
    }

    /**
     * @param TableSettings $settings
     * @param string $name
     * @return string|null
     *
     * Okay, time to be ready for Reflection.
     * When there is settings property with name the same as the entry's then get the value it contains and return it
     */
    private function getNameBasedOnExtraSettings(TableSettings $settings, string $name): ?string
    {
        if (property_exists($settings, $name)) {
            if (is_null($settings->{$name})) {
                return null;
            }
            return strval($settings->{$name});
        }
        return $name;
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
