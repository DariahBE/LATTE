<?php
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'/includes/client.inc.php'); 
include_once(ROOT_DIR.'/includes/user.inc.php');
include_once(ROOT_DIR."/includes/mail.inc.php");


$mail = $_GET['mail'];
$token = $_GET['token']; 
$uuid = $_GET['uuid']; 


//CHECK IF: 
/*
    - user is not blocked
    - token is assigned to user
    - mail matches uuid
*/

/**
 * if checks pass proceed with reset
 * OTHERWISE: error out. 
 */



$user = new User($client); 
$precheck_result = $user->prereset_checks($mail, $uuid, $token); 
var_dump($precheck_result);

 

?>





<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset</title>
    <link rel="stylesheet" href="/CSS/style_entities.css">
    <link rel="stylesheet" href="/CSS/stylePublic.css">
</head>
<body class="bg-gray-100 h-screen flex justify-center items-center">

<div class="bg-white p-8 rounded shadow-md max-w-sm">
    <h1 class="text-2xl font-semibold mb-6">Set New Password</h1>

    <form action="#" method="POST">
        <div class="mb-4">
            <label for="password" class="block text-gray-600 font-medium">New Password</label>
            <input type="password" id="password" name="password" class="w-full border border-gray-300 rounded p-2 focus:outline-none focus:border-blue-400" placeholder="Enter new password">
        </div>

        <div class="mb-4">
            <label for="confirmPassword" class="block text-gray-600 font-medium">Confirm Password</label>
            <input type="password" id="confirmPassword" name="confirmPassword" class="w-full border border-gray-300 rounded p-2 focus:outline-none focus:border-blue-400" placeholder="Confirm new password">
        </div>

        <button type="submit" class="p-2 rounded w-full text-white" style="
                            background: linear-gradient(
                              to right,
                              #ee7724,
                              #d8363a,
                              #dd3675,
                              #b44593
                            );
                          ">Update Password</button>
    </form>
</div>

</body>
</html>
