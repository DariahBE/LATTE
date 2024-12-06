<?php
/*
    requires the node type and attribute type
        node_type = string (label of the node)
        attribute_type = string (label of an integer + uniquely set attribute)

    returns: the current max value of node_type.attribute_type+1
*/

header('Content-Type: application/json; charset=utf-8');
include_once("../config/config.inc.php");
include_once(ROOT_DIR.'/includes/getnode.inc.php');

//GET: 
$nodelabel = $_GET['node']; 
$attribute = $_GET['prop']; 

//Every check is valid; perform maxcount and return value incremented by one! 
$node = new Node($client);

$repl = $node->extractMaxAttributeValue($nodelabel, $attribute); 
$new_value = $repl[0]['maxval']+1;
echo json_encode(array($new_value)); 

?>