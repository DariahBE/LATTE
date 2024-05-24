<?php

header('Content-Type: application/json; charset=utf-8');

include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'/includes/getnode.inc.php');
$egoNodeId = (int)$_GET['center'];
$edgesByLabel = isset($_GET['onlabel']) ? $_GET['onlabel'] : 'has_entity';

$graph = new Node($client);
$edges = $graph->getEdges($egoNodeId, $edgesByLabel);
echo json_encode($edges);
die();
?>
