<?php

namespace kalanis\nested_tree\Sources\PDO;

use kalanis\nested_tree\Sources\SourceInterface;
use kalanis\nested_tree\Support\Node;
use kalanis\nested_tree\Support\Options;
use kalanis\nested_tree\Support\TableSettings;
use PDO as base;

abstract class PDO implements SourceInterface
{
    public function __construct(
        protected readonly base $pdo,
        protected readonly Node $nodeBase,
        protected readonly TableSettings $settings,
    ) {
    }

    /**
     * @param array<array<mixed>> $rows
     * @return array<int, Node>
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
     * @return Node
     */
    protected function fillDataFromRow(array $row) : Node
    {
        $data = clone $this->nodeBase;
        foreach ($row as $k => $v) {
            if ($this->settings->idColumnName === $k) {
                $data->id = intval($v);
            } elseif ($this->settings->parentIdColumnName === $k) {
                $data->parentId = is_null($v) && $this->settings->rootIsNull ? null : intval($v);
            } elseif ($this->settings->levelColumnName === $k) {
                $data->level = intval($v);
            } elseif ($this->settings->leftColumnName === $k) {
                $data->left = intval($v);
            } elseif ($this->settings->rightColumnName === $k) {
                $data->right = intval($v);
            } elseif ($this->settings->positionColumnName === $k) {
                $data->position = intval($v);
            } else {
                $data->{$k} = strval($v);
            }
        }

        return $data;
    }

    /**
     * Bind taxonomy values for listTaxonomy() method.
     *
     * @internal This method was called from `listTaxonomy()`.
     * @param \PDOStatement $Sth PDO statement class object.
     * @param Options $options Available options
     */
    protected function listTaxonomyBindValues(\PDOStatement $Sth, Options $options) : void
    {
        if (!is_null($options->currentId)) {
            $Sth->bindValue(':filter_taxonomy_id', $options->currentId, base::PARAM_INT);
        }

        if (!is_null($options->parentId)) {
            $Sth->bindValue(':filter_parent_id', $options->parentId, base::PARAM_INT);
        }

        if (!empty($options->search->value)) {
            $Sth->bindValue(':search', '%' . $options->search->value . '%', base::PARAM_STR);
        }
        if (!empty($options->where->bindValues)) {
            foreach ($options->where->bindValues as $placeholder => $value) {
                $Sth->bindValue($placeholder, $value);
            }
        }
    }
}
