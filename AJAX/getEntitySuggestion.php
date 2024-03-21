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
  /*
    returned data provides directives to connect the nodes to variants!.
  */
  foreach ($data['labelvariants'] as $key => $value) {
    $variantNodeId = $value[0];
    foreach ($data['edges'] as $subkey => $subvalue) {
      if($subvalue['startNodeId']===$variantNodeId){
        if(!(array_key_exists('variantOfEntity',$data['labelvariants'][$key][2]))){
          $data['labelvariants'][$key][2]['variantOfEntity'] = array();
        }
        array_push($data['labelvariants'][$key][2]['variantOfEntity'],$subvalue['endNodeId']);
      }
    }
  }
  echo json_encode((array)$data);
}else{
  die(json_encode(array('message'=>'Invalid request')));
}

die();
?>
