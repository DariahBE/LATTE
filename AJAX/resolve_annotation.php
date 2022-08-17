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
//echo json_encode($annotationInformation);
foreach ($annotationInformation['properties'] as $key => $value) {
  $formattedResponse['annotation']['properties'][] = array($key, $value, $annotation->isProtectedKey($key));
}


echo json_encode($formattedResponse);

?>
