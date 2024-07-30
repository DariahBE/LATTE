<?php

session_start(); 
header('Content-Type: application/json; charset=utf-8');
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR."/includes/client.inc.php");
include_once(ROOT_DIR.'/includes/getnode.inc.php');
include_once(ROOT_DIR.'/includes/nodes_extend_cud.inc.php');
include_once(ROOT_DIR.'/includes/user.inc.php');
include_once(ROOT_DIR.'/includes/csrf.inc.php');

$postdata = $_POST;
$token = $postdata['csrf_token']; 

$tokenManager = new CsrfTokenManager(); 
$node = new CUDNode($client);
$node->startTransaction(); 
if(!($tokenManager->checkToken($token))){
    die(); 
}

//1 check if extra parameters are present in the psotdata request. 
//otherwise kill it!
if(!array_key_exists('app_logic_db_label', $postdata)){
    die(); 
}
if(!array_key_exists('app_logic_db_neoid', $postdata)){
    die(); 
}
//else: if both app_logic_db_* elements exist: read and use them. 
$neo_id_constraint = (int)$postdata['app_logic_db_neoid']; 
$neo_label_constraint = $postdata['app_logic_db_label']; 

//2 get NODESMODEL to know which key value pairs shoulc be retained and which are tampered with. 
//check if app_logic_db_label is a defined label in the nodemodel. The definitions are needed to validate the POST request. 
if(!(array_key_exists($neo_label_constraint, NODEMODEL))){
    die();
}
$modelslice = NODEMODEL[$neo_label_constraint]; 


//token accepted: 
//var_dump($modelslice); 
//3 iterate over POSTDATE dand compare to NODESMODEL


//var_dump($postdata); 
$datadir = array(); 
$retain = array_intersect_key($postdata, $modelslice);

//4 do a query constraint: Updata node with ID == <read from post> and label == <read from post>
// we can have datafields which are empty: this means you want to UNSET the values for those fields
// (i.e. the property gets removed from the NODE together with the stored value!)
// non-empty fields should be updated (i.e. the property gets assigned a new value)
// ===> https://neo4j.com/docs/cypher-manual/current/clauses/remove/ 

$node->updateNode((int)$neo_id_constraint, $retain); 



//5 kill the token: 
$tokenManager->revokeToken(); 
//if everything went okay > Commit data: 
$node->commitTransaction();
?>