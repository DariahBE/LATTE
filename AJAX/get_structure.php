<?php 
session_start(); 
header('Content-Type: application/json; charset=utf-8');
include_once("../config/config.inc.php");

//only allow if there's a logged in user. 
if(!(isset($_SESSION['userid']))){
  die(); 
}
//AND if there is a type set
if(!(isset($_GET['type']))){
  die();
}
//AND if the set type is part of the corenodes which can be set by uses!
if(!(array_key_exists($_GET['type'], CORENODES))){
  die(); 
}
//then, respond with the structure required to generate a form!
echo json_encode(NODEMODEL[$_GET['type']]); 
die(); 

?>