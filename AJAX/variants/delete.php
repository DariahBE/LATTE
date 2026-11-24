<?php
header('Content-Type: application/json; charset=utf-8'); 

// OKAY converted to transactional model!
//only open for registered users
if (!defined('ROOT_DIR')) {
  include_once(dirname(dirname(__DIR__)).'/config/config.inc.php');
}
include_once(ROOT_DIR.'/includes/getnode.inc.php');
include_once(ROOT_DIR.'/includes/nodes_extend_cud.inc.php'); 
include_once(ROOT_DIR.'/includes/user.inc.php');
include_once(ROOT_DIR.'/includes/csrf.inc.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("HTTP/1.0 405 Method Not Allowed");
  echo json_encode(array('msg' => 'Method not allowed. Use POST.'));
  die();
}

//checks for user: Only a logged in user with rights can DELETE
$user = new User($client);
$user->checkAccess(TEXTSAREPUBLIC);
$user_id = $user->checkSession();
if($user_id === false || $user->hasEditRights($user->myRole) < 2){
  header("HTTP/1.0 403 Forbidden");
  echo json_encode(array('msg' => 'Insufficient permissions to delete variants.'));
  die();
}

$etNeoID = isset($_POST['entityid']) ? (int)$_POST['entityid'] : (isset($_GET['entityid']) ? (int)$_GET['entityid'] : 0);
$varuid = isset($_POST['variantid']) ? (int)$_POST['variantid'] : (isset($_GET['variantid']) ? (int)$_GET['variantid'] : 0);
$token = $_POST['token'] ?? $_GET['token'] ?? ''; 

$tokenManager = new CsrfTokenManager(); 
$validToken = $tokenManager->checkToken($token); 
if(!($validToken)){
    echo json_encode(array('msg' => 'Invalid session token')); 
    die();
}

//connect to graph database
$graph = new CUDNode($client);
$graph->startTransaction();


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