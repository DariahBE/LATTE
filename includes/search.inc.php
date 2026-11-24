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
    $cleanProperty = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$propertyname);
    if ($cleanProperty === '') {
      return null;
    }
    $phname = 'temp_'.$this->whereParameterCounter;
    $phnamevar = 'vtemp_'.$this->whereParameterCounter;
    $this->whereParameterCounter+=1;
    $firstVal = (is_array($value) && isset($value[0])) ? $value[0] : (is_string($value) ? $value : '');

    if($valuetype === 'int' || $valuetype === 'float'){
      // numerical operations: cast to float, works for integers too!
      // float numbers can be processed in this block too.
      $floatVal = floatval($firstVal);
      switch($operator){
        case '':   //'', '!=', '>', '<', '<=', '>=', 'x|y' 
          return array('constraint'=>'n.'.$cleanProperty.' = $'.$phname, 'placeholders'=>array($phname=>$floatVal));
          break;
        case '!=':
          return array('constraint'=>'(n.'.$cleanProperty.' <> $'.$phname .' OR n.'.$cleanProperty.' IS NULL )', 'placeholders'=>array($phname=>$floatVal));
          break;
        case'>':
          return array('constraint'=>'n.'.$cleanProperty.' > $'.$phname, 'placeholders'=>array($phname=>$floatVal));
          break;
        case '<':
          return array('constraint'=>'n.'.$cleanProperty.' < $'.$phname, 'placeholders'=>array($phname=>$floatVal));
          break;
        case '>=':
          return array('constraint'=>'n.'.$cleanProperty.' >= $'.$phname, 'placeholders'=>array($phname=>$floatVal));
          break;
        case '<=':
          return array('constraint'=>'n.'.$cleanProperty.' <= $'.$phname, 'placeholders'=>array($phname=>$floatVal));
          break;
        case 'x|y':
          $valA = (is_array($value) && isset($value[0])) ? floatval($value[0]) : 0.0;
          $valB = (is_array($value) && isset($value[1])) ? floatval($value[1]) : 0.0;
          return array('constraint'=> '(n.'.$cleanProperty.' >= $'.$phname.'a AND n.'.$cleanProperty.' <= $'.$phname.'b )', 'placeholders'=>array($phname.'a'=>$valA, $phname.'b'=>$valB));
          break;
      }
    }else if($valuetype === 'string' || $valuetype == 'uri' ){
      //string and URI can be processed in the same way. 
      switch($operator){
        case '': // '', '!=', '^', 'word*', '*word', '*word*')
          return array(
            'type' => $valuetype,
            'constraint'=>'n.'.$cleanProperty.' = $'.$phname, 
            'varconstraint' =>'v.variant = $'.$phnamevar, 
            'placeholders'=>array(
              $phname=>$firstVal,
              $phnamevar=>$firstVal
            )
          );
          break; 
        case'!=':
          return array(
            'type' => $valuetype,
            'constraint'=>'(n.'.$cleanProperty.' <> $'.$phname .' OR n.'.$cleanProperty.' IS NULL )', 
            'varconstraint'=>'(v.variant <> $'.$phnamevar .' OR v.variant IS NULL )',
            'placeholders'=>array(
              $phname=>$firstVal, 
              $phnamevar=>$firstVal
            )
          );
          break;
        case '^':
          return array(
            'type' => $valuetype,
            'constraint'=>'toLower(n.'.$cleanProperty.') = toLower($'.$phname.')', 
            'varconstraint'=>'toLower(v.variant) = toLower($'.$phnamevar.')', 
            'placeholders'=>array(
              $phname=>$firstVal,
              $phnamevar=>$firstVal
          )
        );
          break;

          case 'link*':
            return array(
              'type' => $valuetype,
              'constraint'=>'toLower(n.'.$cleanProperty.') STARTS WITH toLower($'.$phname.')', 
              'placeholders'=>array(
                $phname=>$firstVal,
              )
            );
            break;
          case 'word*':
          return array(
            'type' => $valuetype,
            'constraint'=>'toLower(n.'.$cleanProperty.') STARTS WITH toLower($'.$phname.')', 
            'varconstraint'=>'toLower(v.variant) STARTS WITH toLower($'.$phnamevar.')', 
            'placeholders'=>array(
              $phname=>$firstVal,
              $phnamevar=>$firstVal
            )
          );
          break;

          case '*link':
            return array(
              'type' => $valuetype,
              'constraint'=>'toLower(n.'.$cleanProperty.') ENDS WITH toLower($'.$phname.')', 
              'placeholders'=>array(
                $phname=>$firstVal,
              )
            );
            break;
          case '*word':
          return array(
            'type' => $valuetype,
            'constraint'=>'toLower(n.'.$cleanProperty.') ENDS WITH toLower($'.$phname.')', 
            'varconstraint'=>'toLower(v.variant) ENDS WITH toLower($'.$phnamevar.')', 
            'placeholders'=>array(
              $phname=>$firstVal,
              $phnamevar=>$firstVal
            )
          );
          break;
        case '*link*':
          return array(
            'type' => $valuetype,
            'constraint'=>'toLower(n.'.$cleanProperty.') CONTAINS toLower($'.$phname.')', 
            'placeholders'=>array(
              $phname=>$firstVal,
            )
          );
          break;
        case '*word*':
          return array(
            'type' => $valuetype,
            'constraint'=>'toLower(n.'.$cleanProperty.') CONTAINS toLower($'.$phname.')', 
            'varconstraint'=>'toLower(v.variant) CONTAINS toLower($'.$phnamevar.')', 
            'placeholders'=>array(
              $phname=>$firstVal,
              $phnamevar=>$firstVal
            )
          );
          break;
      }
    } else if ($valuetype === 'wikidata'){
      //Wikidata is an exact match and requires a precheck (Q....)
      // cases: null
      if(empty($firstVal) || !preg_match('/^Q\d+$/', (string)$firstVal)){
        return Null;
      }
      return array(
        'type' => $valuetype,
        'constraint'=>'n.'.$cleanProperty.' = $'.$phname, 
        'varconstraint' =>'v.variant = $'.$phnamevar, 
        'placeholders'=>array(
          $phname=>$firstVal,
          $phnamevar=>$firstVal
        )
      );
    } else if ($valuetype === 'bool' && $firstVal === 'true'){
      //value is passed as a string indicating either true or false; strict check for type and value!!!
      // if a truthy value was given, this should be taken into account.
      //  don't use the $value in the query, but instead use 'true'
      return array(
        'type' => $valuetype,
        'constraint'=>'n.'.$cleanProperty.' = $'.$phname, 
        'placeholders'=>array(
          $phname=>True,
        )
      );
    } 
  }

  function directNodeSearch($searchdict, $label, $offset = 0, $limit = 20){
    if (!is_string($label) || !defined('NODEMODEL') || !isset(NODEMODEL[$label]) || !is_array($searchdict)) {
      return array();
    }
    $cleanLabel = preg_replace('/[^a-zA-Z0-9_]/', '', $label);
    if ($cleanLabel === '') {
      return array();
    }
    $offset = max(0, (int)$offset);
    $limit = max(1, min(1000, (int)$limit));

    $constraints = array(); 
    $conditions = array(); 
    $optconstraints = array(); 

    foreach($searchdict as $key => $opt){ 
      if(is_array($opt) && isset($opt['operator'], $opt['type'], $opt['values']) && array_key_exists($key, NODEMODEL[$label]) && isset($opt['values'][0]) && $opt['values'][0] !== '' ){      
        $singleParameter= $this->convertOperatorToCypher($opt['operator'], $opt['type'], $key, $opt['values']);
        if ($singleParameter == Null){
          continue;
        }
        $constraints[]=$singleParameter['constraint']; 
        if(isset($singleParameter['varconstraint'])){
          $optconstraints[]= $singleParameter['varconstraint'];
        }
        foreach($singleParameter['placeholders'] as $k =>$v){
          $conditions[$k] = $v;
        }
      }
    }

    $query = " OPTIONAL MATCH (n:$cleanLabel) "; 
    if (boolval($constraints)){
      $query.=" WHERE ".implode(' AND ', $constraints); 
    }
    //add constraint on type!
    if(boolval(count($optconstraints))){
      $constraintsStr = implode(' AND ', $optconstraints);
      $query.= " OPTIONAL MATCH (q:$cleanLabel)--(v:Variant) ";
      $query .= ' WHERE '. $constraintsStr; 
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