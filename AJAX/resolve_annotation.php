<?php

header('Content-Type: application/json; charset=utf-8');
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'/includes/getnode.inc.php');
include_once(ROOT_DIR.'/includes/nodes_extend_cud.inc.php');
include_once(ROOT_DIR.'/includes/annotation.inc.php');
include_once(ROOT_DIR.'/includes/user.inc.php');

$annotationId = $_GET['annotation'];    //should be a UUIDV4 ID.

$formattedResponse = array(
  'annotation' => array(),
  'author' => array(), 
  'entity' => array(),
  'variants' => array()
);

//cudnode for access to data that's still in a transaction elsewhere.
$graph = new CUDNode($client);
$graph->startTransaction(); 
$annotation = new Annotation(False, $graph->gettsx());


$user = new User($client);
$annotation_type = $graph->fetchLabelByUUID($annotationId); 
//end of dealing with the annotation type
// Prevent leaking any other nodes than the annotation and automatic annotations!!
if(!(in_array($annotation_type, array(ANNONODE, 'Annotation_auto')))){
  die(json_encode(array('code'=> -1, 'error'=>'This node type is not supported!')));
}
//$annotation type is now constrained to one of two valid options: use the UID which is always present as means of identification. 
$egodata = $graph->matchSingleNode($annotation_type, 'uid', $annotationId);
$egoId = $egodata['neoID'];
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
  //each ANNOTATION has at most ONE entity connected to it using the 'references'
  //labels for a relation. Use this to find the node, extract the label from it.
  // This is only required when loading entities (so on non-automatic annotations!)
  $connectedEntity = $graph->getNeighbours($egoId, 'references'); 
  $connectedEntityLabel = $connectedEntity[0]['t']->getLabels()[0]; 
  // end of dealing with the author of an annotation
  $annotationInformation = $neighbours['annotation'];
  $formattedResponse['annotationFields'] = NODEMODEL[ANNONODE];
  $formattedResponse['entityFields'] = NODEMODEL[$connectedEntityLabel];
  foreach ($annotationInformation['properties'] as $key => $value) {
    $allowedToEdit = $user->hasEditRights($user->myRole);
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
  //$autodata = $annotation->fetchAutomaticAnnotationById($egoId);    // not required any longer
  //var_dump($autodata); 
  //structure of Annotation_auto is pulled from the annotation.inc.php class as private property. 
  // it is structured the same way as the nodesmodel in config.inc.php; 
  $formattedResponse['annotationFields']= $annotation->auto_model[$annot_key]; 
  $formattedResponse['neo_id_of_auto_anno']  = $egoId;       //pass the internal NEOID to js; needed at later stage for update. 

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
  *foreach ($annotationInformation['properties'] as $key => $value) {
  *  $allowedToEdit = $user->hasEditRights($user->myRole, $user->myName === $owner);
  *  $formattedResponse['annotation']['properties'][$key] = array($key, $value, $annotation->isProtectedKey($key), $allowedToEdit);
  *}
  * 
  */

}else{
  die(); 
}
$formattedResponse['annotation']['neoid'] = $egoId;

$formattedResponse['mode'] = $mode; 
$formattedResponse['code'] = 1;
echo json_encode($formattedResponse);

?>
