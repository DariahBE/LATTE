<?php
header('Content-Type: application/json; charset=utf-8');

session_start();
//user_uuid gets set after login, so is a good indicator of being logged in. 
if (!(isset($_SESSION['user_uuid']))) {
    die(json_encode(array('valid'=>false)));
}
$repl = array(); 

$token = bin2hex(random_bytes(24)); 

if(isset($_GET['fastconnect']) && (int)$_GET['fastconnect'] == 1){
    $_SESSION['fastconnectiontoken'] = $token;
}

$_SESSION['connectiontoken'] = $token;
$_SESSION['connectiontokencreatetime'] = time(); 
//todo, check login and edit the repl valid key; 
$repl['valid'] = true; 
$repl['csrf'] = $token; 
echo json_encode($repl); 
?>