<?php

namespace kalanis\nested_tree\Sources\PDO;

use kalanis\nested_tree\Support;

/**
 * Implementation without ANY_VALUE which will cause problems on MariaDB servers
 * @codeCoverageIgnore cannot connect both MySQL and MariaDB and set the ONLY_FULL_GROUP_BY variable out on Maria.
 * Both Github and Scrutinizer have this problem.
 */
class MariaDB extends MySql
{
    /**
     * {@inheritdoc}
     */
    public function selectCount(Support\Options $options) : int
    {
        $joinChild = $this->canJoinChild($options);
        $sql = 'SELECT ';
        $sql .= ' `parent`.`' . $this->settings->idColumnName . '` AS p_cid';
        $sql .= ', `parent`.`' . $this->settings->parentIdColumnName . '` AS p_pid';
        if ($this->settings->softDelete) {
            $sql .= ', `parent`.`' . $this->settings->softDelete->columnName . '`';
        }
        if ($joinChild) {
            $sql .= ', `child`.`' . $this->settings->idColumnName . '` AS `' . $this->settings->idColumnName . '`';
            $sql .= ', `child`.`' . $this->settings->parentIdColumnName . '` AS `' . $this->settings->parentIdColumnName . '`';
            $sql .= ', `child`.`' . $this->settings->leftColumnName . '` AS `' . $this->settings->leftColumnName . '`';
        }
        $sql .= $this->addAdditionalColumns($options);
        $sql .= ' FROM `' . $this->settings->tableName . '` AS `parent`';

        if ($joinChild) {
            // if there is filter or search, there must be inner join to select all of filtered children.
            $sql .= ' INNER JOIN `' . $this->settings->tableName . '` AS `child`';
            $sql .= ' ON `child`.`' . $this->settings->leftColumnName . '` BETWEEN `parent`.`' . $this->settings->leftColumnName . '` AND `parent`.`' . $this->settings->rightColumnName . '`';
        }

        $sql .= ' WHERE TRUE';
        $sql .= $this->addFilterBy($options);
        $sql .= $this->addCurrentId($options, '`parent`.');
        $sql .= $this->addParentId($options, '`parent`.');
        $sql .= $this->addSearch($options, '`parent`.');
        $sql .= $this->addCustomQuery($options->where);
        $sql .= $this->addSoftDelete('`parent`.');
        $sql .= $this->addSorting($options);

        // prepare and get 'total' count.
        $Sth = $this->pdo->prepare($sql);
        $this->bindCurrentId($options->currentId, $Sth);
        $this->bindParentId($options->parentId, $Sth, true);
        $this->bindSearch($options, $Sth);
        $this->bindCustomQuery($options->where, $Sth);
        $this->bindSoftDelete($Sth);

        $Sth->execute();
        $result = $Sth->fetchAll();

        // "a bit" hardcore - get all lines and then count them
        return $result ? count($result) : 0;
    }

