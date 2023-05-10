<?php
header('Content-Type: application/json; charset=utf-8');
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'/includes/getnode.inc.php');
include_once(ROOT_DIR.'/includes/user.inc.php');

$labelname = $_POST['node']; 
$labeloptions = $_POST['options']; 

if (!array_key_exists($labelname, NODEMODEL)){
  die(json_encode(array('searchparameters invalid'))); 
}
//build the query here: 
$query = "MATCH (n:$labelname) ";

//helper function: check if request contains a valid operator for an expected type. 
function validateOperators($operator, $valueType){
  $allowedSymbols = [
    'int' => array('', '!=', '>', '<', '<=', '>=', 'x|y' ), 
    'string' => array('', '!=', '^', 'word*', '*word', '*word*'), 
  ]; 
  if(array_key_exists($valueType, $allowedSymbols)){
    return in_array($operator, $allowedSymbols[$valueType]);
  }
  return False;
}

//helper function: cast the value to whatever is expected! 
//return default if nothing matches!
function validateAs($what, $value){
  if($what === 'int'){
    return (int)$value; 
  }
  return $value; 
}

$whereParameterCounter = 1; 
function convertOperatorToCypher($operator, $valuetype, $propertyname, $value){
  global $whereParameterCounter;
  $phname = '$temp_'.$whereParameterCounter;

  $whereParameterCounter+=1;
  if($valuetype === 'int'){
    switch($operator){
      case '':   //'', '!=', '>', '<', '<=', '>=', 'x|y' 
        return array('constraint'=>'n.'.$propertyname.' = '.$phname, 'placeholders'=>array($phname=>$value[0]));
        break;
      case '!=':
        return array('constraint'=>'n.'.$propertyname.' <> '.$phname, 'placeholders'=>array($phname=>$value[0]));
        break;
      case'>':
        return array('constraint'=>'n.'.$propertyname.' > '.$phname, 'placeholders'=>array($phname=>$value[0]));
        break;
      case '<':
        return array('constraint'=>'n.'.$propertyname.' < '.$phname, 'placeholders'=>array($phname=>$value[0]));
        break;
      case '>=':
        return array('constraint'=>'n.'.$propertyname.' >= '.$phname, 'placeholders'=>array($phname=>$value[0]));
        break;
      case '<=':
        return array('constraint'=>'n.'.$propertyname.' <= '.$phname, 'placeholders'=>array($phname=>$value[0]));
        break;
      case 'x|y':
        return array('constraint'=> '(n.'.$propertyname.' >= '.$phname.'a AND n.'.$propertyname.' <= '.$phname.'b  )', 'placeholders'=>array($phname.'a'=>$value[0], $phname.'b'=>$value[1]));
        break;
    }
  }else if($valuetype =='string' ){
    switch($operator){
      case '': // '', '!=', '^', 'word*', '*word', '*word*')
        return array('constraint'=>'n.'.$propertyname.' = '.$phname, 'placeholders'=>array($phname=>$value[0]));
        break; 
      case'!=':
        return array('constraint'=>'n.'.$propertyname.' <> '.$phname, 'placeholders'=>array($phname=>$value[0]));
        break;
      case '^':
        return array('constraint'=>'toLower(n.'.$propertyname.') = toLower('.$phname.')', 'placeholders'=>array($phname=>$value[0]));
        break;
      case 'word*':
        return array('constraint'=>'toLower(n.'.$propertyname.') STARTS WITH toLower('.$phname.')', 'placeholders'=>array($phname=>$value[0]));
        break;
      case '*word':
        return array('constraint'=>'toLower(n.'.$propertyname.') ENDS WITH toLower('.$phname.')', 'placeholders'=>array($phname=>$value[0]));
        break;
      case '*word*':
        return array('constraint'=>'toLower(n.'.$propertyname.') CONTAINS toLower('.$phname.')', 'placeholders'=>array($phname=>$value[0]));
        break;
    }
  }
}


$whereClause = ''; 
$whereParameterHolder = array(); 
foreach($labeloptions as $key => $opt){
  if(array_key_exists($key, NODEMODEL[$labelname])){
    //extract expected format: 
    
    $whereParameterHolder[]= convertOperatorToCypher($opt['operator'], $opt['type'], $key, $opt['values']);
    /*  
    $expectedType = NODEMODEL[$labelname][$key][1]; 
    $castValues = array(); 
    for($i=0; $i<count($value['values']); $i++){
      $castValue = validateAs($expectedType, $value['values'][$i]); 
      array_push($castValues, $castValue);
    }*/
    //var_dump($castValues);
    //variable is validated: it's part of the node properties and has been typed:

  }
}

echo json_encode($whereParameterHolder);
#cypher query that looks for a node with the label Person, where the name property is looked up ignoring the case
#query = "MATCH (p:Person) WHERE toLower(p.name) = toLower('Alice') RETURN p"

#cypher query that looks for a node with the label Person, where the name property contains 'ete'
#  query = "MATCH (p:Person) WHERE p.name CONTAINS 'ete' RETURN p.name, p.age"

#cypher query that looks for a node with the label Person, where the name property starts with 'Pete'
#  query = "MATCH (p:Person) WHERE p.name STARTS WITH 'Pete' RETURN p.name, p.age"


?>