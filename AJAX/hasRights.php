<?php

/*
    Checks if the current user has rights or not to DELETE or UDPATE a node.
    This API-endpoint is only for building DOM-elements, not for the server
    side validation or a request.
*/

header('Content-Type: application/json; charset=utf-8');
//includes: 
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'/includes/user.inc.php');
include_once(ROOT_DIR.'/includes/csrf.inc.php');
include_once(ROOT_DIR.'/includes/getnode.inc.php');
include_once(ROOT_DIR.'/includes/nodes_extend_cud.inc.php');

//check if the annotation is being created by a valid user: If no user is logged in => die()
$user = new User($client);
$node = new CUDNode($client);
//check user
$user_id = $user->checkSession();

if(isset($_GET['id_e'])){
    $et = (int)$_GET['id_e'];
}else{
    $et = -1;
}

if(isset($_GET['id_a'])){
    $anno = (int)$_GET['id_a'];
}else{
    $anno = -1;
}

$access_level = $user->hasEditRights($user->myRole); 
if($access_level >= 3){
    $delete_level_et = True;
    $delete_level_anno = True;
    $update_level_et = True;
    $update_level_anno = True;
}elseif($access_level == 2){
    //only update is allowed if you are the owner of the node.
    $ownerShip_et = $node->checkOwnershipOfNode($et, $user->neoId);
    $ownerShip_anno = $node->checkOwnershipOfNode($anno, $user->neoId);
    $delete_level_et = False;
    $delete_level_anno = False;
    $update_level_et = $ownerShip_et;
    $update_level_anno = $ownerShip_anno;
}else{
    $delete_level_et = False;
    $delete_level_anno = False;
    $update_level_et = False;
    $update_level_anno = False;
}



$results = array(
    'et' => array('delete' => $delete_level_et, 'update' => $update_level_et),
    'anno' => array('delete' => $delete_level_anno, 'update' => $update_level_anno)
);
echo json_encode($results);

?>