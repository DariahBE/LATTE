<?php
ini_set('memory_limit', '512M');
header('Content-Type: application/json; charset=utf-8');

include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once('../includes/search.inc.php');

try{
  $request = file_get_contents("php://input");
  var_dump($request);
  $request = mb_convert_encoding($request, 'UTF-8', mb_detect_encoding($request, 'UTF-8, ISO-8859-1', true));
  $data = json_decode($request, true)['searchdata'];
}catch(Exception $e){
  throw new \Exception("Request rejected.", 1);
  die();
}


if (boolval($data)){
  $searchdata = $data;
  //extract the relevant parts from the provided search instructions
  try{
    $nodes = $searchdata['nodes'];
    $edges = $searchdata['edges'];
  }catch(Exception $e){
    die(json_encode(array('error'=>'Missing parameters in provided searchcommand.')));
  }
  //initiate the searchclass:
  $search = new Search($client, $nodes, $edges);

  $search->validateSearchInstruction();
  //converts the searchdict to a set of subinstructions to generate the cypher statement:
  $search->makeNodes();
  $search->makeEdges();
  //merges the subinstructions into a cypher statement:
  $search->mergeCypher();
  $search->executeCypherStatement();

}


?>
