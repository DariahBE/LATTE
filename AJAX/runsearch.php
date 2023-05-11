<?php
header('Content-Type: application/json; charset=utf-8');
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'/includes/getnode.inc.php');
include_once(ROOT_DIR.'/includes/user.inc.php');
include_once(ROOT_DIR.'/includes/search.inc.php');

$labelname = $_POST['node']; 
$labeloptions = $_POST['options']; 

if (!array_key_exists($labelname, NODEMODEL)){
  die(json_encode(array('searchparameters invalid'))); 
}
//pagination
$page = 0;
if(isset($_GET['p'])){$page = (int)$_GET['p'];}

$search = new Search($client); 
$graph = new Node($client); 
$data = $search->directNodeSearch($labeloptions, $labelname, $page); 

$controlledResponse = array(); 
foreach ($data->getresults() as $rowkey=> $row){
  $row = $row['n'];
  $neoID = $row['id'];
  $stableURI = $graph->generateURI($neoID);
  $rowLabel = $row['labels'][0];
  $rowProperties = $row['properties']; 
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
      $rowResponse['properties'][] = array($model[$propname][0], $model[$propname][1], $propval);
    }
  }
  $controlledResponse[$neoID]= $rowResponse;
  //echo json_encode($row);
}

echo json_encode($controlledResponse);


?>