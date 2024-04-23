<?php
header('Content-Type: application/json; charset=utf-8');
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'\includes\user.inc.php');
include_once(ROOT_DIR.'\includes\csrf.inc.php');
include_once(ROOT_DIR.'\includes\getnode.inc.php');
include_once(ROOT_DIR.'\includes\nodes_extend_cud.inc.php');

//gets the ID of the node to be deleted. 
if(!isset($_POST['ID'])){
    die(); 
}else{
    $id = (int)$_POST['ID'];
}

//gets the single use token that should be set.
if(!isset($_POST['token'])){
    die();
}else{
    $token = $_POST['token']; 
}

if(!isset($_POST['confirmbox'])){
    die(); 
}else{
    $confirmed = $_POST['confirmbox']; 
    if ($confirmed !== 'userconfirmation'){
        die();
    }
} 


$tokenManager = new CsrfTokenManager(); 
$validToken = $tokenManager->checkToken($token); 
if(!($validToken)){
    echo json_encode(array('msg' => 'Invalid session token')); 
    die();
}

//Check if deleterights are granted: 
// assume no ownership 
$ownership = False; 
$user = new User($client); 
$user->checkSession();
//implement: // TODO   !!!!! 
$allowedDelete = $user->hasEditRights($user->myRole, $ownership);


$crudNode = new CUDNode($client);
$crudNode->startTransaction(); 
//get all data of the node: 
//  EGO info: 
$egoLabel = $crudNode->fetchLabelById($id); 


//delete endpoint can delete the following nodetypes: 
//  TEXNODE
//  ENTITYNODE
//  ANNOTATIONNODE

$delete = []; 

//if egolabel is an entitynode! ==> Look for connected annotations
if($egoLabel == TEXNODE ){
    //deleting a text:
    $ntype = 'text';    
    $annos = $crudNode->distinctAnnotationsInText($id);     //NEO4J ids of all ANNOTATIONS that will be deleted.
    $ets = $crudNode->find_isolated_entities($annos);       //NEO4J ids of all ENTITIES that only have ONE annotation which itself is marked for DELETION
    $delete['text'] = array($id); 
    $delete['see_alsos'] = array(); 
    $delete['annotations'] = $annos;
    $delete['entities'] = $ets; 
    $delete['et_floaters'] = $crudNode->find_floating_entity_connections($ets); //todo
}elseif (($egoLabel == ANNONODE ) || ($egoLabel == 'Annotation_auto') ) {
    //deleting an annotation
    $ntype = 'anno'; 
    $ets = $crudNode->find_isolated_entities(array($id));       //NEO4J ids of all ENTITIES that only have ONE annotation which itself is marked for DELETION
    $delete['text'] = array();
    $delete['see_alsos'] = array();
    $delete['annotations'] = array($id); 
    $delete['entities'] = $ets;
    $delete['et_floaters'] = $crudNode->find_floating_entity_connections($ets);; //todo
}elseif(array_key_exists($egoLabel, CORENODES)){
    //deleting an entity
    $ntype = 'entity'; 
    $delete['text'] = array();
    $delete['see_alsos'] = array();
    $delete['annotations'] = $crudNode->annotationsWithThisEntity($id); 
    //corenodes includes text and annonodes, but these cases are captured already
    $delete['entities'] = array($id); 
    $delete['et_floaters'] = $crudNode->find_floating_entity_connections(array($id)); //todo


}else{
    //not allowed 
    die();
}

// var_dump($crudNode->bulk_delete_by_ids(array(7877, 7881))); 
// die(); 
//do delete action here: 
$deleteOrder = array('text', 'see_alsos', 'annotations', 'entities', 'et_floaters'); 
foreach($deleteOrder as $deletePart){
    $crudNode->bulk_delete_by_ids($delete[$deletePart]);
}

$crudNode->commitTransaction();
/*
$crudNode->bulk_delete_by_ids($delete['see_alsos']);
$crudNode->bulk_delete_by_ids($delete['annotations']); 
$crudNode->bulk_delete_by_ids($delete['entities']); 
$crudNode->bulk_delete_by_ids($delete['entities_neighbhours']); 
*/




?>