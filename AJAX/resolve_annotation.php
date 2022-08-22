<?php
header('Content-Type: application/json; charset=utf-8');
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR."\includes\getnode.inc.php");
include_once(ROOT_DIR."\includes\annotation.inc.php");
include_once(ROOT_DIR."\includes\user.inc.php");

$annotationId = $_GET['annotation'];    //should be a UUIDV4 ID.

$formattedResponse = array(
  'annotation' => array(),
  'author' => array()
);

$graph = new Node($client);
$annotation = new Annotation($client);
$user = new User($client);
$egodata = $graph->matchSingleNode('Annotation', 'uid', $annotationId);
$egoId = $egodata['coreID'];
$neighbours = $annotation->getAnnotationInfo($egoId);

$annotationInformation = $neighbours['annotation'];
$formattedResponse['annotationFields'] = $neighbours['annotationModel'];
foreach ($annotationInformation['properties'] as $key => $value) {
  //does the user OWN the records (is $user->name === $annotation->owner)
  //what role does the user have. ($user->role;)
  //if there's no owner/role set ==> by default assume false false.

  $allowedToEdit = $user->hasEditRights();
  $formattedResponse['annotation']['properties'][$key] = array($key, $value, $annotation->isProtectedKey($key), $allowedToEdit);
}

$authorInformation = $neighbours['author'];
foreach ($authorInformation as $key => $value) {
  $formattedResponse['author']['properties'][$key] = array($key, $value, true);
}


echo json_encode($formattedResponse);

?>
