<?php
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
header('Content-Type: application/json; charset=utf-8');
$x = $_GET;
if (isset($x['node'])){
  $nodeid = (int)$x['node'];
  $pathOverride = SCRIPTROOT;
  $command = PYTHON.' "'.$pathOverride.'detect_language.py" --nodeid='.$nodeid. ' --extractor="'.LANGEXTRACTOR.'" --uri="'.URI.'" --username="'.USERNAME.'" --password="'.PASSWORD.'" --database="'.DBNAME.'" --textlabel="'.TEXNODE.'"  --textproperty="'.TEXNODETEXT.'"';
  $scriptResult = shell_exec($command);
  $parsedResult = json_decode($scriptResult);
  echo json_encode($parsedResult);
}else{
  die(json_encode("invalid request."));
}
?>
