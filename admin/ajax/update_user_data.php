<?php
/**
 *   UPDATE ENDPOINT FOR THE USER PROFILES, ONLY ACCESIBLE BY ADMIN USERS 
 */


 //TODO: work in progress.
 header('Content-Type: application/json; charset=utf-8');


 include_once($_SERVER["DOCUMENT_ROOT"]."/config/config.inc.php");
 include_once(ROOT_DIR."/includes/client.inc.php");
 include_once(ROOT_DIR."/includes/user.inc.php");
 include_once(ROOT_DIR."/includes/csrf.inc.php");

$token = $_POST['token'];
$tokenManager = new CsrfTokenManager();
$tokenIsValid = $tokenManager->checkToken($token);
if(!($tokenIsValid)){
  echo json_encode(array('msg'=>'request rejected.'));
  die();
}

//check if user is an admin, else: kill request. 
if(isset($_SESSION["userid"])){
    $user = new User($client);
  }else{
    header("Location: /user/login.php?redir=/admin/index.php");
    die("redir required"); 
  }
  //only allow admins here; 
  if($user->myRole !== "Admin"){
    header("HTTP/1.0 403 Forbidden");
    die("Insufficient rights, forbidden access");
}


// // Get the raw POST data
// $postData = file_get_contents('php://input');

// // Decode the JSON data
// $data = json_decode($postData, true);

// // Debugging: Check the received data
// var_dump($data);


$userID = $_POST['userId']; 
$newRole = $_POST['selectedRole']; 
$updateResult = $user->promoteUser($userID, $newRole);
var_dump($updateResult); 
?>