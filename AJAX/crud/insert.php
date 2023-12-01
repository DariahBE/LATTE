<?php
header('Content-Type: application/json; charset=utf-8');

include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'\includes\getnode.inc.php');
include_once(ROOT_DIR.'\includes\nodes_extend_cud.inc.php');
include_once(ROOT_DIR.'\includes\user.inc.php');
include_once(ROOT_DIR.'\includes\csrf.inc.php');

$submitdata = $_POST; 
$token = $submitdata['token']; 
$form = $submitdata['formdata']; 
$entity_type = $submitdata['etype']; 
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

// if token is accepted and ETType is valid: insert it as ETTYpe with $form as properties: 
foreach ($submitdata['formdata'] as $key => $value) {
    if(!(array_key_exists($key, NODEMODEL[$entity_type]))){
        die(json_encode(array('error'=>'Invalid request. (invalid property)')));
    }
}


//foreach loop has passed ==> new to be created node is definitely valid: SO create it. 
$node->startTransaction();
$graphResult = $node->createNewNode($entity_type, $submitdata['formdata'], true); 
$node->commitTransaction(); 

//get the stable identifier of the element. 
$uri = $node->generateURI($graphResult); 
echo json_encode(array('stable'=> $uri)); 
?>