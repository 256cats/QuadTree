<?php
class QuadTree {
    public $maxItems = 2;
    public $nodes = array();
    public $nodeCount = 0;
    public $nodesByItem = array();
    public $nodeIdByItem = array();
    public $rectsByItem = array(); // rectangles of items

    /**Creates first node with specified size
     * @param $x1
     * @param $y1
     * @param $x2
     * @param $y2
     */
    public function __construct($x1, $y1, $x2, $y2) {
        $this->createNewNode(self::createRect($x1, $y1, $x2, $y2));
    }

    /**Clears quadtree and reinitializes it
     * @param $x1
     * @param $y1
     * @param $x2
     * @param $y2
     */
    public function init($x1, $y1, $x2, $y2) {
        $this->clearAll();
        $this->createNewNode(self::createRect($x1, $y1, $x2, $y2));
    }

    /**Creates new node with specified rectangle
     * @param $rect
     * @param null $parentId
     * @return int
     */
    protected function createNewNode($rect, $parentId = null) {
        $this->nodes[$this->nodeCount] =
            array(
                'id' => $this->nodeCount,
                'rect' => $rect,
                'childNodes' => null,
                'items' => array(),
                'parent' => $parentId
            );
        $this->nodeCount++;
        return $this->nodeCount - 1;
    }

    /**Determines if node contains rectangle
     * @param $rect
     * @param $nodeId
     * @return bool
     */
    protected function nodeContainsRect($rect, $nodeId) {
        $node = &$this->nodes[$nodeId];
        $nodeR = $node['rect'];
        return (
            $nodeR['x1'] <= $rect['x1'] &&
            $nodeR['y1'] <= $rect['y1'] &&
            $nodeR['x2'] >= $rect['x2'] &&
            $nodeR['y2'] >= $rect['y2']
        );
    }

    /**Determines if node intersects rectangle
     * @param $rect
     * @param $nodeId
     * @return bool
     */
    protected function nodeIntersectsRect($rect, $nodeId) {
        $quadTreeNode = &$this->nodes[$nodeId];
        $r2 = $quadTreeNode['rect'];
        return $this->rectIntersectsRect($rect, $r2);
    }

    /**Determines if two rectangles overlap each other
     * @param $r1
     * @param $r2
     * @return bool
     */
    protected function rectIntersectsRect($r1, $r2) {
        return ( //Assume that Y coordinate increases downwards as usual
            $r1['x1'] <= $r2['x2'] &&
            $r2['x1'] <= $r1['x2'] &&
            $r1['y1'] <= $r2['y2'] &&
            $r2['y1'] <= $r1['y2']
        );
    }

    /**Adds item to specified node
     * @param $item
     * @param $nodeId
     */
    protected function addItemToNode($item, $nodeId) {
        $node = &$this->nodes[$nodeId];
        $node['items'][$item] = $item;
        $this->nodesByItem[$item] = &$node;
        $this->nodeIdByItem[$item] = $nodeId;
    }

    /**Inserts item into specified node or to child nodes
     * @param $item
     * @param $nodeId
     */
    protected function insertItem($item, $nodeId) {
        $node = &$this->nodes[$nodeId];
        if(!$this->nodeIntersectsRect($this->rectsByItem[$item], $nodeId)) { // if item is outside of this node
            if($node['parent'] !== null) { // and this is not the first node, then move item to parent node
                $this->insertItem($item, $node['parent']);
            } else { // otherwise store item in this node
                $this->addItemToNode($item, $nodeId);
            }
            return;
        }

        if(!$this->insertItemIntoChild($item, $nodeId)) { // if can't push item to child node, store in this node
            $this->addItemToNode($item, $nodeId);
            if(!$node['childNodes'] && count($node['items']) >= $this->maxItems) { // if too many items in this node already, partition it
                $this->partitionNode($nodeId);
                $this->pushItemsDown($nodeId);
            }
        }
    }

    /**Creates rectangle with specified coordinates
     * @param $x1
     * @param $y1
     * @param $x2
     * @param $y2
     * @return array
     */
    static function createRect($x1, $y1, $x2, $y2) {
        return array('x1' => $x1, 'y1' => $y1, 'x2' => $x2, 'y2' =>$y2);
    }

    /**Partitions node into 4 childs
     * @param $nodeId
     */
    protected function partitionNode($nodeId) {
        $node = &$this->nodes[$nodeId];

        $nodeR = $node['rect'];
        $x1 = $nodeR['x1'];
        $y1 = $nodeR['y1'];
        $x2 = $nodeR['x2'];
        $y2 = $nodeR['y2'];

        $cx = ($x1 + $x2) / 2;
        $cy = ($y1 + $y2) / 2;

        $rectNW = self::createRect($x1, $y1, $cx, $cy);
        $rectNE = self::createRect($cx, $y1, $x2, $cy);
        $rectSW = self::createRect($x1, $cy, $cx, $y2);
        $rectSE = self::createRect($cx, $cy, $x2, $y2);

        $node['childNodes'] = array(
            'NW' => $this->createNewNode($rectNW, $nodeId),
            'NE' => $this->createNewNode($rectNE, $nodeId),
            'SW' => $this->createNewNode($rectSW, $nodeId),
            'SE' => $this->createNewNode($rectSE, $nodeId),
        );

    }

