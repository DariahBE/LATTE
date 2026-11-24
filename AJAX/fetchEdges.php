<?php

header('Content-Type: application/json; charset=utf-8');

include_once(dirname(__DIR__) . '/config/config.inc.php');
include_once(ROOT_DIR . '/includes/getnode.inc.php');
$egoNodeId = isset($_GET['center']) ? (int)$_GET['center'] : 0;
$edgesByLabel = isset($_GET['onlabel']) ? $_GET['onlabel'] : 'has_entity';

$graph = new Node($client);
$edges = $graph->getEdges($egoNodeId, $edgesByLabel);
echo json_encode($edges);
die();
?>
