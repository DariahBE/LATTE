<?php
/**
 *      Bulk insert endpoint of texts into NEO. 
 * 
 * Only open to admin users. 
 */

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


//Check admin from session object; no admin, no access. No csrf token. 
session_start(); 
if($_SESSION['userrole'] !== "Admin"){
    header("HTTP/1.0 403 Forbidden");
    die("Insufficient rights, forbidden access");
}
//required for reading the model: 
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'/includes/getnode.inc.php');


//get POST data when it is sent.
$keys = json_decode($_POST['keys'], false);
$data = json_decode($_POST['data'], false);

//check if postdata matches the model: 
$nodetype = TEXNODE;
$primary = helper_extractPrimary($nodeType);
if (in_array($primary, $keys) === false){
    die(json_encode(array('error'=>'Invalid request.', "msg"=>"Primary key is undefined.")));
}
foreach ($keys as $key) {
    if(!(array_key_exists($key, NODEMODEL[$nodetype]))){
        die(json_encode(array('error'=>'Invalid request.', "msg"=> "Invalid keys in request.")));
    }
}

include_once(ROOT_DIR.'/includes/getnode.inc.php');
include_once(ROOT_DIR.'/includes/nodes_extend_cud.inc.php');
include_once(ROOT_DIR.'/includes/user.inc.php');




$user = new User($client);
$node = new CUDNode($client);
$node->startTransaction();
foreach ($data as $new_text) {
    $formdata = array_combine($keys, $new_text);
    $graphResult = $node->createNewNode($nodetype, $formdata, true);
    $connection = $node->connectCreatorToNode($_SESSION['neoid'], $graphResult); 
}


$node->commitTransaction(); 
die(); 
?>