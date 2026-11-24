<?php

header('Content-Type: application/json; charset=utf-8');
include_once(dirname(__DIR__) . '/config/config.inc.php');
include_once(ROOT_DIR . '/includes/getnode.inc.php');
include_once(ROOT_DIR . '/includes/nodes_extend_cud.inc.php');
include_once(ROOT_DIR . '/includes/annotation.inc.php');
include_once(ROOT_DIR . '/includes/user.inc.php');

$annotationId = isset($_GET['annotation']) ? trim($_GET['annotation']) : '';

$formattedResponse = array(
  'annotation' => array(),
  'author' => array(), 
  'entity' => array(),
  'variants' => array()
);

$graph = new CUDNode($client);
$annotation = new Annotation($client);

$user = new User($client);
$user->checkSession();
$annotation_type = $graph->fetchLabelByUUID($annotationId); 

// If not a standalone node, check whether it is a persistent suggestion stored on a text node.
if (!(in_array($annotation_type, array(ANNONODE, 'Annotation_auto')))) {
  $textLabel = defined('TEXNODE') ? TEXNODE : 'Text';
  try {
    $sugQuery = "
      MATCH (t:{$textLabel})
      WHERE t.latte_suggestions CONTAINS \$annotationId
      RETURN id(t) AS text_neo_id, t.latte_suggestions AS suggestions
      LIMIT 1
    ";
    $sugRes = $client->run($sugQuery, ['annotationId' => $annotationId]);
    if ($sugRes->count() > 0) {
      $row = $sugRes->first();
      $textNeoId = (int)$row->get('text_neo_id');
      $rawSuggestions = $row->get('suggestions');
      $sugList = is_string($rawSuggestions) ? json_decode($rawSuggestions, true) : (is_array($rawSuggestions) ? $rawSuggestions : []);
      
      $matched = null;
      if (is_array($sugList)) {
        foreach ($sugList as $s) {
          if (($s['id'] ?? ($s['annotation'] ?? '')) === $annotationId) {
            $matched = $s;
            break;
          }
        }
      }

      if ($matched !== null) {
        $start = (int)($matched['start'] ?? ($matched['startPos'] ?? 0));
        $stop = (int)($matched['stop'] ?? ($matched['endPos'] ?? 0));
        $detectedType = (string)($matched['label'] ?? ($matched['labelTex'] ?? 'Entity'));
        $allowedToEdit = ($user->hasEditRights($user->myRole) >= 1);

        $formattedResponse['mode'] = 'automated';
        $formattedResponse['code'] = 1;
        $formattedResponse['suggestion_id'] = $annotationId;
        $formattedResponse['text_neo_id'] = $textNeoId;
        $formattedResponse['neo_id_of_auto_anno'] = 0;
        $formattedResponse['can_remove'] = $allowedToEdit;
        $formattedResponse['annotation']['neoid'] = 0;
        $formattedResponse['annotation']['properties'] = [
          ANNOSTART => [ANNOSTART, $start, true, false],
          ANNOSTOP => [ANNOSTOP, $stop, true, false],
          'detected_type' => ['detected_type', $detectedType, true, false],
          'source' => ['source', 'LATTE connector', true, false]
        ];
        $formattedResponse['annotationFields'] = [
          ANNOSTART => ['Starts at', 'int'],
          ANNOSTOP => ['Stops at', 'int'],
          'detected_type' => ['Detected entity type', 'text'],
          'source' => ['Source', 'text']
        ];
        echo json_encode($formattedResponse);
        exit();
      }
    }
  } catch (\Throwable $e) {
    error_log('Failed to lookup suggestion on text node: ' . $e->getMessage());
  }

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
    //filter out keys that are in the NEO4J database; but not in the configured model any more. 
    if(array_key_exists($key, NODEMODEL[ANNONODE])){
      $formattedResponse['annotation']['properties'][$key] = array($key, $value, $annotation->isProtectedKey($key), $allowedToEdit);
    }
  }
  //Find the connecting entity that is linked to the annotation and labelvariants associated with this entity: 
  $etData = $graph->findEntity($annotationId);
  $varData = $graph->findVariants($etData['entity']['neoID']);
  $formattedResponse['variants'][]=$varData; 
  $formattedResponse['entity'][]=$etData['entity'];
}else if($annotation_type === 'Annotation_auto'){
  $mode = 'automated'; 
  $annot_key  = 'Automatic_annotation'; 
  //structure of Annotation_auto is pulled from the annotation.inc.php class as private property. 
  // it is structured the same way as the nodesmodel in config.inc.php; 
  $formattedResponse['annotationFields']= $annotation->auto_model[$annot_key]; 
  $formattedResponse['neo_id_of_auto_anno']  = $egoId;       //pass the internal NEOID to js; needed at later stage for update. 

  $node = $egodata['data'][0][0]->get('node'); 
  foreach ($annotation->auto_model[$annot_key] as $key => $value) {
    $allowedToEdit = False;   //non-editable by default!
    $protected = True;        //always protect autogen values!
    $value = $node->getProperty($key); 
    
    $formattedResponse['annotation']['properties'][$key]= array($key, $value, $protected, $allowedToEdit);
  }

}else{
  die(); 
}
$formattedResponse['annotation']['neoid'] = $egoId;

$formattedResponse['mode'] = $mode; 
$formattedResponse['code'] = 1;
echo json_encode($formattedResponse);

?>
