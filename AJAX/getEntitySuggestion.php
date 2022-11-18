<?php
header('Content-Type: application/json; charset=utf-8');

include_once("../config/config.inc.php");
include_once(ROOT_DIR."\includes\getnode.inc.php");
$graph = new Node($client);

$approvedEntities = array_keys(CORENODES);
$approvedEntities[] = '';     // words of unknown type should be looked for too!
$caseSensitive = isset($_GET['casesensitive']) ? $_GET['casesensitive'] : false;
$caseSensitive = (strtolower($caseSensitive)=='true')? true : false;
$findEntityByType = $_GET['type'];
$findEntityByValue = $_GET['value'];
if(in_array($findEntityByType, $approvedEntities)){
  $data = $graph->getEntities($findEntityByType,$findEntityByValue,$caseSensitive);
  $modifiedData = array();
  /*
    modify $data so that variants reference which entitity node they are part of.
  */
  foreach ($data['labelvariants'] as $key => $value) {
    $variantNodeId = $value[0];
    foreach ($data['edges'] as $key => $subvalue) {
      if($subvalue['startNodeId']===$variantNodeId){
        $data['labelvariants'][$key]['variantOfEntity'][]=$subvalue['endNodeId'];
      }
    }
  }
  echo json_encode($data);
}else{
  die(json_encode('Invalid request'));
}

die();
?>
