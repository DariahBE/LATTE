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

$node->startTransaction(); 

//token accepted: 
//3 iterate over POSTDATA and compare to NODESMODEL

$datadir = array(); 
$retain = array_intersect_key($postdata, $modelslice);
//4 do a query constraint: Updata node with ID == <read from post> and label == <read from post>
// we can have datafields which are empty: this means you want to UNSET the values for those fields
// (i.e. the property gets removed from the NODE together with the stored value!)
// non-empty fields should be updated (i.e. the property gets assigned a new value)
// ===> https://neo4j.com/docs/cypher-manual/current/clauses/remove/ 
$all_bools = array_filter($modelslice, function($key): bool{
    return $key[1] === 'bool'; 
}); 
// if the array_key_exists in $all_bools but not in $retain, then it means the property has to be unset!
$filteredKeys = array_filter(array_keys($all_bools), function($key) use ($retain) {
    return !in_array($key, $retain); // Return true if key is not in $retain
});
//everything in $filteredKeys has to be unset (remove) from the given node!
foreach ($retain as $key => $value) {
    $type = $modelslice[$key][1]; 
    if($type === 'int'){
        if($retain[$key]==''){
            $filteredKeys[] = $key;
            unset($retain[$key]); 
        }else{
            $retain[$key] = (int)$retain[$key]; 
        }
    } else if ($type === 'bool'){
        $retain[$key] = (bool)$retain[$key]; 
    } else if ($type === 'float'){
        $retain[$key] = floatval($retain[$key]); 
    }
}
//casting of $retain data should be done there!
try {
    $node->updateNode((int)$neo_id_constraint, $retain, $filteredKeys); 
    $node->commitTransaction();
} catch (\Throwable $th) {
    //throw $th;
    $node->rollbackTransaction(); 
}



//5 kill the token: 
$tokenManager->revokeToken(); 
?>