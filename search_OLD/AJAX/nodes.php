<?php
header('Content-Type: application/json; charset=utf-8');

include_once($_SERVER["DOCUMENT_ROOT"]."/config/config.inc.php");
include_once(ROOT_DIR."/includes/getnode.inc.php");

if(isset($_GET["action"])){
  $approved_actions = array("labels", "properties", "connections");
  $action = $_GET["action"];
  if(in_array($action, $approved_actions)){
    $graph = new Node($client);
    if($action === "labels"){
      echo json_encode($graph->getDistinctLabels());
    }else if($action === 'properties'){
      $data = $graph->getDistinctProperties($_GET['on']); 
      // you should not echo $data directly!
      echo json_encode($data);
    }else if($action === 'connections'){
      $data = $graph->getConnections($_GET['on']); 
      echo json_encode($data); 
    }
  }
}



?>
