<?php


function helper_searchInNestedArray($array, $idx, $val){
  foreach ($array as $key => $value) {
    if (!is_array($value)){return FALSE;}
    if (!isset($value[$idx])){continue;}
    if ($value[$idx] === $val){return $key;}
  }
  return NULL;
}

class Siloconnector{

  protected $client;
  public $output; 
  function __construct($client) {
    $this->client = $client;
    $this->output = array(); 
  }


  //by default silorelations are see_also!
  public function getNeighboursConnectedBy($neoID, $relation='see_also'){
    $connectedSiloAnnotations = $this->client->run('MATCH (x)-[r:'.$relation.']-(silo) WHERE id(x) = $nodeval RETURN silo', ['nodeval'=>$neoID]);
    $this->connectedOverRelation = $connectedSiloAnnotations; 
  }

  public function makeURIs(){
    foreach($this->connectedOverRelation as $row){
      $urival = false; 
      $urikey = false; 
      var_dump($row['silo']); 
      $siloLabel = $row['silo']['labels'][0]; 
      $model = NODEMODEL[$siloLabel]; 
      var_dump($model); 
      $urikey = helper_searchInNestedArray($model, 1, 'uri');
      $urival = $row['silo']['properties'][$urikey]; 
      //var_dump($urikey);    //OK
      //var_dump($urival);    //OK
      //var_dump($urikey); 
    }
  }



}

?>