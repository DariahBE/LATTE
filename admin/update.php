<?php
  //only allow admins here: 


  //base imports
  include_once($_SERVER["DOCUMENT_ROOT"]."/config/config.inc.php");
  include_once(ROOT_DIR."/includes/client.inc.php");
  include_once(ROOT_DIR."/includes/user.inc.php");
  include_once(ROOT_DIR."/includes/navbar.inc.php");
  //there must be a logged in  user; if no session is active, make them log in and redirect back here. 
  if(isset($_SESSION["userid"])){
    $user = new User($client);
  }else{
    header("Location: /user/login.php?redir=/admin/index.php");
    die("redir required"); 
  }
  //only allow admins here; 
  if($user->myRole !== "Admin"){
    header("HTTP/1.0 403 Forbidden");
    die("Insufficient rights, forbidden access");
  }
$lastMsg = ''; 
$dropquery = "CALL apoc.schema.assert({}, {})"; 
$client->run($dropquery); 
$lastMsg = "All constraints dropped."; 

echo $lastMsg; 
// rebuild all constraints on the database model
// everything that can be searched on as defined in the config file => needs an index!
// everything that has an unique key constraint defined in the config file => needs a unique constraint in the DB. 
// do this as part of a for loop!
// iterate over all KEYS in NODEMODEL
//var_dump(NODEMODEL); 
foreach(NODEMODEL as $key => $value){
  foreach($value as $propName => $propertyList){
    //echo $propName;
    $nameForUQConstraint = $key.'_'.$propName.'_uq';
    $nameForIXConstraint = $key.'_'.$propName.'_index';
    $addUniqueness = $propertyList[2]; 
    $addIndex = $propertyList[4];
    //cypher manual: 
    // https://neo4j.com/docs/cypher-manual/current/constraints/examples/#constraints-create-a-node-uniqueness-constraint
    // https://neo4j.com/docs/cypher-manual/current/constraints/#_implications_on_indexes
    $uniqueQuery = "CREATE CONSTRAINT $nameForUQConstraint IF NOT EXISTS ON (n:$key) ASSERT n.$propName IS UNIQUE;"; 
    $indexQuery = "CREATE INDEX $nameForIXConstraint IF NOT EXISTS FOR (n:$key) on (n.$propName)"; 
    if ($addUniqueness){
      $client->run($uniqueQuery);
    } else if($addIndex && !$addUniqueness){
      $client->run($indexQuery);
    }
    
  }
}

?>
