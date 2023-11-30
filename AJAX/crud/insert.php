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
/*var_dump($_POST);
*/
$token = $submitdata['token']; 
$form = $submitdata['formdata']; 
$entity_type = $submitdata['etype']; 
//var_dump($token); 


//check if the token is valid; if so: use it once and invallidate it. 

$tokenManager = new CsrfTokenManager();
$node = new CUDNode($client);

//check the token ==> die if invalid
//TODO anonymyze error messages. 
if(!($tokenManager->checkToken($token))){
    die(json_encode(array('error'=>'Invalid request (invalid token).'))); 
}

if(!(array_key_exists($entity_type,NODEMODEL))){
    die(json_encode(array('error'=>'Invalid request. (invalid nodetyped)'))); 
}

if(!array_key_exists('formdata', $submitdata)){
    die(json_encode(array('error'=>'Invalid request. (invalid data submission)'))); 
}

// if token is accepted and ETType is valid: insert it as ETTYpe iwth $fortm as properties: 
var_dump($submitdata);
foreach ($submitdata['formdata'] as $key => $value) {
    if(!(array_key_exists($key, NODEMODEL[$entity_type]))){
        die(json_encode(array('error'=>'Invalid request. (invalid property)')));
    }
    //check the typology of $value based on NODEMODEL definition: 
    // not needed: CUDNODE has a private method that deals with this when calling: createNewNode(); 
    //$required_type = NODEMODEL[$entity_type][$key][1]; 
}


//foreach loop has passed ==> new to be created node is definitely valid: SO create it. 
//                  $label, $data, $createUID = true
$node->startTransaction();
$graphResult = $node->createNewNode($entity_type, $submitdata['formdata'], true); 
$node->commitTransaction(); 
var_dump($graphResult); 


?>