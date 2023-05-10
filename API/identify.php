<?php

/**
 *  the /API/ * endpoint JSON parallel of /URI/
 * 
 */


include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'/includes/datasilo.inc.php');


header('Content-Type: application/json; charset=utf-8');

$typeOK = false;
$uuid = false;
if(isset($_GET['type'])){
  $type = ucfirst($_GET['type']);
  $approvedTypes = array_keys(NODEMODEL);
  if(in_array($type, $approvedTypes)){
    $typeOK = true;
  }
}
if(!($typeOK)){
  header('Location: /error.php?type=node');
  die();
}

if(isset($_GET['uuid'])){
  $uuid = $_GET['uuid'];
}

//NOTE: if the nodetype is configured to have another PK than the UUID; it should accept that too!
if(!($uuid)){
  header('Location: /error.php?type=id');
  die();
}


include_once(ROOT_DIR.'/includes/getnode.inc.php');
include_once(ROOT_DIR.'/includes/entityviews.inc.php');

$graph = new Node($client);
$silo = new Siloconnector($client); 
$propertyWithPK = 'uid';
if (array_key_exists($type, PRIMARIES) && boolval(PRIMARIES[$type])){
  $propertyWithPK = PRIMARIES[$type];
}

//getting the data from the backend:
$core = $graph->matchSingleNode($type, $propertyWithPK, $uuid);
if (array_key_exists('coreID', $core)){
  $coreNeoID = $core["neoID"]; 
  $coreId = $core['coreID'];
  //$neighbours = $graph->getNeighbours($core['data'][0][0]['ID']);
  $neighbours = $graph->getNeighbours($coreNeoID, false, 'see_also');
  //$textSharingEt = $graph->getTextsSharingEntity($coreId, true);
  $textSharingEt = $graph->getTextsSharingEntity($coreId, true);
  $silo->getNeighboursConnectedBy($coreNeoID); 
  $siloArray = $silo->makeURIs('json'); 
  $textConnections = $graph->listTextsConnectedToEntityWithID((int)$coreNeoID);
  $neighbourArrayOutput = array(); 
  foreach($neighbours as $row){
    $relation = $row['r'];
    $relatedNode = $row['t']; 
    $nodeProps = $relatedNode['properties']; 
    $nodePropsList = array(); 
    foreach ($nodeProps as $propkey => $propValue){
      if(array_key_exists($propkey, NODEMODEL[$relatedNode['labels'][0]])){
        $value = NODEMODEL[$relatedNode['labels'][0]][$propkey];
        $propCleanName = $value[0];
        $nodePropsList[] = array('name'=> $propCleanName, 'value'=> $propValue);
      }
    }
    $nodeRow = array('connectedTo'=>$relatedNode['labels'][0], 'relationType'=>$relation['type'], 'nodeProperties'=>$nodePropsList);
    $neighbourArrayOutput[]=$nodeRow; 
  }

  $relatedTexts = array();
  if(count($textConnections['annotations'])){
    //count annotations: 
    $annos = $textConnections['annotations']; 
    $texts = $textConnections ['texts']; 
    //display text: 
    foreach($texts as $tex){
      $texuri = $baseURI.'/text/'.$tex;
      $relatedTexts[] = array('id'=> $tex, 'uri'=> $texuri); 
    }
  }
  $egoProps = array();

  $model = NODEMODEL[$type]; 
  $props = $core['data'][0][0]['node']['properties'];
  //die();
  foreach($props as $property => $value){
    if(array_key_exists($property, $model)){
      $showAs = array($model[$property][0], $props[$property], $model[$property][1]);
      //$result['entity']['properties'][] = $showAs;
      $egoProps[]= $showAs;
    }
  }

  //sending it to the views-class:
  //$view = new View($type, array('egoNode' => $core, 'neighbours' => $neighbours, 'relatedTexts' => $textSharingEt));
  //$view->generateJSONOnly(false);
}else{
  echo json_encode(array('error' => 'The provided ID does not have matching record. The related node may be deleted, or it never existed.'));
  die();
}


//merging individual JSON-blocks built by the view-class

$variants = array(); 
$egoType = $type; 
$egoURI = $graph->generateURI($coreNeoID); 

$egoProperties = array(
  'type' => $egoType,
  'URI' => $egoURI,
  'primary_key' => $coreId,
  'properties' => $egoProps
); 

echo json_encode(
  array(
    'egonode' => $egoProperties,
    'project_relations' => $siloArray,
    'related_texts' => $relatedTexts,
    'connections' => $neighbourArrayOutput
  )
);

?>
