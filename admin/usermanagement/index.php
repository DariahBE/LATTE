<?php

//header('Content-Type: application/json; charset=utf-8');

include_once("../../config/config.inc.php");
include_once(ROOT_DIR."\includes\user.inc.php");
include_once(ROOT_DIR."\includes\mail.inc.php");





?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tailwind Page</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body>
  <div class="flex justify-center items-center h-screen">
    <div class="space-y-4">
      <button id="addUserBtn" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
        Add User
      </button>
      <button id="promoteUserBtn" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
        Promote User
      </button>
      <button id="resetUserBtn" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
        Reset User Access
      </button>
    </div>
  </div>

  <div id="addUserView" class="hidden">
    <h2>Add User Form</h2>
    <!-- Add User Form HTML goes here -->
  </div>

  <div id="promoteUserView" class="hidden">
    <h2>Promote User Form</h2>
    <!-- Promote User Form HTML goes here -->
  </div>

  <div id="resetUserView" class="hidden">
    <h2>Reset User Access Form</h2>
    <!-- Reset User Access Form HTML goes here -->
  </div>

  <script>
    const addUserBtn = document.getElementById('addUserBtn');
    const promoteUserBtn = document.getElementById('promoteUserBtn');
    const resetUserBtn = document.getElementById('resetUserBtn');
    const addUserView = document.getElementById('addUserView');
    const promoteUserView = document.getElementById('promoteUserView');
    const resetUserView = document.getElementById('resetUserView');

    addUserBtn.addEventListener('click', () => {
      addUserView.classList.remove('hidden');
      promoteUserView.classList.add('hidden');
      resetUserView.classList.add('hidden');
    });

    promoteUserBtn.addEventListener('click', () => {
      addUserView.classList.add('hidden');
      promoteUserView.classList.remove('hidden');
      resetUserView.classList.add('hidden');
    });

    resetUserBtn.addEventListener('click', () => {
      addUserView.classList.add('hidden');
      promoteUserView.classList.add('hidden');
      resetUserView.classList.remove('hidden');
    });
  </script>
</body>
</html>