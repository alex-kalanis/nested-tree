<?php

namespace kalanis\nested_tree\Sources\PDO;

use kalanis\nested_tree\Sources\SourceInterface;
use kalanis\nested_tree\Support;
use PDO as base;

abstract class PDO implements SourceInterface
{
    use Support\ColumnsTrait;

    public function __construct(
        protected readonly base $pdo,
        protected readonly Support\Node $nodeBase,
        protected readonly Support\TableSettings $settings,
    ) {
    }

    /**
     * @param array<array<mixed>> $rows
     * @return array<int, Support\Node>
     */
    protected function fromDbRows(array $rows) : array
    {
        $result = [];
        foreach ($rows as &$row) {
            $data = $this->fillDataFromRow($row);
            $result[$data->id] = $data;
        }

        return $result;
    }

    /**
     * @param array<mixed> $row
     * @return Support\Node
     */
    protected function fillDataFromRow(array $row) : Support\Node
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
