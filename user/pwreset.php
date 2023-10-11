<?php
    include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
    include_once(ROOT_DIR.'/includes/client.inc.php'); 
    include_once(ROOT_DIR.'/includes/user.php');
    $user = new User($client);

    $user->requestPasswordReset();  

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.16/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 h-screen flex justify-center items-center">

<div class="bg-white p-8 rounded shadow-md max-w-sm">
    <h1 class="text-2xl font-semibold mb-6">Password Reset</h1>

    <form action="#" method="POST">
        <div class="mb-4">
            <label for="email" class="block text-gray-600 font-medium">Email</label>
            <input type="email" id="email" name="email" class="w-full border border-gray-300 rounded p-2 focus:outline-none focus:border-blue-400">
        </div>

        <div class="mb-4">
            <label for="password" class="block text-gray-600 font-medium">New Password</label>
            <input type="password" id="password" name="password" class="w-full border border-gray-300 rounded p-2 focus:outline-none focus:border-blue-400">
        </div>

        <div class="mb-4">
            <label for="password_confirmation" class="block text-gray-600 font-medium">Confirm New Password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" class="w-full border border-gray-300 rounded p-2 focus:outline-none focus:border-blue-400">
        </div>

        <button type="submit" class="bg-blue-500 text-white p-2 rounded w-full hover:bg-blue-600">Reset Password</button>
    </form>
</div>

</body>
</html>