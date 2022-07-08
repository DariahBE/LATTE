<?php

header('Content-Type: application/json; charset=utf-8');

include_once("../config/config.inc.php");
include_once(ROOT_DIR."\includes\getnode.inc.php");


$texid = (int)$_GET['texid'];
$start = (int)$_GET['start'];
$stop = (int)$_GET['stop'];
$node = new Node($client);

if (!boolval($texid) or !boolval($start) or !boolval($stop)){
  die(json_encode(array('err'=> 'invalid paramters')));
}


//get text from db:
$propKey = 'texid';
$nodeType = 'Text';
$text = $node->matchSingleNode($nodeType, $propKey, $texid);

$textcontent = $text['data']['properties']['text'];

//echo json_encode($text);

echo json_encode(array('match'=> substr($textcontent, $start, $stop-$start+1)));






?>
