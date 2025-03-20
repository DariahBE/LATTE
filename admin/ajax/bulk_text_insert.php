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

//get POST data when it is sent.
$keys = json_decode($_POST['keys'], false);
$data = json_decode($_POST['data'], false);

//check if postdata matches the model: 
$nodetype = TEXNODE;
foreach ($keys as $key) {
    if(!(array_key_exists($key, NODEMODEL[$nodetype]))){
        die(json_encode(array('error'=>'Invalid request.')));
    }
}

include_once(ROOT_DIR.'/includes/getnode.inc.php');
include_once(ROOT_DIR.'/includes/nodes_extend_cud.inc.php');




$node = new CUDNode($client);
$node->startTransaction();
foreach ($data as $new_text) {
    $formdata = array_combine($keys, $new_text);
    //TODO: enforce datatypes!! (NEO4J will type according to the given data-object. So typing should be done here.)
    var_dump($formdata);
    // $node->createNewNode($nodetype, $formdata, true);
}


$node->commitTransaction(); 
die(); 
?>