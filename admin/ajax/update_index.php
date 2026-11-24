<?php
header('Content-Type: application/json; charset=utf-8');
// Base imports: 
include_once(dirname(__DIR__, 2) . "/config/config.inc.php");
include_once(ROOT_DIR . "/includes/client.inc.php");
include_once(ROOT_DIR . "/includes/user.inc.php");
include_once(ROOT_DIR . "/includes/csrf.inc.php");
include_once(ROOT_DIR . "/includes/getnode.inc.php");

$user = new User($client);
$user_id = $user->checkSession();
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit(); 
}
// Only allow admins here: 
if (!$user->isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Insufficient rights, forbidden access']);
    exit(); 
}
$adminMode = true;

$node = new Node($client); 

// Check and revoke the token here: 
if(isset($_POST['token'])){
    $token = $_POST['token'];
    $tokenManager = new CsrfTokenManager();
    $validToken = $tokenManager->checkToken($token); 
    if(!($validToken)){
        http_response_code(403);
        echo json_encode(['error' => 'Invalid or expired CSRF token']);
        exit();
    }
    $tokenManager->revokeToken();   // Kill the token if valid and continue code logic
}else{
    http_response_code(400);
    echo json_encode(['error' => 'Missing CSRF token']);
    exit(); 
}

// Code continues if token is valid (at this point, token has been revoked): 

if(isset($_POST['action']) && isset($_POST['label']) && isset($_POST['prop'])){
    $action = $_POST['action']; 
    $label = $_POST['label']; 
    $prop = $_POST['prop']; 
    $idxname = null; 
    if($action === 'drop'){
        if(isset($_POST['idxname'])){
            $idxname = $_POST['idxname']; 
        }
    }
    $result = $node->modifyIndex($label, $prop, $action, $idxname); 
    echo json_encode($result);
}else{
    http_response_code(400);
    echo json_encode(['error' => 'Missing required action, label, or prop parameter']);
    exit(); 
}
?>