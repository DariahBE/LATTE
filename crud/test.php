<?php

include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'/includes/user.inc.php');
include_once(ROOT_DIR.'/includes/getnode.inc.php');
include_once(ROOT_DIR.'/includes/nodes_extend_cud.inc.php');
$crudNode = new CUDNode($client); 
echo "Running testcode"; 

$texToDelete = 1691;
$testresult =  $crudNode->annotationsWithThisEntity(1874);
var_dump($testresult); 
//$annoArray = array(7822,7830,7824); 

