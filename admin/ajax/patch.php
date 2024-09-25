<?php
header('Content-Type: application/json; charset=utf-8');

include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'/includes/getnode.inc.php');
include_once(ROOT_DIR.'/includes/user.inc.php');
include_once(ROOT_DIR.'/includes/csrf.inc.php');
include_once(ROOT_DIR.'/includes/integrity.inc.php');

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

  //check CSRF
  $token = False;
  if(isset($_POST['token'])){
    $token = $_POST['token']; 
  }
  $tokenManager = new CsrfTokenManager();
  $tokenIsValid = $tokenManager->checkToken($token);

  if(!($tokenIsValid && $adminMode)){
    header("HTTP/1.0 403 Forbidden");
    die();
  }else{
    // CSRF is valid and the user is an admin: 
    //revoke the CSRF it's a single use token.
    $tokenManager->revokeToken();
    $integrity = new Integrity($client); 
    switch ($_GET['operation']){
        case "noderemoval":
            // Code to execute for option 1
            $integrity->deleteNodesNotMatchingModel($_GET['nodename']); 
            break;
        case "fixuuid":
            // Code to execute for option 2
            $integrity->asignUUIDToNodes(); 
            break;
        case "option3":
            // Code to execute for option 3
            echo "Option 3 selected";
            break;
        default:
            // Code to execute if none of the options match
            header("HTTP/1.0 403 Forbidden");
            die();
        }
  }



?>