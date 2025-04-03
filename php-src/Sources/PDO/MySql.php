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
            . ' WHERE `' . $this->settings->parentIdColumnName . '` = :parent_id';
        if (!empty($where->query)) {
            $sql .= ' AND ' . $where->query;
        }
        $sql .= ' ORDER BY `' . $this->settings->positionColumnName . '` DESC';

        $Sth = $this->pdo->prepare($sql);

        if (null === $parentNodeId) {
            $Sth->bindValue(':parent_id', null, base_pdo::PARAM_NULL);
        } else {
            $Sth->bindValue(':parent_id', $parentNodeId, base_pdo::PARAM_INT);
        }

        if (!empty($where->bindValues)) {
            foreach ($where->bindValues as $bindName => $bindValue) {
                $Sth->bindValue($bindName, $bindValue);
            }
        }

        $Sth->execute();
        /** @var array<string|int, mixed>|false $row */
        $row = $Sth->fetch();
        $Sth->closeCursor();

        if (!empty($row)) {
            return is_null($row[$this->settings->positionColumnName]) ? null : intval($row[$this->settings->positionColumnName]);
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
        if (!empty($options->additionalColumns)) {
            foreach ($options->additionalColumns as $column) {
                $sql .= ', ' . $column;
            }
        }
        $sql .= ' FROM `' . $this->settings->tableName . '` node';
        if (!empty($options->where->query)) {
            $sql .= ' WHERE ' . $options->where->query;
        }
        $sql .= ' ORDER BY `' . $this->settings->positionColumnName . '` ASC';
        $Sth = $this->pdo->prepare($sql);

        if (!empty($options->where->bindValues)) {
            foreach ($options->where->bindValues as $bindName => $bindValue) {
                $Sth->bindValue($bindName, $bindValue);
            }
        }

        $Sth->execute();
        $result = $Sth->fetchAll();

        return $result ? $this->fromDbRows($result) : [];
    }

    public function selectParent(int $nodeId, Support\Options $options) : ?int
    {
        $sql = 'SELECT node.`' . $this->settings->idColumnName . '`'
            . ', node.`' . $this->settings->parentIdColumnName . '`'
            . ', node.`' . $this->settings->leftColumnName . '`'
            . ', node.`' . $this->settings->rightColumnName . '`'
            . ', node.`' . $this->settings->levelColumnName . '`'
            . ', node.`' . $this->settings->positionColumnName . '`'
        ;
        if (!empty($options->additionalColumns)) {
            foreach ($options->additionalColumns as $column) {
                $sql .= ', ' . $column;
            }
        }
        $sql .= ' FROM `' . $this->settings->tableName . '` node';
        $sql .= ' WHERE `' . $this->settings->idColumnName . '` = :taxonomy_id';
        if (!empty($options->where->query)) {
            $sql .= ' AND ' . $options->where->query;
        }
        $Sth = $this->pdo->prepare($sql);

        $Sth->bindValue(':taxonomy_id', $nodeId, base_pdo::PARAM_INT);
        if (!empty($options->where->bindValues)) {
            foreach ($options->where->bindValues as $bindName => $bindValue) {
                $Sth->bindValue($bindName, $bindValue);
            }
        }
        $Sth->execute();
        /** @var array<string|int, mixed>|false $row */
        $row = $Sth->fetch();
        $parent_id = $row ? $row[$this->settings->parentIdColumnName] : null;
        $Sth->closeCursor();

        return (empty($parent_id)) ? ($this->settings->rootIsNull ? null : 0) : intval($parent_id);
    }

    public function selectCount(Support\Options $options) : int
    {
        // create query SQL statement ------------------------------------------------------
        $sql = 'SELECT ';
        $sql .= ' ANY_VALUE(`parent`.`' . $this->settings->idColumnName . '`)';
        $sql .= ', ANY_VALUE(`parent`.`' . $this->settings->parentIdColumnName . '`)';
        if (!is_null($options->currentId) || !is_null($options->parentId) || !empty($options->search) || $options->joinChild) {
            $sql .= ', ANY_VALUE(`child`.`' . $this->settings->idColumnName . '`) AS `' . $this->settings->idColumnName . '`';
            $sql .= ', ANY_VALUE(`child`.`' . $this->settings->parentIdColumnName . '`) AS `' . $this->settings->parentIdColumnName . '`';
            $sql .= ', ANY_VALUE(`child`.`' . $this->settings->leftColumnName . '`) AS `' . $this->settings->leftColumnName . '`';
        }
        if (!empty($options->additionalColumns)) {
            foreach ($options->additionalColumns as $column) {
                $sql .= ', ' . $column;
            }
        }
        $sql .= ' FROM `' . $this->settings->tableName . '` AS `parent`';

        if (!is_null($options->currentId) || !is_null($options->parentId) || !empty($options->search) || $options->joinChild) {
            // if there is filter or search, there must be inner join to select all of filtered children.
            $sql .= ' INNER JOIN `' . $this->settings->tableName . '` AS `child`';
            $sql .= ' ON `child`.`' . $this->settings->leftColumnName . '` BETWEEN `parent`.`' . $this->settings->leftColumnName . '` AND `parent`.`' . $this->settings->rightColumnName . '`';
        }

        $sql .= ' WHERE 1';

        if (!empty($options->filterIdBy)) {
            // Due to IN() and NOT IN() cannot using bindValue directly.
            // read more at http://stackoverflow.com/questions/17746667/php-pdo-for-not-in-query-in-mysql
            // and http://stackoverflow.com/questions/920353/can-i-bind-an-array-to-an-in-condition

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

        if (!is_null($options->currentId)) {
            $sql .= ' AND `parent`.`' . $this->settings->idColumnName . '` = :filter_taxonomy_id';
        }
        if (!is_null($options->parentId)) {
            $sql .= ' AND `parent`.`' . $this->settings->parentIdColumnName . '` = :filter_parent_id';
        }

        if (
            !empty($options->search->columns)
            && !empty($options->search->value)
        ) {
            // if found search array with its columns and search value.
            $sql .= ' AND (';
            $array_keys = array_keys($options->search->columns);
            $last_array_key = array_pop($array_keys);
            foreach ($options->search->columns as $key => $column) {
                $sql .= '`parent`.`' . $column . '` LIKE :search';
                if ($key !== $last_array_key) {
                    $sql .= ' OR ';
                }
            }
            $sql .= ')';
        }

        if (
            !empty($options->where->query)
        ) {
            $sql .= ' AND ' . $options->where->query;
        }

        // group, sort and order
        if (!$options->noSortOrder) {
            if (!is_null($options->currentId) || !is_null($options->parentId) || !empty($options->search) || $options->joinChild) {
                $sql .= ' GROUP BY `' . $this->settings->idColumnName . '`';
                $order_by = '`' . $this->settings->leftColumnName . '` ASC';
            } elseif (isset($nodeIdIn)) {
                $order_by = 'FIELD(`' . $this->settings->idColumnName . '`,' . $nodeIdIn . ')';
            } else {
                $order_by = '`parent`.`' . $this->settings->leftColumnName . '` ASC';
            }
            $sql .= ' ORDER BY ' . $order_by;
        } elseif ($options->joinChild) {
            $sql .= ' GROUP BY `' . $this->settings->idColumnName . '`';
        }
        // end create query SQL statement -------------------------------------------------

        // prepare and get 'total' count.
        $Sth = $this->pdo->prepare($sql);
        $this->listTaxonomyBindValues($Sth, $options);
        $Sth->execute();
        $result = $Sth->fetchAll();

        // "a bit" hardcore - get all lines and then count them
        return $result ? count($result) : 0;
    }

    public function selectLimited(Support\Options $options) : array
    {
        // create query SQL statement ------------------------------------------------------
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
        if (!empty($options->additionalColumns)) {
            foreach ($options->additionalColumns as $column) {
                $sql .= ', ' . $column;
            }
        }
        $sql .= ' FROM `' . $this->settings->tableName . '` AS `parent`';

        if (!is_null($options->currentId) || !is_null($options->parentId) || !empty($options->search) || $options->joinChild) {
            // if there is filter or search, there must be inner join to select all of filtered children.
            $sql .= ' INNER JOIN `' . $this->settings->tableName . '` AS `child`';
            $sql .= ' ON `child`.`' . $this->settings->leftColumnName . '` BETWEEN `parent`.`' . $this->settings->leftColumnName . '` AND `parent`.`' . $this->settings->rightColumnName . '`';
        }

        $sql .= ' WHERE 1';

        if (!empty($options->filterIdBy)) {
            // Due to IN() and NOT IN() cannot using bindValue directly.
            // read more at http://stackoverflow.com/questions/17746667/php-pdo-for-not-in-query-in-mysql
            // and http://stackoverflow.com/questions/920353/can-i-bind-an-array-to-an-in-condition

            // loop remove non-number for safety.
            foreach ($options->filterIdBy as $key => $eachNodeId) {
                if (!is_numeric($eachNodeId) || $eachNodeId !== (int) $eachNodeId) {
                    unset($options->filterIdBy[$key]);
                }
            }

            // build value for use with `IN()` function. Example: 1,3,4,5.
            $nodeIdIn = implode(',', $options->filterIdBy);
            $sql .= ' AND `parent`.`' . $this->settings->idColumnName . '` IN (' . $nodeIdIn . ')';
        }

        if (!is_null($options->currentId)) {
            $sql .= ' AND `parent`.`' . $this->settings->idColumnName . '` = :filter_taxonomy_id';
        }
        if (!is_null($options->parentId)) {
            $sql .= ' AND `parent`.`' . $this->settings->parentIdColumnName . '` = :filter_parent_id';
        }

        if (
            !empty($options->search->columns)
            && !empty($options->search->value)
        ) {
            // if found search array with its columns and search value.
            $sql .= ' AND (';
            $array_keys = array_keys($options->search->columns);
            $last_array_key = array_pop($array_keys);
            foreach ($options->search->columns as $key => $column) {
                $sql .= '`parent`.`' . $column . '` LIKE :search';
                if ($key !== $last_array_key) {
                    $sql .= ' OR ';
                }
            }
            $sql .= ')';
        }

        if (
            !empty($options->where->query)
        ) {
            $sql .= ' AND ' . $options->where->query;
        }

        // group, sort and order
        if (!$options->noSortOrder) {
            if (!is_null($options->currentId) || !is_null($options->parentId) || !empty($options->search) || $options->joinChild) {
                $sql .= ' GROUP BY `child`.`' . $this->settings->idColumnName . '`';
                $order_by = '`child`.`' . $this->settings->leftColumnName . '` ASC';
            } elseif (isset($nodeIdIn)) {
                $order_by = 'FIELD(`' . $this->settings->idColumnName . '`,' . $nodeIdIn . ')';
            } else {
                $order_by = '`parent`.`' . $this->settings->leftColumnName . '` ASC';
            }
            $sql .= ' ORDER BY ' . $order_by;
        } elseif ($options->joinChild) {
            $sql .= ' GROUP BY `' . $this->settings->idColumnName . '`';
        }
        // end create query SQL statement -------------------------------------------------

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
        $this->listTaxonomyBindValues($Sth, $options);
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
        if (!empty($options->additionalColumns)) {
            foreach ($options->additionalColumns as $column) {
                $sql .= ', ' . $column;
            }
        }
        $sql .= ' FROM `' . $this->settings->tableName . '` AS `node`,';
        $sql .= ' `' . $this->settings->tableName . '` AS `parent`';
        $sql .= ' WHERE';
        $sql .= ' (`node`.`' . $this->settings->leftColumnName . '` BETWEEN `parent`.`' . $this->settings->leftColumnName . '` AND `parent`.`' . $this->settings->rightColumnName . '`)';

        if (!is_null($options->currentId)) {
            $sql .= ' AND `node`.`' . $this->settings->idColumnName . '` = :filter_taxonomy_id';
        }

        if (
            !empty($options->search->columns)
            && !empty($options->search->value)
        ) {
            $sql .= ' AND (';
            $array_keys = array_keys($options->search->columns);
            $last_array_key = array_pop($array_keys);
            foreach ($options->search->columns as $key => $column) {
                $sql .= '`node`.`' . $column . '` LIKE :search';
                if ($key !== $last_array_key) {
                    $sql .= ' OR ';
                }
            }
            $sql .= ')';
        }

        if (
            !empty($options->where->query)
        ) {
            $sql .= ' AND ' . $options->where->query;
        }

        $sql .= ' GROUP BY `parent`.`' . $this->settings->idColumnName . '`';
        $sql .= ' ORDER BY `parent`.`' . $this->settings->leftColumnName . '`';

        $Sth = $this->pdo->prepare($sql);
        if (!is_null($options->currentId)) {
            $Sth->bindValue(':filter_taxonomy_id', $options->currentId, base_pdo::PARAM_INT);
        }
        if (!empty($options->search->value)) {
            $Sth->bindValue(':search', '%' . $options->search->value . '%', base_pdo::PARAM_STR);
        }
        if (!empty($options->where->bindValues)) {
            foreach ($options->where->bindValues as $placeholder => $value) {
                $Sth->bindValue($placeholder, $value);
            }
        }

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
            if (!is_numeric($column) && !in_array($column, [
                $this->settings->idColumnName,
            ])) {
                $lookup['`' . $column . '`'] = ':' . $column;
                $pairs[':' . $column] = $value;
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
            throw new \LogicException('Cannot save!');
        }
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
            throw new \LogicException('Node not found in database');
        }

        return $node;
    }

    public function updateData(Support\Node $node, ?Support\Conditions $where = null) : bool
    {
        $sql = 'UPDATE `' . $this->settings->tableName . '`';
        $sql .= ' SET';
        $pairs = [];
        foreach ((array) $node as $column => $value) {
            if (!is_numeric($column) && !in_array($column, [
                $this->settings->idColumnName,
                $this->settings->parentIdColumnName,
                $this->settings->leftColumnName,
                $this->settings->rightColumnName,
                $this->settings->levelColumnName,
                $this->settings->positionColumnName,
            ])) {
                $sql .= ' `' . $column . '` = :' . $column . ',';
                $pairs[':' . $column] = $value;
            }
        }
        $sql .= ' WHERE `' . $this->settings->idColumnName . '` = :id';

        if (!empty($where->query)) {
            $sql .= ' AND ' . $where->query;
        }

        $Sth = $this->pdo->prepare($sql);
        $Sth->bindValue(':id', $node->id, base_pdo::PARAM_INT);

        foreach ($pairs as $column => $value) {
            $Sth->bindValue($column, $value);
        }
        if (!empty($where->bindValues)) {
            foreach ($where->bindValues as $bindName => $bindValue) {
                $Sth->bindValue($bindName, $bindValue);
            }
        }

        $execute = $Sth->execute();
        $Sth->closeCursor();

        return $execute;
    }

    public function updateChildrenParent(?int $parentId, int $nodeId, ?Support\Conditions $where = null) : bool
    {
        $sql = 'UPDATE `' . $this->settings->tableName . '`';
        $sql .= ' SET `' . $this->settings->parentIdColumnName . '` = :parent_id';
        $sql .= ' WHERE `' . $this->settings->parentIdColumnName . '` = :taxonomy_id';
        if (!empty($where->query)) {
            $sql .= ' AND ' . $where->query;
        }
        $Sth = $this->pdo->prepare($sql);

        if (is_null($parentId)) {
            $Sth->bindValue(':parent_id', null, base_pdo::PARAM_NULL);
        } else {
            $Sth->bindValue(':parent_id', $parentId, base_pdo::PARAM_INT);
        }
        $Sth->bindValue(':taxonomy_id', $nodeId, base_pdo::PARAM_INT);
        if (!empty($where->bindValues)) {
            foreach ($where->bindValues as $bindName => $bindValue) {
                $Sth->bindValue($bindName, $bindValue);
            }
        }

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

        if (!empty($where->query)) {
            $sql .= ' AND ' . $where->query;
        }

        $Sth = $this->pdo->prepare($sql);
        $Sth->bindValue(':level', $row->level, base_pdo::PARAM_INT);
        $Sth->bindValue(':left', $row->left, base_pdo::PARAM_INT);
        $Sth->bindValue(':right', $row->right, base_pdo::PARAM_INT);
        $Sth->bindValue(':pos', $row->position, base_pdo::PARAM_INT);
        $Sth->bindValue(':id', $row->id, base_pdo::PARAM_INT);

        if (!empty($where->bindValues)) {
            foreach ($where->bindValues as $bindName => $bindValue) {
                $Sth->bindValue($bindName, $bindValue);
            }
        }

        $execute = $Sth->execute();
        $Sth->closeCursor();

        return $execute;
    }

    public function deleteSolo(int $nodeId, ?Support\Conditions $where = null) : bool
    {
        // delete the selected taxonomy ID
        $sql = 'DELETE FROM `' . $this->settings->tableName . '` WHERE `' . $this->settings->idColumnName . '` = :taxonomy_id';
        if (!empty($where->query)) {
            $sql .= ' AND ' . $where->query;
        }
        $Sth = $this->pdo->prepare($sql);
        $Sth->bindValue(':taxonomy_id', $nodeId, base_pdo::PARAM_INT);
        if (!empty($where->bindValues)) {
            foreach ($where->bindValues as $bindName => $bindValue) {
                $Sth->bindValue($bindName, $bindValue);
            }
        }

        $execute = $Sth->execute();
        $Sth->closeCursor();

        return $execute;
    }

    public function deleteWithChildren(Support\Node $row, ?Support\Conditions $where = null) : bool
    {
        $sql = 'DELETE FROM `' . $this->settings->tableName . '` WHERE `' . $this->settings->idColumnName . '` = :taxonomy_id';
        if (!empty($where->query)) {
            $where->query = str_replace(['`parent`.', '`child`.'], '', $where->query);
            $sql .= ' AND ' . $where->query;
        }
        $Sth = $this->pdo->prepare($sql);

        $Sth->bindValue(':taxonomy_id', $row->id, base_pdo::PARAM_INT);
        if (!empty($where->bindValues)) {
            foreach ($where->bindValues as $bindName => $bindValue) {
                $Sth->bindValue($bindName, $bindValue);
            }
        }

        $execute = $Sth->execute();
        $Sth->closeCursor();

        return $execute;
    }
}
