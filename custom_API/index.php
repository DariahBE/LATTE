<?php
header('Content-Type: application/json; charset=utf-8');

include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'\includes\getnode.inc.php');
include_once(ROOT_DIR.'\includes\user.inc.php');
include_once(ROOT_DIR.'\includes\csrf.inc.php');
include_once(ROOT_DIR.'\includes\nodes_extend_cud.inc.php');
include_once(ROOT_DIR.'\includes\customapi.inc.php');
include_once(ROOT_DIR.'\custom_api\config\config.php');

//die('okido'); 

$api_profile = $_GET['profile']; 
$profile_secret = $_GET['secret']; 
$request_type = $_GET['type']; 

$api = new API($api_settings); 



//die(); 

$api->checkrequestsecret($api_profile, $profile_secret); 
$api->readrequests($request_type); 

$api->makeCypherStatement(); 
var_dump($api->search_parameters); 
