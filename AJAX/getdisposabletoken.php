<?php
//session_start(); 
include_once("../config/config.inc.php");

header('Content-Type: application/json; charset=utf-8');

include_once(ROOT_DIR.'/includes/csrf.inc.php');
include_once(ROOT_DIR.'/includes/getnode.inc.php');
include_once(ROOT_DIR.'/includes/user.inc.php');

//check user
$user = new User($client);
$user_id = $user->checkSession();

//if there is a task set as get parameter you need to check if the 
//user asking to perform the task has sufficient rights. If not the 
//token does not get assigned. Assigning a toke automatically means
//the user has sufficient rights!!
//You can't use this method everywhere, sometimes you need extra
//info about the node ownership, here' we assume NO ownership! This 
//could lead to false negatives for lowlevel users wanting to update
//owned nodes
if(isset($_GET['task'])){
    //ADDING  == Level 1
    //UPDATING == Level 2
    //DELETING == Level 3
    $taskLevel = (int)$_GET['task']; 
    $userLevel = $user->hasEditRights($user->myRole); 
    if ($taskLevel > $userLevel){
        die(); 
    }
}

//you cannot use boolval. if the userid == 0; it is a valid ID in the 
//  neo4j scheme, but will cast to FALSE!
if($user_id !== False){
    $tokenManager = new CsrfTokenManager; 
    $tokenManager->revokeToken();
    $tokenManager->generateToken();
    echo json_encode($tokenManager->getTokenFromSession()); 
}
?>