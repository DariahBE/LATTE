<?php
header('Content-Type: application/json; charset=utf-8');

//only open for registered users
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'/includes/getnode.inc.php');
include_once(ROOT_DIR.'/includes/nodes_extend_cud.inc.php'); 
include_once(ROOT_DIR.'/includes/user.inc.php');

//checks for user: 
$user = new User($client);
$user->checkAccess(TEXTSAREPUBLIC);
$user_uuid = $user->checkSession();
if(!(boolval($user_uuid))){
  die();
}
//connect to graph database
$graph = new CUDNode($client);


//gets the neoID for both connected nodes.  //cast to integers!!
$etNeoID = (int)$_GET['entityid'];
$varuid = (int)$_GET['variantid'];

//preparation: 
//1. count connections from variant to any other node over the 'same_as' relation.
$res = $graph->countConnectionsOver($varuid, 'same_as'); 
$countVar = $res->first()->get('count');
//2. count connections between the two given ids over the same_as relation
$res = $graph->countConnectionsBetweenAndOver($varuid, $etNeoID, 'same_as'); 
$countConnect = $res->first()->get('count');

//decisionMaking:
$output = $graph->dropVariant($varuid, $etNeoID, $countVar == $countConnect);
var_dump($output); 

echo json_encode($countConnect);


//if there's more than one, the node cannot be deleted. 

?>