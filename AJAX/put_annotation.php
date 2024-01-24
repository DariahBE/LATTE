<?php
/**
 *  this endpoint creates a new node 
 *  then connects an annotation to it and the text
 *  then all spelling variants including the selected text get added to the node as variant.
 * If the Annotationmode key is set to 'auto', then the application knows it should update the
 * annoation_auto to annotation; otherwise annotation is created. 
 */

    //header:
    header('Content-Type: application/json; charset=utf-8');
    //includes: 
    include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
    include_once(ROOT_DIR.'/includes/user.inc.php');
    include_once(ROOT_DIR.'/includes/csrf.inc.php');
    include_once(ROOT_DIR.'/includes/getnode.inc.php');
    include_once(ROOT_DIR.'/includes/nodes_extend_cud.inc.php');
    include_once(ROOT_DIR.'/includes/annotation.inc.php');

    //check if the annotation is being created by a valid user: If no user is logged in => die()
    $user = new User($client);
    //check user
    $user_uuid = $user->checkSession();
    if(!(isset($_POST['data']))){
        echo json_encode(array('msg' => 'Data is missing in request. Your request was rejected.'));
        die(); 
    }


    // TODO : spelling variants are not handled yet!

    //TODO: conversion from auto to manual annotation is not done correctly

    //interpretation of the post request!
    $data = $_POST['data']; 
    //parse parts of the data: 
    //// csrf: 
    $token = array_key_exists('token', $data) ? $data['token'] : false;
    //// annotation: 
    $annotationNode = array_key_exists('annotation', $data) ? $data['annotation'] : false; 
    //// variants: 
    $variants = array_key_exists('variants', $data) ? $data['variants'] : false; 
    //// properties: 
    $properties = array_key_exists('properties', $data) ? $data['properties'] : false; 
    //// Neo id of the text: 
    $texid = array_key_exists('texid', $data) ? $data['texid'] : false; 
    //// Labeltype of the node to be created:
    $nodelabel = array_key_exists('nodetype', $data) ? $data['nodetype'] : false; 
    //// Extract the annotation mode: automated (= convert auto to confirmed) or '' (create confirmed directly)
    ////        Default to manual: only one chain of actions will lead to  automatic-conversion. 
    $annomode = array_key_exists('annotationmode', $data) ? $data['annotationmode'] : '';         //default to manual! 
    // BUG: there's an issue in the logic flow of the program: 
    //      automated nodes should have a way of identifying the existing node based on it's NEO4J internal ID!!!
    ///////////////////////////////////
    // check if token is part of data dict AND for validity: 
    if(!($token)){
        echo json_encode(array('msg' => 'Invalid session token')); 
        die();
    }
    if(!($texid) || !($nodelabel)){
        echo json_encode(array('msg' => 'Invalid node content'));
        die();
    }
    $tokenManager = new CsrfTokenManager(); 
    $validToken = $tokenManager->checkToken($token); 
    if(!($validToken)){
        echo json_encode(array('msg' => 'Invalid session token')); 
        die();
    }

    $node = new CUDNode($client);
    $annotation = new Annotation($client); 

    ///////////////////////////////////
    // check if the provided annotation matches the structure in config. 
    $annotationModel = NODEMODEL[ANNONODE]; 
    $startProperty = ANNOSTART;
    $endProperty = ANNOSTOP; 

    //make database commits a transaction!
    $node->startTransaction();
    //entire chain is conditional and should only be committed if all queries succeed!!
    ///////////////////////////////////
    // creates a new node with a shortlived NEOID; properties are checked in the 
    // remote function. Invalid input leads to early termination. 
    // use the properties to create a new node if it does not exist: 
    try {
        $createdEntity = $node->createNewNode($nodelabel, $properties, true); 
        //var_dump($createdEntity);
    }catch (\Throwable $th){
        //throw $th;
        $node->rollbackTransaction();
        die('rollback of changes: init error');
    }
    //connect the user ID to $createdEnditity!
    try{
        $userNeoId = $user->neoId;
        $node->connectNodes($userNeoId, $createdEntity, 'priv_created');
    }catch(\Throwable $th){
        //throw $th;
        $node->rollbackTransaction();
        die('rollback of changes: user error');
    }
    //connect variant spellings to the $createdentity:
    //user ID is not connected to variants.
    //Connect the variants: 
    //TODO  test variant creation! ==> Are not being created at the moment: //BUG!!
    foreach($variants as $variant){
        try{
            $node->createVariantRelation($variant, $createdEntity); 
        }catch(\Throwable $th){
            $node->rollbackTransaction();
            die('Rejected variant node. '); 
        }
    }
        
    //connect the $createdEntity to a text using the text NEOID and the $createdEntity ID
    if ($annomode === 'automated'){
        try {
            //TODO: the update dict with values writtin in the DOM are still missin in here: you need to remove the start/stop properties!
            unset($annotationNode[ANNOSTART]);
            unset($annotationNode[ANNOSTOP]);
            $annotation_neo_id = $data['neo_id_internal']; 
            $createAnnotation = $annotation->convertAutomaticAnnotationToConfirmedAnnotation($annotation_neo_id, $annotationNode); 
        }catch(\Throwable $th){
            $node->rollbackTransaction();
            throw $th;
            die('rollback of changes: annoupdate error');
        }
    }else{
        try {
            $createAnnotation = $node->createNewNode(ANNONODE, $annotationNode,true);
        }catch(\Throwable $th){
            $node->rollbackTransaction();
            throw $th;
            die('rollback of changes: annocreation error');
        }
    }
        
    //connect the entity with the annotation !
    try{
        $node->connectNodes($createAnnotation, $createdEntity, 'references');
    }catch(\Throwable $th){
        //throw $th;
        $node->rollbackTransaction();
        die('rollback of changes: annotation ID error');
    }

    //connect the text with the annotation !
    //BUG: should only be triggered when using manual annotations. Not on nodes which where of the Annotation_auto-type!
    try{
        $node->connectNodes($texid, $createAnnotation, 'contains');
    }catch(\Throwable $th){
        //throw $th;
        $node->rollbackTransaction();
        die('rollback of changes: annotation ID error');
    }

    $node->commitTransaction();
    // if database commit was successfull: revoke the token. 
    $tokenManager->revokeToken(); 
    echo json_encode($node); 
    //TODO or //BUG: figure out why $node is returning the tsx object on completion rather then the newly created elements!
    //die('token revoked');
?>