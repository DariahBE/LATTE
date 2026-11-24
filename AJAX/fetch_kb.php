<?php
/*
        IMPLEMENTS CRUD API ENDPOINT for all operations
        related to knowledgebases. 
        Read = open
        create and delete require: 
            tokenmanager
            csrf token

    DOCUMENTATION: see_also relation is hardcoded. Must not be user configurable!
    uid property is hardcoded!
    partner's name and uri's are hardcoded!!
*/
header('Content-Type: application/json; charset=utf-8');
include_once(dirname(__DIR__) . '/config/config.inc.php');
include_once(ROOT_DIR . '/includes/getnode.inc.php');
include_once(ROOT_DIR . '/includes/nodes_extend_cud.inc.php');
include_once(ROOT_DIR . '/includes/csrf.inc.php');
include_once(ROOT_DIR . '/includes/user.inc.php');

$graph = new CUDNode($client);

function required_get($name){
    if (isset($_GET[$name])){
        return $_GET[$name]; 
    }
    echo json_encode(array('err'=> 'Incomplete request')); 
    die(); 
}

$etNeoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$allowed_modes = ['create', 'delete'];    //or read, that is however the defaultmode!

if(isset($_GET['mode']) && in_array($_GET['mode'], $allowed_modes) ){
    $mode = $_GET['mode']; 
    $token = required_get('token');
    $tokenManager = new CsrfTokenManager(); 
    $validToken = $tokenManager->checkToken($token); 
    if(!($validToken)){
        echo json_encode(array('msg' => 'Invalid session token')); 
        die();
    }
    $tokenManager->revokeToken(); 

    // Check user edit permissions:
    $user = new User($client);
    $user_id = $user->checkSession();
    if (!$user_id) {
        http_response_code(401);
        die(json_encode(array('error' => 'Login required')));
    }
    $rights = $graph->determineRightsSet($user, $etNeoId);
    if (!$rights['edit']) {
        http_response_code(403);
        die(json_encode(array('error' => 'Permission denied')));
    }

    //start transaction if youwork with the CUD-modes: 
    $graph->startTransaction(); 
}else{
    $mode = 'read'; 
}


if($mode == 'read'){
    echo json_encode($graph->fetchKnowledgebase($etNeoId)); 
    die(); 
}
if($mode == 'create'){
    $label = $_POST['data']['label']; 
    $uri = $_POST['data']['uri']; 
    try {
        $data = $graph->createNewKnowledgebase($label, $uri, $etNeoId);
        //echo json_encode($data); 
    } catch(\Throwable $th) {
        echo 'rollback'; 
        $graph->rollbackTransaction(); 
        die(); 
    }
    $graph->commitTransaction(); 
    echo json_encode($data); 
}

if($mode == 'delete'){
    /**
     * You can have a KB entry connected to multiple entities; check
     * if you can safely delete the KB node or not!
    */
    $kbNeoId = required_get('kbid'); 
    //count the amount of connections between kb node and entities! (over see_also); 
    $data = $graph->countConnectionsOver($kbNeoId, 'see_also'); 
    $count = $data[0]['count']; 
    //var_dump($count); 
    //count == 0 shouldn't happen
    if($count == 1){
        //delete NODE and Relation
        try {
            $graph->delete($kbNeoId); 
        } catch (\Throwable $th) {
            $graph->rollbackTransaction(); 
            die(); 
        }
    }else if($count > 1){
        //keep node, delete a single relation between the relevant nodes!
        try {
            $graph->disconnect($kbNeoId, $etNeoId, 'see_also');
        } catch (\Throwable $th) {
            $graph->rollbackTransaction(); 
            die(); 
        }
    }
    //it all worked: commit delete!
    $graph->commitTransaction(); 
    //operation completed: revoke the token!
    die(); 
}

?>