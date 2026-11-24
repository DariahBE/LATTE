<?php
$configFile = __DIR__ . '/config/config.inc.php';
if (!file_exists($configFile)) {
    require_once __DIR__ . '/includes/config_model_manager.inc.php';
    ConfigModelManager::renderHoldingPage();
}
if (!defined('ROOT_DIR')) {
  include_once($configFile);
}
include_once(ROOT_DIR.'/includes/navbar.inc.php');
include_once(ROOT_DIR.'/includes/user.inc.php');
include_once(ROOT_DIR.'/includes/client.inc.php');

$user = new User($client);
$adminMode = $user->isAdmin(); 
$navbar = new Navbar($adminMode); 
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(PROJECTNAME, ENT_QUOTES, 'UTF-8');?></title>
    <link rel="stylesheet" href="/CSS/stylePublic.css">
    <script type="text/javascript" src="/JS/homepage.js"></script>
    <?php
      include_once(ROOT_DIR . '/includes/matomo.inc.php');
      echo renderMatomoTracking();
    ?>
  </head>
  <body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col">
    <div>
      <?php echo $navbar->getNav(); ?>
    </div>

    <main class="flex-grow">
      <!-- Hero section -->
      <section class="max-w-6xl mx-auto px-4 py-16 sm:py-24 text-center">
        <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-200 mb-6">
          Powered by CLARIAH-VL
        </div>
        <h1 class="text-4xl sm:text-5xl font-extrabold text-slate-900 tracking-tight mb-4">
          <?php echo htmlspecialchars(PROJECTNAME, ENT_QUOTES, 'UTF-8'); ?>
        </h1>
        <p class="text-lg sm:text-xl text-slate-600 max-w-2xl mx-auto mb-8">
          A graph based research platform for textual annotation, named entity linking, and knowledge graph exploration.
        </p>

        <div class="flex flex-wrap justify-center gap-4 mb-12">
          <a href="/text/1" class="px-6 py-3 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-medium shadow-sm transition-colors">
            Explore texts
          </a>
          <a href="/search.php" class="px-6 py-3 rounded-lg bg-white hover:bg-slate-50 text-slate-700 font-medium border border-slate-300 shadow-sm transition-colors">
            Search entities
          </a>
          <a href="#statistics" class="px-6 py-3 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium transition-colors">
            View statistics
          </a>
        </div>

        <div class="flex justify-center">
          <img class="h-16 sm:h-20 object-contain opacity-80 hover:opacity-100 transition-opacity" src="images/clariah_vl_logo.png" alt="CLARIAH-VL logo" />
        </div>
      </section>

      <!-- Statistics section -->
      <section id="statistics" class="bg-white border-y border-slate-200 py-16">
        <div class="max-w-6xl mx-auto px-4">
          <div class="text-center mb-12">
            <h2 class="text-2xl sm:text-3xl font-bold text-slate-900">Database statistics</h2>
            <p class="text-slate-500 mt-2">Real-time metrics from the underlying knowledge graph.</p>
          </div>

          <div class="mb-10">
            <h3 class="text-lg font-semibold text-slate-700 mb-4 flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-indigo-500 inline-block"></span> Nodes
            </h3>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4" id="nodesCounter"></div>
          </div>

          <div>
            <h3 class="text-lg font-semibold text-slate-700 mb-4 flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block"></span> Relationships
            </h3>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4" id="edgesCounter"></div>
          </div>
        </div>
      </section>

      <!-- Workflow breakdown -->
      <section class="max-w-6xl mx-auto px-4 py-16">
        <div class="text-center mb-12">
          <h2 class="text-2xl sm:text-3xl font-bold text-slate-900">Annotation workflow</h2>
          <p class="text-slate-500 mt-2">Connecting historical and literary texts with structured knowledge bases.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
          <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
            <div class="w-10 h-10 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-lg mb-4">1</div>
            <h3 class="text-lg font-semibold text-slate-900 mb-2">Text selection</h3>
            <p class="text-sm text-slate-600 leading-relaxed">
              Select any segment in primary source texts with exact multi-byte and compound diacritic preservation.
            </p>
          </div>

          <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
            <div class="w-10 h-10 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-lg mb-4">2</div>
            <h3 class="text-lg font-semibold text-slate-900 mb-2">Entity linking</h3>
            <p class="text-sm text-slate-600 leading-relaxed">
              Link mentions directly to canonical graph entities, Wikidata authority records, or create new items.
            </p>
          </div>

          <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
            <div class="w-10 h-10 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-lg mb-4">3</div>
            <h3 class="text-lg font-semibold text-slate-900 mb-2">Graph exploration</h3>
            <p class="text-sm text-slate-600 leading-relaxed">
              Query relationships, discover spelling variants, and export linked data in standard open formats.
            </p>
          </div>
        </div>
      </section>
    </main>

    <footer class="bg-slate-100 border-t border-slate-200 py-8 text-center text-sm text-slate-500">
      <div class="max-w-6xl mx-auto px-4">
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(PROJECTNAME, ENT_QUOTES, 'UTF-8'); ?>. Supported by CLARIAH-VL.</p>
      </div>
    </footer>

    <script>
      document.addEventListener('DOMContentLoaded', function() {
        showcounters('nodes');
        showcounters('edges');
      });
    </script>
  </body>
</html>
