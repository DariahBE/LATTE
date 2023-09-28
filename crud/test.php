<?php

include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'\includes\user.inc.php');
include_once(ROOT_DIR.'\includes\getnode.inc.php');
include_once(ROOT_DIR.'\includes\nodes_extend_cud.inc.php');
/*$crudNode = new CUDNode($client); 


$result = $crudNode->createNewNode('Person', array());
echo json_encode($result);

$node = new Node($client); 
$result = $node->fetchRawEtById(5952);
var_dump($result);
$result = $node->fetchRawEtById(5952,1);
var_dump($result); 
*/

?>