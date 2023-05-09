<?php 
session_start(); 
header('Content-Type: application/json; charset=utf-8');
include_once("../config/config.inc.php");
//only allow if there's a logged in user. 
if(!(isset($_SESSION['userid']))){
  echo json_encode(array('msg'=> 'failed', 'data'=>array('No login detected'))); 
  die(); 
}
//AND if there is a type set
if(!(isset($_GET['type']))){
  echo json_encode(array('msg'=> 'failed', 'data'=>array('Unknown type'))); 
  die();
}
//AND if the set type is part of the corenodes which can be set by uses!
if(!(array_key_exists($_GET['type'], CORENODES))){
  echo json_encode(array('msg'=> 'failed', 'data'=>array('Unknown type'))); 
  die(); 
}
//then, respond with the structure required to generate a form!
echo json_encode(array('msg'=> 'success', 'data'=>NODEMODEL[$_GET['type']])); 

die(); 

?>