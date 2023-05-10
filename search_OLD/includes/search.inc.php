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
  private $statement;

  function __construct($graph, $nodes, $edges){
    $this->graph = $graph;
    $this->raw_nodes = $nodes;
    $this->raw_edges = $edges;
    $this->identifiers = array();
    $this->nodes = array();
    $this->edges = array();
    $this->preparedPlaceholders = array();
    $this->statement = '';
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
    for($i = 0; $i < 5; $i++){
      $rtn .= $str[rand(0, strlen($str)-1)];
    }
    return $rtn;
  }

  function valuePlaceholders(){
    $str = 'abcdefghijklmnopqrstuvwxyz';
    $ph = 'PH_'.'';
    for($i = 0; $i < 5; $i++){
      $ph .= $str[rand(0, strlen($str)-1)];
    }
    while(in_array($ph, array_keys($this->preparedPlaceholders))){
      $ph .= $str[rand(0, strlen($str)-1)];
    }
    return $ph;
  }

  function makeNodes(){
    foreach($this->raw_nodes as $key => $chosenNode){

      $nodeId = $this->generateIdentifier();
      while(in_array($nodeId, $this->identifiers)){
        $nodeId = generateIdentifier();
      }
      $this->identifiers[] = $nodeId;
      $nodeLabel = $chosenNode['label'];
      $nodeProperties = $chosenNode['property'];
      $definedNodeProperties = array();
      foreach($nodeProperties as $propKey => $propValue){
        $placeHolder = $this->valuePlaceholders();
        $definedNodeProperties[] = $propKey.':$'.$placeHolder;
        $this->preparedPlaceholders[$placeHolder] = $propValue;
      }
      if(boolval(count($definedNodeProperties))){
        $definedNodeProperties = '{'.implode(', ', $definedNodeProperties).'}';
      }else{
        $definedNodeProperties = '';
      }

      $this->nodes[] = "($nodeId:$nodeLabel $definedNodeProperties)";

      return true;
    }
  }

  function makeEdges(){
    return true;
  }

  function mergeCypher(){
    $matchStatement = implode(', ', array(implode(', ', $this->nodes), implode(', ', $this->edges)));
    $query = 'MATCH '.$matchStatement.' ';
    $query .= ' RETURN '.implode(', ', $this->identifiers);
    $this->statement = $query;
  }

  function executeCypherStatement(){
    $result = $this->graph->run($this->statement, $this->preparedPlaceholders);
    $data = array();
    var_dump($result);
  }
}
?>
