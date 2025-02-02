<?php
header('Content-Type: application/json; charset=utf-8');
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'/includes/getnode.inc.php');
include_once(ROOT_DIR.'/includes/nodes_extend_cud.inc.php');
include_once(ROOT_DIR.'/includes/annotation.inc.php');
include_once(ROOT_DIR.'/includes/user.inc.php');
include_once(ROOT_DIR.'/includes/csrf.inc.php');

//check if the user is logged in; 
if(isset($_SESSION['userid'])){
  $user = new User($client);
  //node and client need to share all updates in a single transactino object: 
  // SHARED TRANSACTION
  $node = new CUDNode($client); 
  $node->startTransaction(); 
  $annotation = new Annotation(False, $node->gettsx());
}else{
  die();
}

$tokenManager = new CsrfTokenManager();


$data = $_POST;
$texID = (int)$data['texNeoid'];
$entityID = (int)$data['sourceNeoID'];
$texSelection = $data['selection']; 
$selectionStart = (int)$data['start']; 
$selectionEnd = (int)$data['stop'];
$token = $data['csrf'];
$extra = $data['properties'];
//check if token equals the session variable and that the session did not yet expire 
if (isset($_SESSION['connectiontokencreatetime']) && isset($_SESSION['fastconnectiontoken']) && $token === $_SESSION['fastconnectiontoken'] && time() - $_SESSION['connectiontokencreatetime'] < 300 ){
  //destroy the token: can only be used once. 
  unset($_SESSION['fastconnectiontoken']);
  unset($_SESSION['connectiontokencreatetime']);
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

  echo json_encode($mergeToDict); 
  $annotation->commitTransaction();   // you need to commit when the transaction finishes without errors!
}else{
  die(json_encode('Insecure or expired request.')); 
}
#cyper query that creates a new node with label Annotation and connects it to two other nodes by passing the internal ID
// TODO: auto_annotation node has to be deleted when ET's are connected to a text over an automatic annotation!!!! 
?>