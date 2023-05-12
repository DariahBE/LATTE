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
            'type' => $valuetype,
            'constraint'=>'n.'.$propertyname.' = $'.$phname, 
            'varconstraint' =>'v.variant = $'.$phnamevar, 
            'placeholders'=>array(
              $phname=>$value[0],
              $phnamevar=>$value[0],
            )
          );
          break; 
        case'!=':
          return array(
            'type' => $valuetype,
            'constraint'=>'(n.'.$propertyname.' <> $'.$phname .' OR n.'.$propertyname.' IS NULL )', 
            'varconstraint'=>'(v.variant <> $'.$phnamevar .' OR v.variant IS NULL )',
            'placeholders'=>array(
              $phname=>$value[0], 
              $phnamevar=>$value[0]
            )
          );
          break;
        case '^':
          return array(
            'type' => $valuetype,
            'constraint'=>'toLower(n.'.$propertyname.') = toLower($'.$phname.')', 
            'varconstraint'=>'toLower(v.variant) = toLower($'.$phnamevar.')', 
            'placeholders'=>array(
              $phname=>$value[0],
              $phnamevar=>$value[0]
          )
        );
          break;
        case 'word*':
          return array(
            'type' => $valuetype,
            'constraint'=>'toLower(n.'.$propertyname.') STARTS WITH toLower($'.$phname.')', 
            'varconstraint'=>'toLower(v.variant) STARTS WITH toLower($'.$phnamevar.')', 
            'placeholders'=>array(
              $phname=>$value[0],
              $phnamevar=>$value[0]
            )
          );
          break;
        case '*word':
          return array(
            'type' => $valuetype,
            'constraint'=>'toLower(n.'.$propertyname.') ENDS WITH toLower($'.$phname.')', 
            'varconstraint'=>'toLower(v.variant) ENDS WITH toLower($'.$phnamevar.')', 
            'placeholders'=>array(
              $phname=>$value[0],
              $phnamevar=>$value[0]
            )
          );
          break;
        case '*word*':
          return array(
            'type' => $valuetype,
            'constraint'=>'toLower(n.'.$propertyname.') CONTAINS toLower($'.$phname.')', 
            'varconstraint'=>'toLower(v.variant) CONTAINS toLower($'.$phnamevar.')', 
            'placeholders'=>array(
              $phname=>$value[0],
              $phnamevar=>$value[0]
            )
          );
          break;
      }
    }
  }


  function directNodeSearch($searchdict, $label, $offset = 0, $limit = 20){
    $constraints = array(); 
    $conditions = array(); 
    $optconstraints = array(); 

    foreach($searchdict as $key => $opt){
      if(array_key_exists($key, NODEMODEL[$label])){      
        $singleParameter= $this->convertOperatorToCypher($opt['operator'], $opt['type'], $key, $opt['values']);
        $constraints[]=$singleParameter['constraint']; 
        $optconstraints[]=$singleParameter['varconstraint']; 
        foreach($singleParameter['placeholders'] as $k =>$v){
          $conditions[$k] =$v;
        }

      }
    }

    $query = "OPTIONAL MATCH (n:$label) "; 
    $query.=" WHERE ".implode(' AND ', $constraints); 
    //add constraint on type!
    if(boolval(count($optconstraints))){
      $query.= " OPTIONAL MATCH (q:$label)--(v:Variant) WHERE ".implode(' AND ', $optconstraints);
    }
    $query.=" RETURN distinct(n)";
    if(boolval(count($optconstraints))){
      $query.=",q ";
    }
    $query.=" SKIP $offset LIMIT $limit "; 
    $data = $this->client->run($query, $conditions);
    return $data;
  }

}


?>