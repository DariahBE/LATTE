<?php
header('Content-Type: application/json; charset=utf-8');

include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR."\includes\getnode.inc.php");
if(isset($_GET['by'])){
  $by = $_GET['by'];
  $approvedBy = array('nodes', 'edges');
  if(in_array($by, $approvedBy)){
    $graph = new Node($client);
    if($by==='nodes'){
      $counter = $graph->countNodesByLabel();
    }else{
      $counter = $graph->countEdgesByLabel();
    }
    echo json_encode($counter);
  }else{
    die();
  }
}
?>
