<?php
header('Content-Type: application/json; charset=utf-8');
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'/includes/getnode.inc.php');

if(!(isset($_GET['id']))){
    die(json_encode(array('error'=>'Rejected request!')));
}
$neoID = $_GET['id'];
$graph = new Node($client);


if(isset($_GET['mode']) && ($_GET['mode'] == 'list')){
    echo json_encode($graph->listTextsConnectedToEntityWithID((int)$neoID));
}else{
    echo json_encode($graph->countTextsConnectedToEntityWithID((int)$neoID));
}

?>