<?php

//header('Content-Type: application/json; charset=utf-8');

include_once($_SERVER["DOCUMENT_ROOT"].'/config/config.inc.php');
include_once(ROOT_DIR.'/includes/user.inc.php');
include_once(ROOT_DIR.'/includes/mail.inc.php');
include_once(ROOT_DIR.'/includes/client.inc.php');
include_once(ROOT_DIR."/includes/navbar.inc.php");

$user = new User($client);

if($user->myRole !== "Admin"){
  header("HTTP/1.0 403 Forbidden");
  die("Insufficient rights, forbidden access");
  }

/**
 *    LAYOUT DEPENDS ON REGISTRATION POLICY
 *If the registration policy is invite-only (code 1) then, you need an extra form to add users. 
 *When the registration policy is open to all, thenyou can skip that form.  
 */

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin - User management</title>
  <link rel="stylesheet" href="/CSS/stylePublic.css">
  <link rel="stylesheet" href="/CSS/overlaystyling.css">
</head>
<body>
  <div>
  <?php
    $adminMode = $user->myRole == 'Admin'; 
    $navbar = new Navbar($adminMode); 
    echo $navbar->getNav();
  ?>
  </div>
  <div class="flex justify-center py-4">
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
    <?php
    //allow the admin to register new users in policy 1 or 2 configurations.
    if(REGISTRATIONPOLICY != 0){ 
      ?>
    <h2>Add User Form</h2>
    <!-- Add User Form HTML goes here -->
    <form id="addUserForm" class="space-y-4">
      <!-- Name Field -->
      <div>
        <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
        <input type="text" id="name" name="name" 
               class="mt-1 block w-full border border-gray-300 rounded-md p-2 focus:ring-indigo-500 focus:border-indigo-500" 
               placeholder="Enter name" required>
      </div>
      
      <!-- Email Field -->
      <div>
        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
        <input type="email" id="email" name="email"
               class="mt-1 block w-full border border-gray-300 rounded-md p-2 focus:ring-indigo-500 focus:border-indigo-500"
               placeholder="Enter email" required>
      </div>

      <!-- Role Dropdown -->
      <div>
        <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
        <select id="role" name="role"
                class="mt-1 block w-full border border-gray-300 rounded-md p-2 focus:ring-indigo-500 focus:border-indigo-500" 
                required>
          <option value="">Select role</option>
          <option value="contributor">Contributor</option>
          <option value="researcher">Researcher</option>
          <option value="projectlead">Project leader</option>
          <option value="admin">Admin</option>
        </select>
      </div>

      <!-- Save Button -->
      <div class="text-center">
        <button type="submit" 
                class="w-full bg-indigo-500 text-white rounded-md py-2 hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-opacity-50">
          Save
        </button>
      </div>
    </form>

    <?php } ?>

  </div>

  <div id="promoteUserView" class="hidden">
    <h2>Promote User Form</h2>
    <!-- Promote User Form HTML goes here --> 
    <table class="min-w-full bg-white border border-gray-300">
    <thead>
      <tr class="bg-gray-200 text-gray-600 uppercase text-sm">
        <th class="py-3 px-4 border-b">ID</th>
        <th class="py-3 px-4 border-b">UUID</th>
        <th class="py-3 px-4 border-b">Mail</th>
        <th class="py-3 px-4 border-b">Username</th>
        <th class="py-3 px-4 border-b">Role</th>
        <th class="py-3 px-4 border-b">Completed</th>
        <th class="py-3 px-4 border-b">Save</th>
      </tr>
    </thead>
      <tbody>
        <?php 
        $userdata = $user->listAllUsers(); 
        foreach ($userdata as $row) {
        ?>
          <tr data-uid="<?php echo htmlspecialchars($row['uuid']); ?>" class="hover:bg-gray-100">
            <td class="py-3 px-4 border-b"><?php echo htmlspecialchars($row['id']); ?></td>
            <td class="py-3 px-4 border-b"><?php echo htmlspecialchars($row['uuid']); ?></td>
            <td class="py-3 px-4 border-b"><?php echo htmlspecialchars($row['mail']); ?></td>
            <td class="py-3 px-4 border-b"><?php echo htmlspecialchars($row['username']); ?></td>
            <td class="py-3 px-4 border-b">
              <select name="role" class="border border-gray-300 rounded role-dropdown">
                <option value="Contributor" <?php echo (strtolower($row['role']) === 'contributor') ? 'selected' : ''; ?>>Contributor</option>
                <option value="Researcher" <?php echo (strtolower($row['role']) === 'researcher') ? 'selected' : ''; ?>>Researcher</option>
                <option value="Projectlead" <?php echo (strtolower($row['role']) === 'projectlead') ? 'selected' : ''; ?>>Project leader</option>
                <option value="Admin" <?php echo (strtolower($row['role']) === 'admin') ? 'selected' : ''; ?>>Admin</option>
              </select>
            </td>
            <td class="py-3 px-4 border-b"><?php echo htmlspecialchars($row['completed']); ?></td>
            <td class="py-3 px-4 border-b">
              <button class="save-button bg-gray-300 text-white py-1 px-3 rounded">Save</button>
            </td>
          </tr>
        <?php } ?>
      </tbody>
    </table>
    
  </div>

  <div id="resetUserView" class="hidden">
    <h2>Reset User Access Form</h2>
    <table class="min-w-full bg-white border border-gray-300">
    <thead>
      <tr class="bg-gray-200 text-gray-600 uppercase text-sm">
        <th class="py-3 px-4 border-b">ID</th>
        <th class="py-3 px-4 border-b">UUID</th>
        <th class="py-3 px-4 border-b">Mail</th>
        <th class="py-3 px-4 border-b">Username</th>
        <th class="py-3 px-4 border-b">Block</th>
        <th class="py-3 px-4 border-b">Reset</th>
      </tr>
    </thead>
    <?php 
      foreach ($userdata as $row) {
        $isAllowed = $row['blocked'] === 0; 
        $blockPrompt = $isAllowed ? 'Block' : 'Unblock';
        $blockClass = $isAllowed ? 'bg-red-500' : 'bg-green-500';
        ?>
        <tr data-uid="<?php echo htmlspecialchars($row['uuid']); ?>" class="hover:bg-gray-100">
          <td class="py-3 px-4 border-b"><?php echo htmlspecialchars($row['id']); ?></td>
          <td class="py-3 px-4 border-b"><?php echo htmlspecialchars($row['uuid']); ?></td>
          <td class="py-3 px-4 border-b"><?php echo htmlspecialchars($row['mail']); ?></td>
          <td class="py-3 px-4 border-b"><?php echo htmlspecialchars($row['username']); ?></td>
          <td class="py-3 px-4 border-b">
              <button onclick="runblock()" data-state=<?php echo $row['blocked']; ?> class="block-button <?php echo $blockClass; ?> text-white py-1 px-3 rounded"><?php echo $blockPrompt; ?></button>
          </td>
          <td class="py-3 px-4 border-b">
            <button onclick="runreset()" class="reset-button bg-gray-300 text-white py-1 px-3 rounded">Reset</button>
          </td>
        </tr>

    <?php
      }
    ?>
      </tbody>

  </div>

  <script>
    function runblock(){
      const trigger = event.srcElement || event.target;
      const user_row = trigger.closest('tr');
      const user_uuid = user_row.getAttribute('data-uid');
      let currentValue = trigger.getAttribute('data-state'); 
      let toggledValue = currentValue ^ 1; 
      let formData = new FormData();
        formData.append('userId', user_uuid);
        formData.append('blockValue', toggledValue);
        formData.append('action', 'block');
        fetch("/AJAX/getdisposabletoken.php")
          .then(response => response.json())
          .then(data => {
            formData.append('token', data);
            fetch('../ajax/update_user_data.php', {
              method: 'POST',
              body: formData
            })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                //Toggle button Color.
                trigger.classList.toggle('bg-red-500');
                trigger.classList.toggle('bg-green-500');
                //toglle number state: 
                trigger.setAttribute('data-state', toggledValue); 
                //toggle text: 
                trigger.textContent = toggledValue===1 ? 'Unblock' : 'Block';
              } else {
                console.error('Error: Server failed to process request'); 
              }
            })
            .catch(error => {
              console.error('Error:', error);
            });
          });
    }

    function runreset(){
      const trigger = event.srcElement || event.target;
      const user_row = trigger.closest('tr');
      const user_uuid = user_row.getAttribute('data-uid');
      let formData = new FormData();
      formData.append('userId', user_uuid);
      formData.append('action', 'reset');
      fetch("/AJAX/getdisposabletoken.php")
        .then(response => response.json())
        .then(data => {
          formData.append('token', data);
          fetch('../ajax/update_user_data.php', {
            method: 'POST',
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              //Toggle button Color.
              trigger.classList.add('bg-blue-500', 'delay-150', 'duration-300', 'ease-in-out', 'disabled:cursor-not-allowed'); 
              //disable button for this pageview (admin can reset again later.)
              trigger.disabled = true;
            } else {
              console.error('Error: Server failed to process request');
            }
          })
          .catch(error => {
            console.error('Error:', error);
          });
      });
    }

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

    document.querySelectorAll('.role-dropdown').forEach(dropdown => {
      dropdown.addEventListener('change', function() {
        const button = this.closest('tr').querySelector('.save-button');
        button.classList.remove('bg-gray-300', 'bg-green-500');
        button.classList.add('bg-red-500');
      });
    });

    document.querySelectorAll('.save-button').forEach(button => {
        button.addEventListener('click', function() {
          const dropdown = this.closest('tr').querySelector('.role-dropdown');
          const userId = dropdown.parentElement.parentElement.getAttribute('data-uid');
          const selectedRole = dropdown.value;
          let formData = new FormData();
          formData.append('userId', userId);
          formData.append('selectedRole', selectedRole);
          fetch("/AJAX/getdisposabletoken.php")
          .then(response => response.json())
          .then(data => {
            formData.append('token', data);
            fetch('../ajax/update_user_data.php', {
              method: 'POST',
              body: formData
            })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                this.classList.remove('bg-red-500');
                this.classList.add('bg-green-500');
              } else {

              }
            })
            .catch(error => {
              console.error('Error:', error);
            });
          });
      });
    });

    
    document.getElementById('addUserForm').addEventListener('submit', function (e) {
      e.preventDefault();

      // Fetch form data
      const name = document.getElementById('name').value;
      const email = document.getElementById('email').value;
      const role = document.getElementById('role').value;

      // Basic validation
      if (!name || !email || !role) {
        alert('Please fill in all fields.');
        return;
      }

      // Prepare form data
      const formData = new FormData();
      formData.append('name', name);
      formData.append('email', email);
      formData.append('role', role);
      //add the token to the post data. 
      fetch("/AJAX/getdisposabletoken.php")
          .then(response => response.json())
          .then(data => {
            formData.append('token', data); 
            // Send data via POST request
            fetch('../ajax/add_new_user.php', {
              method: 'POST',
              body: formData,
            })
            .then(response => response.json())
            .then(data => {
              if (data.success) {
                alert('User added successfully!');
              } else {
                alert('Error adding user.');
              }
            })
            .catch(error => {
              console.error('Error:', error);
              alert('Failed to add user.');
            });
          });


    });


    let type = window.location.hash.substr(1);
    if (type === 'overview'){
      addUserView.classList.add('hidden');
      promoteUserView.classList.remove('hidden');
      resetUserView.classList.add('hidden');
    }else if (type === 'block'){
      addUserView.classList.add('hidden');
      promoteUserView.classList.add('hidden');
      resetUserView.classList.remove('hidden');
    }else if(type === 'add'){
      addUserView.classList.remove('hidden');
      promoteUserView.classList.add('hidden');
      resetUserView.classList.add('hidden');
    }




  </script>
</body>
</html>