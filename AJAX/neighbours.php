<?php
header('Content-Type: application/json; charset=utf-8');

include_once("../config/config.inc.php");
include_once(ROOT_DIR."\includes\getnode.inc.php");

function getTranslation($nodeLabel, $propertyName){
  if(array_key_exists($propertyName, NODEMODEL[$nodeLabel])){
    return NODEMODEL[$nodeLabel][$propertyName];
  } else if($propertyName === 'uid'){         //the uid property is present in all nodes. Even if not defined in the model
    return ["UUIDV4", "string", true];        //if config.inc.php has overrideoptions set, use those, otherwise, this is the fallback option.
  }
}


if(isset($_GET['mode'])){
  $mode = $_GET['mode'];
}else{
  $mode = false;
}

if(!in_array($mode, array('byproperty', 'byneo'))){
  die( json_encode('Invalid mode passed'));
}
//if the node is valid: load class and methods:
$graph = new Node($client);
//determine identificiatin strategy:
if($mode === 'byneo'){
  //the internal int for neonodes are passed:
  $egoNodeId = (int)$_GET['center'];
  //no extra code required to get the neighbours
}else{
  //using the mode "byproperty":
  //start with identifying the node in the neo4J backend.
  $nodeType = $_GET['label'];
  //check that the label is a valid key based on the config.inc.php settings.
  if(!array_key_exists($nodeType, NODEMODEL)){
    die(json_encode(array('Illegal Node type declared')));
  }
  $property = $_GET['property']; //uid as it is a standard property assigned to all nodes in the model or a key as defined in config.inc.php
  if($property != 'uid' && !array_key_exists($property, NODEMODEL[$nodeType])){
    die(json_encode(array('Illegal Node property declared')));
  }
  $value = $_GET['value'];
  $x = $graph->matchSingleNode($nodeType, $property, $value);
  //after identification ==> extract the internal ID for neonodes from the cyphermap, pass it to the rest of the code.
  $egoNodeId = $x['data'][0][0]['ID'];
}

$data = $graph->getNeighbours($egoNodeId);
if(boolval($data->count())){
  $ego['data'] = $data[0]['n'];
  $ego['model'] = array();
  $egoLabel = $data[0]['n']['labels'][0];
  foreach($ego['data']['properties'] as $key => $value){
    $submodel = getTranslation($egoLabel, $key);
    $ego['model'][$key] = $submodel;
  }
  $edges = array();
  $neighbours = array();
  foreach ($data as $key => $value){
    $edges[]=$value['r'];
    $neighbours[]=array('data'=> $value['t'], 'model'=>array());
  }
}else{
  $ego = array();
  $neighbours = array();
  $edges = array();
}
echo json_encode(array(
  'ego' => $ego,
  'neighbours' => $neighbours,
  'edges' => $edges
  )
);
?>
