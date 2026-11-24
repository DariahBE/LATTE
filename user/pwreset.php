<?php
include_once(dirname(__DIR__) . '/config/config.inc.php');
include_once(ROOT_DIR . '/includes/client.inc.php'); 
include_once(ROOT_DIR . '/includes/user.inc.php');
include_once(ROOT_DIR . "/includes/mail.inc.php");
include_once(ROOT_DIR . '/includes/csrf.inc.php');

$baseUrl = defined('BASE_URL') ? BASE_URL : '';
$tokenManager = new CsrfTokenManager();
$csrfToken = $tokenManager->getToken();
$feedbackMessage = '';
$feedbackSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !$tokenManager->checkToken($_POST['csrf_token'])) {
        die('Invalid CSRF token');
    }
    if (
        isset($_POST['captchaSolution']) &&
        isset($_POST['email']) &&
        filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)
    ) {
        if (isset($_SESSION['captcha_token']) && strtolower($_SESSION['captcha_token']) === strtolower(trim($_POST['captchaSolution']))) {
            $user = new User($client);
            $r = $user->requestPasswordReset($_POST['email'], false);
            $feedbackSuccess = true;
            $feedbackMessage = 'If a matching account exists, a password reset link has been dispatched to your email address.';
        } else {
            $feedbackMessage = 'Invalid security captcha solution entered. Please try again.';
        }
    } else {
        $feedbackMessage = 'Please provide a valid email address and captcha solution.';
    }
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset password - <?php echo htmlspecialchars(PROJECTNAME, ENT_QUOTES, 'UTF-8'); ?></title>
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
        <h1 class="text-xl font-semibold text-slate-900">Reset your password</h1>
        <p class="text-xs text-slate-500 mt-1">Enter your account email to receive a recovery link.</p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-md p-6 sm:p-8">
        <?php if (!empty($feedbackMessage)): ?>
            <div class="mb-5 p-3 rounded-xl text-xs font-medium border <?php echo $feedbackSuccess ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-rose-50 text-rose-800 border-rose-200'; ?>" role="alert">
                <?php echo htmlspecialchars($feedbackMessage, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form action="#" method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

            <!-- Email field -->
            <div>
                <label for="email" class="block text-xs font-semibold text-slate-700 mb-1.5">Email address</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 text-xs">
                        <i class="fas fa-envelope"></i>
                    </span>
                    <input type="email" id="email" name="email" required
                           autocomplete="email"
                           placeholder="name@example.com"
                           class="w-full rounded-xl border border-slate-200 pl-9 pr-3 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all placeholder:text-slate-400">
                </div>
            </div>

            <!-- Captcha verification -->
            <div>
                <label for="captchaSolution" class="block text-xs font-semibold text-slate-700 mb-1.5">Security verification</label>
                <div class="flex items-center gap-3 mb-2">
                    <img src="<?php echo $baseUrl; ?>/captcha/image.php" alt="Security captcha" class="rounded-xl border border-slate-200 shadow-2xs h-10">
                    <span class="text-[11px] text-slate-500">Type the characters shown</span>
                </div>
                <input
                    type="text"
                    required
                    class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all placeholder:text-slate-400 uppercase tracking-wider"
                    placeholder="Enter captcha text"
                    name="captchaSolution"
                    id="captchaSolution"
                />
            </div>

            <!-- Submit button -->
            <div class="pt-2">
                <button type="submit"
                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-semibold rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                    <i class="fas fa-paper-plane"></i>
                    <span>Send reset link</span>
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
</body>
</html>