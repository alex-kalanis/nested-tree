<?php

namespace kalanis\nested_tree\Sources\PDO;

use kalanis\nested_tree\Support;
use PDO as base_pdo;

class MySql extends PDO
{
    public function selectLastPosition(?int $parentNodeId, ?Support\Conditions $where = null) : ?int
    {
        $sql = 'SELECT `' . $this->settings->idColumnName . '`, `' . $this->settings->parentIdColumnName . '`, `' . $this->settings->positionColumnName . '`'
            . ' FROM `' . $this->settings->tableName . '`'
            . ' WHERE `' . $this->settings->parentIdColumnName . '` = :filter_parent_id';
        $sql .= $this->addCustomQuery($where, '');
        $sql .= ' ORDER BY `' . $this->settings->positionColumnName . '` DESC';

        $Sth = $this->pdo->prepare($sql);

        $this->bindParentId($parentNodeId, $Sth);
        $this->bindCustomQuery($where, $Sth);

        $Sth->execute();
        /** @var array<string|int, mixed>|false $row */
        $row = $Sth->fetch();
        $Sth->closeCursor();

        if (!empty($row)) {
            return is_null($row[$this->settings->positionColumnName]) ? null : max(1, intval($row[$this->settings->positionColumnName]));
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function selectSimple(Support\Options $options) : array
    {
        $sql = 'SELECT node.`' . $this->settings->idColumnName . '`'
            . ', node.`' . $this->settings->parentIdColumnName . '`'
            . ', node.`' . $this->settings->leftColumnName . '`'
            . ', node.`' . $this->settings->rightColumnName . '`'
            . ', node.`' . $this->settings->levelColumnName . '`'
            . ', node.`' . $this->settings->positionColumnName . '`'
        ;
        $sql .= $this->addAdditionalColumns($options, 'node.');
        $sql .= ' FROM `' . $this->settings->tableName . '` node';
        $sql .= ' WHERE 1';
        $sql .= $this->addCurrentId($options, 'node.');
        $sql .= $this->addCustomQuery($options->where, 'node.');
        $sql .= ' ORDER BY `' . $this->settings->positionColumnName . '` ASC';

        $Sth = $this->pdo->prepare($sql);
        $this->bindCurrentId($options->currentId, $Sth);
        $this->bindCustomQuery($options->where, $Sth);

        $Sth->execute();
        $result = $Sth->fetchAll();

        return $result ? $this->fromDbRows($result) : [];
    }

    /**
     * {@inheritdoc}
     */
    public function selectParent(int $nodeId, Support\Options $options) : ?int
    {
        $sql = 'SELECT node.`' . $this->settings->idColumnName . '`'
            . ', node.`' . $this->settings->parentIdColumnName . '`'
            . ', node.`' . $this->settings->leftColumnName . '`'
            . ', node.`' . $this->settings->rightColumnName . '`'
            . ', node.`' . $this->settings->levelColumnName . '`'
            . ', node.`' . $this->settings->positionColumnName . '`'
        ;
        $sql .= $this->addAdditionalColumns($options, 'node.');
        $sql .= ' FROM `' . $this->settings->tableName . '` node';
        $sql .= ' WHERE `' . $this->settings->idColumnName . '` = :filter_taxonomy_id';
        $sql .= $this->addCustomQuery($options->where, 'node.');

        $Sth = $this->pdo->prepare($sql);
        $this->bindCurrentId($nodeId, $Sth);
        $this->bindCustomQuery($options->where, $Sth);

        $Sth->execute();
        /** @var array<string|int, mixed>|false $row */
        $row = $Sth->fetch();
        $parent_id = $row ? $row[$this->settings->parentIdColumnName] : null;
        $Sth->closeCursor();

        return (empty($parent_id)) ? ($this->settings->rootIsNull ? null : 0) : max(0, intval($parent_id));
    }

    /**
     * {@inheritdoc}
     */
    public function selectCount(Support\Options $options) : int
    {
        $sql = 'SELECT ';
        $sql .= ' ANY_VALUE(`parent`.`' . $this->settings->idColumnName . '`)';
        $sql .= ', ANY_VALUE(`parent`.`' . $this->settings->parentIdColumnName . '`)';
        if (!is_null($options->currentId) || !is_null($options->parentId) || !empty($options->search) || $options->joinChild) {
            $sql .= ', ANY_VALUE(`child`.`' . $this->settings->idColumnName . '`) AS `' . $this->settings->idColumnName . '`';
            $sql .= ', ANY_VALUE(`child`.`' . $this->settings->parentIdColumnName . '`) AS `' . $this->settings->parentIdColumnName . '`';
            $sql .= ', ANY_VALUE(`child`.`' . $this->settings->leftColumnName . '`) AS `' . $this->settings->leftColumnName . '`';
        }
        $sql .= $this->addAdditionalColumns($options);
        $sql .= ' FROM `' . $this->settings->tableName . '` AS `parent`';

        if (!is_null($options->currentId) || !is_null($options->parentId) || !empty($options->search) || $options->joinChild) {
            // if there is filter or search, there must be inner join to select all of filtered children.
            $sql .= ' INNER JOIN `' . $this->settings->tableName . '` AS `child`';
            $sql .= ' ON `child`.`' . $this->settings->leftColumnName . '` BETWEEN `parent`.`' . $this->settings->leftColumnName . '` AND `parent`.`' . $this->settings->rightColumnName . '`';
        }

        $sql .= ' WHERE 1';
        $sql .= $this->addFilterBy($options);
        $sql .= $this->addCurrentId($options, '`parent`.');
        $sql .= $this->addParentId($options, '`parent`.');
        $sql .= $this->addSearch($options, '`parent`.');
        $sql .= $this->addCustomQuery($options->where);
        $sql .= $this->addSorting($options);

        // prepare and get 'total' count.
        $Sth = $this->pdo->prepare($sql);
        $this->bindCurrentId($options->currentId, $Sth);
        $this->bindParentId($options->parentId, $Sth, true);
        $this->bindSearch($options, $Sth);
        $this->bindCustomQuery($options->where, $Sth);

        $Sth->execute();
        $result = $Sth->fetchAll();

        // "a bit" hardcore - get all lines and then count them
        return $result ? count($result) : 0;
    }

    public function selectLimited(Support\Options $options) : array
    {
        $sql = 'SELECT';
        $sql .= ' ANY_VALUE(`parent`.`' . $this->settings->idColumnName . '`)';
        $sql .= ', ANY_VALUE(`parent`.`' . $this->settings->parentIdColumnName . '`)';
        $sql .= ', ANY_VALUE(`parent`.`' . $this->settings->leftColumnName . '`)';
        if (!is_null($options->currentId) || !is_null($options->parentId) || !empty($options->search) || $options->joinChild) {
            $sql .= ', ANY_VALUE(`child`.`' . $this->settings->idColumnName . '`) AS `' . $this->settings->idColumnName . '`';
            $sql .= ', ANY_VALUE(`child`.`' . $this->settings->parentIdColumnName . '`) AS `' . $this->settings->parentIdColumnName . '`';
            $sql .= ', ANY_VALUE(`child`.`' . $this->settings->leftColumnName . '`) AS `' . $this->settings->leftColumnName . '`';
            $sql .= ', ANY_VALUE(`child`.`' . $this->settings->rightColumnName . '`) AS `' . $this->settings->rightColumnName . '`';
            $sql .= ', ANY_VALUE(`child`.`' . $this->settings->levelColumnName . '`) AS `' . $this->settings->levelColumnName . '`';
            $sql .= ', ANY_VALUE(`child`.`' . $this->settings->positionColumnName . '`) AS `' . $this->settings->positionColumnName . '`';
        }
        $sql .= $this->addAdditionalColumns($options);
        $sql .= ' FROM `' . $this->settings->tableName . '` AS `parent`';

        if (!is_null($options->currentId) || !is_null($options->parentId) || !empty($options->search) || $options->joinChild) {
            // if there is filter or search, there must be inner join to select all of filtered children.
            $sql .= ' INNER JOIN `' . $this->settings->tableName . '` AS `child`';
            $sql .= ' ON `child`.`' . $this->settings->leftColumnName . '` BETWEEN `parent`.`' . $this->settings->leftColumnName . '` AND `parent`.`' . $this->settings->rightColumnName . '`';
        }

        $sql .= ' WHERE 1';
        $sql .= $this->addFilterBy($options);
        $sql .= $this->addCurrentId($options, '`parent`.');
        $sql .= $this->addParentId($options, '`parent`.');
        $sql .= $this->addSearch($options, '`parent`.');
        $sql .= $this->addCustomQuery($options->where);
        $sql .= $this->addSorting($options);

        // re-create query and prepare. second step is for set limit and fetch all items.
        if (!$options->unlimited) {
            if (empty($options->offset)) {
                $options->offset = 0;
            }
            if (empty($options->limit) || (10000 < $options->limit)) {
                $options->limit = 20;
            }

            $sql .= ' LIMIT ' . $options->offset . ', ' . $options->limit;
        }

        $Sth = $this->pdo->prepare($sql);
        $this->bindCurrentId($options->currentId, $Sth);
        $this->bindParentId($options->parentId, $Sth, true);
        $this->bindSearch($options, $Sth);
        $this->bindCustomQuery($options->where, $Sth);

        $Sth->execute();
        $result = $Sth->fetchAll();

        return $result ? $this->fromDbRows($result) : [];
    }

    /**
     * {@inheritdoc}
     */
    public function selectWithParents(Support\Options $options) : array
    {
        $sql = 'SELECT';
        $sql .= ' ANY_VALUE(`parent`.`' . $this->settings->idColumnName . '`) AS `' . $this->settings->idColumnName . '`';
        $sql .= ', ANY_VALUE(`parent`.`' . $this->settings->parentIdColumnName . '`) AS `' . $this->settings->parentIdColumnName . '`';
        $sql .= ', ANY_VALUE(`parent`.`' . $this->settings->leftColumnName . '`) AS `' . $this->settings->leftColumnName . '`';
        $sql .= ', ANY_VALUE(`parent`.`' . $this->settings->rightColumnName . '`) AS `' . $this->settings->rightColumnName . '`';
        $sql .= ', ANY_VALUE(`parent`.`' . $this->settings->levelColumnName . '`) AS `' . $this->settings->levelColumnName . '`';
        $sql .= ', ANY_VALUE(`parent`.`' . $this->settings->positionColumnName . '`) AS `' . $this->settings->positionColumnName . '`';
        $sql .= $this->addAdditionalColumns($options);
        $sql .= ' FROM `' . $this->settings->tableName . '` AS `node`,';
        $sql .= ' `' . $this->settings->tableName . '` AS `parent`';
        $sql .= ' WHERE';
        $sql .= ' (`node`.`' . $this->settings->leftColumnName . '` BETWEEN `parent`.`' . $this->settings->leftColumnName . '` AND `parent`.`' . $this->settings->rightColumnName . '`)';
        $sql .= $this->addCurrentId($options, '`node`.');
        $sql .= $this->addSearch($options, '`node`.');
        $sql .= $this->addCustomQuery($options->where);
        $sql .= ' GROUP BY `parent`.`' . $this->settings->idColumnName . '`';
        $sql .= ' ORDER BY `parent`.`' . $this->settings->leftColumnName . '`';

        $Sth = $this->pdo->prepare($sql);
        $this->bindCurrentId($options->currentId, $Sth);
        $this->bindSearch($options, $Sth);
        $this->bindCustomQuery($options->where, $Sth);

        $Sth->execute();
        $result = $Sth->fetchAll();
        $Sth->closeCursor();

        if (empty($result)) {
            return [];
        }
        if ($options->skipCurrent) {
            unset($result[count($result)-1]);
        }

        return $this->fromDbRows($result);
    }

    public function add(Support\Node $node, ?Support\Conditions $where = null) : Support\Node
    {
        // Insert itself
        $sql = 'INSERT INTO `' . $this->settings->tableName . '`';
        $lookup = [];
        $pairs = [];
        foreach ((array) $node as $column => $value) {
            if (!is_numeric($column) && !$this->isColumnNameFromBasic($column)) {
                $translateColumn = $this->translateColumn($this->settings, $column);
                $lookup['`' . $translateColumn . '`'] = ':' . $translateColumn;
                $pairs[':' . $translateColumn] = $value;
            }
        }
        $sql .= '(' . implode(',', array_keys($lookup)) . ')';
        $sql .= 'VALUES (' . implode(',', array_keys($pairs)) . ')';

        $Sth = $this->pdo->prepare($sql);

        foreach ($pairs as $column => $value) {
            $Sth->bindValue($column, $value);
        }

        $execute = $Sth->execute();
        if (!$execute) {
            // @codeCoverageIgnoreStart
            // when this happens it is problem with DB, not with library
            throw new \RuntimeException('Cannot save!');
        }
        // @codeCoverageIgnoreEnd
        $Sth->closeCursor();

        // Now get it back
        $sql = 'SELECT * FROM `' . $this->settings->tableName . '` WHERE 1';
        foreach ($lookup as $column => $bind) {
            $sql .= ' AND ' . $column . ' = ' . $bind;
        }
        $Lth = $this->pdo->prepare($sql);
        foreach ($pairs as $column => $value) {
            $Lth->bindValue($column, $value);
        }

        $Lth->execute();
        /** @var array<string|int, mixed>|false $row */
        $row = $Lth->fetch();
        $node = $row ? $this->fillDataFromRow($row) : null;
        $Lth->closeCursor();

        if (is_null($node)) {
            // @codeCoverageIgnoreStart
            // when this happens it is problem with DB, not with library
            throw new \RuntimeException('Node not found in database');
        }
        // @codeCoverageIgnoreEnd

        return $node;
    }

    public function updateData(Support\Node $node, ?Support\Conditions $where = null) : bool
    {
        $sql = 'UPDATE `' . $this->settings->tableName . '`';
        $sql .= ' SET';
        $pairs = [];
        $lookup = [];
        foreach ((array) $node as $column => $value) {
            if (
                !is_numeric($column)
                && !$this->isColumnNameFromBasic($column)
                && !$this->isColumnNameFromTree($column)
                && !is_null($value)
            ) {
                $translateColumn = $this->translateColumn($this->settings, $column);
                $lookup[] = '`' . $translateColumn . '` = :' . $translateColumn;
                $pairs[':' . $translateColumn] = $value;
            }
        }
        $sql .= implode(',', $lookup);
        $sql .= ' WHERE `' . $this->settings->idColumnName . '` = :id';

        $sql .= $this->addCustomQuery($where, '');
        $Sth = $this->pdo->prepare($sql);

        $Sth->bindValue(':id', $node->id, base_pdo::PARAM_INT);
        foreach ($pairs as $column => $value) {
            $Sth->bindValue($column, $value);
        }
        $this->bindCustomQuery($where, $Sth);

        $execute = $Sth->execute();
        $Sth->closeCursor();

        return $execute;
    }

    /**
     * {@inheritdoc}
     */
    public function updateNodeParent(int $nodeId, ?int $parentId, int $position, ?Support\Conditions $where = null) : bool
    {
        $sql = 'UPDATE `' . $this->settings->tableName . '`';
        $sql .= ' SET `' . $this->settings->parentIdColumnName . '` = :filter_parent_id';
        $sql .= ' , `' . $this->settings->positionColumnName . '` = :position';
        $sql .= ' WHERE `' . $this->settings->idColumnName . '` = :filter_taxonomy_id';
        $sql .= $this->addCustomQuery($where, '');

        $Sth = $this->pdo->prepare($sql);
        $this->bindParentId($parentId, $Sth);
        $Sth->bindValue(':position', $position, base_pdo::PARAM_INT);
        $this->bindCurrentId($nodeId, $Sth);
        $this->bindCustomQuery($where, $Sth);

        $execute = $Sth->execute();
        $Sth->closeCursor();

        return $execute;
    }

    /**
     * {@inheritdoc}
     */
    public function updateChildrenParent(int $nodeId, ?int $parentId, ?Support\Conditions $where = null) : bool
    {
        $sql = 'UPDATE `' . $this->settings->tableName . '`';
        $sql .= ' SET `' . $this->settings->parentIdColumnName . '` = :filter_parent_id';
        $sql .= ' WHERE `' . $this->settings->parentIdColumnName . '` = :filter_taxonomy_id';
        $sql .= $this->addCustomQuery($where, '');

        $Sth = $this->pdo->prepare($sql);
        $this->bindParentId($parentId, $Sth);
        $this->bindCurrentId($nodeId, $Sth);
        $this->bindCustomQuery($where, $Sth);

        $execute = $Sth->execute();
        $Sth->closeCursor();

        return $execute;
    }

    public function updateLeftRightPos(Support\Node $row, ?Support\Conditions $where = null) : bool
    {
        $sql = 'UPDATE `' . $this->settings->tableName . '`';
        $sql .= ' SET';
        $sql .= ' `' . $this->settings->levelColumnName . '` = :level,';
        $sql .= ' `' . $this->settings->leftColumnName . '` = :left,';
        $sql .= ' `' . $this->settings->rightColumnName . '` = :right,';
        $sql .= ' `' . $this->settings->positionColumnName . '` = :pos';
        $sql .= ' WHERE `' . $this->settings->idColumnName . '` = :id';
        $sql .= $this->addCustomQuery($where, '');

        $Sth = $this->pdo->prepare($sql);
        $Sth->bindValue(':level', $row->level, base_pdo::PARAM_INT);
        $Sth->bindValue(':left', $row->left, base_pdo::PARAM_INT);
        $Sth->bindValue(':right', $row->right, base_pdo::PARAM_INT);
        $Sth->bindValue(':pos', $row->position, base_pdo::PARAM_INT);
        $Sth->bindValue(':id', $row->id, base_pdo::PARAM_INT);
        $this->bindCustomQuery($where, $Sth);

        $execute = $Sth->execute();
        $Sth->closeCursor();

        return $execute;
    }

    /**
     * {@inheritdoc}
     */
    public function makeHole(?int $parentId, int $position, bool $moveUp, ?Support\Conditions $where = null) : bool
    {
        $direction = $moveUp ? '-' : '+';
        $compare = $moveUp ? '<=' : '>=';
        $sql = 'UPDATE `' . $this->settings->tableName . '`';
        $sql .= ' SET `' . $this->settings->positionColumnName . '` = `' . $this->settings->positionColumnName . '` ' . $direction . ' 1';
        $sql .= ' WHERE `' . $this->settings->parentIdColumnName . '` = :filter_parent_id';
        $sql .= ' AND `' . $this->settings->positionColumnName . '` ' . $compare . ' :position';

        $sql .= $this->addCustomQuery($where, '');
        $Sth = $this->pdo->prepare($sql);

        $Sth->bindValue(':position', $position, base_pdo::PARAM_INT);
        $this->bindParentId($parentId, $Sth);
        $this->bindCustomQuery($where, $Sth);

        $execute = $Sth->execute();
        $Sth->closeCursor();

        return $execute;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteSolo(int $nodeId, ?Support\Conditions $where = null) : bool
    {
        // delete the selected taxonomy ID
        $sql = 'DELETE FROM `' . $this->settings->tableName . '` WHERE `' . $this->settings->idColumnName . '` = :filter_taxonomy_id';
        $sql .= $this->addCustomQuery($where, '');
        $Sth = $this->pdo->prepare($sql);

        $this->bindCurrentId($nodeId, $Sth);
        $this->bindCustomQuery($where, $Sth);

        $execute = $Sth->execute();
        $Sth->closeCursor();

        return $execute;
    }

    public function deleteWithChildren(Support\Node $row, ?Support\Conditions $where = null) : bool
    {
        $sql = 'DELETE FROM `' . $this->settings->tableName . '` WHERE `' . $this->settings->idColumnName . '` = :filter_taxonomy_id';
        $sql .= $this->addCustomQuery($where, '');
        $Sth = $this->pdo->prepare($sql);

        $this->bindCurrentId($row->id, $Sth);
        $this->bindCustomQuery($where, $Sth);

        $execute = $Sth->execute();
        $Sth->closeCursor();

        return $execute;
    }

    protected function replaceColumns(string $query, string $byWhat = '') : string
    {
        foreach (['`parent`.', '`child`.', 'parent.', 'child.'] as $toReplace) {
            $query = str_replace($toReplace, $byWhat, $query);
        }

        return $query;
    }

    protected function addAdditionalColumns(Support\Options $options, ?string $replaceName = null) : string
    {
        $sql = '';
        if (!empty($options->additionalColumns)) {
            foreach ($options->additionalColumns as $column) {
                $sql .= ', ' . (!is_null($replaceName) ? $this->replaceColumns($column, $replaceName) : $column);
            }
        }

        return $sql;
    }

    protected function addFilterBy(Support\Options $options) : string
    {
        $sql = '';
        if (!empty($options->filterIdBy)) {
            // Due to IN() and NOT IN() cannot using bindValue directly.
            // read more at http://stackoverflow.com/questions/17746667/php-pdo-for-not-in-query-in-mysql
            // and http://stackoverflow.com/questions/920353/can-i-bind-an-array-to-an-in-condition
            // it is possible to go around that, but it needs a bit more tinkering

            // loop remove non-number for safety.
            foreach ($options->filterIdBy as $key => $eachNodeId) {
                if (!is_numeric($eachNodeId) || intval($eachNodeId) !== intval($eachNodeId)) {
                    unset($options->filterIdBy[$key]);
                }
            }

            // build value for use with `IN()` function. Example: 1,3,4,5.
            $nodeIdIn = implode(',', $options->filterIdBy);
            $sql .= ' AND `parent`.`' . $this->settings->idColumnName . '` IN (' . $nodeIdIn . ')';
        }

        return $sql;
    }

    protected function addCurrentId(Support\Options $options, string $dbPrefix = '') : string
    {
        $sql = '';
        if (!is_null($options->currentId)) {
            $sql .= ' AND ' . $dbPrefix . '`' . $this->settings->idColumnName . '` = :filter_taxonomy_id';
        }

        return $sql;
    }

    protected function addParentId(Support\Options $options, string $dbPrefix = '') : string
    {
        $sql = '';
        if (!is_null($options->parentId)) {
            $sql .= ' AND ' . $dbPrefix . '`' . $this->settings->parentIdColumnName . '` = :filter_parent_id';
        }

        return $sql;
    }

    protected function bindCurrentId(?int $currentId, \PDOStatement $pdo) : void
    {
        if (!is_null($currentId)) {
            $pdo->bindValue(':filter_taxonomy_id', $currentId, base_pdo::PARAM_INT);
        }
    }

    protected function bindParentId(?int $parentId, \PDOStatement $pdo, bool $skipNull = false) : void
    {
        if (is_null($parentId) && !$skipNull) {
            $pdo->bindValue(':filter_parent_id', null, base_pdo::PARAM_NULL);
        } elseif (!is_null($parentId)) {
            $pdo->bindValue(':filter_parent_id', $parentId, base_pdo::PARAM_INT);
        }
    }

    protected function addSearch(Support\Options $options, string $dbPrefix = '') : string
    {
        $sql = '';
        if (
            !empty($options->search->columns)
            && !empty($options->search->value)
        ) {
            $sql .= ' AND (';
            $array_keys = array_keys($options->search->columns);
            $last_array_key = array_pop($array_keys);
            foreach ($options->search->columns as $key => $column) {
                $sql .= $dbPrefix . '`' . $column . '` LIKE :search';
                if ($key !== $last_array_key) {
                    $sql .= ' OR ';
                }
            }
            $sql .= ')';
        }

        return $sql;
    }

    protected function bindSearch(Support\Options $options, \PDOStatement $pdo) : void
    {
        if (!empty($options->search->value)) {
            $pdo->bindValue(':search', '%' . $options->search->value . '%');
        }
    }

    protected function addCustomQuery(?Support\Conditions $where, ?string $replaceName = null) : string
    {
        $sql = '';
        if (!empty($where->query)) {
            $sql .= ' AND ' . (!is_null($replaceName) ? $this->replaceColumns($where->query, $replaceName) : $where->query);
        }

        return $sql;
    }

    protected function bindCustomQuery(?Support\Conditions $where, \PDOStatement $pdo) : void
    {
        if (!empty($where->bindValues)) {
            foreach ($where->bindValues as $bindName => $bindValue) {
                $pdo->bindValue($bindName, $bindValue);
            }
        }
    }

    protected function addSorting(Support\Options $options) : string
    {
        $sql = '';
        if (!$options->noSortOrder) {
            if (!is_null($options->currentId) || !is_null($options->parentId) || !empty($options->search) || $options->joinChild) {
                $sql .= ' GROUP BY `child`.`' . $this->settings->idColumnName . '`';
                $order_by = '`child`.`' . $this->settings->leftColumnName . '` ASC';
            } elseif (!empty($options->filterIdBy)) {
                $nodeIdIn = implode(',', $options->filterIdBy);
                $order_by = 'FIELD(`' . $this->settings->idColumnName . '`,' . $nodeIdIn . ')';
            } else {
                $order_by = '`parent`.`' . $this->settings->leftColumnName . '` ASC';
            }
            $sql .= ' ORDER BY ' . $order_by;
        } elseif ($options->joinChild) {
            $sql .= ' GROUP BY `' . $this->settings->idColumnName . '`';
        }

        return $sql;
    }
}
