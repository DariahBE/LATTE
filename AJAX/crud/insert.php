<?php
/*

include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'\includes\getnode.inc.php');
include_once(ROOT_DIR.'\includes\nodes_extend_cud.inc.php');
include_once(ROOT_DIR.'\includes\user.inc.php');

//Endpoint nodes_extend_cud.inc.php has a method to check user login status. 
$node = new CUDNode;
$input = $_POST; 
var_dump($input); 
die();

//$node->createNewNode($input['label'], $input['data'], true); 
//read the post request: 
*/
header('Content-Type: application/json; charset=utf-8');

include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'\includes\getnode.inc.php');
include_once(ROOT_DIR.'\includes\nodes_extend_cud.inc.php');
include_once(ROOT_DIR.'\includes\user.inc.php');
include_once(ROOT_DIR.'\includes\csrf.inc.php');

$submitdata = $_POST; 
var_dump($submitdata);

$token = $submitdata['token']; 
var_dump($token); 


?>