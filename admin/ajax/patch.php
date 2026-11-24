<?php
header('Content-Type: application/json; charset=utf-8');

include_once(dirname(__DIR__, 2) . '/config/config.inc.php');
include_once(ROOT_DIR . '/includes/getnode.inc.php');
include_once(ROOT_DIR . '/includes/user.inc.php');
include_once(ROOT_DIR . '/includes/csrf.inc.php');
include_once(ROOT_DIR . '/includes/integrity.inc.php');

$user = new User($client);
$user_id = $user->checkSession();
if (!$user_id) {
    header("Location: " . BASE_URL . "/user/login.php?redir=" . urlencode(BASE_URL . "/admin/index.php"));
    exit("Redirect required"); 
}
// Only allow admins here: 
if (!$user->isAdmin()) {
    header("HTTP/1.0 403 Forbidden");
    exit();
}
$adminMode = true;

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
    $operation = isset($_POST['operation']) ? $_POST['operation'] : (isset($_GET['operation']) ? $_GET['operation'] : '');
    $nodename = isset($_POST['nodename']) ? $_POST['nodename'] : (isset($_GET['nodename']) ? $_GET['nodename'] : '');
    switch ($operation){
        case "noderemoval":
            // Code to execute for option 1.
            $integrity->deleteNodesNotMatchingModel($nodename); 
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