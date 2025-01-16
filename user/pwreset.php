<?php


//TODO: Actual password reset procedure not implemented yet. (Pending test required. )
//TODO: implement captcha (OK)
    if(
        isset($_POST['captchaSolution']) &&
        isset($_POST['email']) &&
        filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)
    ){
        session_start(); 
        var_dump($_SESSION['captcha_token']);
        var_dump($_POST['captchaSolution']);
        if ($_SESSION['captcha_token'] == $_POST['captchaSolution']){
            //die('start this block');
            include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
            include_once(ROOT_DIR.'/includes/client.inc.php'); 
            include_once(ROOT_DIR.'/includes/user.inc.php');
            $user = new User($client);
            $user->requestPasswordReset($_POST['email'], False);
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
</head>
<body class="bg-gray-100 h-screen flex justify-center items-center">

<div class="bg-white p-8 rounded shadow-md max-w-sm">
    <h1 class="text-2xl font-semibold mb-6">Password Reset</h1>

    <form action="#" method="POST">
        <div class="mb-4">
            <label for="email" class="block text-gray-600 font-medium">Email</label>
            <input type="email" id="email" name="email" class="w-full border border-gray-300 rounded p-2 focus:outline-none focus:border-blue-400">
        </div>

        <!-- captcha image -->
        <img src='/captcha/image.php'>

        <!-- captcha solution --> 
        <div class="mb-4">
        <input
            type="input"
            class="form-control block w-full px-3 py-1.5 text-base font-normal text-gray-700 bg-white bg-clip-padding border border-solid border-gray-300 rounded transition ease-in-out m-0 focus:text-gray-700 focus:bg-white focus:border-blue-600 focus:outline-none"
            placeholder="Type captcha solution of six characters"
            name="captchaSolution"
            id="captchaSolution"
        />
        </div>

        <button type="submit" class="bg-blue-500 text-white p-2 rounded w-full hover:bg-blue-600">Reset Password</button>
    </form>
</div>

</body>
</html>