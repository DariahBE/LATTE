<?php
/*
  same as stable.php but as JSON API:
*/

header('Content-Type: application/json; charset=utf-8');

$typeOK = false;
$uuid = false;
if(isset($_GET['type'])){
  $type = ucfirst($_GET['type']);
  $approvedTypes = array('Place', 'Person', 'Event', 'Annotation');
  if(in_array($type, $approvedTypes)){
    $typeOK = true;
  }
}
if(!($typeOK)){
  header('Location: /error.php?type=node');
  die();
}

if(isset($_GET['uuid'])){
  $uuid = $_GET['uuid'];

}


if(!($uuid)){
  header('Location: /error.php?type=uuid');
  die();
}


include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'/includes/getnode.inc.php');
include_once(ROOT_DIR.'/includes/entityviews.inc.php');

$graph = new Node($client);
$propertyWithPK = 'uid';
if (array_key_exists($type, PRIMARIES) && boolval(PRIMARIES[$type])){
  $propertyWithPK = PRIMARIES[$type];
}

//getting the data from the backend:
$core = $graph->matchSingleNode($type, $propertyWithPK, $uuid);
if (array_key_exists('coreID', $core)){
  $coreId = $core['coreID'];
  $neighbours = $graph->getNeighbours($coreId);
  $textSharingEt = $graph->getTextsSharingEntity($coreId, true);

  //sending it to the views-class:
  $view = new View($type, array('egoNode' => $core, 'neighbours' => $neighbours, 'relatedTexts' => $textSharingEt));

  $view->generateJSONOnly();
}else{
  echo json_encode(array('error' => 'The provided ID does not have matching record. The related node may be deleted, or it never existed.'));

  die();
}



//merging individual JSON-blocks built by the view-class

echo json_encode(
  array(
    'egonode' => array(),
    'neighbours' => array(
      'projectRelations' => $view->datasilos,
      'variants' => $view->variants,
      'related_texts' => $view->relatedText
    )
  )
);

?>
