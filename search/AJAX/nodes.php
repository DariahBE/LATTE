<?php
header('Content-Type: application/json; charset=utf-8');

include_once($_SERVER["DOCUMENT_ROOT"]."/config/config.inc.php");
include_once(ROOT_DIR."/includes/getnode.inc.php");

if(isset($_GET["action"])){
  $approved_actions = array("labels", "properties");
  $action = $_GET["action"];
  if(in_array($action, $approved_actions)){
    $graph = new Node($client);
    if($action === "labels"){
      echo json_encode($graph->getDistinctLabels());
    }else{
      echo json_encode($graph->getDistinctProperties($_GET['on']));
    }
  }
}



?>
