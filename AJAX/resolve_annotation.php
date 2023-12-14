<?php

//BUG: critical: http://entitylinker.test/AJAX/resolve_annotation.php?annotation=77405f18-f6c7-4d47-98d3-ad73721b4f8b 
/**
 * Annotations which are loaded in having the Annotation_auto label don't fit the pattern expected here. 
 * You need to run an extra check and split logic!
 */



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
//PATCH TODO: generate a new method which acts as classifier to distinguish Annotation and Annotation_auto nodes! 
$annotation_type = $graph->fetchLabelByUUID($annotationId); 
//end of dealing with the annotation type
// Prevent leaking any other nodes than the annotation and automatic annotations!!
if(!(in_array($annotation_type, array(ANNONODE, 'Annotation_auto')))){
  die(json_encode(array('error'=>'Annotation type not supported!')));
}
//$annotation type is now constrained to one of two valid options: use the UID which is always present as means of identification. 
$egodata = $graph->matchSingleNode($annotation_type, 'uid', $annotationId);
$egoId = $egodata['neoID'];
//var_dump($egoId); 
if ($annotation_type === ANNONODE){
  $mode = 'controll'; 
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
  $formattedResponse['annotationFields'] = NODEMODEL[ANNONODE];
  foreach ($annotationInformation['properties'] as $key => $value) {
    $allowedToEdit = $user->hasEditRights($user->myRole, $user->myName === $owner);
    $formattedResponse['annotation']['properties'][$key] = array($key, $value, $annotation->isProtectedKey($key), $allowedToEdit);
  }
  //Find the connecting entity that is linked to the annotation and labelvariants associated with this entity: 
  $etData = $graph->findEntity($annotationId);
  $varData = $graph->findVariants($etData['entity']['neoID']);
  $formattedResponse['variants'][]=$varData; 
  $formattedResponse['entity'][]=$etData['entity'];
}else if($annotation_type === 'Annotation_auto'){
  $mode = 'automated'; 
  $autodata = $annotation->fetchAutomaticAnnotationById($egoId);    // TODO stuck here!!
  var_dump($autodata); 
  //structure of Annotation_auto is pulled from the annotation.inc.php class as private property. 
  // it is structured the same way as the nodesmodel in config.inc.php; 
  $formattedResponse['annotationFields'] = $annotation->auto_model; 
  //TODO: implement key and value here. True and 0 should remain. 

  //$formattedResponse['annotation']['properties'] = ['starts' => ['starts', 'vl', true, 0], 'stops' => ['stops', 'vl', true, 0]];
  foreach ($annotation->auto_model as $key => $value) {
    $allowedToEdit = false;   //non-editable by default!
    $formattedResponse['annotation']['properties'][$key] = array($key, $value, True, $allowedToEdit);
  }
  /**
  foreach ($annotationInformation['properties'] as $key => $value) {
    $allowedToEdit = $user->hasEditRights($user->myRole, $user->myName === $owner);
    $formattedResponse['annotation']['properties'][$key] = array($key, $value, $annotation->isProtectedKey($key), $allowedToEdit);
  }
   * 
   */

}else{
  die(); 
}

$formattedResponse['mode'] = $mode; 
echo json_encode($formattedResponse);

?>
