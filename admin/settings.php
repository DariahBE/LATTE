<?php
// Base imports.
if (!defined('ROOT_DIR')) {
    include_once(dirname(__DIR__) . "/config/config.inc.php");
}
include_once(ROOT_DIR . "/includes/client.inc.php");
include_once(ROOT_DIR . "/includes/user.inc.php");
include_once(ROOT_DIR . "/includes/navbar.inc.php");
include_once(ROOT_DIR . "/includes/csrf.inc.php");
include_once(ROOT_DIR . "/includes/config_model_manager.inc.php");

$user = new User($client);
if ($user->checkSession() === false) {
    $loginUrl = (defined('BASE_URL') ? BASE_URL : '') . "/user/login.php?redir=" . urlencode((defined('BASE_URL') ? BASE_URL : '') . "/admin/settings.php");
    header("Location: $loginUrl");
    exit();
}

// Only allow administrators here.
if (!$user->isAdmin()) {
    header("HTTP/1.0 403 Forbidden");
    die("Insufficient rights, forbidden access");
}

$adminMode = true;
$baseUrl = defined('BASE_URL') ? BASE_URL : '';
$tokenManager = new CsrfTokenManager();
$csrfToken = $tokenManager->getToken();

$settings = ConfigModelManager::getAllSettings();
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System settings - <?php echo htmlspecialchars(PROJECTNAME, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/CSS/stylePublic.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/CSS/overlaystyling.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="bg-slate-100 text-slate-800 w-full antialiased min-h-screen">
<?php
    $navbar = new Navbar($adminMode);
    echo $navbar->getNav();
    include_once('admin_tasks.php');
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-16">
    <!-- Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">System settings and environment configuration</h1>
            <p class="mt-1 text-sm text-slate-500">
                Manage backend database connections, LATTE NLP services, SMTP delivery, and global application policies.
            </p>
        </div>
    </div>

    <!-- Alert container -->
    <div id="alertBox" class="hidden mb-6 p-4 rounded-xl border text-xs font-medium transition-all"></div>

    <div class="space-y-6 max-w-4xl">
        <!-- Project branding & access -->
        <form onsubmit="saveSection(event, this)" data-section="project" class="bg-white rounded-2xl border border-slate-200/90 shadow-xs p-6">
            <input type="hidden" name="token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="save_section">
            <input type="hidden" name="section" value="project">
            <div class="flex items-center gap-3 mb-4">
                <span class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm font-semibold">
                    <i class="fas fa-tag"></i>
                </span>
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Project identification & access policies</h2>
                    <p class="text-xs text-slate-500">Public visibility, registration behavior, and branding</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1" for="project_name">Project name</label>
                    <input type="text" id="project_name" name="project_name" value="<?= htmlspecialchars($settings['project_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required
                           class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1" for="registration_policy">Registration policy</label>
                    <select id="registration_policy" name="registration_policy"
                            class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                        <option value="1" <?= ($settings['registration_policy'] ?? '') === '1' ? 'selected' : '' ?>>1 = Invite only</option>
                        <option value="2" <?= ($settings['registration_policy'] ?? '') === '2' ? 'selected' : '' ?>>2 = Open registration</option>
                        <option value="0" <?= ($settings['registration_policy'] ?? '') === '0' ? 'selected' : '' ?>>0 = Closed</option>
                    </select>
                </div>
                <div class="sm:col-span-3">
                    <label class="block text-xs font-semibold text-slate-700 mb-1" for="base_url">Application base URL (optional / leave empty for auto-detect)</label>
                    <input type="text" id="base_url" name="base_url" value="<?= htmlspecialchars($settings['base_url'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                </div>
                <div class="sm:col-span-3 flex flex-wrap gap-4 pt-1">
                    <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-700">
                        <input type="checkbox" name="texts_public" value="1" <?= !empty($settings['texts_public']) ? 'checked' : '' ?>
                               class="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4 border-slate-300">
                        <span>Texts are publicly accessible</span>
                    </label>
                    <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-700">
                        <input type="checkbox" name="entities_public" value="1" <?= !empty($settings['entities_public']) ? 'checked' : '' ?>
                               class="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4 border-slate-300">
                        <span>Entity stable pages are publicly accessible</span>
                    </label>
                </div>
            </div>

            <div class="mt-5 pt-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
                <div class="section-status hidden text-xs font-medium flex items-center gap-1.5" role="status" aria-live="polite"></div>
                <button type="submit"
                        class="ml-auto inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-60 transition-colors shadow-xs">
                    <i class="fas fa-save"></i>
                    <span>Save project settings</span>
                </button>
            </div>
        </form>

        <!-- Graph database -->
        <form onsubmit="saveSection(event, this)" data-section="database" class="bg-white rounded-2xl border border-slate-200/90 shadow-xs p-6">
            <input type="hidden" name="token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="save_section">
            <input type="hidden" name="section" value="database">
            <div class="flex items-center gap-3 mb-4">
                <span class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm font-semibold">
                    <i class="fas fa-database"></i>
                </span>
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Neo4j graph database connection</h2>
                    <p class="text-xs text-slate-500">Hostname, port, and authentication credentials for the graph database</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1" for="db_host">Hostname</label>
                    <input type="text" id="db_host" name="db_host" value="<?= htmlspecialchars($settings['db_host'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required
                           class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1" for="db_port">Bolt port</label>
                    <input type="number" id="db_port" name="db_port" value="<?= htmlspecialchars($settings['db_port'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required
                           class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1" for="db_name">Database name</label>
                    <input type="text" id="db_name" name="db_name" value="<?= htmlspecialchars($settings['db_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required
                           class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1" for="db_user">Username</label>
                    <input type="text" id="db_user" name="db_user" value="<?= htmlspecialchars($settings['db_user'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required
                           class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1" for="db_password">Password (leave blank to keep unchanged)</label>
                    <input type="password" id="db_password" name="db_password" placeholder="••••••••"
                           class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                </div>
            </div>

            <div class="mt-5 pt-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
                <div class="section-status hidden text-xs font-medium flex items-center gap-1.5" role="status" aria-live="polite"></div>
                <button type="submit"
                        class="ml-auto inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-60 transition-colors shadow-xs">
                    <i class="fas fa-save"></i>
                    <span>Save database settings</span>
                </button>
            </div>
        </form>

        <!-- LATTE NLP connector -->
        <form onsubmit="saveSection(event, this)" data-section="latte" class="bg-white rounded-2xl border border-slate-200/90 shadow-xs p-6">
            <input type="hidden" name="token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="save_section">
            <input type="hidden" name="section" value="latte">
            <div class="flex items-center gap-3 mb-4">
                <span class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm font-semibold">
                    <i class="fas fa-brain"></i>
                </span>
                <div>
                    <h2 class="text-sm font-bold text-slate-900">LATTE NLP connector</h2>
                    <p class="text-xs text-slate-500">Language identification and entity extraction service</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1" for="latte_connector">Connector endpoint</label>
                    <input type="text" id="latte_connector" name="latte_connector" value="<?= htmlspecialchars($settings['latte_connector'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required
                           class="w-full px-3 py-2 text-xs font-mono rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1" for="language_engine">Language model engine</label>
                    <select id="language_engine" name="language_engine"
                            class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                        <option value="langid" <?= ($settings['language_engine'] ?? '') === 'langid' ? 'selected' : '' ?>>langid</option>
                    </select>
                </div>
                <div class="sm:col-span-3 pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
                    <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-700">
                        <input type="checkbox" name="use_latte" value="1" <?= !empty($settings['use_latte']) ? 'checked' : '' ?>
                               class="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4 border-slate-300">
                        <span>Enable LATTE connector</span>
                    </label>
                    <a href="<?= (defined('BASE_URL') ? BASE_URL : '') ?>/admin/connector_queue.php" class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition">
                        <i class="fas fa-tasks text-[11px]"></i>
                        <span>LATTE background processing settings</span>
                        <i class="fas fa-arrow-right text-[10px]"></i>
                    </a>
                </div>
            </div>

            <div class="mt-5 pt-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
                <div class="section-status hidden text-xs font-medium flex items-center gap-1.5" role="status" aria-live="polite"></div>
                <button type="submit"
                        class="ml-auto inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-60 transition-colors shadow-xs">
                    <i class="fas fa-save"></i>
                    <span>Save LATTE settings</span>
                </button>
            </div>
        </form>

        <!-- SMTP & Email -->
        <form onsubmit="saveSection(event, this)" data-section="email" class="bg-white rounded-2xl border border-slate-200/90 shadow-xs p-6">
            <input type="hidden" name="token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="save_section">
            <input type="hidden" name="section" value="email">
            <div class="flex items-center gap-3 mb-4">
                <span class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm font-semibold">
                    <i class="fas fa-envelope"></i>
                </span>
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Email and SMTP settings</h2>
                    <p class="text-xs text-slate-500">Mail dispatch configuration for user onboarding and password resets</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1" for="mail_protocol">Protocol</label>
                    <select id="mail_protocol" name="mail_protocol"
                            class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                        <option value="SMTP" <?= ($settings['mail_protocol'] ?? '') === 'SMTP' ? 'selected' : '' ?>>SMTP</option>
                        <option value="" <?= ($settings['mail_protocol'] ?? '') === '' ? 'selected' : '' ?>>Disabled / Local</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1" for="origin_email">Sender address (origin email)</label>
                    <input type="email" id="origin_email" name="origin_email" value="<?= htmlspecialchars($settings['origin_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required
                           class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1" for="smtp_server">SMTP server host</label>
                    <input type="text" id="smtp_server" name="smtp_server" value="<?= htmlspecialchars($settings['smtp_server'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1" for="smtp_port">SMTP port</label>
                    <input type="number" id="smtp_port" name="smtp_port" value="<?= htmlspecialchars($settings['smtp_port'] ?? '25', ENT_QUOTES, 'UTF-8') ?>"
                           class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1" for="smtp_user">SMTP user (optional)</label>
                    <input type="text" id="smtp_user" name="smtp_user" value="<?= htmlspecialchars($settings['smtp_user'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1" for="smtp_password">SMTP password (leave blank to keep unchanged)</label>
                    <input type="password" id="smtp_password" name="smtp_password" placeholder="••••••••"
                           class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                </div>
                <div class="flex items-center pt-5">
                    <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-700">
                        <input type="checkbox" name="smtp_self_signed" value="1" <?= !empty($settings['smtp_self_signed']) ? 'checked' : '' ?>
                               class="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4 border-slate-300">
                        <span>Allow self-signed SSL certificates</span>
                    </label>
                </div>
            </div>

            <div class="mt-5 pt-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
                <div class="section-status hidden text-xs font-medium flex items-center gap-1.5" role="status" aria-live="polite"></div>
                <button type="submit"
                        class="ml-auto inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-60 transition-colors shadow-xs">
                    <i class="fas fa-save"></i>
                    <span>Save email settings</span>
                </button>
            </div>
        </form>

        <!-- Matomo analytics -->
        <form onsubmit="saveSection(event, this)" data-section="matomo" class="bg-white rounded-2xl border border-slate-200/90 shadow-xs p-6">
            <input type="hidden" name="token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="save_section">
            <input type="hidden" name="section" value="matomo">
            <div class="flex items-center gap-3 mb-4">
                <span class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm font-semibold">
                    <i class="fas fa-chart-line"></i>
                </span>
                <div>
                    <h2 class="text-sm font-bold text-slate-900">Matomo analytics</h2>
                    <p class="text-xs text-slate-500">Configure Matomo tracking, custom tracker paths, and privacy exclusions (admin pages are never tracked)</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-700 mb-1" for="matomo_url">Matomo base URL</label>
                    <input type="url" id="matomo_url" name="matomo_url" placeholder="https://analytics.example.org/matomo/"
                           value="<?= htmlspecialchars($settings['matomo_url'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           class="w-full px-3 py-2 text-xs font-mono rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1" for="matomo_site_id">Matomo site ID</label>
                    <input type="number" id="matomo_site_id" name="matomo_site_id" min="1" placeholder="1"
                           value="<?= htmlspecialchars($settings['matomo_site_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                </div>
                <div class="sm:col-span-3 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1" for="matomo_js_path">Tracker JS filename or path</label>
                        <input type="text" id="matomo_js_path" name="matomo_js_path" placeholder="matomo.js"
                               value="<?= htmlspecialchars($settings['matomo_js_path'] ?? 'matomo.js', ENT_QUOTES, 'UTF-8') ?>"
                               class="w-full px-3 py-2 text-xs font-mono rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                        <p class="mt-1 text-[11px] text-slate-500">Relative to Matomo base URL (e.g. matomo.js), root-relative (e.g. /analytics/matomo.js), or full URL.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1" for="matomo_php_path">Tracker PHP endpoint filename or path</label>
                        <input type="text" id="matomo_php_path" name="matomo_php_path" placeholder="matomo.php"
                               value="<?= htmlspecialchars($settings['matomo_php_path'] ?? 'matomo.php', ENT_QUOTES, 'UTF-8') ?>"
                               class="w-full px-3 py-2 text-xs font-mono rounded-lg border border-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                        <p class="mt-1 text-[11px] text-slate-500">Relative to Matomo base URL (e.g. matomo.php), root-relative (e.g. /analytics/matomo.php), or full URL.</p>
                    </div>
                </div>
                <div class="sm:col-span-3 pt-2 flex flex-wrap gap-4">
                    <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-700">
                        <input type="checkbox" name="matomo_enabled" value="1" <?= !empty($settings['matomo_enabled']) ? 'checked' : '' ?>
                               class="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4 border-slate-300">
                        <span>Enable Matomo tracking</span>
                    </label>
                    <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-700">
                        <input type="checkbox" name="matomo_exclude_logged_in" value="1" <?= !empty($settings['matomo_exclude_logged_in']) ? 'checked' : '' ?>
                               class="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4 border-slate-300">
                        <span>Exclude logged-in users from tracking</span>
                    </label>
                    <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-700">
                        <input type="checkbox" name="matomo_honor_dnt" value="1" <?= !empty($settings['matomo_honor_dnt']) ? 'checked' : '' ?>
                               class="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4 border-slate-300">
                        <span>Honor Do Not Track (DNT) requests</span>
                    </label>
                </div>
            </div>

            <div class="mt-5 pt-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
                <div class="section-status hidden text-xs font-medium flex items-center gap-1.5" role="status" aria-live="polite"></div>
                <button type="submit"
                        class="ml-auto inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-60 transition-colors shadow-xs">
                    <i class="fas fa-save"></i>
                    <span>Save Matomo settings</span>
                </button>
            </div>
        </form>
    </div>
</main>

<script>
    const AJAX_URL = '<?= $baseUrl ?>/admin/ajax/manage_settings.php';

    function showSectionStatus(form, message, isError = false) {
        const statusEl = form.querySelector('.section-status');
        if (!statusEl) return;

        statusEl.className = `section-status text-xs font-medium flex items-center gap-1.5 ${
            isError ? 'text-rose-700' : 'text-emerald-700'
        }`;
        statusEl.innerHTML = `<i class="fas ${isError ? 'fa-exclamation-circle' : 'fa-check-circle'}"></i><span></span>`;
        statusEl.querySelector('span').textContent = message;
    }

    async function saveSection(e, form) {
        e.preventDefault();
        const btn = form.querySelector('button[type="submit"]');
        if (!btn.dataset.originalHtml) {
            btn.dataset.originalHtml = btn.innerHTML;
        }
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Saving...</span>';

        const statusEl = form.querySelector('.section-status');
        if (statusEl) {
            statusEl.classList.add('hidden');
        }

        const formData = new FormData(form);

        try {
            const resp = await fetch(AJAX_URL, { method: 'POST', body: formData });
            const data = await resp.json();
            if (resp.ok && data.status === 'success') {
                showSectionStatus(form, data.message || 'Settings saved successfully.');
                form.querySelectorAll('input[type="password"]').forEach((el) => {
                    el.value = '';
                });
            } else {
                showSectionStatus(form, data.message || 'Failed to save settings.', true);
            }
        } catch (err) {
            showSectionStatus(form, 'Server request error.', true);
        } finally {
            btn.disabled = false;
            btn.innerHTML = btn.dataset.originalHtml;
        }
    }
</script>
</body>
</html>
