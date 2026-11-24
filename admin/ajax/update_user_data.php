<?php
/**
 *   UPDATE ENDPOINT FOR THE USER PROFILES, ONLY ACCESIBLE BY ADMIN USERS 
 */


 header('Content-Type: application/json; charset=utf-8');


include_once(dirname(__DIR__, 2) . "/config/config.inc.php");
include_once(ROOT_DIR . "/includes/client.inc.php");
include_once(ROOT_DIR . "/includes/user.inc.php");
include_once(ROOT_DIR . "/includes/csrf.inc.php");

$token = isset($_POST['token']) ? $_POST['token'] : '';
$tokenManager = new CsrfTokenManager();
$tokenIsValid = $tokenManager->checkToken($token);
if(!($tokenIsValid)){
  echo json_encode(array('msg'=>'request rejected.'));
  die();
}

// Check if user is an admin, else kill request. 
$user = new User($client);
$user_id = $user->checkSession();
if (!$user_id) {
    header("Location: " . BASE_URL . "/user/login.php?redir=" . urlencode(BASE_URL . "/admin/index.php"));
    exit("Redirect required"); 
}
// Only allow admins here: 
if (!$user->isAdmin()) {
    header("HTTP/1.0 403 Forbidden");
    die("Insufficient rights, forbidden access");
}

if($_POST['action'] == 'block'){
  $userID = $_POST['userId']; 
  $toggleTo = $_POST['blockValue']; 
  $updateResult = $user->setBlockTo($userID, $toggleTo);
  $good = $updateResult === 1 ? True : False;
  echo json_encode(array('success'=>$good)); 
} else if($_POST['action'] == 'reset'){
  $userID = $_POST['userId']; 
  //you need to retrieve the usermail by passing the userid to the backend.
  $usermail = $user->getMailFromUUID($userID);
  //add the true flag: text will reflect the reset was performed by the admin. 
  $updateResult = $user->requestPasswordReset($usermail, True)[0];
  $good = $updateResult === 1 ? True : False;
  echo json_encode(array('success'=>$good)); 
}else{
  $userID = $_POST['userId']; 
  $newRole = $_POST['selectedRole']; 
  $updateResult = $user->promoteUser($userID, $newRole);
  $good = $updateResult === 1 ? True : False;
  echo json_encode(array('success'=>$good));
}
?>