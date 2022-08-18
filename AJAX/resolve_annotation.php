<?php
header('Content-Type: application/json; charset=utf-8');
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR."\includes\getnode.inc.php");
include_once(ROOT_DIR."\includes\annotation.inc.php");

$annotationId = $_GET['annotation'];    //should be a UUIDV4 ID.

$formattedResponse = array(
  'annotation' => array(),
  'author' => array()
);

$graph = new Node($client);
$annotation = new Annotation($client);
$egodata = $graph->matchSingleNode('Annotation', 'uid', $annotationId);
$egoId = $egodata['coreID'];
$neighbours = $annotation->getAnnotationInfo($egoId);

$annotationInformation = $neighbours['annotation'];
$formattedResponse['annotationFields'] = $neighbours['annotationModel']; 
foreach ($annotationInformation['properties'] as $key => $value) {
  $formattedResponse['annotation']['properties'][$key] = array($key, $value, $annotation->isProtectedKey($key));
}

$authorInformation = $neighbours['author'];
foreach ($authorInformation as $key => $value) {
  $formattedResponse['author']['properties'][$key] = array($key, $value, true);
}


echo json_encode($formattedResponse);

?>
