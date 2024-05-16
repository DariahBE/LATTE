<?php
/**
 * this is the ajax endpoint to perform a delete and determine whether or not the DOM should show the option. 
 * Permissions have to be integrated with CUDNode methods. 
 */

header('Content-Type: application/json; charset=utf-8');
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'\includes\user.inc.php');
include_once(ROOT_DIR.'\includes\csrf.inc.php');
include_once(ROOT_DIR.'\includes\getnode.inc.php');
include_once(ROOT_DIR.'\includes\nodes_extend_cud.inc.php');


if(!isset($_GET['id'])){
    die(); 
}


if(!isset($_GET['token'])){
    die();
}else{
    $token = $_GET['token']; 
}
$tokenManager = new CsrfTokenManager(); 
$validToken = $tokenManager->checkToken($token); 
if(!($validToken)){
    echo json_encode(array('msg' => 'Invalid session token')); 
    die();
}


$crudNode = new CUDNode($client);
//Transactions = test passed. 
/** determine rights first delete endpoint is only granted to limit set of users. 
 * This could depend on the node ID so do a read-check first!
 * DELETE rights are granted for: 
 *      ANY node where the user is a level 3 or higher!
 *      ANY anotation node owned by the user!
 * 
*/
$crudNode->startTransaction(); 
$IsTheUserAllowedToDelete = $crudNode->determineRightsSet(3, (int)$_GET['id']); 
if(isset($_GET['checkrights'])){
    die(json_encode(array('actionAllowed'=>$IsTheUserAllowedToDelete)));//if the application only has to check for the delete rights
}
if(!($IsTheUserAllowedToDelete)){
    die(json_encode(array('msg'=>'You have no permission to delete this node.')));
}else{
    //add a switch here for dryrun method. 
    $dryrun = isset($_GET['dryrun']); 
    try {
        $data = $crudNode->delete((int)$_GET['id'], $dryrun);
    } catch (\Throwable $th) {
        //throw $th;
        $crudNode->rollbackTransaction(); 
        die('Error deleting data'); 
    }
    $crudNode->commitTransaction();
    echo json_encode($data);
}

?>