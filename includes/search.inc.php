<?php 
//include_once("../config/config.inc.php");
include_once(ROOT_DIR."/includes/client.inc.php");

class Search{
  protected $client;
  function __construct($client) {
    $this->client = $client;
    $this->whereParameterCounter = 1; 
  }

  function convertOperatorToCypher($operator, $valuetype, $propertyname, $value){
    $phname = 'temp_'.$this->whereParameterCounter;
    $phnamevar = 'vtemp_'.$this->whereParameterCounter;
    $this->whereParameterCounter+=1;
    if($valuetype === 'int'){
      switch($operator){
        case '':   //'', '!=', '>', '<', '<=', '>=', 'x|y' 
          return array('constraint'=>'n.'.$propertyname.' = $'.$phname, 'placeholders'=>array($phname=>$value[0]));
          break;
        case '!=':
          return array('constraint'=>'(n.'.$propertyname.' <> $'.$phname .' OR n.'.$propertyname.' IS NULL )', 'placeholders'=>array($phname=>$value[0]));
          break;
        case'>':
          return array('constraint'=>'n.'.$propertyname.' > $'.$phname, 'placeholders'=>array($phname=>$value[0]));
          break;
        case '<':
          return array('constraint'=>'n.'.$propertyname.' < $'.$phname, 'placeholders'=>array($phname=>$value[0]));
          break;
        case '>=':
          return array('constraint'=>'n.'.$propertyname.' >= $'.$phname, 'placeholders'=>array($phname=>$value[0]));
          break;
        case '<=':
          return array('constraint'=>'n.'.$propertyname.' <= $'.$phname, 'placeholders'=>array($phname=>$value[0]));
          break;
        case 'x|y':
          return array('constraint'=> '(n.'.$propertyname.' >= $'.$phname.'a AND n.'.$propertyname.' <= $'.$phname.'b  )', 'placeholders'=>array($phname.'a'=>$value[0], $phname.'b'=>$value[1]));
          break;
      }
    }else if($valuetype === 'string' ){
      switch($operator){
        case '': // '', '!=', '^', 'word*', '*word', '*word*')
          return array(
            'constraint'=>'n.'.$propertyname.' = $'.$phname, 
            'varconstraint' =>'v.'.$propertyname.' = $'.$phnamevar, 
            'placeholders'=>array(
              $phname=>$value[0],
              $phnamevar=>$value[0],
            )
          );
          break; 
        case'!=':
          return array(
            'constraint'=>'(n.'.$propertyname.' <> $'.$phname .' OR n.'.$propertyname.' IS NULL )', 
            'varconstraint'=>'(v.'.$propertyname.' <> $'.$phnamevar .' OR v.'.$propertyname.' IS NULL )',
            'placeholders'=>array(
              $phname=>$value[0], 
              $phnamevar=>$value[0]
            )
          );
          break;
        case '^':
          return array('constraint'=>'toLower(n.'.$propertyname.') = toLower($'.$phname.')', 'placeholders'=>array($phname=>$value[0]));
          break;
        case 'word*':
          return array('constraint'=>'toLower(n.'.$propertyname.') STARTS WITH toLower($'.$phname.')', 'placeholders'=>array($phname=>$value[0]));
          break;
        case '*word':
          return array('constraint'=>'toLower(n.'.$propertyname.') ENDS WITH toLower($'.$phname.')', 'placeholders'=>array($phname=>$value[0]));
          break;
        case '*word*':
          return array('constraint'=>'toLower(n.'.$propertyname.') CONTAINS toLower($'.$phname.')', 'placeholders'=>array($phname=>$value[0]));
          break;
      }
    }
  }


  function directNodeSearch($searchdict, $label, $offset = 0, $limit = 20){
    $constraints = array(); 
    $conditions = array(); 

    foreach($searchdict as $key => $opt){
      if(array_key_exists($key, NODEMODEL[$label])){      
        $singleParameter= $this->convertOperatorToCypher($opt['operator'], $opt['type'], $key, $opt['values']);
        //var_dump($singleParameter);
        $constraints[]=$singleParameter['constraint']; 
        foreach($singleParameter['placeholders'] as $k =>$v){
          $conditions[$k] =$v;
        }

      }
    }

    $query = "MATCH (n:$label) "; 
    $query.=" WHERE ".implode(' AND ', $constraints); 
    //add constraint on type!
    $query.= "OPTIONAL MATCH (q:$label)--(v:Variant) ";
    $query.=" RETURN n,q SKIP $offset LIMIT $limit "; 
    var_dump($query); 
    $data = $this->client->run($query, $conditions);
    return $data;
    
  }

}


#Look for a cypher statement to find all nodes with label place that have label "Manchester" or where Place is connected to a node with label Variant where the property alt is "Manchester"
#optional MATCH (n:Place)  WHERE n.label CONTAINS 'Man'
#OPTIONAL MATCH (q:Place)-[]-(v:Variant) where v.variant CONTAINS 'Man' 
#RETURN n,q SKIP 0 LIMIT 20
?>