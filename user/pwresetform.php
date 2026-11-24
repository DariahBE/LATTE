<?php
if (!defined('ROOT_DIR')) {
  include_once(dirname(__DIR__) . '/config/config.inc.php');
}
include_once(ROOT_DIR . '/includes/client.inc.php'); 
include_once(ROOT_DIR . '/includes/user.inc.php');
include_once(ROOT_DIR . "/includes/mail.inc.php");
include_once(ROOT_DIR . "/includes/csrf.inc.php");

$baseUrl = defined('BASE_URL') ? BASE_URL : '';
$mail = $_GET['mail'] ?? '';
$token = $_GET['token'] ?? ''; 
$uuid = $_GET['uid'] ?? ''; 

$user = new User($client); 
$precheck_result = $user->prereset_checks($mail, $uuid, $token); 
if ($precheck_result !== 1) {
    die("Error: The password reset link is invalid or has expired."); 
}

$tokenManager = new CsrfTokenManager();
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedCsrf = $_POST['csrf_token'] ?? '';
    if (!$tokenManager->checkToken($submittedCsrf)) {
        die("Error: Invalid session token. Please reload the page.");
    }
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    if (!($user->passwordPolicyCheck($password))) {
        $errorMessage = "Password does not meet requirements. A minimum of 8 characters is needed and at least two of the following must be true: uppercase, lowercase, special symbols, numbers.";
    } elseif (empty($password) || empty($confirmPassword)) {
        $errorMessage = "Password fields cannot be empty.";
    } elseif ($password !== $confirmPassword) {
        $errorMessage = "Passwords do not match.";
    } else {
        $update_result = $user->resetPassword($uuid, $password);
        if ($update_result === true) {
            $tokenManager->revokeToken();
            header("Location: " . $baseUrl . "/user/login.php");
            exit;
        } else {
            $errorMessage = (string)$update_result;
        }
    }
}
$resetCsrfToken = $tokenManager->getToken();
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set new password - <?php echo htmlspecialchars(PROJECTNAME, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/CSS/stylePublic.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/CSS/overlaystyling.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="<?php echo $baseUrl; ?>/JS/password_policy.js"></script>
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
        <h1 class="text-xl font-semibold text-slate-900">Set a new password</h1>
        <p class="text-xs text-slate-500 mt-1">Choose a secure password for your account.</p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-md p-6 sm:p-8">
        <?php if (!empty($errorMessage)): ?>
            <div class="mb-5 p-3 rounded-xl text-xs font-medium border bg-rose-50 text-rose-800 border-rose-200" role="alert">
                <?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($resetCsrfToken, ENT_QUOTES, 'UTF-8'); ?>">

            <!-- New password -->
            <div>
                <label for="password" class="block text-xs font-semibold text-slate-700 mb-1.5">New password</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-xs">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" id="password" name="password" required
                           autocomplete="new-password"
                           placeholder="At least 8 characters"
                           class="w-full rounded-xl border border-slate-200 pl-9 pr-3 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all placeholder:text-slate-400">
                </div>
            </div>

            <!-- Confirm password -->
            <div>
                <label for="confirmPassword" class="block text-xs font-semibold text-slate-700 mb-1.5">Confirm new password</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-xs">
                        <i class="fas fa-check-circle"></i>
                    </span>
                    <input type="password" id="confirmPassword" name="confirmPassword" required
                           autocomplete="new-password"
                           placeholder="Repeat your password"
                           class="w-full rounded-xl border border-slate-200 pl-9 pr-3 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all placeholder:text-slate-400">
                </div>
                <p class="text-[11px] text-slate-500 mt-1.5 leading-relaxed">
                    Password must be at least 8 characters and contain at least two of: uppercase, lowercase, numbers, or symbols.
                </p>
            </div>

            <!-- Submit button -->
            <div class="pt-2">
                <button id="submitbutton" disabled type="submit"
                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-semibold rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500/30 disabled:bg-slate-300 disabled:cursor-not-allowed disabled:text-slate-500">
                    <i class="fas fa-key"></i>
                    <span>Update password</span>
                </button>
            </div>
        </form>

        <div class="mt-6 pt-5 border-t border-slate-100 text-center">
            <a href="login.php" class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-700 transition-colors">
                <i class="fas fa-arrow-left text-[10px]"></i>
                <span>Return to sign in</span>
            </a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    setupPasswordValidation('password', 'confirmPassword', 'submitbutton');
});
</script>
</body>
</html>