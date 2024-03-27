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
$node = new Node($client); 


//var_dump($node); 
//die(); 

$api->checkrequestsecret($api_profile, $profile_secret); 
$api->readrequests($request_type); 
$api->restrictNodeByParameters(); 

$api->makeCypherStatement(); 

$data = $node->executePremadeParameterizedQuery(
    $api->getQuery(),
    $api->getParams()
); 


//echo json_encode($data); 


$echodata = array(
    'entities' => array(), 
); 
if($api->vars_required())

// the extra behaviour: 
//  1) is the stable URI required to be returned?
foreach ($data as $key => $noderecord) {
    $neoid = (int)$noderecord['n']['id']; 
    if($api->uri_required()){
        $node->generateURI($neoid); 
    }
}

if($api->uri_required()){
    //foreach neoid of a node generate a stable uri!

};
//  2) Are variant nodes required to be returned?

