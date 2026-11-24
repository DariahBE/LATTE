<?php
/**
 *      Bulk insert endpoint of texts into NEO. 
 * 
 * Only open to admin users. 
 */

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


if (!defined('ROOT_DIR')) {
  include_once(dirname(dirname(__DIR__)).'/config/config.inc.php');
}
include_once(ROOT_DIR.'/includes/csrf.inc.php');

$tokenManager = new CsrfTokenManager();
if (!isset($_SESSION['userrole']) || strtolower($_SESSION['userrole']) !== "admin") {
    header("HTTP/1.0 403 Forbidden");
    die(json_encode(array('error' => 'Forbidden', 'msg' => 'Insufficient rights, forbidden access')));
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!$tokenManager->checkToken($csrfToken)) {
    header("HTTP/1.0 403 Forbidden");
    die(json_encode(array('error' => 'CSRF error', 'msg' => 'Invalid session token')));
}

//required for reading the model: 
include_once(ROOT_DIR.'/includes/getnode.inc.php');
//get POST data when it is sent.
$keys = json_decode($_POST['keys'], false);
$data = json_decode($_POST['data'], false);
if ($data === null){die();}
if ($keys === null){die();}
//check if postdata matches the model: 
$nodetype = TEXNODE;
$primary = helper_extractPrimary($nodetype);
if (in_array($primary, $keys) === false){
    die(json_encode(array('error'=>'Invalid request.', "msg"=>"Primary key ($primary) is undefined.")));
}
foreach ($keys as $key) {
    if(!(array_key_exists($key, NODEMODEL[$nodetype]))){
        die(json_encode(array('error'=>'Invalid request.', "msg"=> "Invalid keys in request.")));
    }
}
include_once(ROOT_DIR.'/includes/getnode.inc.php');
include_once(ROOT_DIR.'/includes/nodes_extend_cud.inc.php');
include_once(ROOT_DIR.'/includes/user.inc.php');


$user = new User($client);
$node = new CUDNode($client);
$node->startTransaction();
try {
    foreach ($data as $new_text) {
        $formdata = array_combine($keys, $new_text);
        $graphResult = $node->createNewNode($nodetype, $formdata, true);
        $connection = $node->connectCreatorToNode($_SESSION['neoid'], $graphResult); 
    }
    $node->commitTransaction(); 
    echo json_encode(array(1)); 
} catch (\Throwable $th) {
    $node->rollbackTransaction();
    http_response_code(500);
    echo json_encode(array('error' => 'Bulk insert failed: ' . $th->getMessage()));
}
exit;
?>