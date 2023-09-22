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

    $debug = true; 

    if(!($debug)){
        //check if the annotation is being created by a valid user: If no user is logged in => die()
        $user = new User($client); 
        //check user
        $user_uuid = $user->checkSession();
        if(!(isset($_POST['data']))){
            echo json_encode(array('msg' => 'Data is missing in request. Your request was rejected.'));
            die(); 
        }

        $data = $_POST['data']; 
        //parse parts of the data: 
        //// csrf: 
        $token = $data['token'] ? $data['token'] : false;
        //// annotation: 
        $annotation = $data['annotation'] ? $data['annotation'] : false; 
        //// variants: 
        $variants = $data['variants'] ? $data['variants'] : false; 
        //// properties: 
        $properties = $data['properties'] ? $data['properties'] : false; 
        //// Neo id of the text: 
        $texid = $data['texid']; 
        //// Labeltype of the node to be created:
        $nodelabel = $data['nodetype']; 


        ///////////////////////////////////
        // check if token is part of data dict AND for validity: 
        if(!($token)){
            echo json_encode(array('msg' => 'Invalid session token')); 
            die();
        }
        $tokenManager = new CsrfTokenManager(); 
        $validToken = $tokenManager->checkToken($token); 
        if(!($validToken)){
            echo json_encode(array('msg' => 'Invalid session token')); 
            die();
        }
    }

    if($debug){
        $data = array(
            "token"=> "86eb6e48e3cadb7061f686cf24707be9f9beb64842e33b055927be15d715f5f9",
            "texid"=> 1375, 
            "nodetype"=> "Place",
            "annotation"=> array(
              "start"=> 123,
              "stop"=> 129,
              "selectedText"=> "Zurich"
        ),
            "variants"=> array("Zuerich", "Zuri"),
            "properties"=> array(
              "geoid"=> "45",
              "label"=> "zu",
              "region"=> "zur",
              "wikidata"=> "Q72"
            )
        );

        $token = $data['token'] ? $data['token'] : false;
        //// annotation: 
        $annotation = $data['annotation'] ? $data['annotation'] : false; 
        //// variants: 
        $variants = $data['variants'] ? $data['variants'] : false; 
        //// properties: 
        $properties = $data['properties'] ? $data['properties'] : false; 
        //// Neo id of the text: 
        $texid = $data['texid']; 
        //// Labeltype of the node to be created:
        $nodelabel = $data['nodetype']; 
        

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
        var_dump($createdEntity);
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
    //connect the $createdEntity to a text using the text NEOID and the $createdEntity ID
    try {
        $createAnnotation = $node->createNewNode(ANNONODE, $annotation,true);
        //on $createdEntity, attach all labelvariants!
        var_dump($createAnnotation);
    }catch(\Throwable $th){
        //throw $th;
        $node->rollbackTransaction();
        die('rollback of changes: annocreation error');
    }



    $node->commitTransaction();


    die('debug statement verwijderen');
    /////////////////////////////////////
    // if database commit was successfull: revoke the token. 
    $tokenManager->revokeToken(); 



?>