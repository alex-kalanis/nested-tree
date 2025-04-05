<?php

namespace kalanis\nested_tree;

use kalanis\nested_tree\Sources\SourceInterface;

/**
 * Nested Set class for build left, right, level data.
 *
 * @see http://mikehillyer.com/articles/managing-hierarchical-data-in-mysql/ Query references.
 */
class NestedSet
{
    public function __construct(
        protected readonly SourceInterface $source,
        protected readonly Support\Node $nodeBase = new Support\Node(),
        protected readonly Support\TableSettings $tableSettings = new Support\TableSettings(),
    ) {
    }

    /**
     * Add new record to structure
     * The position is set to end
     *
     * Note: Properties of tree will be skipped and filled later. For their change use different methods.
     *
     * @param Support\Node $node
     * @param Support\Options $options
     * @return Support\Node
     */
    public function add(Support\Node $node, Support\Options $options = new Support\Options()) : Support\Node
    {
        $node->position = $this->getNewPosition($node->parentId);

        return $this->source->add($node, $options->where);
    }

    /**
     * Update current record in structure
     *
     * Note: Properties with Null value will be skipped and stay same in the storage.
     * Note: Properties of tree will be skipped too. For their change use different methods.
     *
     * @param Support\Node $node
     * @param Support\Options $options
     * @return bool
     */
    public function update(Support\Node $node, Support\Options $options = new Support\Options()) : bool
    {
        return $this->source->updateData($node, $options->where);
    }

    /**
     * Change parent node to different one; put the content on the last position
     * Parent id can be either some number for existing one or 0/null for root
     *
     * Return true for pass, false otherwise
     *
     * @param int<1, max> $nodeId
     * @param int<0, max>|null $newParentId
     * @param Support\Options $options
     * @return bool
     */
    public function changeParent(int $nodeId, ?int $newParentId, Support\Options $options = new Support\Options()) : bool
    {
        if (!$this->isNewParentOutsideCurrentNodeTree($nodeId, $newParentId, $options)) {
            return false;
        }
        $newPosition = $this->getNewPosition($newParentId, $options->where);

        return $this->source->updateNodeParent($nodeId, $newParentId, $newPosition, $options->where);
    }

    /**
     * Move node to new position
     *
     * @param int<1, max> $nodeId
     * @param int<0, max> $newPosition
     * @param Support\Options $options
     * @return bool
     */
    public function move(int $nodeId, int $newPosition, Support\Options $options = new Support\Options()) : bool
    {
        $parentId = $this->source->selectParent($nodeId, $options);
        // get single node
        $customOptions = new Support\Options();
        $customOptions->currentId = $nodeId;
        $nodes = $this->source->selectSimple($customOptions);
        $node = reset($nodes);
        if (empty($node)) {
            return false;
        }
        // move it
        if ($this->source->makeHole($parentId, $newPosition, $newPosition > $node->position, $options->where)) {
            return $this->source->updateNodeParent($nodeId, $parentId, $newPosition, $options->where);
        }

        return false;
    }

    /**
     * Delete the selected taxonomy ID and pull children's parent ID to the same as selected one.<br>
     * Example: selected taxonomy ID is 4, its parent ID is 2. This method will be pull all children that has parent ID = 4 to 2 and delete the taxonomy ID 4.<br>
     * Always run <code>$NestedSet->rebuild()</code> after insert, update, delete to rebuild the correctly level, left, right data.
     *
     * @param int<1, max> $nodeId The selected taxonomy ID.
     * @param Support\Options $options Where array structure will be like this.
     * @return bool Return true on success, false for otherwise.
     */
    public function deletePullUpChildren(int $nodeId, Support\Options $options = new Support\Options()) : bool
    {
        // get this taxonomy parent id
        $parentNodeId = $this->source->selectParent($nodeId, $options);
        // update this children first level.
        $this->source->updateChildrenParent($nodeId, $parentNodeId, $options->where);

        return $this->source->deleteSolo($nodeId, $options->where);
    }

    /**
     * Delete the selected taxonomy ID with its ALL children.<br>
     * Always run <code>$NestedSet->rebuild()</code> after insert, update, delete to rebuild the correctly level, left, right data.
     *
     * The columns `left`, `right` must have been built before using this method, otherwise the result will be incorrect.
     *
     * @param int<1, max> $nodeId The taxonomy ID to delete.
     * @param Support\Options $options Where array structure will be like this.
     * @return int|null Return number on success, return null for otherwise.
     */
    public function deleteWithChildren(int $nodeId, Support\Options $options = new Support\Options()) : ?int
    {
        $options->currentId = $nodeId;
        $options->unlimited = true;
        $result = $this->getNodesWithChildren($options);
        $i_count = 0;

        if (!empty($result->items)) {
            foreach ($result->items as $row) {
                if ($this->source->deleteWithChildren($row, $options->where)) {
                    $i_count++;
                }
            }
        }

        if (0 >= $i_count) {
            return null;
        }

        return $i_count;
    }

