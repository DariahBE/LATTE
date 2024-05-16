<?php
header('Content-Type: application/json; charset=utf-8');

//only open for registered users
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'/includes/getnode.inc.php');
include_once(ROOT_DIR.'/includes/nodes_extend_cud.inc.php'); 
include_once(ROOT_DIR.'/includes/user.inc.php');
include_once(ROOT_DIR.'/includes/csrf.inc.php');

//checks for user: 
$user = new User($client);
$user->checkAccess(TEXTSAREPUBLIC);
$user_id = $user->checkSession();
if($user_id === false){
  die();        //only registered users can make changes to the database.
}
//connect to graph database
$node = new CUDNode($client);

$varstring = $_GET['varlabel'];
$connectToEt = (int)$_GET['entity'];


//CSRF token : same way of checking it as elsewhere. 
if(!isset($_GET['token'])){
  die();
}else{
  $token = $_GET['token']; 
}
$tokenManager = new CsrfTokenManager(); 
$validToken = $tokenManager->checkToken($token); 
if(!($validToken)){
  echo json_encode(array('msg' => 'Invalid session token')); 
  die();
}

$node->startTransaction(); 
try {
  $repl = $node->createVariantRelation($varstring, $connectToEt);
} catch (\Throwable $th) {
  //throw $th;
  $node->rollbackTransaction();
  echo json_encode(array('msg'=>'An error ocurred in the database'));
  die();
}

$node->commitTransaction();
echo json_encode($repl);
die();
?>