<?php
header('Content-Type: application/json; charset=utf-8');
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'/includes/getnode.inc.php');
include_once(ROOT_DIR.'/includes/user.inc.php');
include_once(ROOT_DIR.'/includes/search.inc.php');

//check access policy: endpoint should die() when data is not configured to be openly accessible.
//both text and entities have to be public, otherwise die()
$user = new User($client);
$user->checkAccess(TEXTSAREPUBLIC &&  ENTITIESAREPUBLIC);

$labelname = $_POST['node']; 
$labeloptions = $_POST['options']; 

if (!array_key_exists($labelname, NODEMODEL)){
  die(json_encode(array('searchparameters invalid'))); 
}
//pagination
$page = 0;
if(isset($_GET['p'])){$page = (int)$_GET['p'];}

function merge($datarow){
  //set shorten to true if it's the textproperty in the Text node!
  global $graph;
  $neoID = $datarow['id'];
  $stableURI = $graph->generateURI($neoID);
  $rowLabel = $datarow['labels'][0];
  $rowProperties = $datarow['properties']; 
  $model = NODEMODEL[$rowLabel]; 
  $rowResponse = array(
    'neoid' => (int)$neoID,
    'stable' => $stableURI,
    'label' => $rowLabel, 
    'properties' => array()
  ); 
  //format properties according to NODEMODEL: 
  foreach($rowProperties as $propname => $propval){
    if (array_key_exists($propname, $model)){
      $shorten = ($rowLabel === TEXNODE && $propname === TEXNODETEXT) ? True : False;
      $rowResponse['properties'][] = array($model[$propname][0], $model[$propname][1], $propval, $shorten);
    }
  }
  return array('neo'=>(int)$neoID, 'data'=>$rowResponse);
}

$search = new Search($client); 
$graph = new Node($client); 
$data = $search->directNodeSearch($labeloptions, $labelname, $page); 
$controlledResponse = array(); 
foreach ($data->getresults() as $rowkey=> $row){
  $rowKeys = $row->keys(); 
  //default = null; if it exists ==> override null by row value!
  $rowDirect = null;
  $rowIndirect = null; 
  foreach($rowKeys as $key){
    if ($key === 'n'){
      $rowDirect = $row['n'];
    }
    if ($key === 'q'){
      $rowIndirect = $row['q'];
    }
  }
  if(!(is_null($rowIndirect))){
    $method = 'indirect';
    $rowResponse = merge($rowIndirect);
    $controlledResponse[$rowResponse['neo']]= $rowResponse['data'];
  }
  if(!(is_null($rowDirect))){
    $method = 'direct';
    $rowResponse = merge($rowDirect);
    $controlledResponse[$rowResponse['neo']]= $rowResponse['data'];
  }
}
echo json_encode($controlledResponse);


?>