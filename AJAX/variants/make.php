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

$varstring = $_GET['varlabel']; 
$connectToEt = (int)$_GET['entity']; 

$graph->createVariantRelation($varstring, $connectToEt); 


//creates a UID

//gets the node ID where the variant connects to

//Checks if there's already a node with that label

//if not; create node and edge
//if true; create edge only and connect it to the existing variant and entity.

?>