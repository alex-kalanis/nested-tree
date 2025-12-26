<?php

namespace kalanis\nested_tree\Support;

/**
 * Trait to work with rows - convert them to the node data
 * @property Node $nodeBase
 * @property TableSettings $settings
 */
trait RowsTrait
{
    /**
     * @param array<array<mixed>> $rows
     * @param bool $hasIdAsKey
     * @return array<int, Node>
     */
    protected function fromDbRows(array $rows, bool $hasIdAsKey = true) : array
    {
        $result = [];
        foreach ($rows as &$row) {
            $data = $this->fillDataFromRow($row);
            if ($hasIdAsKey) {
                $result[$data->id] = $data;
            } else {
                $result[] = $data;
            }
        }

        return $result;
    }

    /**
     * @param array<mixed> $row
     * @return Node
     */
    protected function fillDataFromRow(array $row) : Node
    {
        $data = clone $this->nodeBase;
        foreach ($row as $k => $v) {
            if ($this->settings->idColumnName === $k) {
                $data->id = max(0, intval($v));
            } elseif ($this->settings->parentIdColumnName === $k) {
                $data->parentId = is_null($v) && $this->settings->rootIsNull ? null : max(0, intval($v));
            } elseif ($this->settings->levelColumnName === $k) {
                $data->level = max(0, intval($v));
            } elseif ($this->settings->leftColumnName === $k) {
                $data->left = max(0, intval($v));
            } elseif ($this->settings->rightColumnName === $k) {
                $data->right = max(0, intval($v));
            } elseif ($this->settings->positionColumnName === $k) {
                $data->position = max(0, intval($v));
            } else {
                $data->{$k} = strval($v);
            }
        }

        return $data;
    }
}
