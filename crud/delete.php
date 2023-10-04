<?php
//TODO: integration in app still pending.
//check conflicting implementation in AJAX/Crud/delete.php
header('Content-Type: application/json; charset=utf-8');

include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'\includes\getnode.inc.php');
include_once(ROOT_DIR.'\includes\user.inc.php');
include_once(ROOT_DIR.'\includes\csrf.inc.php');
include_once(ROOT_DIR.'\includes\nodes_extend_cud.inc.php');

if(!isset($_GET['id'])){
    die();
}
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

$user = new User($client);
$user_uuid = $user->checkSession();
if($user_uuid === false){
    die('login required');
}
$crudNode = new CUDNode($client); 


$crudNode->startTransaction();
try{
    $data = $crudNode->delete((int)$_GET['id'], true);
} catch(\Throwable $th) {
    $crudNode->rollbackTransaction();
    echo json_encode(array('msg'=>'Node could not be deleted'));   
    die(); 
}
$crudNode->commitTransaction();
echo json_encode($data);


?>