<?php
header('Content-Type: application/json; charset=utf-8');
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'/includes/getnode.inc.php');
include_once(ROOT_DIR.'/includes/user.inc.php');

//only open for registered users
$user = new User($client);
$user->checkAccess(False);  //automatically go to session checking.

//fetches an entity by ID and returns the properties and alternate spellings. 
$response = array(); 
$id = (int)$_GET['id']; 

//make a graph object ==> use appropriate method. 
$graph = new Node($client);
$embeddedPropsFull = $graph->fetchEtById($id);
$embeddedProps = $embeddedPropsFull[0]; 
$rawprops = $embeddedPropsFull[1]; 

$relatedVariants = $graph->fetchAltSpellingsById($id);

$stableLink = $graph->generateURI($id); 

$replData = array(
    //'props'=> $embeddedProps[0], 
    'preprocessed_props'=> $embeddedProps, 
    'props'=> $rawprops,
    'variantSpellings' => $relatedVariants, 
    'stable' => $stableLink
);

if(isset($_GET['extended']) && ($_GET['extended']==1)){
    //add the label of the node
    $label = $graph->fetchLabelById($id);
    $replData['extra']['label'] = $label;

    //add the relational model
    $replData['extra']['model']  = $graph->fetchModelByLabel($label);
}

echo json_encode($replData ); 



?>