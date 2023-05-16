<?php

include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'\includes\getnode.inc.php');
include_once(ROOT_DIR.'\includes\nodes_extend_cud.inc.php');

$crudNode = new CUDNode($client); 

/**TODO: update endpoint as JSON */
/**TODO: check user login!!!! */

$data = $crudNode->delete((int)$_GET['id'], true);
echo json_encode($data);


?>