    /**
     * Get new position for taxonomy in the selected parent.
     *
     * @param int<0, max>|null $parentId The parent ID. If root, set this to 0 or null.
     * @param Support\Conditions|null $where Where array structure will be like this.
     * @return int<1, max> Return the new position in the same parent.<br>
     *              WARNING! If there are no results or the results according to the conditions cannot be found. It always returns 1.
     */
    public function getNewPosition(?int $parentId, ?Support\Conditions $where = null) : int
    {
        $lastPosition = $this->source->selectLastPosition($parentId, $where);

        return null === $lastPosition ? 1 : $lastPosition + 1;
    }

    /**
     * Get taxonomy from selected item and fetch its ALL children.<br>
     * Example: There are taxonomy tree like this. Root 1 > 1.1 > 1.1.1, Root 2, Root 3 > 3.1, Root 3 > 3.2 > 3.2.1, Root 3 > 3.2 > 3.2.2, Root 3 > 3.3<br>
     * Assume that selected item is Root 3. So, the result will be Root 3 > 3.1, 3.2 > 3.2.1, 3.2.2, 3.3<br>
     *
     * Warning! Even this method has options for search, custom where conditions,
     * but it is recommended that you should set the option to select only specific item.<br>
     * This method is intended to show results from a single target.
     *
     * The columns `left`, `right` must have been built before using this method, otherwise the result will be incorrect.
     *
     * @param Support\Options $options Available options
     *
     * @return Support\Result Return object of taxonomy data
     */
    public function getNodesWithChildren(Support\Options $options = new Support\Options()) : Support\Result
    {
        // set unwanted options that is available in `listTaxonomy()` method to defaults
        $options->parentId = null;
        $options->filterIdBy = [];
        $options->noSortOrder = false;
        // set required option.
        $options->listFlattened = true;

        return $this->listNodes($options);
    }

    /**
     * Get simple taxonomy from all known; set things to make the query more limited
     *
     * @param Support\Options $options
     * @return Support\Node|null
     */
    public function getNode(Support\Options $options = new Support\Options()) : ?Support\Node
    {
        $options = clone $options;
        $options->unlimited = false;
        $options->offset = 0;
        $options->limit = 1;
        $options->noSortOrder = false;
        $nodes = $this->source->selectLimited($options);
        if (empty($nodes)) {
            return null;
        }

        return reset($nodes);
    }

    /**
     * Get taxonomy from selected item and fetch its parent in a line until root item.<br>
     * Example: There are taxonomy tree like this. Root1 > 1.1 > 1.1.1 > 1.1.1.1<br>
     * Assume that you selected at 1.1.1. So, the result will be Root1 > 1.1 > 1.1.1<br>
     * But if you set 'skipCurrent' to true the result will be Root1 > 1.1
     *
     * Warning! Even this method has options for search, custom where conditions,
     * but it is recommended that you should set the option to select only specific item.<br>
     * This method is intended to show results from a single target.
     *
     * The columns `left`, `right` must have been built before using this method, otherwise the result will be incorrect.
     *
     * @see http://mikehillyer.com/articles/managing-hierarchical-data-in-mysql/ Original source.
     * @param Support\Options $options Available options
     * @return Support\Result Return array object of taxonomy data
     */
    public function getNodesWithParents(Support\Options $options = new Support\Options()) : Support\Result
    {
        $result = new Support\Result();
        $result->items = $this->source->selectWithParents($options);
        $result->count = count($result->items);

        return $result;
    }

    /**
     * Rebuild children into array.
     *
     * @internal This method was called from `getTreeWithChildren()`.
     * @param array<int<0, max>, Support\Node> $array The array data that was get while running `getTreeWithChildren()`. This data contains 'children' object property but empty, it will be added here.
     * @return array<int<0, max>, Support\Node> Return added correct id of the children to data.
     */
    protected function getTreeRebuildChildren(array $array) : array
    {
        foreach ($array as $id => $row) {
            if (!is_null($row->parentId) && !empty($array[$row->parentId]) && ($row->parentId !== $row->id)) {
                $array[$row->parentId]->childrenIds[$id] = $id;
                $array[$row->parentId]->childrenNodes[$id] = $row;
            } elseif (is_null($row->parentId) && $this->tableSettings->rootIsNull && !empty($row->id)) {
                $array[0]->childrenIds[$id] = $id;
                $array[0]->childrenNodes[$id] = $row;
            }
        }

        return $array;
    }

