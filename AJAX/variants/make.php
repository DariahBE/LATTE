<?php
header('Content-Type: application/json; charset=utf-8');

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

//checks for user: 
$user = new User($client);
$user->checkAccess(TEXTSAREPUBLIC);
$user_id = $user->checkSession();
if($user_id === false || $user->hasEditRights($user->myRole) < 1){
  header("HTTP/1.0 403 Forbidden");
  echo json_encode(array('msg' => 'Insufficient permissions to create variants.'));
  die();
}
//connect to graph database
$node = new CUDNode($client);

$varstring = $_POST['varlabel'] ?? $_GET['varlabel'] ?? '';
$connectToEt = isset($_POST['entity']) ? (int)$_POST['entity'] : (isset($_GET['entity']) ? (int)$_GET['entity'] : 0);

$token = $_POST['token'] ?? $_GET['token'] ?? ''; 
if(!$token){
  die();
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