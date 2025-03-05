<?php
include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'/includes/client.inc.php'); 
include_once(ROOT_DIR.'/includes/user.inc.php');
include_once(ROOT_DIR."/includes/mail.inc.php");

$mail = $_GET['mail'];
$token = $_GET['token']; 
$uuid = $_GET['uid']; 

$user = new User($client); 
$precheck_result = $user->prereset_checks($mail, $uuid, $token); 
if ($precheck_result !== 1){
    die("Error: ".$precheck_result); 
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];

    if (!($user->passwordPolicyCheck($password))){
        die("Error: Password does not meet requirements. A minimum of 8 characters is needed and at least two of the following must be true: Uppercase, Lowercase, Special symbols, numbers");
    }

    if (empty($password) || empty($confirmPassword)) {
        die("Error: Password fields cannot be empty.");
    }
    
    if ($password !== $confirmPassword) {
        die("Error: Passwords do not match.");
    }
    
    $update_result = $user->resetPassword($uuid, $password);
    if ($update_result === true) {
        echo "<script>alert('Password successfully updated!'); window.location.href='/login.php';</script>";
    } else {
        die("Error: ".$update_result);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset</title>
    <link rel="stylesheet" href="/CSS/style_entities.css">
    <link rel="stylesheet" href="/CSS/stylePublic.css">
    <script src="/JS/password_policy.js"></script>

</head>
<body class="bg-gray-100 h-screen flex justify-center items-center">

<div class="bg-white p-8 rounded shadow-md max-w-sm">
    <h1 class="text-2xl font-semibold mb-6">Set New Password</h1>

    <form action="" method="POST">
        <div class="mb-4">
            <label for="password" class="block text-gray-600 font-medium">New Password</label>
            <input type="password" id="password" name="password" class="w-full border border-gray-300 rounded p-2 focus:outline-none focus:border-blue-400" placeholder="Enter new password" required>
        </div>

        <div class="mb-4">
            <label for="confirmPassword" class="block text-gray-600 font-medium">Confirm Password</label>
            <input type="password" id="confirmPassword" name="confirmPassword" class="w-full border border-gray-300 rounded p-2 focus:outline-none focus:border-blue-400" placeholder="Confirm new password" required>
        </div>
        <div class="mb-4">
                        <p class="text-xs text-gray-500">Password must be at least 8 characters long and contain at least two of the following: uppercase, lowercase, number, special character</p>
        </div>
        <button id="submitbutton" disabled type="submit" class="p-2 rounded w-full text-white" style="
                            background: linear-gradient(
                              to right,
                              #ee7724,
                              #d8363a,
                              #dd3675,
                              #b44593
                            );
                          ">Update Password</button>
    </form>

    <script>
    //Activate the password policy script:
    document.addEventListener('DOMContentLoaded', function() {
        setupPasswordValidation('password', 'confirmPassword', 'submitbutton');
    });
    </script>
</div>

</body>
</html>