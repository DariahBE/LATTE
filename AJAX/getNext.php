<?php
/*
    requires the node type and attribute type
        node_type = string (label of the node)
        attribute_type = string (label of an integer + uniquely set attribute)

    returns: the current max value of node_type.attribute_type+1
*/

header('Content-Type: application/json; charset=utf-8');
include_once(ROOT_DIR.'/includes/getnode.inc.php');

//GET: 
$nodelabel = $_GET['node']; 
$attribute = $_GET['label']; 

//check request does the config file specifiy this node/attribute combo as INT + UNIQUE?
$go = False;
$nodedefinition = NODEMODEL[$nodelabel];
if (isset($nodedefinition['attributes'][$attribute])) {
    $attribute_definition = $nodedefinition['attributes'][$attribute];
    if ($attribute_definition[1] == 'int' && $attribute_definition[2]) {
        $go = True;
    }
}
if(!($go)){die();}

//Every check is valid; perform maxcount and return value incremented by one! 
$node = new Node($client);

//TODO busy with this.



?>