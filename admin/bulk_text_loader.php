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
    $loginUrl = (defined('BASE_URL') ? BASE_URL : '') . "/user/login.php?redir=" . urlencode((defined('BASE_URL') ? BASE_URL : '') . "/admin/bulk_text_loader.php");
    header("Location: $loginUrl");
    die("redir required"); 
  }

  // Only allow admins here.
  if (!$user->isAdmin()) {
    header("HTTP/1.0 403 Forbidden");
    die("Insufficient rights, forbidden access");
  }
  $adminMode = true;
  $tokenManager = new CsrfTokenManager();
  $csrfToken = $tokenManager->getToken();
  $baseUrl = defined('BASE_URL') ? BASE_URL : '';
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk text importer - <?php echo htmlspecialchars(PROJECTNAME, ENT_QUOTES, 'UTF-8'); ?></title>
    <script src="<?php echo $baseUrl; ?>/admin/JS/bulk_loader.js"></script>
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

  <script>
    const model = <?php echo json_encode(NODEMODEL[TEXNODE] ?? []); ?>;
    window.csrfToken = '<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>';
  </script>

  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold tracking-tight text-slate-900">Bulk text importer</h1>
      <p class="mt-1 text-sm text-slate-500">Upload structured CSV files with text content and optional metadata into <?php echo htmlspecialchars(PROJECTNAME, ENT_QUOTES, 'UTF-8'); ?>.</p>
    </div>

    <!-- Format selector tabs -->
    <div class="flex border-b border-slate-200 mb-6">
      <button id="tab_csv" class="py-2.5 px-4 font-semibold text-sm border-b-2 border-indigo-600 text-indigo-600 focus:outline-hidden">
        CSV text importer
      </button>
      <button id="tab_preannotated" class="py-2.5 px-4 font-semibold text-sm border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 focus:outline-hidden">
        Pre-annotated corpus (TEI-XML / CoNLL-U)
      </button>
    </div>

    <!-- Upload container card -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs p-6">
      <div id="drag_and_drop_container"></div>
      <div id="preannotated_container" class="hidden"></div>
      <div id="progress_container" class="mt-4"></div>
    </div>
  </main>

  <script>
    let csv_handler = new CSVHandler('drag_and_drop_container');
    csv_handler.setDatamodel(model); 
    let preanno_handler = new PreannotatedHandler('preannotated_container');

    const tabCsv = document.getElementById('tab_csv');
    const tabPreanno = document.getElementById('tab_preannotated');
    const csvDiv = document.getElementById('drag_and_drop_container');
    const preannoDiv = document.getElementById('preannotated_container');

    tabCsv.addEventListener('click', () => {
      tabCsv.className = 'py-2.5 px-4 font-semibold text-sm border-b-2 border-indigo-600 text-indigo-600 focus:outline-hidden';
      tabPreanno.className = 'py-2.5 px-4 font-semibold text-sm border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 focus:outline-hidden';
      csvDiv.classList.remove('hidden');
      preannoDiv.classList.add('hidden');
    });

    tabPreanno.addEventListener('click', () => {
      tabPreanno.className = 'py-2.5 px-4 font-semibold text-sm border-b-2 border-indigo-600 text-indigo-600 focus:outline-hidden';
      tabCsv.className = 'py-2.5 px-4 font-semibold text-sm border-b-2 border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 focus:outline-hidden';
      csvDiv.classList.add('hidden');
      preannoDiv.classList.remove('hidden');
    });
  </script>
  </body>
</html>