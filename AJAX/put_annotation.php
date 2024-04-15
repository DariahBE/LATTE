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
    $node->startTransaction();

    $annotation = new Annotation(False, $node->gettsx()); 

    ///////////////////////////////////
    // check if the provided annotation matches the structure in config. 
    $annotationModel = NODEMODEL[ANNONODE]; 
    $startProperty = ANNOSTART;
    $endProperty = ANNOSTOP; 

    //make database commits a transaction!
    //$annotation->startTransaction(); 
    //entire chain is conditional and should only be committed if all queries succeed!!
    ///////////////////////////////////
    // creates a new node with a shortlived NEOID; properties are checked in the 
    // remote function. Invalid input leads to early termination. 
    // use the properties to create a new node if it does not exist: 

    //set the neo id of the user as a global variable, needed in multiple places. 
    $userNeoId = (int)$user->neoId; 

    /*
     *          enforce values for properties which are set by
     *          model primary keys!!!
    */
    // do a check of the provided properties: 
    // is there a uniqueness key defined in the model? If so: 
    //      check if it is provide, 
    //      if not, generate a unique value. 
    $model = NODEMODEL[$nodelabel];
    //look for access to the primary key data. What key in the returned NODE type is the Primary Key. (unique field)
    $found_key = array_search(true, array_column($model, 2), true);
    if ($found_key !== false){
        $keys = array_keys($model);
        $primaryKeyName = $keys[$found_key];
        if(array_key_exists($primaryKeyName, $properties)){
            //check for uniqueness:
            $value_to_check = $properties[$primaryKeyName]; 
            if ($value_to_check == ''){
                $value_to_check = $node->generateUniqueKey($nodelabel, $primaryKeyName); 
                $properties[$primaryKeyName] = $value_to_check; 
            }else{
                $node->checkKeyUniqueness($nodelabel, $primaryKeyName, $value_to_check);
            }
        }else{
            // no unique key provided: let the program generate a new integer ID. 
            // then assign it into the properties dictionary!
            $properties[$primaryKeyName] = $node->generateUniqueKey($nodelabel, $primaryKeyName); 
        }
    }try {
        //createNewNode needs to automatically generate a primary key for entities where the model uses an integer key as unique value. 
        $createdEntity = $node->createNewNode($nodelabel, $properties, true); 
        //var_dump($properties); 
        //returns NEO ID of created node. 
    }catch (\Throwable $th){
        //throw $th;
        $node->rollbackTransaction();
        die($th);
    }

    //connect the user ID to $createdEnditity!
    try{
        $node->connectNodes($userNeoId, $createdEntity, 'priv_created');
    }catch(\Throwable $th){
        //throw $th;
        $node->rollbackTransaction();
        die('rollback of changes: user error');
    }
    
    //connect variant spellings to the $createdentity:
    //user ID is not connected to variants.
    //Connect the variants: 
    foreach($variants as $variant){
        try{
            $r = $node->createVariantRelation($variant, $createdEntity); 
        }catch(\Throwable $th){
            $node->rollbackTransaction();
            die('Rejected variant node. '); 
        }
    }
        
    //connect the $createdEntity to a text using the text NEOID and the $createdEntity ID
    if ($annomode === 'automated'){
        try {
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

    //TODO (Low): needs to be done more efficiently, get the UUID directly when creating annotationnode!
    $createdAnnotationUUID = $annotation->fetchAnnotationUUID($createAnnotation); 
    //connect the user to the created annotation: 
    $node->connectNodes($userNeoId, $createAnnotation, 'priv_created'); 

    //connect the entity with the annotation !
    try{
        $node->connectNodes($createAnnotation, $createdEntity, 'references');
    }catch(\Throwable $th){
        //throw $th;
        $node->rollbackTransaction();
        die('rollback of changes: annotation ID error');
    }

    //connect the text with the annotation !
    if($annomode !== 'automated'){
        try{
            $node->connectNodes($texid, $createAnnotation, 'contains');
        }catch(\Throwable $th){
            //throw $th;
            $node->rollbackTransaction();
            die('rollback of changes: annotation ID error');
        }
    }


    $node->commitTransaction();
    $node_reply = array();
    $node_reply['data'] = array(
        'intid' => $createAnnotation,
        'uuid' => $createdAnnotationUUID, 
        'type' => $nodelabel, 
        'start' => $annotationNode[ANNOSTART], 
        'stop' => $annotationNode[ANNOSTOP],
    );
    $node_reply['tsx'] = $node;

    // if database commit was successfull: revoke the token. 
    $tokenManager->revokeToken(); 
    echo json_encode($node_reply); 
    //die('token revoked');
?>