<?php
header('Content-Type: application/json; charset=utf-8');
// Base imports: 
include_once($_SERVER["DOCUMENT_ROOT"]."/config/config.inc.php");
include_once(ROOT_DIR."/includes/client.inc.php");
include_once(ROOT_DIR."/includes/user.inc.php");
include_once(ROOT_DIR."/includes/csrf.inc.php");
include_once(ROOT_DIR."/includes/getnode.inc.php");


//          !!!!!!!!!!!!!!!!!!!!!
//test if user === admin
if(isset($_SESSION["userid"])){
    $user = new User($client);
  }else{
    header("Location: /user/login.php?redir=/admin/index.php");
    die("redir required"); 
  }
  //only allow admins here; 
  $adminMode = False;
  if($user->myRole !== "Admin"){
    header("HTTP/1.0 403 Forbidden");
    die();
  }else{
    $adminMode = True;
  }

  $node = new Node($client); 

//check and revoke the token here: 
if(isset($_POST['token'])){
    $token = $_POST['token'];
    $tokenManager = new CsrfTokenManager();
    $validToken = $tokenManager->checkToken($token); 
    if(!($validToken)){
        die();
    }
    $tokenManager->revokeToken();   //kill the token if valid and continue code logic
}else{
    die(); 
}

//          code continues if token is valid (at this point, token has been revoked): 

if(isset($_POST['action']) && isset($_POST['label']) && isset($_POST['prop'])){
    $action = $_POST['action']; 
    $label = $_POST['label']; 
    $prop = $_POST['prop']; 
    $idxname = null; 
    if($action === 'drop'){
        if(isset($_POST['idxname'])){
            $idxname = $_POST['idxname']; 
        }
    }
    $result = $node->modifyIndex($label, $prop, $action, $idxname); 
    echo json_encode($result);
}else{
    die(); 
}

?>