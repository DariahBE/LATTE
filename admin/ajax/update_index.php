<?php
header('Content-Type: application/json; charset=utf-8');
// Base imports: 
include_once($_SERVER["DOCUMENT_ROOT"]."/config/config.inc.php");
include_once(ROOT_DIR."/includes/client.inc.php");
include_once(ROOT_DIR."/includes/user.inc.php");
include_once(ROOT_DIR."/includes/csrf.inc.php");
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

if(isset($_GET['action']) && isset($_GET['label']) && isset($_GET['prop'])){
    $action = $_GET['action']; 
    $label = $_GET['label']; 
    $prop = $_GET['prop']; 
    $idxname = null; 
    if($action === 'drop'){
        $idxname = $_GET['idxname']; 
    }
    $result = $node->modifyIndex($label, $prop, $action, $idxname); 
    echo json_encode($result);
}else{
    die(); 
}

?>