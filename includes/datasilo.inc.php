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
  /**
   *  Finds neighbouring nodes over a specified relation, to a node with a specific NEOID
   *  The internal NEO ID is used!!! Do not use to provide stable identifiers!!!
  */
  public function getNeighboursConnectedBy($neoID, $relation='see_also'){
    $connectedSiloAnnotations = $this->client->run('MATCH (x)-[r:'.$relation.']-(silo) WHERE id(x) = $nodeval RETURN silo', ['nodeval'=>$neoID]);
    $this->connectedOverRelation = $connectedSiloAnnotations; 
  }

  /**
   * Returns an array with the URI and anchortext; 
   * $mode is either html or json
   *  json returns a nested encodeable array
   *  html returns an array where each entry is formatted as HTML!
  */
  public function makeURIs($mode){
    $output = array(); 
    foreach($this->connectedOverRelation as $row){
      $urival = false; 
      $urikey = false; 
      $siloLabel = $row['silo']['labels'][0]; 
      $model = NODEMODEL[$siloLabel]; 
      $urikey = helper_searchInNestedArray($model, 1, 'uri');
      $urival = $row['silo']['properties'][$urikey]; 
      //find a name: use a string property and optional the distinguish-property: 
      $anchorText = False;
      foreach($model as $key => $value){
        if ($value[1]=='string' && $value[3]){
          $anchorText = $row['silo']['properties'][$value[0]];
          break;
        }
      }
      if(!$anchorText){
        $anchorText = $row['silo']['properties'][helper_searchInNestedArray($model, 1, 'string')];
      }
      if(!$anchorText){
        $anchorText = 'Link';
      }
      if($mode === 'html'){
        $oneBlock = "<a href='".$urival."' target='_blank'>$anchorText</a>"; 
        $output[]=$oneBlock; 
      }else if ($mode === 'json'){
        $oneBlock = array(
          'URI'=>$urival,
          'anchor'=>$anchorText
        ); 
        $output[]=$oneBlock; 
      }else{
        throw new \Exception("Unknown $mode");
      }


    }

    return $output;
  }



}

?>