<?php
// start by determining if the graph starts from a neo4j ID or from a key-value property pair.
$value = isset($_GET['value']) ? $_GET['value'] : 0;
include_once(dirname(__DIR__) . '/config/config.inc.php');
include_once(ROOT_DIR . '/includes/user.inc.php');
include_once(ROOT_DIR . '/includes/navbar.inc.php');

$user = new User($client);
$user->checkAccess(ENTITIESAREPUBLIC);
if ((isset($_SESSION) && isset($_SESSION['userid']) && boolval($_SESSION['userid']))) {
  $adminMode = $user->isAdmin();
} else {
  $adminMode = false;
}

// to identify a node when property is provided:
if (isset($_GET['property'])) {
  include_once(ROOT_DIR . '/includes/getnode.inc.php');
  $graph = new Node($client);
  $keyname = $_GET['property'];
  $subresult = $graph->matchSingleNode(false, $keyname, $value);
  $value = isset($subresult['data'][0][0]) ? $subresult['data'][0][0]->get('ID') : 0;
  unset($graph);
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(defined('PROJECTNAME') ? PROJECTNAME . ' - Interactive graph explorer' : 'Interactive graph explorer', ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/CSS/stylePublic.css?v=<?php echo filemtime(ROOT_DIR . '/CSS/stylePublic.css'); ?>">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/CSS/browse.css?v=<?php echo filemtime(ROOT_DIR . '/CSS/browse.css'); ?>">
    <script type="text/javascript" src="<?php echo BASE_URL; ?>/JS/vis-network.min.js"></script>
    <script type="text/javascript" src="<?php echo BASE_URL; ?>/JS/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" src="<?php echo BASE_URL; ?>/JS/wikidata_sdk/wikibase-sdk.js"></script>
    <script type="text/javascript" src="<?php echo BASE_URL; ?>/JS/wikidata_sdk/wikidata-sdk.js"></script>
    <script type="text/javascript" src="<?php echo BASE_URL; ?>/JS/wikidata.js"></script>
    <script>
      window.BASE_URL = <?php echo json_encode(BASE_URL); ?>;
      window.colorDefinitions = <?php echo json_encode(CORENODES); ?>;
      var colorDefinitions = window.colorDefinitions;
      window.nodeTranslations = <?php echo json_encode(defined('NODETRANSLATIONS') ? NODETRANSLATIONS : []); ?>;
      window.textPrimaryLabel = <?php echo json_encode(defined('TEXNODE') && defined('PRIMARIES') && isset(PRIMARIES[TEXNODE]) && isset(NODEMODEL[TEXNODE][PRIMARIES[TEXNODE]][0]) ? NODEMODEL[TEXNODE][PRIMARIES[TEXNODE]][0] : 'ID'); ?>;
      window.seedNodeId = <?php echo (int)$value; ?>;
    </script>
    <script type="text/javascript" src="<?php echo BASE_URL; ?>/JS/browse_viz.js?v=<?php echo filemtime(ROOT_DIR . '/JS/browse_viz.js'); ?>"></script>
    <?php
      include_once(ROOT_DIR . '/includes/matomo.inc.php');
      echo renderMatomoTracking();
    ?>
  </head>
  <body class="bg-slate-100 text-slate-800 antialiased min-h-screen flex flex-col">
    <?php
      $navbar = new Navbar($adminMode);
      echo $navbar->getNav();
    ?>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 w-full flex-grow flex flex-col gap-4">
      <!-- page header and graph legend -->
      <header id="top" class="bg-white border border-slate-200 rounded-2xl shadow-sm p-4 sm:p-5 flex flex-col gap-3">
        <div class="flex flex-wrap items-center justify-between gap-4">
          <div class="flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0 border border-indigo-100">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
              </svg>
            </div>
            <div>
              <h1 class="text-xl font-bold text-slate-900 tracking-tight">Interactive graph explorer</h1>
              <p class="text-xs sm:text-sm text-slate-500">Explore entities, relationships, and provenance across the knowledge graph.</p>
            </div>
          </div>

          <div class="flex flex-wrap items-center gap-2.5">
            <!-- node and edge counters -->
            <div class="inline-flex items-center gap-3 px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-600">
              <span class="inline-flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                Nodes: <strong id="nodeDisplay" class="font-semibold text-slate-900">0</strong>
              </span>
              <span class="text-slate-300">•</span>
              <span class="inline-flex items-center gap-1.5">
                <span class="w-2 h-0.5 bg-emerald-500"></span>
                Edges: <strong id="edgeDisplay" class="font-semibold text-slate-900">0</strong>
              </span>
            </div>

            <a href="<?php echo BASE_URL ?: '/'; ?>" id="btn-back" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs sm:text-sm font-medium text-slate-700 bg-white hover:bg-slate-50 border border-slate-300 rounded-lg shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500" title="Return to previous page">
              <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
              </svg>
              <span>Back</span>
            </a>
          </div>
        </div>

        <!-- interactive legend filters -->
        <div class="border-t border-slate-100 pt-3 flex flex-wrap items-center gap-2">
          <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 mr-1">Filter nodes:</span>
          <div id="legend-container" class="flex flex-wrap items-center gap-1.5 text-xs">
            <!-- legend items populated dynamically -->
          </div>
        </div>
      </header>

      <!-- graph workspace card -->
      <div id="explorer-viewport" class="relative w-full bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden select-none">
        <!-- graph canvas -->
        <div id="viz"></div>

        <!-- floating bottom control bar -->
        <nav id="bottom" aria-label="Graph navigation controls" class="absolute bottom-4 left-1/2 -translate-x-1/2 z-30 flex items-center gap-1 sm:gap-1.5 p-1.5 bg-white rounded-xl shadow-md border border-slate-200 max-w-[calc(100%-2rem)] overflow-x-auto">
          <!-- zoom in -->
          <button type="button" id="btn-zoom-in" class="p-2 text-slate-700 hover:text-indigo-600 hover:bg-slate-100 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500" title="Zoom in (+)">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span class="sr-only">Zoom in</span>
          </button>
          <!-- zoom out -->
          <button type="button" id="btn-zoom-out" class="p-2 text-slate-700 hover:text-indigo-600 hover:bg-slate-100 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500" title="Zoom out (-)">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
            </svg>
            <span class="sr-only">Zoom out</span>
          </button>
          <!-- fit to screen -->
          <button type="button" id="btn-zoom-fit" class="p-2 text-slate-700 hover:text-indigo-600 hover:bg-slate-100 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500" title="Fit graph to screen (0 or F)">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-5h-4m4 0v4m0-4l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
            </svg>
            <span class="sr-only">Fit to view</span>
          </button>

          <div class="w-px h-6 bg-slate-200 mx-1"></div>

          <!-- 1-hop graph extender -->
          <button type="button" id="btn-extend-graph" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500" title="Extend network by 1 hop">
            <svg id="extend-icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
            </svg>
            <span id="extend-text">Extend 1-hop</span>
          </button>

          <div class="w-px h-6 bg-slate-200 mx-1"></div>

          <!-- physics solver selector -->
          <div class="flex items-center">
            <label for="physics-solver-select" class="sr-only">Physics solver algorithm</label>
            <select id="physics-solver-select" class="text-xs font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg px-2.5 py-1.5 border border-slate-200 focus:ring-2 focus:ring-indigo-500 focus:outline-none cursor-pointer" title="Switch layout physics solver">
              <option value="barnesHut" selected>Barnes-Hut</option>
              <option value="repulsion">Repulsion</option>
              <option value="hierarchicalRepulsion">Hierarchical</option>
              <option value="forceAtlas2Based">ForceAtlas2</option>
            </select>
          </div>

          <!-- physics simulation toggle -->
          <button type="button" id="btn-physics-toggle" class="p-2 text-slate-700 hover:text-indigo-600 hover:bg-slate-100 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500" title="Pause or resume physics simulation (Space)">
            <svg id="physics-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="sr-only">Toggle physics simulation</span>
          </button>

          <div class="w-px h-6 bg-slate-200 mx-1"></div>

          <!-- fullscreen toggle -->
          <button type="button" id="btn-fullscreen-toggle" class="p-2 text-slate-700 hover:text-indigo-600 hover:bg-slate-100 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500" title="Toggle full screen">
            <svg id="fullscreen-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-5h-4m4 0v4m0-4l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
            </svg>
            <span class="sr-only">Toggle fullscreen</span>
          </button>
        </nav>

        <!-- node inspector card -->
        <aside id="node-inspector" class="absolute top-4 right-4 w-96 max-w-[calc(100%-2rem)] max-h-[calc(100%-5.5rem)] z-40 bg-white rounded-xl shadow-xl border border-slate-200 flex flex-col overflow-hidden transition-all duration-300 ease-out transform translate-x-[110%] opacity-0 pointer-events-none inspector-card" role="region" aria-label="Node inspector">
          <!-- inspector header -->
          <div class="p-4 border-b border-slate-200 flex items-start justify-between gap-3 bg-slate-50">
            <div class="min-w-0 flex-1">
              <div class="flex items-center gap-2 mb-1">
                <span id="inspector-color-dot" class="w-3 h-3 rounded-full flex-shrink-0 bg-indigo-500"></span>
                <span id="inspector-type" class="px-2 py-0.5 text-xs font-semibold rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200">Entity</span>
                <span id="inspector-id" class="text-xs text-slate-500 font-mono">#0</span>
              </div>
              <h3 id="inspector-title" class="text-base font-bold text-slate-900 truncate">Node details</h3>
            </div>
            <button type="button" id="btn-close-inspector" class="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-200/60 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500" aria-label="Close inspector">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <!-- inspector scrollable body -->
          <div class="inspector-scroll flex-1 overflow-y-auto p-4 space-y-4 text-sm">
            <!-- identifier and connection links -->
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
              <div class="flex items-center justify-between text-xs text-slate-500">
                <span class="font-medium uppercase tracking-wider">Identifiers and links</span>
                <span id="inspector-connections-badge" class="inline-flex items-center gap-1 font-semibold text-indigo-600">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                  </svg>
                  <span id="inspector-connections-count">0</span> connections
                </span>
              </div>
              <div id="inspector-uri-box" class="flex items-center gap-2 text-xs">
                <span class="text-slate-400">Select a node to view its URI.</span>
              </div>
              <div id="inspector-action-buttons" class="pt-1 flex flex-wrap gap-2"></div>
            </div>

            <!-- connected entities list -->
            <div>
              <button type="button" id="toggle-connected-nodes" class="w-full flex items-center justify-between py-1 text-xs font-semibold uppercase tracking-wider text-slate-500 hover:text-slate-800 transition-colors">
                <span>Connected entities (<span id="connected-count">0</span>)</span>
                <svg id="connected-arrow" class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
              </button>
              <div id="connected-nodes-list" class="mt-2 flex flex-wrap gap-1.5 max-h-36 overflow-y-auto inspector-scroll">
                <span class="text-xs text-slate-400">No connected nodes.</span>
              </div>
            </div>

            <!-- structured metadata attributes -->
            <div>
              <button type="button" id="toggle-metadata" class="w-full flex items-center justify-between py-1 text-xs font-semibold uppercase tracking-wider text-slate-500 hover:text-slate-800 transition-colors">
                <span>Node properties</span>
                <svg id="metadata-arrow" class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
              </button>
              <div id="inspector-metadata-box" class="mt-2 space-y-2">
                <!-- populated dynamically -->
              </div>
            </div>
          </div>
        </aside>

        <!-- legacy container hook for backwards compatibility -->
        <div id="slideover-container" class="hidden">
          <div id="slideover"></div>
          <div id="nodestatisticsBox"></div>
          <div id="nodeLinkBox"></div>
          <div id="metadataboxGoesHere"></div>
        </div>
      </div>
    </main>

    <script>
      document.addEventListener('DOMContentLoaded', function() {
        init(window.seedNodeId);
      });
    </script>
  </body>
</html>
