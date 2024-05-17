<?php
header('Content-Type: application/json; charset=utf-8');

include_once("../config/config.inc.php");
include_once(ROOT_DIR."\includes\getnode.inc.php");
$graph = new Node($client);

$approvedEntities = array_keys(CORENODES);
$approvedEntities[] = '';     // words of unknown type should be looked for too!
//OKAY
$caseSensitive = isset($_GET['casesensitive']) ? $_GET['casesensitive'] : false;
$caseSensitive = (strtolower($caseSensitive)=='true')? true : false;
$findEntityByType = False; 
if(isset($_GET['type'])){
  //OKAY
  $findEntityByType = $_GET['type'];
}
//OKAY
$findEntityByValue = $_GET['value'];

$use_levenshtein = isset($_GET['allow_levenshtein']) ? $_GET['allow_levenshtein'] : false;
if (strtolower(trim($use_levenshtein)) === 'true'){
  $use_levenshtein = true;
}else{
  $use_levenshtein = false;
}
$max_levenshtein_hits = isset($_GET['levenshtein_items']) ? $_GET['levenshtein_items'] : 5;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100; 
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0; 
if(in_array($findEntityByType, $approvedEntities)){
  //type is falseable
  $data = $graph->getEntities($findEntityByType,$findEntityByValue,$caseSensitive,$limit, $offset, $use_levenshtein, $max_levenshtein_hits);
  /*
    returned data provides directives to connect the nodes to variants!.
  */
  foreach ($data['labelvariants'] as $key => $value) {
    $variantNodeId = $value[0];
    foreach ($data['edges'] as $subkey => $subvalue) {
      if($subvalue['startNodeId']===$variantNodeId){
        if(!(array_key_exists('variantOfEntity',$data['labelvariants'][$key][2]))){
          $data['labelvariants'][$key][2]['variantOfEntity'] = array();
        }
        array_push($data['labelvariants'][$key][2]['variantOfEntity'],$subvalue['endNodeId']);
      }
    }
  }
  echo json_encode((array)$data);
}else{
  die(json_encode(array('message'=>'Invalid request')));
}

die();
?>
