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

var_dump($_POST);
$data = $_POST['sourceNeoID']; 
die(); 

//$annotation->createAnnotationWithExistingEt((int)$_GET[''], (int)$_GET[''], $user); 


#cyper query that creates a new node with label Annotation and connects it to two other nodes by passing the internal ID

?>