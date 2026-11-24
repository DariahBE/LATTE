<?php
  // Base imports.
  if (!defined('ROOT_DIR')) {
    include_once(dirname(__DIR__) . "/config/config.inc.php");
  }
  include_once(ROOT_DIR . "/includes/client.inc.php");
  include_once(ROOT_DIR . "/includes/user.inc.php");
  include_once(ROOT_DIR . "/includes/navbar.inc.php");
  include_once(ROOT_DIR . "/includes/integrity.inc.php");
  include_once(ROOT_DIR . "/includes/csrf.inc.php");

  $user = new User($client);
  if ($user->checkSession() === false) {
    $loginUrl = (defined('BASE_URL') ? BASE_URL : '') . "/user/login.php?redir=" . urlencode((defined('BASE_URL') ? BASE_URL : '') . "/admin/validate_db.php");
    header("Location: $loginUrl");
    die("redir required"); 
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
    <title>Database validation - <?php echo htmlspecialchars(PROJECTNAME, ENT_QUOTES, 'UTF-8'); ?></title>
    <script src="<?php echo $baseUrl; ?>/admin/JS/validation.js"></script>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/CSS/stylePublic.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/CSS/overlaystyling.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  </head>
  <body class="bg-slate-100 text-slate-800 w-full antialiased min-h-screen">
  <?php
    $navbar = new Navbar($adminMode); 
    echo $navbar->getNav();
    $integrity = new Integrity($client);
    include_once('admin_tasks.php');
  ?>

  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-2xl font-bold tracking-tight text-slate-900">Database validation and integrity</h1>
      <p class="mt-1 text-sm text-slate-500">Inspect graph database structures against the defined data model, repair schema deviations, and ensure identifier consistency.</p>
    </div>

    <!-- Health cards grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <?php
        $inconsistentNodes = $integrity->checkNodesNotMatchingModel();
        $hasInconsistent = !empty($inconsistentNodes);
      ?>
      <!-- Inconsistent nodes card -->
      <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs p-6 flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
            <div class="flex items-center gap-3">
              <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl <?php echo $hasInconsistent ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'; ?>">
                <i class="fas <?php echo $hasInconsistent ? 'fa-exclamation-circle' : 'fa-check'; ?> text-base"></i>
              </span>
              <div>
                <h2 class="text-base font-semibold text-slate-900">Inconsistent nodes</h2>
                <p class="text-xs text-slate-500">Nodes not conforming to the active data model</p>
              </div>
            </div>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold <?php echo $hasInconsistent ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'; ?>">
              <?php echo $hasInconsistent ? 'Action needed' : 'Healthy'; ?>
            </span>
          </div>

          <?php if ($hasInconsistent): ?>
            <p class="text-xs text-slate-600 mb-3">The following node types exist in the database but have no definition in the schema model:</p>
            <ul class="space-y-2">
              <?php foreach ($inconsistentNodes as $key => $value): ?>
                <li class="flex items-center justify-between p-3 rounded-lg bg-slate-50 border border-slate-200/60 text-xs">
                  <div>
                    <span class="font-semibold text-slate-800"><?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="ml-2 text-slate-500">(<?php echo htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); ?> records)</span>
                  </div>
                  <button type="button"
                          onclick="dropnodefromdb('<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>')"
                          class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-semibold bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 transition-colors shadow-2xs">
                    <i class="fas fa-trash-alt text-[10px]"></i>
                    <span>Drop</span>
                  </button>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <div class="py-8 text-center text-slate-500">
              <i class="fas fa-check-double text-emerald-500 text-3xl mb-2"></i>
              <p class="text-sm font-medium text-slate-700">All nodes match the defined schema.</p>
              <p class="text-xs text-slate-400 mt-1">No unmodeled labels or orphan classes were detected in the database.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <?php
        $nodesMissingUUID = $integrity->checkNodesWithoutUUID();
        $hasMissingUUID = $nodesMissingUUID > 0;
      ?>
      <!-- Missing UUID card -->
      <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs p-6 flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
            <div class="flex items-center gap-3">
              <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl <?php echo $hasMissingUUID ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'; ?>">
                <i class="fas <?php echo $hasMissingUUID ? 'fa-fingerprint' : 'fa-check'; ?> text-base"></i>
              </span>
              <div>
                <h2 class="text-base font-semibold text-slate-900">Nodes missing UUID</h2>
                <p class="text-xs text-slate-500">Records missing an authoritative unique identifier</p>
              </div>
            </div>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold <?php echo $hasMissingUUID ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'; ?>">
              <?php echo $hasMissingUUID ? 'Action needed' : 'Healthy'; ?>
            </span>
          </div>

          <?php if ($hasMissingUUID): ?>
            <div class="p-4 rounded-lg bg-amber-50/70 border border-amber-200/60 mb-4">
              <p class="text-xs text-amber-900">
                Found <span class="font-bold"><?php echo (int)$nodesMissingUUID; ?></span> node<?php echo $nodesMissingUUID === 1 ? '' : 's'; ?> without a valid UUID property. Missing UUIDs can prevent persistent link resolution.
              </p>
            </div>
            <button type="button"
                    onclick="patchUUID()"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-semibold bg-indigo-600 text-white hover:bg-indigo-700 transition-colors shadow-xs">
              <i class="fas fa-magic text-xs"></i>
              <span>Generate missing UUIDs</span>
            </button>
          <?php else: ?>
            <div class="py-8 text-center text-slate-500">
              <i class="fas fa-id-card text-emerald-500 text-3xl mb-2"></i>
              <p class="text-sm font-medium text-slate-700">All nodes have valid UUID identifiers.</p>
              <p class="text-xs text-slate-400 mt-1">Every graph node carries an authoritative unique property.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>
  </body>
</html>