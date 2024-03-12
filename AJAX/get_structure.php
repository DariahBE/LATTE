<?php 
session_start(); 
header('Content-Type: application/json; charset=utf-8');
include_once("../config/config.inc.php");
//only allow if there's a logged in user. 
if(!(isset($_SESSION['userid']))){
  echo json_encode(array(
    'msg'=> 'failed', 
    'data'=>array('No login detected'),
    'datacode'=> 0
  )); 
  die(); 
}
//AND if there is a type set
if(!(isset($_GET['type']))){
  echo json_encode(array(
    'msg'=> 'failed', 
    'data'=>array('Unknown type'), 
    'datacode'=> 1              //for JS
  )); 
  die();
}
$exclude = false;
$setType = $_GET['type'];
if($setType === 'createNewAnnotation'){
  $setType = ANNONODE;
  $exclude = array(ANNOSTART, ANNOSTOP); 
}
//AND if the set type is part of the corenodes which can be set by uses!
if(!(array_key_exists($setType, CORENODES))){
  echo json_encode(array(
    'msg'=> 'failed', 
    'data'=>array('Unknown type'), 
    'datacode' => 1
  )); 
  die(); 
}
//then, respond with the structure required to generate a form!

echo json_encode(array('msg'=> 'success', 'data'=>NODEMODEL[$setType], 'exclude'=>$exclude)); 

die(); 

?>