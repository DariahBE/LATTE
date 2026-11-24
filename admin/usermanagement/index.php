<?php
  if (!defined('ROOT_DIR')) {
    include_once(dirname(dirname(__DIR__)) . '/config/config.inc.php');
  }
  include_once(ROOT_DIR . '/includes/client.inc.php');
  include_once(ROOT_DIR . '/includes/user.inc.php');
  include_once(ROOT_DIR . '/includes/mail.inc.php');
  include_once(ROOT_DIR . '/includes/navbar.inc.php');

  $user = new User($client);
  if ($user->checkSession() === false) {
    $loginUrl = (defined('BASE_URL') ? BASE_URL : '') . "/user/login.php?redir=" . urlencode((defined('BASE_URL') ? BASE_URL : '') . "/admin/usermanagement/index.php");
    header("Location: $loginUrl");
    exit();
  }

  // Only allow admins here.
  if (!$user->isAdmin()) {
    header("HTTP/1.0 403 Forbidden");
    die("Insufficient rights, forbidden access");
  }

  $adminMode = true;
  $baseUrl = defined('BASE_URL') ? BASE_URL : '';
  $userdata = $user->listAllUsers();
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User management - <?php echo htmlspecialchars(PROJECTNAME, ENT_QUOTES, 'UTF-8'); ?></title>
  <link rel="stylesheet" href="<?php echo $baseUrl; ?>/CSS/stylePublic.css">
  <link rel="stylesheet" href="<?php echo $baseUrl; ?>/CSS/overlaystyling.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="bg-slate-100 text-slate-800 w-full antialiased min-h-screen">
  <?php
    $navbar = new Navbar($adminMode); 
    echo $navbar->getNav();
    include_once('../admin_tasks.php');
  ?>

  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
    <!-- Header -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">User management</h1>
        <p class="mt-1 text-sm text-slate-500">Oversee user accounts, assign permission roles, manage access blocks, and invite collaborators.</p>
      </div>

      <!-- Segmented tab controls -->
      <div class="inline-flex rounded-lg bg-slate-200/80 p-1 text-xs font-semibold">
        <button id="tabOverviewBtn"
                onclick="switchTab('overview')"
                class="tab-btn px-4 py-1.5 rounded-md transition-all bg-white text-indigo-700 shadow-xs">
          <i class="fas fa-users mr-1.5"></i>Users overview
        </button>
        <?php if (REGISTRATIONPOLICY != 0): ?>
          <button id="tabAddBtn"
                  onclick="switchTab('add')"
                  class="tab-btn px-4 py-1.5 rounded-md transition-all text-slate-600 hover:text-slate-900">
            <i class="fas fa-user-plus mr-1.5"></i>Add user
          </button>
        <?php endif; ?>
      </div>
    </div>

    <!-- Overview tab: user accounts table -->
    <section id="viewOverview" class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
      <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
        <h2 class="text-sm font-bold text-slate-800 tracking-tight">Registered accounts (<?php echo count($userdata); ?>)</h2>
        <span class="text-xs text-slate-500">Edit role, toggle account suspension, or dispatch reset links</span>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100 text-xs">
          <thead class="bg-slate-50/60 text-slate-500 uppercase tracking-wider font-semibold">
            <tr>
              <th scope="col" class="py-3 px-4 text-left">ID</th>
              <th scope="col" class="py-3 px-4 text-left">Username</th>
              <th scope="col" class="py-3 px-4 text-left">Email</th>
              <th scope="col" class="py-3 px-4 text-left">Role</th>
              <th scope="col" class="py-3 px-4 text-center">Status</th>
              <th scope="col" class="py-3 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 bg-white">
            <?php foreach ($userdata as $row): 
              $isBlocked = (int)$row['blocked'] === 1;
              $isCompleted = (int)$row['completed'] === 1;
              $currentRole = strtolower($row['role']);
            ?>
              <tr data-uid="<?php echo htmlspecialchars($row['uuid'], ENT_QUOTES, 'UTF-8'); ?>" class="hover:bg-slate-50/60 transition-colors">
                <td class="py-3 px-4 text-slate-400 font-mono"><?php echo htmlspecialchars((string)$row['id'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td class="py-3 px-4 font-semibold text-slate-900">
                  <?php echo htmlspecialchars($row['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                </td>
                <td class="py-3 px-4 text-slate-600 font-mono">
                  <?php echo htmlspecialchars($row['mail'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                </td>
                <td class="py-3 px-4">
                  <div class="inline-flex items-center gap-1.5">
                    <select name="role" class="role-dropdown rounded-lg border border-slate-200 py-1 px-2 text-xs text-slate-700 bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500">
                      <option value="contributor" <?php echo ($currentRole === 'contributor') ? 'selected' : ''; ?>>Contributor</option>
                      <option value="researcher" <?php echo ($currentRole === 'researcher') ? 'selected' : ''; ?>>Researcher</option>
                      <option value="projectlead" <?php echo ($currentRole === 'projectlead') ? 'selected' : ''; ?>>Project leader</option>
                      <option value="admin" <?php echo ($currentRole === 'admin') ? 'selected' : ''; ?>>Admin</option>
                    </select>
                    <button class="save-role-btn hidden px-2 py-1 rounded-md text-[11px] font-semibold bg-indigo-600 text-white hover:bg-indigo-700 shadow-2xs transition-colors">
                      Save
                    </button>
                  </div>
                </td>
                <td class="py-3 px-4 text-center">
                  <div class="inline-flex items-center gap-1.5">
                    <?php if ($isBlocked): ?>
                      <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-rose-100 text-rose-800">
                        Blocked
                      </span>
                    <?php else: ?>
                      <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-800">
                        Active
                      </span>
                    <?php endif; ?>

                    <?php if (!$isCompleted): ?>
                      <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-amber-100 text-amber-800" title="Invitation pending confirmation">
                        Pending
                      </span>
                    <?php endif; ?>
                  </div>
                </td>
                <td class="py-3 px-4 text-right">
                  <div class="inline-flex items-center justify-end gap-1.5">
                    <!-- Block/unblock toggle -->
                    <button onclick="runblock(this)"
                            data-state="<?php echo (int)$row['blocked']; ?>"
                            class="block-btn px-2.5 py-1 rounded-md text-xs font-medium border transition-colors shadow-2xs <?php echo $isBlocked ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-rose-50 hover:text-rose-700 hover:border-rose-200'; ?>">
                      <i class="fas <?php echo $isBlocked ? 'fa-unlock' : 'fa-ban'; ?> mr-1 text-[10px]"></i>
                      <span><?php echo $isBlocked ? 'Unblock' : 'Block'; ?></span>
                    </button>

                    <!-- Reset password trigger -->
                    <button onclick="runreset(this)"
                            title="Send password reset link to user"
                            class="reset-btn px-2.5 py-1 rounded-md text-xs font-medium bg-slate-50 text-slate-700 border border-slate-200 hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-200 transition-colors shadow-2xs">
                      <i class="fas fa-key mr-1 text-[10px]"></i>
                      <span>Reset</span>
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Add user tab -->
    <?php if (REGISTRATIONPOLICY != 0): ?>
      <section id="viewAdd" class="hidden max-w-xl mx-auto bg-white rounded-xl border border-slate-200/80 shadow-xs p-6">
        <div class="mb-5 pb-4 border-b border-slate-100">
          <h2 class="text-base font-bold text-slate-800">Invite new user</h2>
          <p class="mt-0.5 text-xs text-slate-500">Send an invitation email with a verification link to register an account.</p>
        </div>

        <form id="addUserForm" class="space-y-4">
          <div>
            <label for="name" class="block text-xs font-semibold text-slate-700 mb-1">Full name</label>
            <input type="text" id="name" name="name"
                   class="w-full rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                   placeholder="e.g. Jane Doe" required>
          </div>

          <div>
            <label for="email" class="block text-xs font-semibold text-slate-700 mb-1">Email address</label>
            <input type="email" id="email" name="email"
                   class="w-full rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                   placeholder="user@example.com" required>
          </div>

          <div>
            <label for="role" class="block text-xs font-semibold text-slate-700 mb-1">Assigned role</label>
            <select id="role" name="role"
                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-xs text-slate-800 bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500"
                    required>
              <option value="">Select a role</option>
              <option value="contributor">Contributor</option>
              <option value="researcher">Researcher</option>
              <option value="projectlead">Project leader</option>
              <option value="admin">Administrator</option>
            </select>
          </div>

          <div id="addStatusMsg" class="hidden p-3 rounded-lg text-xs"></div>

          <div class="pt-2">
            <button type="submit"
                    id="submitAddBtn"
                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 shadow-xs transition-colors">
              <i class="fas fa-paper-plane"></i>
              <span>Create and send invitation</span>
            </button>
          </div>
        </form>
      </section>
    <?php endif; ?>
  </main>

  <script>
    const baseUrl = <?php echo json_encode($baseUrl); ?>;

    // Switch between overview and add user views.
    function switchTab(target) {
      const viewOverview = document.getElementById('viewOverview');
      const viewAdd = document.getElementById('viewAdd');
      const tabOverviewBtn = document.getElementById('tabOverviewBtn');
      const tabAddBtn = document.getElementById('tabAddBtn');

      if (target === 'add' && viewAdd) {
        viewOverview.classList.add('hidden');
        viewAdd.classList.remove('hidden');
        tabOverviewBtn.classList.remove('bg-white', 'text-indigo-700', 'shadow-xs');
        tabOverviewBtn.classList.add('text-slate-600');
        if (tabAddBtn) {
          tabAddBtn.classList.add('bg-white', 'text-indigo-700', 'shadow-xs');
          tabAddBtn.classList.remove('text-slate-600');
        }
        window.location.hash = 'add';
      } else {
        viewOverview.classList.remove('hidden');
        if (viewAdd) viewAdd.classList.add('hidden');
        tabOverviewBtn.classList.add('bg-white', 'text-indigo-700', 'shadow-xs');
        tabOverviewBtn.classList.remove('text-slate-600');
        if (tabAddBtn) {
          tabAddBtn.classList.remove('bg-white', 'text-indigo-700', 'shadow-xs');
          tabAddBtn.classList.add('text-slate-600');
        }
        window.location.hash = 'overview';
      }
    }

    // Initialize view from hash.
    document.addEventListener('DOMContentLoaded', function() {
      const hash = window.location.hash.replace('#', '');
      if (hash === 'add') {
        switchTab('add');
      } else {
        switchTab('overview');
      }
    });

    // Toggle block status.
    function runblock(trigger) {
      const userRow = trigger.closest('tr');
      const userUuid = userRow.getAttribute('data-uid');
      let currentValue = parseInt(trigger.getAttribute('data-state'), 10) || 0;
      let toggledValue = currentValue ^ 1;

      let formData = new FormData();
      formData.append('userId', userUuid);
      formData.append('blockValue', toggledValue);
      formData.append('action', 'block');

      fetch(baseUrl + '/AJAX/getdisposabletoken.php')
        .then(response => response.json())
        .then(token => {
          formData.append('token', token);
          return fetch(baseUrl + '/admin/ajax/update_user_data.php', {
            method: 'POST',
            body: formData
          });
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            trigger.setAttribute('data-state', toggledValue);
            if (toggledValue === 1) {
              trigger.className = 'block-btn px-2.5 py-1 rounded-md text-xs font-medium border transition-colors shadow-2xs bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100';
              trigger.innerHTML = '<i class="fas fa-unlock mr-1 text-[10px]"></i><span>Unblock</span>';
            } else {
              trigger.className = 'block-btn px-2.5 py-1 rounded-md text-xs font-medium border transition-colors shadow-2xs bg-slate-50 text-slate-700 border-slate-200 hover:bg-rose-50 hover:text-rose-700 hover:border-rose-200';
              trigger.innerHTML = '<i class="fas fa-ban mr-1 text-[10px]"></i><span>Block</span>';
            }
          } else {
            alert('Failed to update user block status.');
          }
        })
        .catch(err => {
          console.error(err);
          alert('Error updating user block status.');
        });
    }

    // Trigger password reset.
    function runreset(trigger) {
      if (!confirm('Send a password reset email to this user?')) return;
      const userRow = trigger.closest('tr');
      const userUuid = userRow.getAttribute('data-uid');

      let formData = new FormData();
      formData.append('userId', userUuid);
      formData.append('action', 'reset');

      fetch(baseUrl + '/AJAX/getdisposabletoken.php')
        .then(response => response.json())
        .then(token => {
          formData.append('token', token);
          return fetch(baseUrl + '/admin/ajax/update_user_data.php', {
            method: 'POST',
            body: formData
          });
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            trigger.disabled = true;
            trigger.className = 'reset-btn px-2.5 py-1 rounded-md text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 cursor-not-allowed shadow-2xs';
            trigger.innerHTML = '<i class="fas fa-check mr-1 text-[10px]"></i><span>Sent</span>';
          } else {
            alert('Server failed to process reset request.');
          }
        })
        .catch(err => {
          console.error(err);
          alert('Error dispatching password reset.');
        });
    }

    // Reveal save button on role change.
    document.querySelectorAll('.role-dropdown').forEach(dropdown => {
      dropdown.addEventListener('change', function() {
        const row = this.closest('tr');
        const saveBtn = row.querySelector('.save-role-btn');
        if (saveBtn) {
          saveBtn.classList.remove('hidden');
        }
      });
    });

    // Save promoted role.
    document.querySelectorAll('.save-role-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        const row = this.closest('tr');
        const dropdown = row.querySelector('.role-dropdown');
        const userUuid = row.getAttribute('data-uid');
        const selectedRole = dropdown.value.toLowerCase();

        let formData = new FormData();
        formData.append('userId', userUuid);
        formData.append('selectedRole', selectedRole);

        fetch(baseUrl + '/AJAX/getdisposabletoken.php')
          .then(response => response.json())
          .then(token => {
            formData.append('token', token);
            return fetch(baseUrl + '/admin/ajax/update_user_data.php', {
              method: 'POST',
              body: formData
            });
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              btn.textContent = 'Saved!';
              btn.classList.remove('bg-indigo-600', 'hover:bg-indigo-700');
              btn.classList.add('bg-emerald-600');
              setTimeout(() => {
                btn.classList.add('hidden');
                btn.textContent = 'Save';
                btn.classList.remove('bg-emerald-600');
                btn.classList.add('bg-indigo-600', 'hover:bg-indigo-700');
              }, 1500);
            } else {
              alert('Error updating user role.');
            }
          })
          .catch(err => {
            console.error(err);
            alert('Failed to save role.');
          });
      });
    });

    // Handle add user submission.
    const addForm = document.getElementById('addUserForm');
    if (addForm) {
      addForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const name = document.getElementById('name').value.trim();
        const email = document.getElementById('email').value.trim();
        const role = document.getElementById('role').value.toLowerCase();
        const statusBox = document.getElementById('addStatusMsg');
        const submitBtn = document.getElementById('submitAddBtn');

        if (!name || !email || !role) {
          alert('Please fill out all required fields.');
          return;
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i><span>Processing...</span>';

        let formData = new FormData();
        formData.append('name', name);
        formData.append('email', email);
        formData.append('role', role);

        fetch(baseUrl + '/AJAX/getdisposabletoken.php')
          .then(response => response.json())
          .then(token => {
            formData.append('token', token);
            return fetch(baseUrl + '/admin/ajax/add_new_user.php', {
              method: 'POST',
              body: formData
            });
          })
          .then(response => response.json())
          .then(data => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane mr-1"></i><span>Create and send invitation</span>';
            statusBox.classList.remove('hidden');
            if (data.success) {
              statusBox.className = 'p-3 rounded-lg text-xs bg-emerald-50 text-emerald-800 border border-emerald-200';
              statusBox.textContent = 'User account created and invitation dispatched successfully.';
              addForm.reset();
              setTimeout(() => {
                window.location.reload();
              }, 2000);
            } else {
              statusBox.className = 'p-3 rounded-lg text-xs bg-rose-50 text-rose-800 border border-rose-200';
              statusBox.textContent = data.msg || 'Failed to create user.';
            }
          })
          .catch(err => {
            console.error(err);
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane mr-1"></i><span>Create and send invitation</span>';
            statusBox.classList.remove('hidden');
            statusBox.className = 'p-3 rounded-lg text-xs bg-rose-50 text-rose-800 border border-rose-200';
            statusBox.textContent = 'A network error occurred while submitting the form.';
          });
      });
    }
  </script>
</body>
</html>