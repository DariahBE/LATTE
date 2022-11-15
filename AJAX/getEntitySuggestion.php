<?php
header('Content-Type: application/json; charset=utf-8');

include_once("../config/config.inc.php");
include_once(ROOT_DIR."\includes\getnode.inc.php");
$graph = new Node($client);

//$approvedEntities = array('Person', 'Place', '');
$approvedEntities = array_keys(CORENODES);
$approvedEntities[] = '';     // words of unknown type should be looked for to!
$caseSensitive = isset($_GET['casesensitive']) ? $_GET['casesensitive'] : false;
$caseSensitive = (strtolower($caseSensitive)=='true')? true : false;
$findEntityByType = $_GET['type'];
$findEntityByValue = $_GET['value'];
if(in_array($findEntityByType, $approvedEntities)){
  $data = $graph->getEntities($findEntityByType,$findEntityByValue,$caseSensitive);
  echo json_encode($data);
}else{
  die(json_encode('Invalid request'));
}

die();
?>
