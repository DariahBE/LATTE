<?php
include_once("../config/config.inc.php");

header('Content-Type: application/json; charset=utf-8');

$x = $_GET;
if (isset($x['node']) && isset($x['lang'])){
  $nodeid = (int)$x['node'];
  $lang = $x['lang'];
  //$text = "hello world";
  $lang = escapeshellarg($lang);

  $pathOverride = SCRIPTROOT;
  $command = PYTHON.' "'.$pathOverride.'entity_extractor.py" --lang='.$lang.'  --nodeid='.$nodeid. ' --uri="'.URI.'" --username="'.USERNAME.'" --password="'.PASSWORD.'" --database="'.DBNAME.'" --textlabel="'.TEXNODE.'"  --textproperty="'.TEXNODETEXT.'"';
  //echo $command; 
  // die(); 
  // $command = PYTHON.' "'.$pathOverride.'hello.py"';
  $scriptResult = shell_exec($command);
  $parsedResult = json_decode($scriptResult);
  echo json_encode($parsedResult);
}else{
  die(json_encode("invalid request."));
}

?>
