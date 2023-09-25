<?php
header('Content-Type: application/json; charset=utf-8');
include_once("../../config/config.inc.php");
include_once(ROOT_DIR."\includes\getnode.inc.php");
include_once(ROOT_DIR."\includes\annotation.inc.php");
include_once(ROOT_DIR."\includes\user.inc.php");

//check if the user is logged in; 
if(isset($_SESSION['userid'])){
  $user = new User($client);
  $annotation = new Annotation($client);
}else{
  die();
}

$data = $_POST;
$texID = (int)$data['texNeoid'];
$entityID = (int)$data['sourceNeoID'];
$texSelection = $data['selection']; 
$selectionStart = (int)$data['start']; 
$selectionEnd = (int)$data['stop'];
$token = $data['csrf'];

//connectiontoken should not be older than 5 minutes. 
//check if token equals the session variable and that the session did not yet expire 
//TODO: 1==1 should be removed here, it's breaking the security system: find impact!!
if (1== 1 || isset($_SESSION['connectiontokencreatetime']) && isset($_SESSION['connectiontoken']) && $token === $_SESSION['connectiontoken'] && time() - $_SESSION['connectiontokencreatetime'] < 300 ){
  //destroy the token: can only be used once. 
  unset($_SESSION['connectiontoken']);
  unset($_SESSION['connectiontokencreatetime']);

  $data = $annotation->createAnnotationWithExistingEt($texID, $entityID, $user, $selectionStart, $selectionEnd);
  $annotationState = $data['success'];
  $annotationMsg = $data['msg']; 
  $mergeToDict = array(
    'msg'=> $annotationMsg,
    'success'=> $annotationState
  );
  if($annotationState){
    $assignedID = $data['data'][0]['id(a)']; 
    $annotationNode = $data['data'][0]['a'];
    $entityNode = $data['data'][0]['e'];
    $entityLabel = $entityNode['labels'][0];
    $user = $data['user'][0]['u'];

    $mergeToDict['annotation'] = $annotationNode['properties']['uid'];
    $mergeToDict['creator'] = $user['properties']['userid'];
    $mergeToDict['private'] = false;
    $mergeToDict['start'] = $annotationNode['properties']['starts'];
    $mergeToDict['stop'] = $annotationNode['properties']['stops'];
    $mergeToDict['type'] = $entityLabel;
    $mergeToDict['neoid'] = $assignedID;
    /** Don't show the other attributes */
  }  
  echo json_encode($mergeToDict); 
}else{
  die('Insecure or expired request.'); 
}
#cyper query that creates a new node with label Annotation and connects it to two other nodes by passing the internal ID

?>