    public function selectLimited(Support\Options $options) : array
    {
        $joinChild = $this->canJoinChild($options);
        $sql = 'SELECT';
        $sql .= ' `parent`.`' . $this->settings->idColumnName . '`' . ($joinChild ? '' : ' AS `' . $this->settings->idColumnName . '`');
        $sql .= ', `parent`.`' . $this->settings->parentIdColumnName . '`' . ($joinChild ? '' : ' AS `' . $this->settings->parentIdColumnName . '`');
        $sql .= ', `parent`.`' . $this->settings->leftColumnName . '`' . ($joinChild ? '' : ' AS `' . $this->settings->leftColumnName . '`');
        $sql .= ', `parent`.`' . $this->settings->rightColumnName . '`' . ($joinChild ? '' : ' AS `' . $this->settings->rightColumnName . '`');
        $sql .= ', `parent`.`' . $this->settings->levelColumnName . '`' . ($joinChild ? '' : ' AS `' . $this->settings->levelColumnName . '`');
        $sql .= ', `parent`.`' . $this->settings->positionColumnName . '`' . ($joinChild ? '' : ' AS `' . $this->settings->positionColumnName . '`');
        if ($joinChild) {
            $sql .= ', `child`.`' . $this->settings->idColumnName . '` AS `' . $this->settings->idColumnName . '`';
            $sql .= ', `child`.`' . $this->settings->parentIdColumnName . '` AS `' . $this->settings->parentIdColumnName . '`';
            $sql .= ', `child`.`' . $this->settings->leftColumnName . '` AS `' . $this->settings->leftColumnName . '`';
            $sql .= ', `child`.`' . $this->settings->rightColumnName . '` AS `' . $this->settings->rightColumnName . '`';
            $sql .= ', `child`.`' . $this->settings->levelColumnName . '` AS `' . $this->settings->levelColumnName . '`';
            $sql .= ', `child`.`' . $this->settings->positionColumnName . '` AS `' . $this->settings->positionColumnName . '`';
        }
        $sql .= $this->addAdditionalColumns($options);
        $sql .= ' FROM `' . $this->settings->tableName . '` AS `parent`';

        if ($joinChild) {
            // if there is filter or search, there must be inner join to select all of filtered children.
            $sql .= ' INNER JOIN `' . $this->settings->tableName . '` AS `child`';
            $sql .= ' ON `child`.`' . $this->settings->leftColumnName . '` BETWEEN `parent`.`' . $this->settings->leftColumnName . '` AND `parent`.`' . $this->settings->rightColumnName . '`';
        }

        $sql .= ' WHERE TRUE';
        $sql .= $this->addFilterBy($options);
        $sql .= $this->addCurrentId($options, '`parent`.');
        $sql .= $this->addParentId($options, '`parent`.');
        $sql .= $this->addSearch($options, '`parent`.');
        $sql .= $this->addCustomQuery($options->where);
        $sql .= $this->addSoftDelete('`parent`.');
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
        $this->bindSoftDelete($Sth);

        $Sth->execute();
        $result = $Sth->fetchAll();

        return $result ? $this->fromDbRows($result, $joinChild) : [];
    }

    /**
     * {@inheritdoc}
     */
    public function selectWithParents(Support\Options $options) : array
    {
        $sql = 'SELECT';
        $sql .= ' `parent`.`' . $this->settings->idColumnName . '` AS `' . $this->settings->idColumnName . '`';
        $sql .= ', `parent`.`' . $this->settings->parentIdColumnName . '` AS `' . $this->settings->parentIdColumnName . '`';
        $sql .= ', `parent`.`' . $this->settings->leftColumnName . '` AS `' . $this->settings->leftColumnName . '`';
        $sql .= ', `parent`.`' . $this->settings->rightColumnName . '` AS `' . $this->settings->rightColumnName . '`';
        $sql .= ', `parent`.`' . $this->settings->levelColumnName . '` AS `' . $this->settings->levelColumnName . '`';
        $sql .= ', `parent`.`' . $this->settings->positionColumnName . '` AS `' . $this->settings->positionColumnName . '`';
        $sql .= $this->addAdditionalColumns($options);
        $sql .= ' FROM `' . $this->settings->tableName . '` AS `node`,';
        $sql .= ' `' . $this->settings->tableName . '` AS `parent`';
        $sql .= ' WHERE';
        $sql .= ' (`node`.`' . $this->settings->leftColumnName . '` BETWEEN `parent`.`' . $this->settings->leftColumnName . '` AND `parent`.`' . $this->settings->rightColumnName . '`)';
        $sql .= $this->addCurrentId($options, '`node`.');
        $sql .= $this->addSearch($options, '`node`.');
        $sql .= $this->addCustomQuery($options->where);
        $sql .= $this->addSoftDelete('`node`.');
        $sql .= ' GROUP BY `parent`.`' . $this->settings->idColumnName . '`';
        $sql .= ' ORDER BY `parent`.`' . $this->settings->leftColumnName . '`';

        $Sth = $this->pdo->prepare($sql);
        $this->bindCurrentId($options->currentId, $Sth);
        $this->bindSearch($options, $Sth);
        $this->bindCustomQuery($options->where, $Sth);
        $this->bindSoftDelete($Sth);

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
}
