<?php
include_once(ROOT_DIR."/includes/client.inc.php");

/**
 *
 */
class Search {
  private $graph;
  private $raw_nodes;
  private $raw_edges;
  private $identifiers;

  function __construct($graph, $nodes, $edges){
    $this->graph = $graph;
    $this->raw_nodes = $nodes;
    $this->raw_edges = $edges;
    $this->identifiers = array();

  }

  function validateSearchInstruction(){
    //searchdict should not have floating edges.
    $error = false;
    $availableNodes =  array_keys($this->raw_nodes);
    $connections = array();
    foreach ($this->raw_edges as $edgeName => $value) {
      $from = $value['from'];
      $to = $value['to'];
      $connections[]=$from;
      $connections[]=$to;
      if(!(in_array($from, $availableNodes))){
        $error = true;
      }
      if(!(in_array($to, $availableNodes))){
        $error = true;
      }
    }
    //All nodes should be connected (do not allow multiple nodeclusters).
    /*
      1 node == no connections.
      0 nodes == no connections.
      >1 nodes ==> at least 1 connection.
    */
    if(count($availableNodes) > 1){
      for($i = 0; $i < count($availableNodes); $i++){
        if(!(in_array($availableNodes[$i], $connections))){
          $error = true;
        }
      }
    }
    if($error){
      echo json_encode(array('err'=> 'Invalid search instructions'));
      die();
    }
  }

  private function generateIdentifier(){
    $str = 'abcdefghijklmnopqrstuvwxyz';
    $rtn = '';
    for(var $i = 0; $i < 5; $i++){
      $rtn .= $str[rand(0, strlen($str)-1)];
    }
    $this->identifiers[] = $rtn;
    if(!(in_array($rtn, $this->identifiers))){
      return $rtn;
    }else{

    }
  }


  function makeNodes(){
    $nodeId = generateIdentifier();


    return true;
  }

  function makeEdges(){
    return true;
  }

  function makeWhere(){
    return true;
  }

  function mergeCypher(){
    return true;
  }
}
?>
