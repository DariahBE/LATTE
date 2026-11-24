<?php 
header('Content-Type: application/json; charset=utf-8');

include_once(dirname(__DIR__) . '/config/config.inc.php');
include_once(ROOT_DIR . '/includes/getnode.inc.php');


$variantString = isset($_GET['value']) ? $_GET['value'] : '';
$connectedTo = isset($_GET['connectedto']) ? $_GET['connectedto'] : '';

$graph = new Node($client);
$data = $graph->findEntitiesWithVariantValue($connectedTo, $variantString);
echo json_encode($data->getResults());
die();
// $r = $data['data'];
// var_dump($r); 
//echo json_encode($data); 

?>