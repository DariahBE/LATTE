<?php
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

//TODO actually deleting the elements still needs to be tested!!!!
die(); 
//do delete action here: 
$deleteOrder = array('text', 'see_alsos', 'annotations', 'entities'); 
$crudNode->bulk_delete_by_ids($delete['text']);
$crudNode->bulk_delete_by_ids($delete['see_alsos']);
$crudNode->bulk_delete_by_ids($delete['annotations']); 
$crudNode->bulk_delete_by_ids($delete['entities']); 
$crudNode->bulk_delete_by_ids($delete['entities_neighbhours']); 


var_dump($delete); 



?>