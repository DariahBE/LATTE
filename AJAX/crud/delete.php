<?php
/**
 * this is the ajax endpoint to perform a delete and determine whether or not the DOM should show the option. 
 * Permissions have to be integrated with CUDNode methods. 
 */

header('Content-Type: application/json; charset=utf-8');
if (!defined('ROOT_DIR')) {
  include_once(dirname(dirname(__DIR__)).'/config/config.inc.php');
}
include_once(ROOT_DIR.'/includes/user.inc.php');
include_once(ROOT_DIR.'/includes/csrf.inc.php');
include_once(ROOT_DIR.'/includes/getnode.inc.php');
include_once(ROOT_DIR.'/includes/nodes_extend_cud.inc.php');

$isCheckRights = isset($_GET['checkrights']) || isset($_POST['checkrights']);

if (!$isCheckRights && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("HTTP/1.0 405 Method Not Allowed");
    echo json_encode(array('msg' => 'Method not allowed. Node deletion requires POST.'));
    die();
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : null);
if ($id === null) {
    die(); 
}

$token = $_POST['token'] ?? $_GET['token'] ?? null;
if (!$token) {
    die();
}

$tokenManager = new CsrfTokenManager(); 
$validToken = $tokenManager->checkToken($token); 
if(!($validToken)){
    echo json_encode(array('msg' => 'Invalid session token')); 
    die();
}

$user = new User($client);
if ($user->checkSession() === false) {
    header("HTTP/1.0 401 Unauthorized");
    die(json_encode(array('msg' => 'Authentication required')));
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
$IsTheUserAllowedToDelete = $crudNode->determineRightsSet(3, $id); 
if($isCheckRights){
    $crudNode->rollbackTransaction();
    die(json_encode(array('actionAllowed'=>$IsTheUserAllowedToDelete)));//if the application only has to check for the delete rights
}
if(!($IsTheUserAllowedToDelete)){
    $crudNode->rollbackTransaction();
    die(json_encode(array('msg'=>'You have no permission to delete this node.')));
}else{
    //add a switch here for dryrun method. 
    $dryrun = isset($_POST['dryrun']) || isset($_GET['dryrun']); 
    try {
        $data = $crudNode->delete($id, $dryrun);
    } catch (\Throwable $th) {
        $crudNode->rollbackTransaction(); 
        die('Error deleting data'); 
    }
    $crudNode->commitTransaction();
    echo json_encode($data);
}

?>