    /**
     * Get the data nest tree with children.<br>
     * Its result will be look like this...<pre>
     * Array(
     *     [0] => Support\Node Object
     *         (
     *             [id] => 0
     *             [children] => Array
     *                 (
     *                     [1] => 1
     *                     [2] => 2
     *                     [3] => 3
     *                 )
     *         )
     *     [1] => Support\Node Object
     *         (
     *             [id] => 1
     *             [parent_id] => 0
     *             [level] => 1
     *             [children] => Array
     *                 (
     *                 )
     *         )
     *     [2] => Support\Node Object
     *         (
     *             [id] => 2
     *             [parent_id] => 0
     *             [level] => 1
     *             [children] => Array
     *                 (
     *                     [4] => 4
     *                     [5] => 5
     *                 )
     *         )
     *     [3] => Support\Node Object
     *         (
     *             [id] => 3
     *             [parent_id] => 0
     *             [level] => 1
     *             [children] => Array
     *                 (
     *                 )
     *         )
     *     [4] => Support\Node Object
     *         (
     *             [id] => 4
     *             [parent_id] => 2
     *             [level] => 2
     *             [children] => Array
     *                 (
     *                 )
     *         )
     *     [5] => Support\Node Object
     *         (
     *             [id] => 5
     *             [parent_id] => 2
     *             [level] => 2
     *             [children] => Array
     *                 (
     *                 )
     *         )
     * )</pre>
     *
     * Usually, this method is for get taxonomy tree data in the array format that suit for loop/nest loop verify level.
     *
     * @since 1.0
     * @internal This method was called from `rebuild()`.
     * @param Support\Options $options Where array structure will be like this.
     * @return array<int<0, max>, Support\Node> Return formatted array structure as seen in example of docblock.
     */
    protected function getTreeWithChildren(Support\Options $options = new Support\Options()) : array
    {
        // create a root node to hold child data about first level items
        $result = $this->source->selectSimple($options);
        $result[0] = clone $this->nodeBase; // hack for root node

        // now process the array and build the child data
        return $this->getTreeRebuildChildren($result);
    }