    /**Pushes items down to child nodes if possible
     * @param $nodeId
     */
    protected function pushItemsDown($nodeId) {
        $node = &$this->nodes[$nodeId];
        foreach($node['items'] as $id =>$item) {
            if($this->insertItemIntoChild($item, $nodeId)) { // id in nodesById will be updated automatically
                unset($node['items'][$id]);
            }
        }
    }
/*
    protected function pushItemUp($item) {
        unset($this->nodesByItem[$item]['items'][$item]);
        $this->insertItem($item, $this->nodeIdByItem[$item]['parent']);
    }
*/
    /**Try to insert item to children of this node
     * @param $item
     * @param $nodeId
     * @return bool
     */
    protected function insertItemIntoChild($item, $nodeId) {
        $node = &$this->nodes[$nodeId];
        if(!$node['childNodes']) return false; //no children

        $rect = $this->rectsByItem[$item];
        foreach($node['childNodes'] as $childNodeId) {
            if($this->nodeContainsRect($rect, $childNodeId)) {
                $this->insertItem($item, $childNodeId);
                return true;
            }
        }
        return false;
    }

    /**Determines if item is in quad tree already
     * @param $id
     * @return bool
     */
    public function hasItemById($id) {
        return isset($this->nodesByItem[$id]);
    }

    /**Moves item with $id to new rectangle (may call it resize as well)
     * @param $x1
     * @param $y1
     * @param $x2
     * @param $y2
     * @param $id
     */
    public function moveItem($x1, $y1, $x2, $y2, $id) {
        if($this->hasItemById($id)) { // if item exists
            $this->rectsByItem[$id] = self::createRect($x1, $y1, $x2, $y2); // change rect coordinates and check if item is still inside it's node
            if(!$this->nodeContainsRect($this->rectsByItem[$id], $this->nodeIdByItem[$id])) { // item is outside of it's node, remove it and add again, to take proper node
                $this->removeItem($id);
                $this->addItem($x1, $y1, $x2, $y2, $id);
            }
        } else { // otherwise just add item
            $this->addItem($x1, $y1, $x2, $y2, $id);
        }
    }

    /**Removes item from quad tree
     * @param $id
     */
    public function removeItem($id) {
        if(isset($this->nodesByItem[$id])) {
            $node = &$this->nodesByItem[$id];
            unset($node['items'][$id]);
            unset($this->nodesByItem[$id]);
            unset($this->nodeIdByItem[$id]);
            unset($this->rectsByItem[$id]);
        }
    }

    /**Returns all items of this region (coordinates)
     * @param $x1
     * @param $y1
     * @param $x2
     * @param $y2
     * @return array
     */
    public function getItemsByRegion($x1, $y1, $x2, $y2) {
        return $this->getItemsByRect(self::createRect($x1, $y1, $x2, $y2));
    }

    /**Returns all items of this region (rectangle)
     * @param $rect
     * @param null $node
     * @return array
     */
    protected function getItemsByRect($rect, $node = null) {
        $items = array();
        if(!$node) {
            $node = $this->nodes[0];
        }
        if($this->nodeIntersectsRect($rect, $node['id'])) {
            if(!empty($node['items'])) {
                foreach($node['items'] as $id) {
                    if($this->rectIntersectsRect($this->rectsByItem[$id], $rect)) {
                        $items[] = $id;
                    }
                }
            }
            if($node['childNodes']) {
                foreach($node['childNodes'] as &$childNode) {
                    $items = array_merge($items, $this->getItemsByRect($rect, $this->nodes[$childNode]));
                }
            }
        }
        return $items;
    }

    /**Adds item to quad tree
     * @param $x1
     * @param $y1
     * @param $x2
     * @param $y2
     * @param $id
     */
    public function addItem($x1, $y1, $x2, $y2, $id) {
        $this->rectsByItem[$id] = $rect = self::createRect($x1, $y1, $x2, $y2); //create rectangle
        $this->insertItem($id, 0); // add item
    }

    /**
     * Clears quad tree, removes all items
     */
    protected function clearAll() {
        foreach($this->nodes as $k => &$n) {
            $n = null;
            unset($this->nodes[$k]);
        }
        $this->nodes = array();

        foreach($this->nodesByItem as $k => &$n) {
            $n = null;
            unset($this->nodesByItem[$k]);
        }
        $this->nodesByItem = array();
        $this->nodeCount = 0;
        $this->nodeIdByItem = array();
        $this->rectsByItem = array();
    }

    public function __destruct() {
        $this->clearAll();
    }
}