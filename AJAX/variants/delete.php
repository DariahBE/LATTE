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

//gets the neoID for both connected nodes. 
$etNeoID = $_GET['et'];
$varuid = $_GET['var']

//gets the node ID where the variant connects to

//count connections from variant to any other node over the 'same_as' relation.

//if there's more than one, the node cannot be deleted. 

?>