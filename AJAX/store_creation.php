<?php
header('Content-Type: application/json; charset=utf-8');

if(!(isset($_SESSION) && boolval($_SESSION['userid']))){
    //creating new data is only allowed if a user is logged in: 
    //die($user_uuid);
    $redir = '?redir=/create.php';
    header('Location: /user/login.php'.$redir);
    die();
}else{
  $user = new User($client);
  $adminMode = $user->myRole == 'Admin';    //don't care here. 
}

include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'/includes/getnode.inc.php');
include_once(ROOT_DIR.'/includes/nodes_extend_cud.inc.php'); 
include_once(ROOT_DIR.'/includes/user.inc.php');

$graph = new CUDNode(); 

$data = $_POST; 
var_dump($data); 

$nodeLabel = $data['label']; 
$properties = $data['properties']; 
$creatingUser = (int)$_SESSION['userid']; 

$graph->createNewNode($nodeLabel, $properties); 
die();

?>