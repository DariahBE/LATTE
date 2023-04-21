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



$whereClause = ''; 
$whereParameterHolder = array(); 
$whereParameterCounter = 1; 
foreach($labeloptions as $key => $value){
  if(array_key_exists($key, NODEMODEL[$labelname])){
    //extract expected format: 
    $expectedType = NODEMODEL[$labelname][$key][1]; 
    $castValues = array(); 
    for($i=0; $i<count($value['values']); $i++){
      $castValue = validateAs($expectedType, $value['values'][$i]); 
      array_push($castValues, $castValue);
    }
    var_dump($castValues);
    //variable is validated: it's part of the node properties and has been typed:

  }
}


?>