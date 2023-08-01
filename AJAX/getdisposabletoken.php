<?php
//session_start(); 
include_once("../config/config.inc.php");

header('Content-Type: application/json; charset=utf-8');

include_once(ROOT_DIR.'/includes/csrf.inc.php');
include_once(ROOT_DIR.'/includes/user.inc.php');
//check user
$user = new User($client);
$user_uuid = $user->checkSession();

$token = new CsrfTokenManager; 
$token->revokeToken();
$token->generateToken();
echo json_encode($token->getTokenFromSession()); 
?>