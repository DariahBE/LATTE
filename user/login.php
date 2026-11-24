<?php
  if (!defined('ROOT_DIR')) {
    include_once(dirname(__DIR__) . '/config/config.inc.php');
  }
  include_once(ROOT_DIR . '/includes/user.inc.php');
  include_once(ROOT_DIR . '/includes/csrf.inc.php');
  $user = new User(false);
  $validRedir = null;
  if (isset($_GET['redir'])) {
    $redir = $_GET['redir']; 
    if (is_string($redir) && strpos($redir, '/') === 0 && strpos($redir, '//') !== 0 && !preg_match('#^[a-z]+://#i', $redir)) {
      $path = filter_var($redir, FILTER_SANITIZE_URL);
      $validRedir = htmlspecialchars($path, ENT_QUOTES, 'UTF-8');
    }
  }
  if ($validRedir !== null) {
    $user->checkForSession($validRedir); 
  } else {
    $user->checkForSession(); 
  }
  $user = null;
  $tokenManager = new CsrfTokenManager();
  $loginToken = $tokenManager->getToken();
  $baseUrl = defined('BASE_URL') ? BASE_URL : '';
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in - <?php echo htmlspecialchars(PROJECTNAME, ENT_QUOTES, 'UTF-8'); ?></title>
    <script src="<?php echo $baseUrl; ?>/JS/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/CSS/stylePublic.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/CSS/overlaystyling.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <?php
      include_once(ROOT_DIR . '/includes/matomo.inc.php');
      echo renderMatomoTracking();
    ?>
  </head>
  <body class="bg-slate-100 text-slate-800 antialiased min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <!-- Brand header -->
      <div class="text-center mb-8">
        <a href="<?php echo $baseUrl; ?>/" class="inline-flex items-center gap-2 mb-3">
          <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-indigo-600 text-white shadow-sm font-bold text-lg">
            <?php echo htmlspecialchars(substr(PROJECTNAME, 0, 1), ENT_QUOTES, 'UTF-8'); ?>
          </span>
          <span class="text-xl font-bold tracking-tight text-slate-900"><?php echo htmlspecialchars(PROJECTNAME, ENT_QUOTES, 'UTF-8'); ?></span>
        </a>
        <h1 class="text-xl font-semibold text-slate-900">Welcome back</h1>
        <p class="text-xs text-slate-500 mt-1">Please enter your credentials to access your account.</p>
      </div>

      <!-- Login card -->
      <div id="loginsquare" class="bg-white rounded-2xl border border-slate-200/80 shadow-md p-6 sm:p-8">
        <!-- Status alert box -->
        <div id="status" class="hidden mb-4 p-3 rounded-xl text-xs font-medium border" role="alert"></div>

        <form id="loginForm" class="space-y-4" onsubmit="return false;">
          <!-- Email input -->
          <div>
            <label for="mailfield" class="block text-xs font-semibold text-slate-700 mb-1.5">Email address</label>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-xs">
                <i class="fas fa-envelope"></i>
              </span>
              <input
                type="email"
                id="mailfield"
                name="mail"
                required
                autocomplete="email"
                placeholder="name@example.com"
                class="w-full rounded-xl border border-slate-200 pl-9 pr-3 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all placeholder:text-slate-400"
              />
            </div>
          </div>

          <!-- Password input -->
          <div>
            <div class="flex items-center justify-between mb-1.5">
              <label for="pwfield" class="block text-xs font-semibold text-slate-700">Password</label>
              <a href="pwreset.php" class="text-xs font-medium text-indigo-600 hover:text-indigo-700 transition-colors">
                Forgot password?
              </a>
            </div>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-xs">
                <i class="fas fa-lock"></i>
              </span>
              <input
                type="password"
                id="pwfield"
                name="password"
                required
                autocomplete="current-password"
                placeholder="••••••••"
                class="w-full rounded-xl border border-slate-200 pl-9 pr-10 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all placeholder:text-slate-400"
              />
              <button
                type="button"
                id="togglePwBtn"
                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 transition-colors text-xs"
                title="Toggle password visibility"
              >
                <i class="fas fa-eye" id="togglePwIcon"></i>
              </button>
            </div>
          </div>

          <!-- Submit button -->
          <div class="pt-2">
            <button
              type="button"
              id="loginbutton"
              class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-semibold rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
            >
              <i class="fas fa-sign-in-alt"></i>
              <span>Sign in</span>
            </button>
          </div>
        </form>

        <?php if (REGISTRATIONPOLICY === 2): ?>
          <div class="mt-6 pt-5 border-t border-slate-100 text-center">
            <p class="text-xs text-slate-500">
              Don't have an account yet?
              <a href="register.php" class="font-semibold text-indigo-600 hover:text-indigo-700 transition-colors">
                Create one now
              </a>
            </p>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <script>
      // Password visibility toggle.
      const togglePwBtn = document.getElementById('togglePwBtn');
      const pwField = document.getElementById('pwfield');
      const togglePwIcon = document.getElementById('togglePwIcon');
      if (togglePwBtn && pwField && togglePwIcon) {
        togglePwBtn.addEventListener('click', function() {
          const isPassword = pwField.type === 'password';
          pwField.type = isPassword ? 'text' : 'password';
          togglePwIcon.className = isPassword ? 'fas fa-eye-slash' : 'fas fa-eye';
        });
      }

      // Execute login request.
      function run_login() {
        const mailValue = document.getElementById('mailfield').value.trim();
        const pwValue = document.getElementById('pwfield').value;
        const statusBox = document.getElementById('status');
        const loginBtn = document.getElementById('loginbutton');

        if (!mailValue || !pwValue) {
          statusBox.className = 'mb-4 p-3 rounded-xl text-xs font-medium border bg-amber-50 text-amber-800 border-amber-200';
          statusBox.textContent = 'Please enter both your email address and password.';
          statusBox.classList.remove('hidden');
          return;
        }

        loginBtn.disabled = true;
        loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i><span>Authenticating...</span>';

        const logindata = {
          mail: mailValue,
          password: pwValue,
          csrf_token: <?php echo json_encode($loginToken); ?>
        };

        $.ajax({
          type: 'POST',
          url: 'runlogin.php',
          data: logindata,
          dataType: 'json',
          success: function(data) {
            loginBtn.disabled = false;
            loginBtn.innerHTML = '<i class="fas fa-sign-in-alt mr-1"></i><span>Sign in</span>';

            if (data) {
              statusBox.classList.remove('hidden');
              if (data.status == 1) {
                statusBox.className = 'mb-4 p-3 rounded-xl text-xs font-medium border bg-emerald-50 text-emerald-800 border-emerald-200';
                statusBox.textContent = data.msg || 'Sign in successful. Redirecting...';
                $('#loginsquare').fadeOut(300, function() {
                  window.location.reload();
                });
              } else {
                statusBox.className = 'mb-4 p-3 rounded-xl text-xs font-medium border bg-rose-50 text-rose-800 border-rose-200';
                statusBox.textContent = data.msg || 'Invalid email or password.';
              }
            } else {
              statusBox.classList.remove('hidden');
              statusBox.className = 'mb-4 p-3 rounded-xl text-xs font-medium border bg-rose-50 text-rose-800 border-rose-200';
              statusBox.textContent = 'An unexpected response was received from the server.';
            }
          },
          error: function() {
            loginBtn.disabled = false;
            loginBtn.innerHTML = '<i class="fas fa-sign-in-alt mr-1"></i><span>Sign in</span>';
            statusBox.classList.remove('hidden');
            statusBox.className = 'mb-4 p-3 rounded-xl text-xs font-medium border bg-rose-50 text-rose-800 border-rose-200';
            statusBox.textContent = 'Network communication error. Please try again.';
          }
        });
      }

      document.getElementById('loginbutton').addEventListener('click', run_login);
      document.getElementById('pwfield').addEventListener('keypress', function(event) {
        if (event.key === 'Enter') run_login();
      });
      document.getElementById('mailfield').addEventListener('keypress', function(event) {
        if (event.key === 'Enter') run_login();
      });
    </script>
  </body>
</html>
