<?php
header('Content-Type: application/json; charset=utf-8');
include_once(dirname(__DIR__, 2) . '/config/config.inc.php');
include_once(ROOT_DIR . '/includes/getnode.inc.php');
include_once(ROOT_DIR . '/includes/nodes_extend_cud.inc.php');
include_once(ROOT_DIR . '/includes/annotation.inc.php');
include_once(ROOT_DIR . '/includes/user.inc.php');
include_once(ROOT_DIR . '/includes/csrf.inc.php');
include_once(ROOT_DIR . '/includes/latte_queue_manager.inc.php');

$user = new User($client);
$user_id = $user->checkSession();
if(!$user_id){
  http_response_code(401);
  die(json_encode(array('error' => 'Login required')));
}

// Node and client need to share all updates in a single transaction object: 
$tokenManager = new CsrfTokenManager();

$data = $_POST;
$texID = isset($data['texNeoid']) ? (int)$data['texNeoid'] : 0;
$entityID = isset($data['sourceNeoID']) ? (int)$data['sourceNeoID'] : 0;
$texSelection = isset($data['selection']) ? $data['selection'] : ''; 
$selectionStart = isset($data['start']) ? (int)$data['start'] : 0; 
$selectionEnd = isset($data['stop']) ? (int)$data['stop'] : 0;
$token = isset($data['csrf']) ? $data['csrf'] : '';
$extra = isset($data['properties']) ? $data['properties'] : [];
//check if token equals the session variable and that the session did not yet expire 
if (isset($_SESSION['connectiontokencreatetime']) && isset($_SESSION['fastconnectiontoken']) && $token === $_SESSION['fastconnectiontoken'] && time() - $_SESSION['connectiontokencreatetime'] < 300 ){
  //destroy the token: can only be used once. 
  unset($_SESSION['fastconnectiontoken']);
  unset($_SESSION['connectiontokencreatetime']);

  // Node and client need to share all updates in a single transaction object: 
  $node = new CUDNode($client); 
  $node->startTransaction(); 
  $annotation = new Annotation(False, $node->gettsx());

  try {
    $data = $annotation->createAnnotationWithExistingEt($texID, $entityID, $user, $selectionStart, $selectionEnd, $extra);
  } catch (\Throwable $th) {
    $annotation->rollbackTransaction(); 
    die(json_encode("An unexpected error occurred.")); 
  }
  $annotationState = $data['success'];
  $annotationMsg = $data['msg']; 
  $mergeToDict = array(
    'msg'=> $annotationMsg,
    'success'=> $annotationState
  );
  if($annotationState){
    $annotationNode = $data['data'][0]['a'];

    $annotationprimary = 'uid';
    // $type = ANNONODE;
    if (array_key_exists(ANNONODE, PRIMARIES) && boolval(PRIMARIES[ANNONODE])){
      $annotationprimary = PRIMARIES[ANNONODE];
      //check if the primary key of the ANNOTATION is set in the node, otherwise do go back to uid.
      if(!(array_key_exists($annotationprimary, $annotationNode['properties']->toArray()))){
        $annotationprimary = 'uid';
      }
    }
    $assignedID = $data['data'][0]['id(a)']; 
    $entityNode = $data['data'][0]['e'];
    $entityLabel = $entityNode['labels'][0];
    $user = $data['user'][0]['u'];
    $mergeToDict['annotation'] = $annotationNode['properties'][$annotationprimary];
    $mergeToDict['annotation'] = $annotationNode['properties']['uid'];
    $mergeToDict['creator'] = $user['properties']['user_sqlid'];
    $mergeToDict['private'] = false;
    $mergeToDict['start'] = $annotationNode['properties'][ANNOSTART];
    $mergeToDict['stop'] = $annotationNode['properties'][ANNOSTOP];
    $mergeToDict['type'] = $entityLabel;
    $mergeToDict['neoid'] = $assignedID;
    /** Don't show the other attributes */
  }  

  //use $texSelection to create a new variant if it does not yet exist. 
  try{
    $node->createVariantRelation($texSelection, $entityID); 
  }catch (\Throwable $th) {
    $annotation->rollbackTransaction(); 
    die(json_encode("An unexpected error occurred.")); 
  }

  // Promote any automatic suggestion covering this span by removing it from latte_suggestions on the text node.
  try {
    LatteQueueManager::promoteSuggestion($client, $texID, $selectionStart, $selectionEnd);
  } catch (\Throwable $e) {
    error_log('Failed to promote automatic suggestion on text node ' . $texID . ': ' . $e->getMessage());
  }

  echo json_encode($mergeToDict); 
  $annotation->commitTransaction();   // you need to commit when the transaction finishes without errors!
}else{
  die(json_encode('Insecure or expired request.')); 
}
?>
