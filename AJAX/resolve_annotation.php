<?php
header('Content-Type: application/json; charset=utf-8');
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR."\includes\getnode.inc.php");
include_once(ROOT_DIR."\includes\annotation.inc.php");
include_once(ROOT_DIR."\includes\user.inc.php");

$annotationId = $_GET['annotation'];    //should be a UUIDV4 ID.

$formattedResponse = array(
  'annotation' => array(),
  'author' => array(), 
  'entity' => array(),
  'variants' => array()
);

$graph = new Node($client);
$annotation = new Annotation($client);
$user = new User($client);
$egodata = $graph->matchSingleNode('Annotation', 'uid', $annotationId);
$egoId = $egodata['neoID'];
$neighbours = $annotation->getAnnotationInfo($egoId);
//annotation can be anonymous!
if($neighbours['author']){
  $owner = $neighbours['author']->get('name');
  foreach ($neighbours['author'] as $key => $value) {
    $formattedResponse['author']['properties'][$key] = array($key, $value, true);
  }
}else{
  $owner = false;
}
// end of dealing with the author of an annotation
$annotationInformation = $neighbours['annotation'];
$formattedResponse['annotationFields'] = NODEMODEL['Annotation'];
foreach ($annotationInformation['properties'] as $key => $value) {
  $allowedToEdit = $user->hasEditRights($user->myRole, $user->myName === $owner);
  $formattedResponse['annotation']['properties'][$key] = array($key, $value, $annotation->isProtectedKey($key), $allowedToEdit);
}

//Find the connecting entity that is linked to the annotation and labelvariants associated with this entity: 
$etData = $graph->findEntity($annotationId);
$varData = $graph->findVariants($etData['entity']['neoID']);
$formattedResponse['variants'][]=$varData; 
$formattedResponse['entity'][]=$etData['entity'];

echo json_encode($formattedResponse);

?>
