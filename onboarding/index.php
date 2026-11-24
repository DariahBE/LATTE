<?php
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(__DIR__));
}
require_once ROOT_DIR . '/includes/config_model_manager.inc.php';

$isInstalled = ConfigModelManager::isInstalled();

// Calculate base url for assets
$docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])) : '';
$appRoot = str_replace('\\', '/', realpath(ROOT_DIR));
if ($docRoot && $appRoot && strpos($appRoot, $docRoot) === 0) {
    $subPath = substr($appRoot, strlen($docRoot));
    $baseUrl = rtrim(str_replace('\\', '/', $subPath), '/');
} else {
    $baseUrl = '';
}

// Handle setup token submission
$tokenError = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_setup_token'])) {
    $submittedToken = trim($_POST['setup_token'] ?? '');
    if (ConfigModelManager::isSetupAuthorized($submittedToken)) {
        header("Location: index.php");
        exit();
    } else {
        $tokenError = 'Invalid setup token. Please check the token in storage/setup_secret.txt on the server.';
    }
}

$isAuthorized = ConfigModelManager::isSetupAuthorized();

// System environment checks
$extensions = [
    'pdo_sqlite' => 'PDO SQLite',
    'sqlite3' => 'SQLite 3',
    'curl' => 'cURL Client',
    'mbstring' => 'Multibyte String',
    'gd' => 'GD Graphics Library',
    'json' => 'JSON Parser',
    'session' => 'Session Handling',
];
$envResults = [];
$allEnvPassed = true;
foreach ($extensions as $ext => $label) {
    $ok = extension_loaded($ext);
    $envResults[] = ['label' => $label, 'ok' => $ok];
    if (!$ok) {
        $allEnvPassed = false;
    }
}
$storageDir = ROOT_DIR . '/storage';
$storageWritable = is_dir($storageDir) ? is_writable($storageDir) : is_writable(ROOT_DIR);
$envResults[] = ['label' => 'Storage directory write permissions', 'ok' => $storageWritable];
if (!$storageWritable) {
    $allEnvPassed = false;
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System onboarding wizard</title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/CSS/stylePublic.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/CSS/overlaystyling.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="bg-slate-100 text-slate-800 w-full antialiased min-h-screen py-10 px-4">
    <div class="max-w-4xl mx-auto">
        <!-- Logo and header -->
        <div class="text-center mb-8">
            <span class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-indigo-600 text-white text-2xl shadow-md mb-3">
                <i class="fas fa-rocket"></i>
            </span>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">System onboarding wizard</h1>
            <p class="mt-1 text-sm text-slate-500">Initial deployment setup and persistent configuration generator</p>
        </div>

        <?php if ($isInstalled): ?>
            <!-- Onboarding is closed card -->
            <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm p-8 text-center max-w-lg mx-auto">
                <div class="w-16 h-16 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-2xl mx-auto mb-4">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2 class="text-xl font-bold text-slate-900">Onboarding is already completed</h2>
                <p class="mt-2 text-sm text-slate-600 leading-relaxed">
                    The base configuration is currently active and an administrator account has already been registered. For security reasons, the onboarding wizard is disabled.
                </p>
                <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                    <a href="<?= $baseUrl ?>/user/login.php"
                       class="inline-flex items-center gap-2 px-5 py-2.5 text-xs font-semibold rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 transition-colors shadow-xs">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Log in to portal</span>
                    </a>
                    <a href="<?= $baseUrl ?>/admin/index.php"
                       class="inline-flex items-center gap-2 px-5 py-2.5 text-xs font-semibold rounded-xl bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 transition-colors shadow-xs">
                        <i class="fas fa-shield-alt"></i>
                        <span>Admin dashboard</span>
                    </a>
                </div>
            </div>
        <?php elseif (!$isAuthorized): ?>
            <!-- Setup secret token gate -->
            <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm p-8 max-w-md mx-auto">
                <div class="w-16 h-16 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-2xl mx-auto mb-4">
                    <i class="fas fa-key"></i>
                </div>
                <h2 class="text-lg font-bold text-slate-900 text-center">Administrator authorization required</h2>
                <p class="mt-2 text-xs text-slate-600 leading-relaxed text-center">
                    To prevent unauthorized deployment, initial setup requires proof of server ownership. Please enter the setup token from the server file:
                </p>
                <div class="my-3 p-2.5 rounded-lg bg-slate-50 border border-slate-200 font-mono text-[11px] text-slate-700 text-center select-all">
                    storage/setup_secret.txt
                </div>

                <?php if ($tokenError): ?>
                    <div class="mb-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs">
                        <?= htmlspecialchars($tokenError, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <form method="post" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1" for="setup_token">Setup token</label>
                        <input type="text" id="setup_token" name="setup_token" required placeholder="Enter 48-character token"
                               class="w-full px-3 py-2 text-xs font-mono rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                    </div>
                    <button type="submit" name="submit_setup_token" value="1"
                            class="w-full py-2.5 text-xs font-semibold rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 transition-colors shadow-xs">
                        Verify and launch wizard
                    </button>
                </form>
            </div>
        <?php else: ?>
            <!-- Onboarding form -->
            <form id="onboardingForm" onsubmit="submitOnboarding(event)" class="space-y-6">
                <!-- Alert banner -->
                <div id="statusAlert" class="hidden p-4 rounded-xl text-xs font-medium border transition-all"></div>

                <!-- Section 1: Environment readiness -->
                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm font-semibold">1</span>
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Environment and server prerequisites</h2>
                            <p class="text-xs text-slate-500">Verifying required PHP modules and filesystem permissions</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <?php foreach ($envResults as $env): ?>
                            <div class="flex items-center justify-between p-2.5 rounded-lg bg-slate-50 border border-slate-200/60">
                                <span class="text-slate-700 font-medium"><?= htmlspecialchars($env['label'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php if ($env['ok']): ?>
                                    <span class="inline-flex items-center gap-1 text-emerald-600 font-semibold text-[11px]">
                                        <i class="fas fa-check-circle"></i> Ready
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 text-rose-600 font-semibold text-[11px]">
                                        <i class="fas fa-times-circle"></i> Missing
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Section 2: Neo4j database connection -->
                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm font-semibold">2</span>
                            <div>
                                <h2 class="text-sm font-bold text-slate-900">Graph database connection (Neo4j)</h2>
                                <p class="text-xs text-slate-500">Connect to the Neo4j instance hosting your graph nodes and relations</p>
                            </div>
                        </div>
                        <button type="button" onclick="testDbConnection()" id="btnTestDb"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors">
                            <i class="fas fa-plug text-[11px]"></i>
                            <span>Test connection</span>
                        </button>
                    </div>

                    <div id="dbTestNotice" class="hidden mb-4 p-3 rounded-lg text-xs"></div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1" for="db_host">Hostname</label>
                            <input type="text" id="db_host" name="db_host" placeholder="e.g. localhost" required
                                   class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1" for="db_port">Bolt port</label>
                            <input type="number" id="db_port" name="db_port" placeholder="7700" required
                                   class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1" for="db_name">Database name</label>
                            <input type="text" id="db_name" name="db_name" placeholder="neo4j" required
                                   class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1" for="db_user">Username</label>
                            <input type="text" id="db_user" name="db_user" placeholder="neo4j" required
                                   class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 mb-1" for="db_password">Password</label>
                            <input type="password" id="db_password" name="db_password" placeholder="Database password"
                                   class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                        </div>
                    </div>
                </div>

                <!-- Section 3: LATTE NLP connector -->
                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm font-semibold">3</span>
                            <div>
                                <h2 class="text-sm font-bold text-slate-900">LATTE NLP connector</h2>
                                <p class="text-xs text-slate-500">Natural language entity extraction and language detection service</p>
                            </div>
                        </div>
                        <button type="button" onclick="testLatteConnection()" id="btnTestLatte"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors">
                            <i class="fas fa-network-wired text-[11px]"></i>
                            <span>Test endpoint</span>
                        </button>
                    </div>

                    <div id="latteTestNotice" class="hidden mb-4 p-3 rounded-lg text-xs"></div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 mb-1" for="latte_connector">Connector endpoint (host:port or URL)</label>
                            <input type="text" id="latte_connector" name="latte_connector" placeholder="e.g. localhost:8000"
                                   class="w-full px-3 py-2 text-xs font-mono rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1" for="language_engine">Language model engine</label>
                            <select id="language_engine" name="language_engine"
                                    class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                                <option value="langid" selected>langid (built-in)</option>
                            </select>
                        </div>
                        <div class="sm:col-span-3">
                            <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-700">
                                <input type="checkbox" name="use_latte" value="1"
                                       class="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4 border-slate-300">
                                <span>Enable LATTE connector integration</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Email / SMTP connection -->
                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm font-semibold">4</span>
                            <div>
                                <h2 class="text-sm font-bold text-slate-900">Email and SMTP notifications</h2>
                                <p class="text-xs text-slate-500">Dispatch user invitations and password reset emails</p>
                            </div>
                        </div>
                        <button type="button" onclick="testSmtpConnection()" id="btnTestSmtp"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors">
                            <i class="fas fa-paper-plane text-[11px]"></i>
                            <span>Test SMTP</span>
                        </button>
                    </div>

                    <div id="smtpTestNotice" class="hidden mb-4 p-3 rounded-lg text-xs"></div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1" for="mail_protocol">Mail protocol</label>
                            <select id="mail_protocol" name="mail_protocol"
                                    class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                                <option value="SMTP">SMTP</option>
                                <option value="" selected>Disabled / Local</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 mb-1" for="origin_email">Origin sender email</label>
                            <input type="email" id="origin_email" name="origin_email" placeholder="admin@example.org"
                                   class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 mb-1" for="smtp_server">SMTP server host</label>
                            <input type="text" id="smtp_server" name="smtp_server" placeholder="e.g. smtp.example.com"
                                   class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1" for="smtp_port">SMTP port</label>
                            <input type="number" id="smtp_port" name="smtp_port" placeholder="25" value="25"
                                   class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1" for="smtp_user">SMTP username (optional)</label>
                            <input type="text" id="smtp_user" name="smtp_user" placeholder="Username"
                                   class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1" for="smtp_password">SMTP password (optional)</label>
                            <input type="password" id="smtp_password" name="smtp_password" placeholder="Password"
                                   class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                        </div>
                        <div class="flex items-center pt-5">
                            <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-700">
                                <input type="checkbox" name="smtp_self_signed" value="1" checked
                                       class="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4 border-slate-300">
                                <span>Allow self-signed SSL certificates</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Section 5: Project & Visibility settings -->
                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm font-semibold">5</span>
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Project identification and public access</h2>
                            <p class="text-xs text-slate-500">Branding, access permissions, and registration policy</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 mb-1" for="project_name">Project name</label>
                            <input type="text" id="project_name" name="project_name" placeholder="e.g. My Research Project" required
                                   class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1" for="registration_policy">Registration policy</label>
                            <select id="registration_policy" name="registration_policy"
                                    class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                                <option value="1" selected>1 = Invite only</option>
                                <option value="2">2 = Open registration</option>
                                <option value="0">0 = Closed (admin creates only)</option>
                            </select>
                        </div>
                        <div class="sm:col-span-3">
                            <label class="block text-xs font-semibold text-slate-700 mb-1" for="base_url">Application base URL (optional / auto-detected)</label>
                            <input type="url" id="base_url" name="base_url" placeholder="http://example.org"
                                   class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                            <span class="text-[11px] text-slate-500">Leave blank to automatically detect host and subfolder.</span>
                        </div>
                        <div class="sm:col-span-3 flex flex-wrap gap-4 pt-1">
                            <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-700">
                                <input type="checkbox" name="texts_public" value="1" checked
                                       class="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4 border-slate-300">
                                <span>Texts publicly accessible</span>
                            </label>
                            <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-700">
                                <input type="checkbox" name="entities_public" value="1" checked
                                       class="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4 border-slate-300">
                                <span>Entity stable pages publicly accessible</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Section 6: Administrator user -->
                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm font-semibold">6</span>
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">Administrator account</h2>
                            <p class="text-xs text-slate-500">Primary administrative credentials for the system</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1" for="admin_username">Admin username</label>
                            <input type="text" id="admin_username" name="admin_username" placeholder="admin" required minlength="3"
                                   class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1" for="admin_email">Admin email address</label>
                            <input type="email" id="admin_email" name="admin_email" placeholder="admin@example.org" required
                                   class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1" for="admin_password">Password</label>
                            <input type="password" id="admin_password" name="admin_password" required minlength="6" placeholder="Minimum 6 characters"
                                   class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1" for="admin_password_repeat">Confirm password</label>
                            <input type="password" id="admin_password_repeat" name="admin_password_repeat" required minlength="6" placeholder="Repeat password"
                                   class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                        </div>
                    </div>
                </div>

                <!-- Submit button -->
                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="submit" id="btnSubmitOnboarding"
                            class="inline-flex items-center gap-2 px-6 py-3 text-sm font-semibold rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 transition-colors shadow-sm">
                        <i class="fas fa-check-circle"></i>
                        <span>Complete onboarding and deploy</span>
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>

<script>
    const AJAX_URL = '<?= $baseUrl ?>/onboarding/ajax_onboard.php';

    function showNotice(elemId, message, isError = false) {
        const el = document.getElementById(elemId);
        el.className = `mb-4 p-3 rounded-lg text-xs font-medium ${isError ? 'bg-rose-50 text-rose-800 border border-rose-200' : 'bg-emerald-50 text-emerald-800 border border-emerald-200'}`;
        el.innerHTML = `<i class="fas ${isError ? 'fa-exclamation-circle' : 'fa-check-circle'} mr-1.5"></i> ${message}`;
        el.classList.remove('hidden');
    }

    async function testDbConnection() {
        const btn = document.getElementById('btnTestDb');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin text-[11px]"></i> Testing...';

        const formData = new FormData();
        formData.append('action', 'test_db');
        formData.append('db_host', document.getElementById('db_host').value);
        formData.append('db_port', document.getElementById('db_port').value);
        formData.append('db_name', document.getElementById('db_name').value);
        formData.append('db_user', document.getElementById('db_user').value);
        formData.append('db_password', document.getElementById('db_password').value);

        try {
            const resp = await fetch(AJAX_URL, { method: 'POST', body: formData });
            const data = await resp.json();
            showNotice('dbTestNotice', data.message, data.status !== 'success');
        } catch (err) {
            showNotice('dbTestNotice', 'Connection test failed: server error.', true);
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-plug text-[11px]"></i> Test connection';
        }
    }

    async function testLatteConnection() {
        const btn = document.getElementById('btnTestLatte');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin text-[11px]"></i> Testing...';

        const formData = new FormData();
        formData.append('action', 'test_latte');
        formData.append('latte_connector', document.getElementById('latte_connector').value);

        try {
            const resp = await fetch(AJAX_URL, { method: 'POST', body: formData });
            const data = await resp.json();
            showNotice('latteTestNotice', data.message, data.status !== 'success');
        } catch (err) {
            showNotice('latteTestNotice', 'Endpoint test failed: server error.', true);
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-network-wired text-[11px]"></i> Test endpoint';
        }
    }

    async function testSmtpConnection() {
        const btn = document.getElementById('btnTestSmtp');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin text-[11px]"></i> Testing...';

        const formData = new FormData();
        formData.append('action', 'test_smtp');
        formData.append('smtp_server', document.getElementById('smtp_server').value);
        formData.append('smtp_port', document.getElementById('smtp_port').value);

        try {
            const resp = await fetch(AJAX_URL, { method: 'POST', body: formData });
            const data = await resp.json();
            showNotice('smtpTestNotice', data.message, data.status !== 'success');
        } catch (err) {
            showNotice('smtpTestNotice', 'SMTP test failed: server error.', true);
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane text-[11px]"></i> Test SMTP';
        }
    }

    async function submitOnboarding(e) {
        e.preventDefault();
        const p1 = document.getElementById('admin_password').value;
        const p2 = document.getElementById('admin_password_repeat').value;
        if (p1 !== p2) {
            alert('Passwords do not match.');
            return;
        }

        const btn = document.getElementById('btnSubmitOnboarding');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Initializing system...';

        const formData = new FormData(document.getElementById('onboardingForm'));
        formData.append('action', 'complete_onboarding');

        try {
            const resp = await fetch(AJAX_URL, { method: 'POST', body: formData });
            const data = await resp.json();
            if (data.status === 'success') {
                alert(data.message || 'Onboarding completed successfully.');
                window.location.href = data.redirect || '<?= $baseUrl ?>/user/login.php';
            } else {
                alert(data.message || 'Setup could not be completed.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check-circle"></i> Complete onboarding and deploy';
            }
        } catch (err) {
            alert('An unexpected server error occurred during onboarding.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-circle"></i> Complete onboarding and deploy';
        }
    }
</script>
</body>
</html>
