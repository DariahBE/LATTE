<?php
header('Content-Type: application/json; charset=utf-8');
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR."\includes\getnode.inc.php");

$nodeLabel = $_GET['nodetype'];
$nodeProperty = $_GET['property'];
$propertyValue = $_GET['value'];
//check if this is exists: 
if(array_key_exists($nodeLabel, NODEMODEL) && array_key_exists($nodeProperty,NODEMODEL[$nodeLabel])){
    $expectedType = NODEMODEL[$nodeLabel][$nodeProperty][1];
}else{
    die();
}
//if it exists, fetch the type and pass it as an argument
$graph = new Node($client);
$data = $graph->checkUniqueness($nodeLabel, $nodeProperty, $propertyValue, $expectedType);
//if it exists, one record gets returned.
//cast to bool and invert. True means the value should be accepted!
echo json_encode(!boolval(count($data->getresults()))); 
die(); 
?>