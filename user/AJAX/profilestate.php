<?php
header('Content-Type: application/json; charset=utf-8');
//TODO: check if user is logged in or not.
session_start(); 
$repl = array(); 

$token = bin2hex(random_bytes(24)); 

$_SESSION['connectiontoken'] = $token;
$_SESSION['connectiontokencreatetime'] = time(); 
//todo, check login and edit the repl valid key; 
$repl['valid'] = true; 
$repl['csrf'] = $token; 
echo json_encode($repl); 
?>