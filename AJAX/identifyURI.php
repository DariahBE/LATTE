<?php
header('Content-Type: application/json; charset=utf-8');
/*
    WITH THE NEO NODE ID ==> IDENTIFY THE NODE AND EXTRACT THE PRIMARY KEY 
    SETTINGS FROM THE CONFIG.INC.PHP FILE

*/ 


include_once(dirname(__DIR__) . '/config/config.inc.php');
include_once(ROOT_DIR . '/includes/getnode.inc.php');

$neoId = isset($_GET['id']) ? (int)$_GET['id'] : 0; 

//extract from the database the type of node it is: 

$node = new Node($client);
$data = $node->generateURI($neoId); 
$connections = $node->countConnections($neoId);

echo json_encode(array('uri' => $data, 'connections' =>$connections)); 
die();
?>