    /**
     * Detect that is this taxonomy's parent setting to be under this taxonomy's children or not.<br>
     * For example: Root 1 > 1.1 > 1.1.1 > 1.1.1.1 > 1.1.1.1.1<br>
     * Assume that you are editing 1.1.1 and its parent is 1.1. Now you change its parent to 1.1.1.1.1 which is under its children.<br>
     * The parent of 1.1.1 must be root, Root 1, 1.1 and never go under that.
     *
     * @param int<1, max> $currentNodeId The taxonomy ID that is changing the parent.
     * @param int<0, max>|null $newParentId The selected parent ID to check.
     * @param Support\Options $options Where array structure will be like this.
     * @return bool Return `false` if its parent is under its children (INCORRECT changes).<br>
     *              Return `false` if search result was not found (INCORRECT changes).<br>
     *              Return `true` if its parent is not under its children (CORRECT changes).
     */
    public function isNewParentOutsideCurrentNodeTree(int $currentNodeId, ?int $newParentId, Support\Options $options = new Support\Options()) : bool
    {
        if (empty($newParentId)) {
            // if parent is root, always return false because that is correct!
            return true;
        }

        // check for selected parent that must not under this taxonomy.
        $options->currentId = $newParentId;
        $nodesWithParents = $this->getNodesWithParents($options);

        if (!empty($nodesWithParents->items)) {
            foreach ($nodesWithParents->items as $row) {
                if ($row->parentId === $currentNodeId) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * List taxonomy.
     *
     * The columns `left`, `right` must have been built before using this method, otherwise the result will be incorrect.
     *
     * @param Support\Options $options Available options
     */
    public function listNodes(Support\Options $options = new Support\Options()) : Support\Result
    {
        $output = new Support\Result();
        $output->count = $this->source->selectCount($options);
        $output->items = $this->source->selectLimited($options);

        if (!$options->listFlattened) {
            $output = $this->listNodesBuildTreeWithChildren($output, $options);
        }

        return $output;
    }

    /**
     * Build tree data with children.
     *
     * @internal This method was called from `listTaxonomy()`.
     * @param Support\Result $result The array item get from fetchAll() method using the PDO.
     * @param Support\Options $options Available options
     * @return Support\Result Return array data of formatted values.
     */
    protected function listNodesBuildTreeWithChildren(Support\Result $result, Support\Options $options) : Support\Result
    {
        $items = [];
        foreach ($result->items as &$item) {
            $items[$item->parentId][] = $item;
        }

        if (empty($options->filterIdBy)) {
            // without taxonomy_id_in option exists, this result can format to be hierarchical.
            foreach ($result->items as $row) {
                if (isset($items[$row->id])) {
                    $row->childrenNodes = $items[$row->id];
                    $row->childrenIds = array_map(fn (Support\Node $node) => $node->id, $row->childrenNodes);
                }
            }

            $partItems = ($items[0] ?? array_shift($items)); // this is important ([0]) for prevent duplicate items
            if (empty($partItems)) {
                return new Support\Result();
            } else {
                $result->items = $partItems;
            }
        }

        return $result;
    }

    /**
     * List taxonomy as flatten not tree.<br>
     * All parameters or arguments are same as `listTaxonomy()` method.
     *
     * @param Support\Options $options Available options
     */
    public function listNodesFlatten(Support\Options $options = new Support\Options()) : Support\Result
    {
        $options->listFlattened = true;

        return $this->listNodes($options);
    }

    /**
     * Rebuilds the tree data and save it to the database.<br>
     * This will rebuild the level, left, right values.
     *
     * The columns `left`, `right` must have been built before using this method, otherwise the result will be incorrect.
     *
     * @param Support\Options $options Where array structure will be like this.
     */
    public function rebuild(Support\Options $options = new Support\Options()) : void
    {
        // get taxonomy tree data in the array format that suit for loop/nest loop verify level.
        $data = $this->getTreeWithChildren($options);

        $n = 0; // need a variable to hold the running n tally
        $p = 0; // need a variable to hold the running position tally

        // rebuild positions
        $this->rebuildGeneratePositionData($data, 0, $p);

        // verify the level data. this method will be alter the $data value because it will be called as reference.
        // so, it doesn't need to use `$data = $this->rebuildGenerateTreeData()`;
        $this->rebuildGenerateTreeData($data, 0, 0, $n);

        foreach ($data as $id => $row) {
            if (0 === $id) {
                continue;
            }

            $this->source->updateLeftRightPos($row, $options->where);
        }
    }

    /**
     * Rebuild taxonomy level, left, right for tree data.<br>
     * This method will alter the $array value. It will be set level, left, right value.
     *
     * This method modify variables via argument reference without return anything.
     *
     * @internal This method was called from `rebuild()`.
     * @param array<int<0, max>, Support\Node> $array The data array, will be call as reference and modify its value.
     * @param int<0, max> $id The ID of taxonomy.
     * @param int<0, max> $level The level of taxonomy.
     * @param int<0, max> $n The tally or count number, will be call as reference and modify its value.
     */
    protected function rebuildGenerateTreeData(array &$array, int $id, int $level, int &$n) : void
    {
        $array[$id]->level = $level;
        $array[$id]->left = $n++;

        // loop over the node's children and process their data
        // before assigning the right value
        foreach ($array[$id]->childrenIds as $childNodeId) {
            $this->rebuildGenerateTreeData($array, $childNodeId, $level + 1, $n);
        }

        $array[$id]->right = $n++;
    }

    /**
     * Rebuild taxonomy positions for tree data.<br>
     * This method will alter the $array value. It will set position value.
     *
     * This method modify variables via argument reference without return anything.
     *
     * @internal This method was called from `rebuild()`.
     * @param array<int<0, max>, Support\Node> $array The data array, will be call as reference and modify its value.
     * @param int<0, max> $id The ID of taxonomy.
     * @param int<0, max> $n The position number, will be call as reference and modify its value.
     */
    protected function rebuildGeneratePositionData(array &$array, int $id, int &$n) : void
    {
        $array[$id]->position = ++$n;

        // loop over the node's children and process their data
        // before assigning the right value
        $p = 0;
        foreach ($array[$id]->childrenIds as $childNodeId) {
            $this->rebuildGeneratePositionData($array, $childNodeId, $p);
        }

        usort($array[$id]->childrenIds, function (int $a, int $b) use ($array) {
            return $array[$a]->position <=> $array[$b]->position;
        });
    }
}
