<?php
require_once __DIR__.'/QuadTree.php';

$qt = new QuadTree(0, 0, 10, 10);
$qt->addItem(0, 0, 1, 1, 0);
$qt->addItem(6, 6, 7, 8, 1);
$qt->addItem(7, 7, 8, 8, 2);
$qt->addItem(7.6, 7.6, 8, 8, 3);
print_r($qt->nodes);

$qt->moveItem(7, 8, 9, 10, 0);
echo 'moveItem';
print_r($qt->nodes);
print_r($qt->getItemsByRegion(1, 1, 10, 10));