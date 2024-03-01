<?php
/*
        IMPLEMENTS CRUD API ENDPOINT for all operations
        related to knowledgebases. 
        Read = open
        create, update and delete require: 
            tokenmanager
            csrf token
*/
header('Content-Type: application/json; charset=utf-8');
include_once("../config/config.inc.php");
include_once(ROOT_DIR."\includes\getnode.inc.php");
include_once(ROOT_DIR.'\includes\nodes_extend_cud.inc.php');
include_once(ROOT_DIR.'\includes\csrf.inc.php');

$graph = new CUDNode($client);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function required_get($name){
    if (isset($_GET[$name])){
        return $_GET[$name]; 
    }
    echo json_encode(array('err'=> 'Incomplete request')); 
    die(); 
}

$nodeNeoId = (int)$_GET['id'];
$allowed_modes = ['create', 'update', 'delete'];    //or read, that is however the defaultmode!

if(isset($_GET['mode']) &&  in_array($_GET['mode'], $allowed_modes) ){
    $mode = $_GET['mode']; 
    $kbid = required_get('kb'); 
    $token = required_get('token');
    $tokenManager = new CsrfTokenManager(); 
}else{
    $mode = 'read'; 
}


if($mode == 'read'){
    echo json_encode($graph->fetchKnowledgebase($nodeNeoId)); 
    die(); 
}
if($mode == 'create'){
    //TODO ==> implement create method
    die(); 
}
if($mode == 'update'){
    //TODO ==> implement update method
    die(); 
}
if($mode == 'delete'){
    //TODO ==> implement delete method
    die(); 
}

?>