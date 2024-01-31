<?php

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
//TODO (critical): Test transaction implementation. 
$annotation->startTransaction(); 


$user = new User($client);
//TODO PATCH: generate a new method which acts as classifier to distinguish Annotation and Annotation_auto nodes! 
$annotation_type = $graph->fetchLabelByUUID($annotationId); 
//end of dealing with the annotation type
// Prevent leaking any other nodes than the annotation and automatic annotations!!
if(!(in_array($annotation_type, array(ANNONODE, 'Annotation_auto')))){
  die(json_encode(array('code'=> -1, 'error'=>'Annotation type not supported!')));
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
  $annot_key  = 'Automatic_annotation'; 
  $autodata = $annotation->fetchAutomaticAnnotationById($egoId);    // TODO stuck here!!
  //var_dump($autodata); 
  //structure of Annotation_auto is pulled from the annotation.inc.php class as private property. 
  // it is structured the same way as the nodesmodel in config.inc.php; 
  $formattedResponse['annotationFields']= $annotation->auto_model[$annot_key]; 
  $formattedResponse['neo_id_of_auto_anno']  = $egoId;       //pass the internal NEOID to js; needed at later stage for update. 
  //TODO: implement key and value here. True and 0 should remain. 

  //$formattedResponse['annotation']['properties'] = ['starts' => ['starts', 'vl', true, 0], 'stops' => ['stops', 'vl', true, 0]];
  //var_dump($egodata['data'][0][0]);
  //var_dump($egodata['data'][0][0]->get('node')->getProperty('uid')); 
  $node = $egodata['data'][0][0]->get('node'); 
  //var_dump($node); 
  foreach ($annotation->auto_model[$annot_key] as $key => $value) {
    $allowedToEdit = False;   //non-editable by default!
    $protected = True;        //always protect autogen values!
    $value = $node->getProperty($key); 
    
    $formattedResponse['annotation']['properties'][$key]= array($key, $value, $protected, $allowedToEdit);
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
$formattedResponse['code'] = 1;
echo json_encode($formattedResponse);

?>
