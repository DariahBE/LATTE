<?php
  // Base imports.
  $configFile = dirname(__DIR__) . "/config/config.inc.php";
  if (!file_exists($configFile)) {
    header("Location: ../onboarding/index.php");
    exit();
  }
  if (!defined('ROOT_DIR')) {
    include_once($configFile);
  }
  include_once(ROOT_DIR . "/includes/client.inc.php");
  include_once(ROOT_DIR . "/includes/user.inc.php");
  include_once(ROOT_DIR . "/includes/navbar.inc.php");

  $user = new User($client);
  if ($user->checkSession() === false) {
    $loginUrl = (defined('BASE_URL') ? BASE_URL : '') . "/user/login.php?redir=" . urlencode((defined('BASE_URL') ? BASE_URL : '') . "/admin/index.php");
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
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin dashboard - <?php echo htmlspecialchars(PROJECTNAME, ENT_QUOTES, 'UTF-8'); ?></title>
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
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
      <!-- Header section -->
      <div class="mb-8">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Administration dashboard</h1>
        <p class="mt-1 text-sm text-slate-500">System maintenance, user administration, and database health tools for <?php echo htmlspecialchars(PROJECTNAME, ENT_QUOTES, 'UTF-8'); ?>.</p>
      </div>

      <!-- User alignment health status -->
      <?php
        $userAlignment = $user->checkAlignment(); 
        $hasAlignmentIssue = boolval(count($userAlignment));
      ?>
      <div class="mb-8 rounded-xl border p-5 shadow-xs transition-all <?php echo $hasAlignmentIssue ? 'bg-amber-50/80 border-amber-200 text-amber-900' : 'bg-white border-slate-200/80'; ?>">
        <div class="flex items-start gap-4">
          <div class="flex-shrink-0 mt-0.5">
            <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl <?php echo $hasAlignmentIssue ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'; ?>">
              <i class="fas <?php echo $hasAlignmentIssue ? 'fa-exclamation-triangle' : 'fa-check-circle'; ?> text-lg"></i>
            </span>
          </div>
          <div class="flex-1">
            <div class="flex flex-wrap items-center justify-between gap-2">
              <h2 class="text-base font-semibold text-slate-900">User alignment check</h2>
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold <?php echo $hasAlignmentIssue ? 'bg-amber-200 text-amber-800' : 'bg-emerald-100 text-emerald-800'; ?>">
                <?php echo $hasAlignmentIssue ? 'Action required' : 'Healthy'; ?>
              </span>
            </div>
            <p class="mt-1 text-sm text-slate-600">
              <?php if ($hasAlignmentIssue): ?>
                One or more users in the SQLite user database do not have corresponding Neo4j user nodes.
              <?php else: ?>
                All SQLite user accounts are correctly synchronized with Neo4j graph nodes.
              <?php endif; ?>
            </p>
            <?php if ($hasAlignmentIssue): ?>
              <div class="mt-3">
                <a href="<?php echo $baseUrl; ?>/admin/usermanagement/user_align.php"
                   class="inline-flex items-center gap-2 px-3.5 py-1.5 text-xs font-semibold rounded-lg bg-amber-600 text-white hover:bg-amber-700 transition-colors shadow-xs">
                  <i class="fas fa-sync-alt"></i>
                  <span>Synchronize users now</span>
                </a>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Quick action cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        <!-- User management -->
        <a href="<?php echo $baseUrl; ?>/admin/usermanagement/index.php"
           class="group block p-6 bg-white rounded-xl border border-slate-200/80 shadow-xs hover:shadow-md hover:border-indigo-300 transition-all">
          <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl mb-4 group-hover:scale-105 transition-transform">
            <i class="fas fa-users-cog"></i>
          </div>
          <h2 class="text-base font-semibold text-slate-900 group-hover:text-indigo-600 transition-colors">User management</h2>
          <p class="mt-1.5 text-xs text-slate-500 line-clamp-2">Manage user accounts, assign roles, block suspended users, and invite members.</p>
          <div class="mt-4 flex items-center text-xs font-semibold text-indigo-600 group-hover:translate-x-1 transition-transform">
            <span>Open management</span>
            <i class="fas fa-arrow-right ml-1.5 text-[10px]"></i>
          </div>
        </a>

        <!-- Entity management -->
        <a href="<?php echo $baseUrl; ?>/admin/entities.php"
           class="group block p-6 bg-white rounded-xl border border-slate-200/80 shadow-xs hover:shadow-md hover:border-indigo-300 transition-all">
          <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl mb-4 group-hover:scale-105 transition-transform">
            <i class="fas fa-cubes"></i>
          </div>
          <h2 class="text-base font-semibold text-slate-900 group-hover:text-indigo-600 transition-colors">Entity management</h2>
          <p class="mt-1.5 text-xs text-slate-500 line-clamp-2">Create and update entity types, property models, display labels, and search settings.</p>
          <div class="mt-4 flex items-center text-xs font-semibold text-indigo-600 group-hover:translate-x-1 transition-transform">
            <span>Configure entities</span>
            <i class="fas fa-arrow-right ml-1.5 text-[10px]"></i>
          </div>
        </a>

        <!-- System settings -->
        <a href="<?php echo $baseUrl; ?>/admin/settings.php"
           class="group block p-6 bg-white rounded-xl border border-slate-200/80 shadow-xs hover:shadow-md hover:border-indigo-300 transition-all">
          <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl mb-4 group-hover:scale-105 transition-transform">
            <i class="fas fa-sliders-h"></i>
          </div>
          <h2 class="text-base font-semibold text-slate-900 group-hover:text-indigo-600 transition-colors">System settings</h2>
          <p class="mt-1.5 text-xs text-slate-500 line-clamp-2">Configure graph database connections, LATTE NLP connectors, SMTP delivery, and project branding.</p>
          <div class="mt-4 flex items-center text-xs font-semibold text-indigo-600 group-hover:translate-x-1 transition-transform">
            <span>Manage settings</span>
            <i class="fas fa-arrow-right ml-1.5 text-[10px]"></i>
          </div>
        </a>

        <!-- Database validation -->
        <a href="<?php echo $baseUrl; ?>/admin/validate_db.php"
           class="group block p-6 bg-white rounded-xl border border-slate-200/80 shadow-xs hover:shadow-md hover:border-emerald-300 transition-all">
          <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl mb-4 group-hover:scale-105 transition-transform">
            <i class="fas fa-heartbeat"></i>
          </div>
          <h2 class="text-base font-semibold text-slate-900 group-hover:text-emerald-600 transition-colors">Database validation</h2>
          <p class="mt-1.5 text-xs text-slate-500 line-clamp-2">Verify graph schema integrity, locate missing identifiers, and fix inconsistencies.</p>
          <div class="mt-4 flex items-center text-xs font-semibold text-emerald-600 group-hover:translate-x-1 transition-transform">
            <span>Check integrity</span>
            <i class="fas fa-arrow-right ml-1.5 text-[10px]"></i>
          </div>
        </a>

        <!-- Index management -->
        <a href="<?php echo $baseUrl; ?>/admin/mod_indexes.php"
           class="group block p-6 bg-white rounded-xl border border-slate-200/80 shadow-xs hover:shadow-md hover:border-amber-300 transition-all">
          <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl mb-4 group-hover:scale-105 transition-transform">
            <i class="fas fa-bolt"></i>
          </div>
          <h2 class="text-base font-semibold text-slate-900 group-hover:text-amber-600 transition-colors">Index management</h2>
          <p class="mt-1.5 text-xs text-slate-500 line-clamp-2">Configure graph database property indexes to optimize query speed and search operations.</p>
          <div class="mt-4 flex items-center text-xs font-semibold text-amber-600 group-hover:translate-x-1 transition-transform">
            <span>Manage indexes</span>
            <i class="fas fa-arrow-right ml-1.5 text-[10px]"></i>
          </div>
        </a>

        <!-- Bulk text importer -->
        <a href="<?php echo $baseUrl; ?>/admin/bulk_text_loader.php"
           class="group block p-6 bg-white rounded-xl border border-slate-200/80 shadow-xs hover:shadow-md hover:border-sky-300 transition-all">
          <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-xl mb-4 group-hover:scale-105 transition-transform">
            <i class="fas fa-file-import"></i>
          </div>
          <h2 class="text-base font-semibold text-slate-900 group-hover:text-sky-600 transition-colors">Bulk text importer</h2>
          <p class="mt-1.5 text-xs text-slate-500 line-clamp-2">Upload and batch-process structured CSV documents and metadata into the database.</p>
          <div class="mt-4 flex items-center text-xs font-semibold text-sky-600 group-hover:translate-x-1 transition-transform">
            <span>Import texts</span>
            <i class="fas fa-arrow-right ml-1.5 text-[10px]"></i>
          </div>
        </a>
      </div>
    </main>
  </body>
</html>
