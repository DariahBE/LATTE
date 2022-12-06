<?php
header('Content-Type: application/json; charset=utf-8');
/*
    WITH THE NEO NODE ID ==> IDENTIFY THE NODE AND EXTRACT THE PRIMARY KEY 
    SETTINGS FROM THE CONFIG.INC.PHP FILE

*/ 


include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR."\includes\getnode.inc.php");

$neoId = (int)$_GET['id']; 

//extract from the database the type of node it is: 

$node = new Node($client);
$data = $node->generateURI($neoId); 

echo json_encode($data); 
die()
?>