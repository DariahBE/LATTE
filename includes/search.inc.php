<?php 
//include_once("../config/config.inc.php");
include_once(ROOT_DIR."/includes/client.inc.php");

class Search{
  protected $client;
  function __construct($client) {
    $this->client = $client;
    $this->whereParameterCounter = 1; 
  }

  //BUG 30-10-24-3: When searching for a PLACE based on WORDmatch ONLY *word* == e (PARTIAL PATCH APPLIED)
    //>WARNING: patch might break when truthy statement is required for a real boolean operator coming from the searchform
  //    then te interface still takes the EMPTY value for wikidata as a part of the search constraint!

  function convertOperatorToCypher($operator, $valuetype, $propertyname, $value){
    // var_dump($operator, $valuetype, $propertyname, $value);
    $phname = 'temp_'.$this->whereParameterCounter;
    $phnamevar = 'vtemp_'.$this->whereParameterCounter;
    $this->whereParameterCounter+=1;
    if($valuetype === 'int' || $valuetype === 'float'){
      // numerical operations: cast to float, works for integers too!
      // float numbers can be processed in this block too.
      switch($operator){
        case '':   //'', '!=', '>', '<', '<=', '>=', 'x|y' 
          return array('constraint'=>'n.'.$propertyname.' = $'.$phname, 'placeholders'=>array($phname=>floatval($value[0])));
          break;
        case '!=':
          return array('constraint'=>'(n.'.$propertyname.' <> $'.$phname .' OR n.'.$propertyname.' IS NULL )', 'placeholders'=>array($phname=>floatval($value[0])));
          break;
        case'>':
          return array('constraint'=>'n.'.$propertyname.' > $'.$phname, 'placeholders'=>array($phname=>floatval($value[0])));
          break;
        case '<':
          return array('constraint'=>'n.'.$propertyname.' < $'.$phname, 'placeholders'=>array($phname=>floatval($value[0])));
          break;
        case '>=':
          return array('constraint'=>'n.'.$propertyname.' >= $'.$phname, 'placeholders'=>array($phname=>floatval($value[0])));
          break;
        case '<=':
          return array('constraint'=>'n.'.$propertyname.' <= $'.$phname, 'placeholders'=>array($phname=>floatval($value[0])));
          break;
        case 'x|y':
          return array('constraint'=> '(n.'.$propertyname.' >= $'.$phname.'a AND n.'.$propertyname.' <= $'.$phname.'b  )', 'placeholders'=>array($phname.'a'=>floatval($value[0]), $phname.'b'=>floatval($value[1])));
          break;
      }
    }else if($valuetype === 'string' || $valuetype == 'uri' ){
      //string and URI can be processed in the same way. 
      switch($operator){
        case '': // '', '!=', '^', 'word*', '*word', '*word*')
          return array(
            'type' => $valuetype,
            'constraint'=>'n.'.$propertyname.' = $'.$phname, 
            'varconstraint' =>'v.variant = $'.$phnamevar, 
            'placeholders'=>array(
              $phname=>$value[0],
              $phnamevar=>$value[0]
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

          case 'link*':
            return array(
              'type' => $valuetype,
              'constraint'=>'toLower(n.'.$propertyname.') STARTS WITH toLower($'.$phname.')', 
              'placeholders'=>array(
                $phname=>$value[0],
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

          case '*link':
            return array(
              'type' => $valuetype,
              'constraint'=>'toLower(n.'.$propertyname.') ENDS WITH toLower($'.$phname.')', 
              'placeholders'=>array(
                $phname=>$value[0],
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
        case '*link*':
          return array(
            'type' => $valuetype,
            'constraint'=>'toLower(n.'.$propertyname.') CONTAINS toLower($'.$phname.')', 
            'placeholders'=>array(
              $phname=>$value[0],
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
    } else if ($valuetype === 'wikidata'){
      //Wikidata is an exact match and requires a precheck (Q....)
      // cases: null
      if(empty($value[0]) || !preg_match('/^Q\d+$/', $value[0])){
        return Null;
      }
      return array(
        'type' => $valuetype,
        'constraint'=>'n.'.$propertyname.' = $'.$phname, 
        'varconstraint' =>'v.variant = $'.$phnamevar, 
        'placeholders'=>array(
          $phname=>$value[0],
          $phnamevar=>$value[0]
        )
      );
    } else if ($valuetype === 'bool'){
      var_dump($operator, $valuetype, $propertyname, $value);
      // cases: null
      //TODO implement bool. Part of //BUG 30/10/24-2
    } 
  }

  function directNodeSearch($searchdict, $label, $offset = 0, $limit = 20){
    $constraints = array(); 
    $conditions = array(); 
    $optconstraints = array(); 

    foreach($searchdict as $key => $opt){ 
      // PATCH in place for //BUG 30-10-24-3 ==> using boolval()-constraint to only perform a constraint build when a value is truthy. 
      // var_dump($key); 
      // var_dump($opt['values'][0]); 
      // var_dump(boolval($opt['values'][0])); 
      if(array_key_exists($key, NODEMODEL[$label]) && boolval($opt['values'][0]) ){      
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

    $query = " OPTIONAL MATCH (n:$label) "; 
    if (boolval($constraints)){
      $query.=" WHERE ".implode(' AND ', $constraints); 
    }
    //add constraint on type!
    if(boolval(count($optconstraints))){
      $constraints = implode(' AND ', $optconstraints);
      $query.= " OPTIONAL MATCH (q:$label)--(v:Variant) ". ' ';
      if (boolval($optconstraints)){
        $query .= ' WHERE '. $constraints; 
      }
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