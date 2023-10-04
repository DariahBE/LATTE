<?php
/**
 *  this endpoint creates a new node 
 *  then connects an annotation to it and the text
 *  then all spelling variants including the selected text get added to the node as variant
 */

    //header:
    header('Content-Type: application/json; charset=utf-8');
    //includes: 
    include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
    include_once(ROOT_DIR.'/includes/user.inc.php');
    include_once(ROOT_DIR.'/includes/csrf.inc.php');
    include_once(ROOT_DIR.'/includes/getnode.inc.php');
    include_once(ROOT_DIR.'/includes/nodes_extend_cud.inc.php');

    //check if the annotation is being created by a valid user: If no user is logged in => die()
    $user = new User($client);
    //check user
    $user_uuid = $user->checkSession();
    if(!(isset($_POST['data']))){
        echo json_encode(array('msg' => 'Data is missing in request. Your request was rejected.'));
        die(); 
    }


    // TODO : spelling variants are not handled yet!

    //die('TODO:  missing variants'); 


    //interpretation of the post request!
    $data = $_POST['data']; 
    //parse parts of the data: 
    //// csrf: 
    $token = array_key_exists('token', $data) ? $data['token'] : false;
    //// annotation: 
    $annotation = array_key_exists('annotation', $data) ? $data['annotation'] : false; 
    //// variants: 
    $variants = array_key_exists('variants', $data) ? $data['variants'] : false; 
    //// properties: 
    $properties = array_key_exists('properties', $data) ? $data['properties'] : false; 
    //// Neo id of the text: 
    $texid = array_key_exists('texid', $data) ? $data['texid'] : false; 
    //// Labeltype of the node to be created:
    $nodelabel = array_key_exists('nodetype', $data) ? $data['nodetype'] : false; 

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
    foreach($variants as $variant){
        try{
            $node->createVariantRelation($variant, $createdEntity); 
        }catch(\Throwable $th){
            $node->rollbackTransaction();
            die('Rejected variant node. '); 
        }
    }
        
    //connect the $createdEntity to a text using the text NEOID and the $createdEntity ID
    try {
        $createAnnotation = $node->createNewNode(ANNONODE, $annotation,true);
    }catch(\Throwable $th){
        $node->rollbackTransaction();
        throw $th;
        die('rollback of changes: annocreation error');
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
    die('token revoked');
?>