<?php 
/**
 * AJAX endpoint that stores app_automatic annotations that were generated
 * by the LATTE connector. Upon storing it needs to receive a UUIDV4. 
 * The basis properties required in an Annotation_auto node are:
 *  start   (int)
 *  stop    (int)
 *  uuid    (uuidV4 ==> generated by APOC)
 */

 //This ajax endpoint is a create point ==> level 1; 
$local_permissionLevel = 1; 

//header:
header('Content-Type: application/json; charset=utf-8');
//includes: 
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'/includes/user.inc.php');
include_once(ROOT_DIR.'/includes/csrf.inc.php');
include_once(ROOT_DIR.'/includes/getnode.inc.php');
include_once(ROOT_DIR.'/includes/nodes_extend_cud.inc.php');
include_once(ROOT_DIR.'/includes/annotation.inc.php');

//reading gets:
if(isset($_GET['starts']) && isset($_GET['stops']) && isset($_GET['texid']) && isset($_GET['token'])){
    $starts = (int)$_GET['starts'];     //character where the annotation starts
    $stops = (int)$_GET['stops'];       //character where the annotation stops
    $texid = (int)$_GET['texid'];       //NEO4J id of the texnode
    $token = $_GET['token']; 
}else{
    die(); 
}

//check the token: 
$tokenManager = new CsrfTokenManager();
$validToken = $tokenManager->checkToken($token); 
if(!($validToken)){
    echo json_encode(array('msg' => 'Invalid session token')); 
    die();
}
$tokenManager->revokeToken();   //kill the token. 

//USERSTUFF
//do a new permissioncheck!!
//(the token could be stored from elsewhere and be used here to bypass the check in the tokenmanager)
$user = new User($client);
if($user->hasEditRights($user->myRole) < $local_permissionLevel){
    die();
};




// Token is accepted AND User has the correct rightsset
//then=> create a new CUDNode make a transaction, store and commit. 

$node = new CUDNode($client);
$node->startTransaction();

$annotation = new Annotation(False, $node->gettsx()); 



try {
    $array_of_annotations = array(array($starts, $stops)); 
    $result = $annotation->createRecognizedAnnotation($texid, $array_of_annotations);
} catch (\Throwable $th) {
    //something went south: rollback:
    $node->rollbackTransaction();
    die(); 
}


//all good: commit the nodes in the DB
$node->commitTransaction();
//echo "hello world"; 

echo json_encode($result); 





?>