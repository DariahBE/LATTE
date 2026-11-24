<?php

header('Content-Type: application/json; charset=utf-8');
// parse the model and get all possible wikidata fields: 

include_once(dirname(__DIR__) . '/config/config.inc.php');
include_once(ROOT_DIR . '/includes/getnode.inc.php');
include_once(ROOT_DIR . '/includes/user.inc.php');

$wikidataID = isset($_GET['qid']) ? $_GET['qid'] : '';
//validate qid= Q123
if(preg_match("/^Q[0-9]*$/", $wikidataID)){
  //only open for registered users (if text are hidden)
  if (!(TEXTSAREPUBLIC)){
    $user = new User($client);
    $user->checkAccess(False);  //automatically go to session checking.

  }
  
  $graph = new Node($client);
  $matchdata = $graph->fetchWikidataFromAnyPossibleEt($wikidataID); 
  
  echo json_encode(array('success'=> true, 'msg'=> 'ID valid', 'hits'=> count($matchdata), 'data' => $matchdata)); 
}else{
  echo json_encode(array('success'=> false, 'msg'=> 'ID rejected')); 
}





?>