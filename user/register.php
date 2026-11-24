<?php
include_once(dirname(__DIR__) . '/config/config.inc.php');
include_once(ROOT_DIR . '/includes/user.inc.php');
include_once(ROOT_DIR . '/includes/getnode.inc.php');
include_once(ROOT_DIR . '/includes/csrf.inc.php');

$baseUrl = defined('BASE_URL') ? BASE_URL : '';

if (REGISTRATIONPOLICY === 0) {
  header('Location: ' . $baseUrl . '/index.php');
  exit('Policy violation: no registrations allowed');
}

$graph = new Node($client);
$user = new User($client); 
$tokenManager = new CsrfTokenManager();
$csrf_name = 'registrationtoken';
$sessiontoken = $tokenManager->generateToken($csrf_name);

// Reject if policy 1 and parameters are missing.
if (!(isset($_GET['invitetoken']) && isset($_GET['mail'])) && REGISTRATIONPOLICY === 1) {
  header('Location: ' . $baseUrl . '/index.php');          
  exit('Policy violation: invalid token');
}

$usermail = null;
$username = '';
$isInviteFlow = false;

if (REGISTRATIONPOLICY === 1 || (REGISTRATIONPOLICY === 2 && isset($_GET['invitetoken']) && isset($_GET['mail']))) {
  if (isset($_GET['invitetoken']) && isset($_GET['mail'])) {
    $matchingTokens = $user->checkTokenRequest($_GET['mail'], $_GET['invitetoken']);
    if (count($matchingTokens) === 1) {
      // Valid user found with correct token and mail combination.
      $username = $matchingTokens[0]['username'] ?? '';
      $usermail = $matchingTokens[0]['mail'] ?? $_GET['mail'];
      $isInviteFlow = true;
    } else {
      header('Location: ' . $baseUrl . '/index.php');          
      die('Policy violation: invalid token');
    }
  } else { 
    header('Location: ' . $baseUrl . '/index.php');          
    die('Policy violation: invalid token');
  }
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign up - <?php echo htmlspecialchars(PROJECTNAME, ENT_QUOTES, 'UTF-8'); ?></title>
    <script src="<?php echo $baseUrl; ?>/JS/jquery-3.6.0.min.js"></script>
    <script src="<?php echo $baseUrl; ?>/JS/password_policy.js"></script>
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
      <div class="text-center mb-6">
        <a href="<?php echo $baseUrl; ?>/" class="inline-flex items-center gap-2 mb-3">
          <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-indigo-600 text-white shadow-sm font-bold text-lg">
            <?php echo htmlspecialchars(substr(PROJECTNAME, 0, 1), ENT_QUOTES, 'UTF-8'); ?>
          </span>
          <span class="text-xl font-bold tracking-tight text-slate-900"><?php echo htmlspecialchars(PROJECTNAME, ENT_QUOTES, 'UTF-8'); ?></span>
        </a>
        <h1 class="text-xl font-semibold text-slate-900">Create your account</h1>
        <p class="text-xs text-slate-500 mt-1">
          <?php echo $isInviteFlow ? 'Complete your invitation to activate your access.' : 'Fill in the registration form to get started.'; ?>
        </p>
      </div>

      <!-- Registration card -->
      <div id="signupsquare" class="bg-white rounded-2xl border border-slate-200/80 shadow-md p-6 sm:p-8">
        <!-- Status alert box -->
        <div id="status" class="hidden mb-4 p-3 rounded-xl text-xs font-medium border" role="alert"></div>

        <form id="signupForm" class="space-y-4" onsubmit="return false;">
          <!-- Email field -->
          <div>
            <label for="emailfield" class="block text-xs font-semibold text-slate-700 mb-1.5">Email address</label>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-xs">
                <i class="fas fa-envelope"></i>
              </span>
              <?php if ($isInviteFlow): ?>
                <input
                  type="email"
                  id="emailfield"
                  name="email"
                  value="<?php echo htmlspecialchars($usermail, ENT_QUOTES, 'UTF-8'); ?>"
                  disabled
                  class="w-full rounded-xl border border-slate-200 pl-9 pr-3 py-2.5 text-xs text-slate-500 bg-slate-50 cursor-not-allowed"
                />
              <?php else: ?>
                <input
                  type="email"
                  id="emailfield"
                  name="email"
                  required
                  placeholder="name@example.com"
                  class="w-full rounded-xl border border-slate-200 pl-9 pr-3 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all placeholder:text-slate-400"
                />
              <?php endif; ?>
            </div>
          </div>

          <!-- Full name field -->
          <div>
            <label for="fullnamefield" class="block text-xs font-semibold text-slate-700 mb-1.5">Full name</label>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-xs">
                <i class="fas fa-user"></i>
              </span>
              <?php if ($isInviteFlow && !empty($username)): ?>
                <input
                  type="text"
                  id="fullnamefield"
                  name="fullname"
                  value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>"
                  disabled
                  class="w-full rounded-xl border border-slate-200 pl-9 pr-3 py-2.5 text-xs text-slate-500 bg-slate-50 cursor-not-allowed"
                />
              <?php else: ?>
                <input
                  type="text"
                  id="fullnamefield"
                  name="fullname"
                  required
                  placeholder="Your full name"
                  class="w-full rounded-xl border border-slate-200 pl-9 pr-3 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all placeholder:text-slate-400"
                />
              <?php endif; ?>
            </div>
          </div>

          <!-- Password field -->
          <div>
            <label for="passwordfield" class="block text-xs font-semibold text-slate-700 mb-1.5">Password</label>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-xs">
                <i class="fas fa-lock"></i>
              </span>
              <input
                type="password"
                id="passwordfield"
                name="password"
                required
                autocomplete="new-password"
                placeholder="At least 8 characters"
                class="w-full rounded-xl border border-slate-200 pl-9 pr-3 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all placeholder:text-slate-400"
              />
            </div>
          </div>

          <!-- Confirm password field -->
          <div>
            <label for="confirmPasswordfield" class="block text-xs font-semibold text-slate-700 mb-1.5">Confirm password</label>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-xs">
                <i class="fas fa-check-circle"></i>
              </span>
              <input
                type="password"
                id="confirmPasswordfield"
                name="password_confirmation"
                required
                autocomplete="new-password"
                placeholder="Repeat your password"
                class="w-full rounded-xl border border-slate-200 pl-9 pr-3 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all placeholder:text-slate-400"
              />
            </div>
            <p class="text-[11px] text-slate-500 mt-1.5 leading-relaxed">
              Minimum 8 characters with at least two of: uppercase, lowercase, numbers, or symbols.
            </p>
          </div>

          <!-- Captcha verification -->
          <div>
            <label for="captchaSolution" class="block text-xs font-semibold text-slate-700 mb-1.5">Security verification</label>
            <div class="flex items-center gap-3 mb-2">
              <img src="<?php echo $baseUrl; ?>/captcha/image.php" alt="Security captcha" class="rounded-xl border border-slate-200 shadow-2xs h-10">
              <span class="text-[11px] text-slate-500">Type the characters shown in the image</span>
            </div>
            <input
              type="text"
              id="captchaSolution"
              name="captchaSolution"
              required
              placeholder="Enter captcha text"
              class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all placeholder:text-slate-400 uppercase tracking-wider"
            />
          </div>

          <input type="hidden" id="csrfToken" value="<?php echo htmlspecialchars($sessiontoken, ENT_QUOTES, 'UTF-8'); ?>">
          <?php if ($isInviteFlow): ?>
            <input type="hidden" id="invitecode" value="<?php echo htmlspecialchars($_GET['invitetoken'], ENT_QUOTES, 'UTF-8'); ?>">
          <?php endif; ?>

          <!-- Submit button -->
          <div class="pt-2">
            <button
              type="button"
              disabled
              id="signupbutton"
              class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-semibold rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500/30 disabled:bg-slate-300 disabled:cursor-not-allowed disabled:text-slate-500"
            >
              <i class="fas fa-user-plus"></i>
              <span>Create account</span>
            </button>
          </div>
        </form>

        <div class="mt-6 pt-5 border-t border-slate-100 text-center">
          <p class="text-xs text-slate-500">
            Already have an account?
            <a href="login.php" class="font-semibold text-indigo-600 hover:text-indigo-700 transition-colors">
              Sign in here
            </a>
          </p>
        </div>
      </div>
    </div>

    <script>
      // Setup password policy validator.
      document.addEventListener('DOMContentLoaded', function() {
        if (typeof setupPasswordValidation === 'function') {
          setupPasswordValidation('passwordfield', 'confirmPasswordfield', 'signupbutton');
        }
      });

      // Submit signup handler.
      const signupBtn = document.getElementById('signupbutton');
      if (signupBtn) {
        signupBtn.addEventListener('click', function() {
          const inviteInput = document.getElementById('invitecode');
          const inviteVal = inviteInput ? inviteInput.value : null;
          const statusBox = document.getElementById('status');

          signupBtn.disabled = true;
          signupBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i><span>Registering...</span>';

          const signupData = {
            fullname: document.getElementById('fullnamefield').value.trim(),
            email: document.getElementById('emailfield').value.trim(),
            password: document.getElementById('passwordfield').value,
            password_confirmation: document.getElementById('confirmPasswordfield').value,
            token: document.getElementById('csrfToken').value,
            captcha: document.getElementById('captchaSolution').value.trim(),
            invitetoken: inviteVal
          };

          $.ajax({
            type: 'POST',
            url: 'runsignup.php',
            data: signupData,
            dataType: 'json',
            success: function(data) {
              signupBtn.disabled = false;
              signupBtn.innerHTML = '<i class="fas fa-user-plus mr-1"></i><span>Create account</span>';

              if (data) {
                statusBox.classList.remove('hidden');
                if (data.status == 1) {
                  statusBox.className = 'mb-4 p-3 rounded-xl text-xs font-medium border bg-emerald-50 text-emerald-800 border-emerald-200';
                  statusBox.textContent = data.msg || 'Registration completed. Redirecting to login...';
                  $('#signupsquare').fadeOut(400, function() {
                    window.location.href = '<?php echo $baseUrl; ?>/user/login.php';
                  });
                } else {
                  statusBox.className = 'mb-4 p-3 rounded-xl text-xs font-medium border bg-rose-50 text-rose-800 border-rose-200';
                  statusBox.textContent = data.msg || 'Registration failed.';
                }
              } else {
                statusBox.classList.remove('hidden');
                statusBox.className = 'mb-4 p-3 rounded-xl text-xs font-medium border bg-rose-50 text-rose-800 border-rose-200';
                statusBox.textContent = 'Something went wrong.';
              }
            },
            error: function() {
              signupBtn.disabled = false;
              signupBtn.innerHTML = '<i class="fas fa-user-plus mr-1"></i><span>Create account</span>';
              statusBox.classList.remove('hidden');
              statusBox.className = 'mb-4 p-3 rounded-xl text-xs font-medium border bg-rose-50 text-rose-800 border-rose-200';
              statusBox.textContent = 'Network communication error. Please try again.';
            }
          });
        });
      }
    </script>
  </body>
</html>
