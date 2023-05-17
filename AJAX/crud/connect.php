<?php
header('Content-Type: application/json; charset=utf-8');
include_once("../../config/config.inc.php");
include_once(ROOT_DIR."\includes\getnode.inc.php");
include_once(ROOT_DIR."\includes\annotation.inc.php");
include_once(ROOT_DIR."\includes\user.inc.php");

//check if the user is logged in; 
if(isset($_SESSION['userid'])){
  $user = new User($client);
  $annotation = new Annotation($client);
}else{
  die();
}

$data = $_POST;
$texID = (int)$data['texNeoid'];
$entityID = (int)$data['sourceNeoID'];
$token = $data['csrf'];

//connectiontoken should not be older than 5 minutes. 
//check if token equals the session variable and that the session did not yet expire 
if (isset($_SESSION['connectiontokencreatetime']) && isset($_SESSION['connectiontoken']) && $token === $_SESSION['connectiontoken'] && time() - $_SESSION['connectiontokencreatetime'] < 300 ){
  //destroy the token: can only be used once. 
  //var_dump(time() - $_SESSION['connectiontokencreatetime']); 
  unset($_SESSION['connectiontoken']);
  unset($_SESSION['connectiontokencreatetime']);

  $annotation->createAnnotationWithExistingEt($texID, $entityID, $user); 

}else{
  die('Insecure or expired request.'); 
}



#cyper query that creates a new node with label Annotation and connects it to two other nodes by passing the internal ID

?>