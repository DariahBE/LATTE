<?php
    //header:
    header('Content-Type: application/json; charset=utf-8');
    //includes: 
    include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
    include_once(ROOT_DIR.'/includes/user.inc.php');
    include_once(ROOT_DIR.'/includes/csrf.inc.php');

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

    ///////////////////////////////////
    // check if the provided annotation matches the structure in config. 
    $annotationModel = NODESMODEL[ANNONODE]; 
    $startProperty = ANNOSTART;
    $endProperty = ANNOSTOP; 
    var_dump($annotationModel); 








    /////////////////////////////////////
    // if database commit was successfull: revoke the token. 
    $tokenManager->revokeToken(); 



?>