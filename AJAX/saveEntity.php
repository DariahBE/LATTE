<?php
header('Content-Type: application/json; charset=utf-8');
include_once("../config/config.inc.php");
include_once(ROOT_DIR.'/includes/csrf.inc.php');
include_once(ROOT_DIR.'/includes/user.inc.php');


$mode = $_POST['data']['mode']; 
$token = $_POST['data']['token']; 

// check if a user is logged in: 


//check that token is valid:



?>
