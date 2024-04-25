<?php
header('Content-Type: application/json; charset=utf-8'); 

// OKAY converted to transactional model!
//only open for registered users
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'/includes/getnode.inc.php');
include_once(ROOT_DIR.'/includes/nodes_extend_cud.inc.php'); 
include_once(ROOT_DIR.'/includes/user.inc.php');
include_once(ROOT_DIR.'/includes/csrf.inc.php');

//checks for user: Only a logged in user can DELETE
$user = new User($client);
$user->checkAccess(TEXTSAREPUBLIC);
$user_uuid = $user->checkSession();
if(!(boolval($user_uuid))){
  die();
}
//connect to graph database
$graph = new CUDNode($client);
$graph->startTransaction(); 
//var_dump($graph); 

//gets the neoID for both connected nodes.  //cast to integers!!
$etNeoID = (int)$_GET['entityid'];
$varuid = (int)$_GET['variantid'];
$securityToken = $_GET['token']; 


$tokenManager = new CsrfTokenManager(); 
$validToken = $tokenManager->checkToken($securityToken); 
if(!($validToken)){
    echo json_encode(array('msg' => 'Invalid session token')); 
    die();
}


try {
  //preparation: 
  //1. count connections from variant to any other node over the 'same_as' relation.
  $res = $graph->countConnectionsOver($varuid, 'same_as'); 
  $countVar = $res->first()->get('count');
  //2. count connections between the two given ids over the same_as relation
  $res = $graph->countConnectionsBetweenAndOver($varuid, $etNeoID, 'same_as'); 
  $countConnect = $res->first()->get('count');

  //decisionMaking:
  $output = $graph->dropVariant($varuid, $etNeoID, $countVar == $countConnect);

} catch (\Throwable $th) {
  $graph->rollbackTransaction();
  die('Could not remove variant. '); 
}


echo json_encode($countConnect);
$tokenManager->revokeToken(); 
$graph->commitTransaction();


//if there's more than one, the node cannot be deleted. 

?>