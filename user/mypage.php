<?php
if (!defined('ROOT_DIR')) {
  include_once(dirname(__DIR__) . '/config/config.inc.php');
}
include_once(ROOT_DIR . "/includes/client.inc.php");
include_once(ROOT_DIR . "/includes/user.inc.php");
include_once(ROOT_DIR . "/includes/annotation.inc.php");
include_once(ROOT_DIR . "/includes/wikidata_user_prefs.inc.php");
include_once(ROOT_DIR . '/includes/navbar.inc.php');
include_once(ROOT_DIR . '/includes/csrf.inc.php');
include_once(ROOT_DIR . '/includes/collection.inc.php');

$baseUrl = defined('BASE_URL') ? BASE_URL : '';

if (isset($_SESSION['userid'])) {
  $user = new User($client);
  $annotation = new Annotation($client);
} else {
  $target = $baseUrl . '/user/login.php';
  header("Location: $target");
  exit;
}

$csrfManager = new CsrfTokenManager();
$csrfToken = $csrfManager->getToken();

$preferences = new Wikidata_user($client);
$preferences->buildPreferences();

$count = $annotation->countPersonalAnnotations($user->myId);
$privcount = (int)($count['private'] ?? 0);
$publcount = (int)($count['public'] ?? 0);
$totalcount = $privcount + $publcount;
$adminMode = $user->isAdmin();

$allUserPreferences = $preferences->getUserPreferences($user->myId);
$activeEntities = $preferences->labelIndicator();
$settingsDictionary = $preferences->makeSettingsDictionary();
$collectionManager = new CollectionManager($client);
$userCollections = $collectionManager->listAccessibleCollections($user->neoId ? (int)$user->neoId : null, TEXTSAREPUBLIC);
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User profile - <?php echo htmlspecialchars(PROJECTNAME, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/CSS/stylePublic.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/CSS/overlaystyling.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/CSS/leaflet/leaflet.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="<?php echo $baseUrl; ?>/JS/wikidata_sdk/wikibase-sdk.js"></script>
    <script src="<?php echo $baseUrl; ?>/JS/wikidata_sdk/wikidata-sdk.js"></script>
    <script src="<?php echo $baseUrl; ?>/JS/leaflet/leaflet.js"></script>
    <script src="<?php echo $baseUrl; ?>/JS/caroussel.js"></script>
    <script src="<?php echo $baseUrl; ?>/JS/makeMap.js"></script>
    <script src="<?php echo $baseUrl; ?>/JS/wikidata.js"></script>
    <?php
      include_once(ROOT_DIR . '/includes/matomo.inc.php');
      echo renderMatomoTracking();
    ?>
  </head>
  <body class="bg-slate-100 text-slate-800 w-full antialiased min-h-screen">
  <?php
    $navbar = new Navbar($adminMode); 
    echo $navbar->getNav();
  ?>

  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Profile header banner -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 mb-8 flex flex-wrap items-center justify-between gap-4">
      <div class="flex items-center gap-4">
        <div class="w-14 h-14 rounded-2xl bg-indigo-600 text-white flex items-center justify-center text-xl font-bold shadow-sm">
          <?php echo htmlspecialchars(strtoupper(substr($user->myName ?: 'U', 0, 1)), ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <div>
          <div class="flex items-center gap-2">
            <h1 class="text-xl font-bold tracking-tight text-slate-900"><?php echo htmlspecialchars($user->myName ?: 'User', ENT_QUOTES, 'UTF-8'); ?></h1>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 capitalize">
              <?php echo htmlspecialchars($user->myRole ?: 'User', ENT_QUOTES, 'UTF-8'); ?>
            </span>
          </div>
          <p class="text-xs text-slate-500 mt-0.5">Manage your personal settings, Wikidata entity preferences, and annotation visibility.</p>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <a href="<?php echo $baseUrl; ?>/user/logout.php"
           class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold rounded-lg bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 transition-colors shadow-2xs">
          <i class="fas fa-sign-out-alt text-[11px]"></i>
          <span>Sign out</span>
        </a>
      </div>
    </div>

    <!-- Summary statistic cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
      <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs p-5">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-medium text-slate-500">Total annotations</p>
            <p class="text-2xl font-bold tracking-tight text-slate-900 mt-1"><?php echo $totalcount; ?></p>
          </div>
          <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-base">
            <i class="fas fa-tags"></i>
          </div>
        </div>
        <p class="text-[11px] text-slate-400 mt-2">All text annotations created by this account</p>
      </div>

      <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs p-5">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-medium text-slate-500">Public annotations</p>
            <p class="text-2xl font-bold tracking-tight text-slate-900 mt-1"><?php echo $publcount; ?></p>
          </div>
          <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-base">
            <i class="fas fa-globe"></i>
          </div>
        </div>
        <p class="text-[11px] text-slate-400 mt-2">Visible to all workspace collaborators</p>
      </div>

      <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs p-5">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-medium text-slate-500">Private annotations</p>
            <p class="text-2xl font-bold tracking-tight text-slate-900 mt-1"><?php echo $privcount; ?></p>
          </div>
          <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-base">
            <i class="fas fa-lock"></i>
          </div>
        </div>
        <p class="text-[11px] text-slate-400 mt-2">Only visible within your personal view</p>
      </div>
    </div>

    <!-- Personal collections summary -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 mb-8">
      <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div>
          <h2 class="text-base font-bold text-slate-900">My personal collections</h2>
          <p class="text-xs text-slate-500 mt-0.5">Quick access to your private text collections.</p>
        </div>
        <a href="<?php echo $baseUrl; ?>/collections.php" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 transition-colors">
          <i class="fas fa-layer-group text-[11px]"></i>
          <span>Manage all collections</span>
        </a>
      </div>
      <?php if (empty($userCollections['personal'])): ?>
        <p class="text-xs text-slate-500">You have not created any personal collections yet.</p>
      <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
          <?php foreach ($userCollections['personal'] as $col): ?>
            <a href="<?php echo $baseUrl; ?>/collection/<?php echo (int)$col['collection_id']; ?>"
               class="p-3.5 rounded-xl border border-slate-200 hover:border-emerald-300 bg-slate-50/50 hover:bg-emerald-50/30 transition-colors flex items-center justify-between gap-3">
              <div class="min-w-0">
                <div class="text-sm font-semibold text-slate-900 truncate"><?php echo htmlspecialchars($col['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="text-xs text-slate-500 mt-0.5"><?php echo (int)$col['text_count']; ?> <?php echo $col['text_count'] === 1 ? 'text' : 'texts'; ?></div>
              </div>
              <span class="text-xs font-mono text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full">#<?php echo (int)$col['collection_id']; ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Hidden language lookup element for Wikidata renderer -->
    <select id="wdlookuplanguage" class="hidden">
      <option value="en" selected>English</option>
      <option value="fr">Français</option>
      <option value="nl">Nederlands</option>
      <option value="de">Deutsch</option>
    </select>

    <!-- Preferences hub section -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden mb-8">
      <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 class="text-base font-bold text-slate-900">Wikidata and knowledge base preferences</h2>
          <p class="text-xs text-slate-500 mt-0.5">Customize which entity properties and Wikipedia portals are displayed when inspecting matched entities.</p>
        </div>

        <!-- Segmented preference tabs -->
        <div class="inline-flex rounded-lg bg-slate-100 p-1 text-xs font-semibold">
          <button type="button"
                  id="tabPropBtn"
                  onclick="switchPrefTab('properties')"
                  class="pref-tab-btn px-3 py-1.5 rounded-md transition-all bg-white text-indigo-700 shadow-xs">
            <i class="fas fa-list-ul mr-1"></i>Properties
          </button>
          <button type="button"
                  id="tabLinksBtn"
                  onclick="switchPrefTab('links')"
                  class="pref-tab-btn px-3 py-1.5 rounded-md transition-all text-slate-600 hover:text-slate-900">
            <i class="fas fa-external-link-alt mr-1"></i>Links
          </button>
          <button type="button"
                  id="tabTitlesBtn"
                  onclick="switchPrefTab('titles')"
                  class="pref-tab-btn px-3 py-1.5 rounded-md transition-all text-slate-600 hover:text-slate-900">
            <i class="fas fa-language mr-1"></i>String matching
          </button>
        </div>
      </div>

      <div class="p-6">
        <!-- Tab 1: Wikidata properties (Per-entity configuration) -->
        <div id="prefTabProperties">
          <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
              <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Per-entity-type Wikidata properties</h3>
              <p class="text-xs text-slate-600 mt-0.5">Select an entity type to customize which P-tags are displayed, whether missing values show a notice, and how evolving temporal data is presented.</p>
            </div>

            <!-- Toast notification container -->
            <div id="saveToastNotice" class="hidden text-xs font-medium px-3.5 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 transition-opacity">
              <i class="fas fa-check-circle mr-1"></i> Preferences saved successfully
            </div>
          </div>

          <!-- Entity type sub-tabs -->
          <div class="flex flex-wrap items-center gap-2 pb-4 border-b border-slate-100 mb-6" id="entitySubTabs">
            <?php
            $firstEntityType = null;
            foreach ($allUserPreferences as $etKey => $etProps) {
              if ($firstEntityType === null) {
                $firstEntityType = $etKey;
              }
              $countActive = 0;
              if (is_array($etProps)) {
                foreach ($etProps as $p) {
                  if (!empty($p['enabled'])) {
                    $countActive++;
                  }
                }
              }
              $isActiveTab = ($etKey === $firstEntityType);
              $activeClasses = $isActiveTab ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-700';
            ?>
              <button type="button"
                      onclick="selectEntitySubTab('<?php echo htmlspecialchars($etKey, ENT_QUOTES, 'UTF-8'); ?>')"
                      data-entity-tab="<?php echo htmlspecialchars($etKey, ENT_QUOTES, 'UTF-8'); ?>"
                      class="entity-subtab-btn inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold transition-all <?php echo $activeClasses; ?>">
                <span><?php echo htmlspecialchars($etKey, ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="entity-active-badge text-[10px] px-1.5 py-0.2 rounded-full <?php echo $isActiveTab ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-600'; ?>">
                  <?php echo $countActive; ?> active
                </span>
              </button>
            <?php } ?>
          </div>

          <!-- Active entity configuration panel -->
          <div id="entityConfigContainer" class="space-y-6">
            <!-- Rendered by JavaScript based on state -->
          </div>
        </div>

        <!-- Tab 2: Wikipedia links -->
        <div id="prefTabLinks" class="hidden">
          <div class="mb-4">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Wikipedia language portals</h3>
            <p class="text-xs text-slate-600 mt-0.5">Select which language versions of Wikipedia should display direct outbound links in the entity inspection drawer.</p>
          </div>
          <?php echo $preferences->generateForm('links'); ?>
        </div>

        <!-- Tab 3: String matching -->
        <div id="prefTabTitles" class="hidden space-y-6">
          <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
              <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Wikipedia title string matching</h3>
              <p class="text-xs text-slate-600 mt-0.5">Configure how text mentions and keywords are matched to Wikipedia article titles to discover entity candidates.</p>
            </div>
            <!-- Toast notification for string matching save -->
            <div id="saveTitlesToastNotice" class="hidden text-xs font-medium px-3.5 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 transition-opacity">
              <i class="fas fa-check-circle mr-1"></i> String matching preferences saved successfully
            </div>
          </div>

          <!-- Prioritize detected language -->
          <div class="bg-white rounded-xl border border-slate-200/80 p-4.5 shadow-2xs">
            <label class="flex items-start gap-3 cursor-pointer">
              <div class="flex items-center h-5">
                <input type="checkbox"
                       id="prioritizeDetectedCheckbox"
                       class="rounded text-indigo-600 focus:ring-indigo-500 h-4 w-4 border-slate-300">
              </div>
              <div>
                <span class="text-xs font-semibold text-slate-800">Prioritize lookup in detected language</span>
                <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">
                  When enabled, title matching automatically queries the language detected by the LATTE connector for the current text (e.g. French Wikipedia for French texts). If language detection is unavailable, lookups will use the fallback languages below.
                </p>
              </div>
            </label>
          </div>

          <!-- Fallback languages guided UI -->
          <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-2xs space-y-5">
            <div>
              <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Fallback languages</h4>
              <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">
                Configure the Wikipedia language editions to use when no language is detected by the LATTE connector, or when querying secondary languages. If no fallback languages are configured, lookups will default to English Wikipedia (<code class="text-[11px] font-mono text-slate-600">enwiki</code>).
              </p>
            </div>

            <!-- List of configured fallback languages -->
            <div>
              <label class="block text-xs font-semibold text-slate-700 mb-2">Configured fallback languages:</label>
              <div id="fallbackLanguagesContainer" class="flex flex-wrap gap-2 min-h-10 items-center p-3 rounded-xl bg-slate-50 border border-slate-200">
                <!-- Rendered dynamically by JavaScript -->
              </div>
            </div>

            <!-- Guided Add Language section -->
            <div class="pt-4 border-t border-slate-100">
              <label class="block text-xs font-semibold text-slate-700 mb-2">Add fallback language:</label>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Dropdown selector of common languages -->
                <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50 space-y-2">
                  <span class="block text-[11px] font-medium text-slate-600">Select from common Wikipedia languages:</span>
                  <div class="flex items-center gap-2">
                    <select id="guidedLangSelect" class="flex-grow px-3 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                      <option value="enwiki">English (enwiki)</option>
                      <option value="frwiki">French (frwiki)</option>
                      <option value="dewiki">German (dewiki)</option>
                      <option value="nlwiki">Dutch (nlwiki)</option>
                      <option value="eswiki">Spanish (eswiki)</option>
                      <option value="itwiki">Italian (itwiki)</option>
                      <option value="lawiki">Latin (lawiki)</option>
                      <option value="ptwiki">Portuguese (ptwiki)</option>
                      <option value="plwiki">Polish (plwiki)</option>
                      <option value="ruwiki">Russian (ruwiki)</option>
                      <option value="jawiki">Japanese (jawiki)</option>
                      <option value="zhwiki">Chinese (zhwiki)</option>
                      <option value="arwiki">Arabic (arwiki)</option>
                      <option value="svwiki">Swedish (svwiki)</option>
                      <option value="ukwiki">Ukrainian (ukwiki)</option>
                      <option value="elwiki">Greek (elwiki)</option>
                      <option value="huwiki">Hungarian (huwiki)</option>
                      <option value="cswiki">Czech (cswiki)</option>
                      <option value="dawiki">Danish (dawiki)</option>
                      <option value="fiwiki">Finnish (fiwiki)</option>
                      <option value="nowiki">Norwegian (nowiki)</option>
                      <option value="trwiki">Turkish (trwiki)</option>
                      <option value="hewiki">Hebrew (hewiki)</option>
                    </select>
                    <button type="button"
                            onclick="handleAddGuidedLanguage()"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 transition-colors shadow-2xs cursor-pointer shrink-0">
                      <i class="fas fa-plus text-[10px]"></i>
                      <span>Add</span>
                    </button>
                  </div>
                </div>

                <!-- Custom code entry -->
                <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50 space-y-2">
                  <span class="block text-[11px] font-medium text-slate-600">Or enter any ISO or Wikipedia code:</span>
                  <div class="flex items-center gap-2">
                    <input type="text"
                           id="customLangCodeInput"
                           placeholder="e.g. ca, ro, eu, or cawiki"
                           class="flex-grow px-3 py-1.5 text-xs rounded-lg border border-slate-300 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                    <button type="button"
                            onclick="handleAddCustomLanguage()"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 transition-colors shadow-2xs cursor-pointer shrink-0">
                      <i class="fas fa-plus text-[10px]"></i>
                      <span>Add code</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Save Action Button -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
              <span class="text-xs text-slate-400">Remember to save your preferences after making changes.</span>
              <button type="button"
                      onclick="saveTitlePreferences()"
                      class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs transition-colors cursor-pointer">
                <i class="fas fa-save text-xs"></i>
                <span>Save string matching preferences</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Interactive entity inspector and live UI preview sandbox -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden mb-12">
      <div class="px-6 py-4 border-b border-slate-100 bg-gradient-to-r from-slate-50 via-white to-indigo-50/30">
        <div class="flex items-center gap-2">
          <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
          <h3 class="text-sm font-bold text-slate-900">Interactive entity inspector and live drawer preview</h3>
        </div>
        <p class="text-xs text-slate-500 mt-0.5">Enter any arbitrary Wikidata Q-ID to browse its available P-tags with their human-readable labels and preview how the entity will be rendered inside the annotation drawer according to your current preferences.</p>
      </div>

      <div class="p-6">
        <!-- Search bar row -->
        <div class="flex flex-wrap items-center gap-3 mb-6 p-4 rounded-xl bg-slate-50/80 border border-slate-200/80">
          <div class="flex-1 min-w-[220px]">
            <label for="inspectorQidInput" class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Wikidata item Q-ID</label>
            <div class="relative">
              <input type="text"
                     id="inspectorQidInput"
                     placeholder="e.g. Q123"
                     value=""
                     class="w-full px-3.5 py-2 pl-9 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-mono font-medium">
              <i class="fas fa-search absolute left-3 top-2.5 text-slate-400 text-xs"></i>
            </div>
          </div>

          <div class="w-44">
            <label for="inspectorEntityTypeSelect" class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Preview entity type</label>
            <select id="inspectorEntityTypeSelect"
                    onchange="handlePreviewEntityTypeChange()"
                    class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium">
              <?php foreach (array_keys($allUserPreferences) as $etName) { ?>
                <option value="<?php echo htmlspecialchars($etName, ENT_QUOTES, 'UTF-8'); ?>">
                  <?php echo htmlspecialchars($etName, ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php } ?>
            </select>
          </div>

          <div class="pt-5">
            <button type="button"
                    id="btnRunInspector"
                    onclick="inspectEntity()"
                    class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs transition-colors">
              <i class="fas fa-play text-[10px]"></i>
              <span>Inspect and preview</span>
            </button>
          </div>
        </div>

        <!-- Entity metadata bar -->
        <div id="inspectorEntityMeta" class="hidden mb-6 p-4 rounded-xl bg-indigo-50/50 border border-indigo-100 flex flex-wrap items-center justify-between gap-3">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold text-sm shadow-2xs">
              <i class="fab fa-wikipedia-w"></i>
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h4 id="inspectorEntityLabel" class="text-sm font-bold text-slate-900">-</h4>
                <span id="inspectorEntityQidBadge" class="text-[11px] font-mono px-2 py-0.5 rounded-md bg-white border border-indigo-200 text-indigo-700 font-semibold">-</span>
              </div>
              <p id="inspectorEntityDesc" class="text-xs text-slate-600 mt-0.5">-</p>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <a id="inspectorEntityWdLink" href="#" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-white hover:bg-indigo-50 text-indigo-700 border border-indigo-200 transition shadow-2xs">
              <i class="fas fa-external-link-alt text-[10px]"></i>
              <span>View on Wikidata</span>
            </a>
          </div>
        </div>

        <!-- Split preview container: Properties table + Live drawer -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
          <!-- Left column: Available P-tags on this item (span 7) -->
          <div class="lg:col-span-7 bg-slate-50/60 rounded-xl border border-slate-200/80 p-4">
            <div class="flex items-center justify-between mb-3">
              <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
                  <i class="fas fa-table text-indigo-500"></i>
                  <span>Available Wikidata properties on this entity</span>
                </h4>
                <p class="text-[11px] text-slate-500 mt-0.5">Browse all P-tags present on the inspected entity with human-readable labels and add any missing P-tag to your active entity configuration.</p>
              </div>
              <span id="inspectorPropCountBadge" class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-slate-200 text-slate-700">0 found</span>
            </div>

            <!-- Filter input -->
            <div class="mb-3">
              <input type="text"
                     id="inspectorPropFilterInput"
                     oninput="filterInspectorProperties()"
                     placeholder="Filter properties by name or P-code..."
                     class="w-full px-3 py-1.5 text-xs rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500">
            </div>

            <!-- Table of P-tags -->
            <div class="overflow-x-auto max-h-[500px] overflow-y-auto rounded-lg border border-slate-200 bg-white">
              <table class="w-full text-left text-xs divide-y divide-slate-200">
                <thead class="bg-slate-50 text-slate-600 font-semibold sticky top-0 z-10">
                  <tr>
                    <th class="py-2.5 px-3">Property</th>
                    <th class="py-2.5 px-3">Datatype</th>
                    <th class="py-2.5 px-3">Configured</th>
                    <th class="py-2.5 px-3 text-right">Action</th>
                  </tr>
                </thead>
                <tbody id="inspectorPropTableBody" class="divide-y divide-slate-100 font-normal text-slate-700">
                  <tr>
                    <td colspan="4" class="py-8 text-center text-xs text-slate-400 italic">
                      Click 'Inspect and preview' to load properties for this entity.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Right column: Live slideover drawer preview (span 5) -->
          <div class="lg:col-span-5 bg-slate-50/60 rounded-xl border border-slate-200/80 p-4">
            <div class="flex items-center justify-between mb-3">
              <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
                  <i class="fas fa-eye text-indigo-500"></i>
                  <span>Live annotation drawer preview</span>
                </h4>
                <p class="text-[11px] text-slate-500 mt-0.5">Exact replica of how this entity renders in the slideover drawer when matched in text analysis.</p>
              </div>
              <span class="text-[10px] font-semibold uppercase px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-100">Slideover UI</span>
            </div>

            <!-- Simulated slideover container -->
            <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm min-h-[300px]">
              <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-3">
                <div class="flex items-center gap-2">
                  <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                  <span class="text-xs font-bold text-slate-700" id="previewDrawerTitle">Entity preview</span>
                </div>
                <span class="text-[10px] text-slate-400 font-mono" id="previewDrawerSubTitle">Q-ID</span>
              </div>

              <!-- Preview target element -->
              <div id="previewWDTarget" class="space-y-2 text-xs">
                <div class="py-12 text-center text-slate-400 italic text-xs">
                  Run an inspection above to view the rendered drawer preview.
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- Client-side State and Controller -->
  <script>
    // Initial state passed from PHP backend:
    const CSRF_TOKEN = <?php echo json_encode($csrfToken); ?>;
    let userPreferences = <?php echo json_encode($allUserPreferences); ?>;
    let baseSettingsDict = <?php echo json_encode($settingsDictionary); ?>;
    let activeEntityType = <?php echo json_encode($firstEntityType ?? 'Person'); ?>;

    let inspectedEntityData = null;
    let inspectedEntityClaims = {};
    let inspectedEntityQid = '';

    // Switch top-level tabs (Properties, Links, String matching)
    function switchPrefTab(tabName) {
      const tabProps = document.getElementById('prefTabProperties');
      const tabLinks = document.getElementById('prefTabLinks');
      const tabTitles = document.getElementById('prefTabTitles');
      const btnProps = document.getElementById('tabPropBtn');
      const btnLinks = document.getElementById('tabLinksBtn');
      const btnTitles = document.getElementById('tabTitlesBtn');

      tabProps.classList.add('hidden');
      tabLinks.classList.add('hidden');
      tabTitles.classList.add('hidden');

      [btnProps, btnLinks, btnTitles].forEach(btn => {
        btn.classList.remove('bg-white', 'text-indigo-700', 'shadow-xs');
        btn.classList.add('text-slate-600');
      });

      if (tabName === 'links') {
        tabLinks.classList.remove('hidden');
        btnLinks.classList.add('bg-white', 'text-indigo-700', 'shadow-xs');
        btnLinks.classList.remove('text-slate-600');
      } else if (tabName === 'titles') {
        tabTitles.classList.remove('hidden');
        btnTitles.classList.add('bg-white', 'text-indigo-700', 'shadow-xs');
        btnTitles.classList.remove('text-slate-600');
      } else {
        tabProps.classList.remove('hidden');
        btnProps.classList.add('bg-white', 'text-indigo-700', 'shadow-xs');
        btnProps.classList.remove('text-slate-600');
      }
    }

    // Select sub-tab for entity type
    function selectEntitySubTab(entityType) {
      activeEntityType = entityType;
      document.querySelectorAll('.entity-subtab-btn').forEach(btn => {
        const badge = btn.querySelector('.entity-active-badge');
        if (btn.getAttribute('data-entity-tab') === entityType) {
          btn.className = 'entity-subtab-btn inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold transition-all bg-indigo-600 text-white shadow-xs';
          if (badge) badge.className = 'entity-active-badge text-[10px] px-1.5 py-0.2 rounded-full bg-white/20 text-white';
        } else {
          btn.className = 'entity-subtab-btn inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold transition-all bg-slate-100 hover:bg-slate-200 text-slate-700';
          if (badge) badge.className = 'entity-active-badge text-[10px] px-1.5 py-0.2 rounded-full bg-slate-200 text-slate-600';
        }
      });

      // Synchronize preview entity type select
      const previewSel = document.getElementById('inspectorEntityTypeSelect');
      if (previewSel && previewSel.value !== entityType) {
        previewSel.value = entityType;
      }

      renderEntityConfig();
      if (inspectedEntityQid) {
        renderLivePreview(inspectedEntityQid);
        renderInspectorPropertyTable();
      }
    }

    // Render active entity configuration table and form
    function renderEntityConfig() {
      const container = document.getElementById('entityConfigContainer');
      const properties = userPreferences[activeEntityType] || {};
      const propKeys = Object.keys(properties);

      let tableRows = '';
      if (propKeys.length === 0) {
        tableRows = `
          <tr>
            <td colspan="6" class="py-6 text-center text-xs text-slate-400 italic">
              No properties configured for ${escapeHtml(activeEntityType)}. Add properties below.
            </td>
          </tr>
        `;
      } else {
        propKeys.forEach(pCode => {
          const item = properties[pCode];
          const label = item.label || pCode;
          const type = item.type || 'str';
          const enabled = Boolean(item.enabled);
          const showMissing = Boolean(item.show_missing);
          const temporalMode = item.temporal_mode || 'latest';

          const typeLabelMap = {
            str: 'Literal',
            uri: 'Identifier URI',
            geo: 'Map coordinate',
            img: 'Media image'
          };
          const typeLabel = typeLabelMap[type] || 'Literal';

          tableRows += `
            <tr class="hover:bg-slate-50/80 transition-colors" data-prop-code="${escapeHtml(pCode)}">
              <td class="py-3 px-3">
                <div class="flex items-center gap-2">
                  <a href="https://www.wikidata.org/wiki/Property:${escapeHtml(pCode)}" target="_blank" class="font-bold text-slate-900 hover:text-indigo-600 flex items-center gap-1">
                    <span>${escapeHtml(label)}</span>
                    <i class="fas fa-external-link-alt text-[9px] text-slate-400"></i>
                  </a>
                  <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 font-semibold">${escapeHtml(pCode)}</span>
                </div>
              </td>
              <td class="py-3 px-3">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-700">
                  ${escapeHtml(typeLabel)}
                </span>
              </td>
              <td class="py-3 px-3">
                <button type="button"
                        role="switch"
                        aria-checked="${enabled ? 'true' : 'false'}"
                        onclick="handleTogglePropertyEnabled('${escapeHtml(pCode)}')"
                        class="inline-flex items-center gap-2 cursor-pointer focus:outline-hidden"
                        title="${enabled ? 'Click to disable' : 'Click to enable'}">
                  <span class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors duration-200 ease-in-out ${enabled ? 'bg-indigo-600' : 'bg-slate-300'}">
                    <span class="inline-block h-3.5 w-3.5 rounded-full bg-white shadow-xs transition-all duration-200 ease-in-out"
                          style="margin-left: ${enabled ? '18px' : '3px'};"></span>
                  </span>
                  <span class="text-xs font-semibold ${enabled ? 'text-indigo-700' : 'text-slate-400'}">
                    ${enabled ? 'Enabled' : 'Disabled'}
                  </span>
                </button>
              </td>
              <td class="py-3 px-3">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                  <input type="checkbox"
                         ${showMissing ? 'checked' : ''}
                         onchange="handleToggleMissingNotice('${escapeHtml(pCode)}', this.checked)"
                         class="rounded text-indigo-600 focus:ring-indigo-500 h-3.5 w-3.5 border-slate-300">
                  <span class="text-[11px] text-slate-600">Show notice</span>
                </label>
              </td>
              <td class="py-3 px-3">
                <select onchange="handleTemporalModeChange('${escapeHtml(pCode)}', this.value)"
                        class="px-2 py-1 text-[11px] rounded border border-slate-200 bg-white text-slate-700 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                  <option value="latest" ${temporalMode === 'latest' ? 'selected' : ''}>Latest value</option>
                  <option value="history" ${temporalMode === 'history' ? 'selected' : ''}>Evolution history</option>
                </select>
              </td>
              <td class="py-3 px-3 text-right">
                <button type="button"
                        onclick="handleRemoveProperty('${escapeHtml(pCode)}')"
                        title="Remove property"
                        class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors">
                  <i class="fas fa-trash-alt text-xs"></i>
                </button>
              </td>
            </tr>
          `;
        });
      }

      container.innerHTML = `
        <div class="rounded-xl border border-slate-200 overflow-hidden shadow-2xs">
          <div class="px-4 py-3 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
              <span class="font-bold text-xs text-slate-700 uppercase tracking-wider">Configured properties for ${escapeHtml(activeEntityType)}</span>
              <span class="text-[11px] text-slate-400">(${propKeys.length} total)</span>
            </div>
            <button type="button"
                    onclick="saveCurrentEntityPreferences()"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs transition-colors">
              <i class="fas fa-save text-[11px]"></i>
              <span>Save ${escapeHtml(activeEntityType)} preferences</span>
            </button>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs divide-y divide-slate-200">
              <thead class="bg-slate-50/50 text-slate-600 font-semibold">
                <tr>
                  <th class="py-2.5 px-3">Property</th>
                  <th class="py-2.5 px-3">Datatype</th>
                  <th class="py-2.5 px-3">Enabled</th>
                  <th class="py-2.5 px-3">Missing notice</th>
                  <th class="py-2.5 px-3">Temporal strategy</th>
                  <th class="py-2.5 px-3 text-right">Action</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 bg-white">
                ${tableRows}
              </tbody>
            </table>
          </div>
        </div>

        <!-- Add custom property widget -->
        <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/70">
          <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 mb-3 flex items-center gap-1.5">
            <i class="fas fa-plus-circle text-indigo-500"></i>
            <span>Add custom property to ${escapeHtml(activeEntityType)}</span>
          </h4>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
            <div>
              <label for="newPropCodeInput" class="block text-[11px] font-semibold text-slate-600 mb-1">P-code</label>
              <div class="flex gap-1.5">
                <input type="text"
                       id="newPropCodeInput"
                       placeholder="e.g. P1082"
                       class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 font-mono font-medium focus:ring-1 focus:ring-indigo-500">
                <button type="button"
                        onclick="lookupNewPropertyLabel()"
                        title="Fetch label from Wikidata"
                        class="px-2 py-1.5 text-xs rounded-lg bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 font-semibold shadow-2xs">
                  <i class="fas fa-search text-[10px]"></i>
                </button>
              </div>
            </div>

            <div>
              <label for="newPropLabelInput" class="block text-[11px] font-semibold text-slate-600 mb-1">Human-readable label</label>
              <input type="text"
                     id="newPropLabelInput"
                     placeholder="e.g. Population"
                     class="w-full px-2.5 py-1.5 text-xs rounded-lg border border-slate-300 focus:ring-1 focus:ring-indigo-500">
            </div>

            <div>
              <label for="newPropTypeSelect" class="block text-[11px] font-semibold text-slate-600 mb-1">Datatype</label>
              <select id="newPropTypeSelect" class="w-full px-2 py-1.5 text-xs rounded-lg border border-slate-300 bg-white focus:ring-1 focus:ring-indigo-500">
                <option value="str">Literal string / quantity</option>
                <option value="uri">Identifier URI</option>
                <option value="geo">Coordinate map</option>
                <option value="img">Media image</option>
              </select>
            </div>

            <div>
              <label for="newPropTemporalSelect" class="block text-[11px] font-semibold text-slate-600 mb-1">Temporal mode</label>
              <select id="newPropTemporalSelect" class="w-full px-2 py-1.5 text-xs rounded-lg border border-slate-300 bg-white focus:ring-1 focus:ring-indigo-500">
                <option value="latest">Latest value</option>
                <option value="history">Evolution history</option>
              </select>
            </div>

            <div class="flex items-center gap-2">
              <button type="button"
                      onclick="handleAddCustomProperty()"
                      class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs transition-colors">
                <i class="fas fa-plus text-[10px]"></i>
                <span>Add property</span>
              </button>
            </div>
          </div>
        </div>
      `;
    }

    // Lookup Wikidata label for new property
    async function lookupNewPropertyLabel() {
      const codeInput = document.getElementById('newPropCodeInput');
      const labelInput = document.getElementById('newPropLabelInput');
      const typeSelect = document.getElementById('newPropTypeSelect');
      const temporalSelect = document.getElementById('newPropTemporalSelect');

      const code = (codeInput?.value || '').trim().toUpperCase();
      if (!code.match(/^P\d+$/)) {
        alert('Please enter a valid Wikidata property identifier starting with P (e.g. P1082).');
        return;
      }

      try {
        const url = `https://www.wikidata.org/w/api.php?action=wbgetentities&ids=${code}&props=labels|datatype&languages=en&format=json&origin=*`;
        const res = await fetch(url).then(r => r.json());
        const entity = res?.entities?.[code];
        if (entity) {
          const label = entity.labels?.en?.value || code;
          const datatype = entity.datatype || 'string';
          if (labelInput) labelInput.value = label;

          if (typeSelect) {
            if (datatype === 'globe-coordinate') typeSelect.value = 'geo';
            else if (datatype === 'commonsMedia') typeSelect.value = 'img';
            else if (datatype === 'external-id') typeSelect.value = 'uri';
            else typeSelect.value = 'str';
          }

          if (temporalSelect) {
            if (code === 'P1082' || code === 'P2131' || datatype === 'quantity') {
              temporalSelect.value = 'history';
            }
          }
        }
      } catch (err) {
        console.warn('Error fetching property info from Wikidata:', err);
      }
    }

    // Toggle enabled state of a property
    function handleTogglePropertyEnabled(pCode, isChecked = null) {
      if (!userPreferences[activeEntityType]) return;
      if (!userPreferences[activeEntityType][pCode]) return;
      const current = Boolean(userPreferences[activeEntityType][pCode].enabled);
      const next = (isChecked !== null) ? Boolean(isChecked) : !current;
      userPreferences[activeEntityType][pCode].enabled = next;
      renderEntityConfig();
      updateActiveBadge();
      if (inspectedEntityQid) renderLivePreview(inspectedEntityQid);
    }

    // Toggle missing notice notice of a property
    function handleToggleMissingNotice(pCode, isChecked) {
      if (!userPreferences[activeEntityType]) return;
      if (!userPreferences[activeEntityType][pCode]) return;
      userPreferences[activeEntityType][pCode].show_missing = isChecked;
      if (inspectedEntityQid) renderLivePreview(inspectedEntityQid);
    }

    // Change temporal mode
    function handleTemporalModeChange(pCode, mode) {
      if (!userPreferences[activeEntityType]) return;
      if (!userPreferences[activeEntityType][pCode]) return;
      userPreferences[activeEntityType][pCode].temporal_mode = mode;
      if (inspectedEntityQid) renderLivePreview(inspectedEntityQid);
    }

    // Remove property from current entity
    function handleRemoveProperty(pCode) {
      if (!userPreferences[activeEntityType]) return;
      if (confirm(`Remove property ${pCode} from ${activeEntityType}?`)) {
        delete userPreferences[activeEntityType][pCode];
        renderEntityConfig();
        updateActiveBadge();
        if (inspectedEntityQid) {
          renderLivePreview(inspectedEntityQid);
          renderInspectorPropertyTable();
        }
      }
    }

    // Add custom property from form
    function handleAddCustomProperty() {
      const codeInput = document.getElementById('newPropCodeInput');
      const labelInput = document.getElementById('newPropLabelInput');
      const typeSelect = document.getElementById('newPropTypeSelect');
      const temporalSelect = document.getElementById('newPropTemporalSelect');

      const code = (codeInput?.value || '').trim().toUpperCase();
      if (!code.match(/^P\d+$/)) {
        alert('Please enter a valid Wikidata property identifier starting with P (e.g. P1082).');
        return;
      }

      const label = (labelInput?.value || '').trim() || code;
      const type = typeSelect?.value || 'str';
      const temporalMode = temporalSelect?.value || 'latest';

      if (!userPreferences[activeEntityType]) {
        userPreferences[activeEntityType] = {};
      }

      userPreferences[activeEntityType][code] = {
        label: label,
        type: type,
        enabled: true,
        show_missing: false,
        temporal_mode: temporalMode
      };

      renderEntityConfig();
      updateActiveBadge();
      if (inspectedEntityQid) {
        renderLivePreview(inspectedEntityQid);
        renderInspectorPropertyTable();
      }
    }

    // Quick add property from inspector table
    function quickAddPropertyToActiveEntity(pCode, label, type, temporalMode) {
      if (!userPreferences[activeEntityType]) {
        userPreferences[activeEntityType] = {};
      }

      userPreferences[activeEntityType][pCode] = {
        label: label || pCode,
        type: type || 'str',
        enabled: true,
        show_missing: false,
        temporal_mode: temporalMode || 'latest'
      };

      renderEntityConfig();
      updateActiveBadge();
      renderInspectorPropertyTable();
      if (inspectedEntityQid) {
        renderLivePreview(inspectedEntityQid);
      }
    }

    // Update active count badge in sub-tab
    function updateActiveBadge() {
      const tabBtn = document.querySelector(`.entity-subtab-btn[data-entity-tab="${activeEntityType}"]`);
      if (tabBtn) {
        const badge = tabBtn.querySelector('.entity-active-badge');
        const count = Object.values(userPreferences[activeEntityType] || {}).filter(p => p.enabled).length;
        if (badge) {
          badge.textContent = `${count} active`;
        }
      }
    }

    // Save preferences for active entity type via AJAX
    async function saveCurrentEntityPreferences() {
      const props = userPreferences[activeEntityType] || {};
      try {
        const response = await fetch('/AJAX/update_wd_prefs.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': CSRF_TOKEN
          },
          body: JSON.stringify({
            action: 'save_entity_preferences',
            entity_type: activeEntityType,
            properties: props
          })
        }).then(r => r.json());

        if (response.success) {
          showSaveToast(`Preferences for ${activeEntityType} saved successfully.`);
        } else {
          alert('Error saving preferences: ' + (response.message || 'Unknown error'));
        }
      } catch (err) {
        console.error('AJAX save error:', err);
        alert('Failed to save preferences to server.');
      }
    }

    function showSaveToast(message) {
      const toast = document.getElementById('saveToastNotice');
      if (toast) {
        toast.innerHTML = `<i class="fas fa-check-circle mr-1"></i> ${escapeHtml(message)}`;
        toast.classList.remove('hidden');
        setTimeout(() => {
          toast.classList.add('hidden');
        }, 3500);
      }
    }

    // Build dynamic settings dictionary for JavaScript client
    function buildActiveClientSettingsDict() {
      const shownByEntity = {};
      const flatShown = {};

      for (const [et, props] of Object.entries(userPreferences)) {
        shownByEntity[et] = {};
        for (const [pCode, pDef] of Object.entries(props)) {
          const tuple = [
            pDef.label || pCode,
            Boolean(pDef.enabled),
            pDef.type || 'str',
            Boolean(pDef.show_missing),
            pDef.temporal_mode || 'latest'
          ];
          shownByEntity[et][pCode] = tuple;
          if (pDef.enabled) {
            flatShown[pCode] = tuple;
          }
        }
      }

      return {
        shownPropertiesByEntity: shownByEntity,
        shownProperties: flatShown,
        showWikipediaLinksTo: baseSettingsDict.showWikipediaLinksTo || {},
        preferredLanguage: baseSettingsDict.preferredLanguage || { en: 'English' },
        fallbackLanguage: baseSettingsDict.fallbackLanguage || 'en',
        stringmatchWikipediaTitles: baseSettingsDict.stringmatchWikipediaTitles || {},
        prioritizeDetectedLanguage: baseSettingsDict.prioritizeDetectedLanguage !== undefined ? baseSettingsDict.prioritizeDetectedLanguage : true
      };
    }

    // Handle change of preview entity type select
    function handlePreviewEntityTypeChange() {
      const sel = document.getElementById('inspectorEntityTypeSelect');
      if (sel && sel.value !== activeEntityType) {
        selectEntitySubTab(sel.value);
      }
    }

    // Main inspector trigger
    async function inspectEntity() {
      const qidInput = document.getElementById('inspectorQidInput');
      const qid = (qidInput?.value || '').trim().toUpperCase();
      if (!qid.match(/^Q\d+$/)) {
        alert('Please enter a valid Wikidata Q-ID (e.g. Q123).');
        return;
      }

      inspectedEntityQid = qid;
      const btn = document.getElementById('btnRunInspector');
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin text-[10px]"></i><span>Loading...</span>';
      }

      try {
        const url = `https://www.wikidata.org/w/api.php?action=wbgetentities&ids=${qid}&languages=en&format=json&origin=*`;
        const res = await fetch(url).then(r => r.json());
        const entity = res?.entities?.[qid];

        if (!entity || entity.missing) {
          alert(`Wikidata entity ${qid} was not found.`);
          return;
        }

        inspectedEntityData = entity;
        inspectedEntityClaims = entity.claims || {};

        // Display metadata bar
        const metaBar = document.getElementById('inspectorEntityMeta');
        const lbl = document.getElementById('inspectorEntityLabel');
        const qBadge = document.getElementById('inspectorEntityQidBadge');
        const desc = document.getElementById('inspectorEntityDesc');
        const wdLink = document.getElementById('inspectorEntityWdLink');

        if (metaBar) metaBar.classList.remove('hidden');
        if (lbl) lbl.textContent = entity.labels?.en?.value || qid;
        if (qBadge) qBadge.textContent = qid;
        if (desc) desc.textContent = entity.descriptions?.en?.value || 'No description available';
        if (wdLink) wdLink.href = `https://www.wikidata.org/wiki/${qid}`;

        // Update preview drawer title
        const drawerTitle = document.getElementById('previewDrawerTitle');
        const drawerSub = document.getElementById('previewDrawerSubTitle');
        if (drawerTitle) drawerTitle.textContent = entity.labels?.en?.value || qid;
        if (drawerSub) drawerSub.textContent = `${qid} (${activeEntityType})`;

        // Render properties table & live preview
        await renderInspectorPropertyTable();
        renderLivePreview(qid);
      } catch (err) {
        console.error('Entity inspection error:', err);
        alert('Failed to inspect entity from Wikidata API.');
      } finally {
        if (btn) {
          btn.disabled = false;
          btn.innerHTML = '<i class="fas fa-play text-[10px]"></i><span>Inspect and preview</span>';
        }
      }
    }

    // Render table of available properties on the entity
    async function renderInspectorPropertyTable() {
      const tbody = document.getElementById('inspectorPropTableBody');
      const countBadge = document.getElementById('inspectorPropCountBadge');
      if (!tbody) return;

      const claimKeys = Object.keys(inspectedEntityClaims);
      if (countBadge) countBadge.textContent = `${claimKeys.length} found`;

      if (claimKeys.length === 0) {
        tbody.innerHTML = `
          <tr>
            <td colspan="4" class="py-6 text-center text-xs text-slate-400 italic">
              No claims found on this entity.
            </td>
          </tr>
        `;
        return;
      }

      tbody.innerHTML = `
        <tr>
          <td colspan="4" class="py-6 text-center text-xs text-slate-500">
            <i class="fas fa-spinner fa-spin mr-1.5 text-indigo-500"></i> Resolving property definitions from Wikidata...
          </td>
        </tr>
      `;

      // Batch resolve property labels and datatypes in chunks of 50
      const propDefinitions = {};
      const chunkSize = 50;
      for (let i = 0; i < claimKeys.length; i += chunkSize) {
        const slice = claimKeys.slice(i, i + chunkSize);
        try {
          const u = `https://www.wikidata.org/w/api.php?action=wbgetentities&ids=${slice.join('|')}&props=labels|datatype&languages=en&format=json&origin=*`;
          const r = await fetch(u).then(res => res.json());
          if (r?.entities) {
            Object.assign(propDefinitions, r.entities);
          }
        } catch (e) {
          console.warn('Batch label fetch error:', e);
        }
      }

      window._currentInspectorProps = [];
      const currentConfigured = userPreferences[activeEntityType] || {};

      let rowsHtml = '';
      claimKeys.forEach(pCode => {
        const propData = propDefinitions[pCode] || {};
        const label = propData.labels?.en?.value || pCode;
        const datatype = propData.datatype || 'string';
        const isConfigured = Boolean(currentConfigured[pCode]);

        let mappedType = 'str';
        if (datatype === 'globe-coordinate') mappedType = 'geo';
        else if (datatype === 'commonsMedia') mappedType = 'img';
        else if (datatype === 'external-id') mappedType = 'uri';

        let temporalMode = (pCode === 'P1082' || pCode === 'P2131' || datatype === 'quantity') ? 'history' : 'latest';

        window._currentInspectorProps.push({
          code: pCode,
          label: label,
          datatype: datatype,
          mappedType: mappedType,
          temporalMode: temporalMode,
          isConfigured: isConfigured
        });

        rowsHtml += `
          <tr class="inspector-prop-row hover:bg-slate-50/80 transition-colors" data-code="${escapeHtml(pCode)}" data-label="${escapeHtml(label.toLowerCase())}">
            <td class="py-2.5 px-3">
              <div class="flex items-center gap-2">
                <a href="https://www.wikidata.org/wiki/Property:${escapeHtml(pCode)}" target="_blank" class="font-semibold text-slate-900 hover:text-indigo-600">
                  ${escapeHtml(label)}
                </a>
                <span class="text-[10px] font-mono px-1.5 py-0.2 rounded bg-slate-100 text-slate-600 font-medium">${escapeHtml(pCode)}</span>
              </div>
            </td>
            <td class="py-2.5 px-3">
              <span class="text-[10px] font-mono text-slate-500">${escapeHtml(datatype)}</span>
            </td>
            <td class="py-2.5 px-3">
              ${isConfigured
                ? `<span class="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full"><i class="fas fa-check text-[9px]"></i> Active</span>`
                : `<span class="text-[10px] text-slate-400">Not in ${escapeHtml(activeEntityType)}</span>`
              }
            </td>
            <td class="py-2.5 px-3 text-right">
              ${isConfigured
                ? `<span class="text-[11px] text-slate-400 italic">Configured</span>`
                : `<button type="button"
                           onclick="quickAddPropertyToActiveEntity('${escapeHtml(pCode)}', '${escapeHtml(label).replace(/'/g, "\\'")}', '${escapeHtml(mappedType)}', '${escapeHtml(temporalMode)}')"
                           class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-semibold rounded-md bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 transition shadow-2xs">
                     <i class="fas fa-plus text-[9px]"></i>
                     <span>Add to ${escapeHtml(activeEntityType)}</span>
                   </button>`
              }
            </td>
          </tr>
        `;
      });

      tbody.innerHTML = rowsHtml;
    }

    // Filter rows in property table
    function filterInspectorProperties() {
    console.warn(q); 
      const q = (document.getElementById('inspectorPropFilterInput')?.value || '').toLowerCase().trim();
      document.querySelectorAll('.inspector-prop-row').forEach(row => {
        const code = row.getAttribute('data-code').toLowerCase();
        const label = row.getAttribute('data-label');
        if (code.includes(q) || label.includes(q)) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    }

    // Render live drawer preview using wikibaseEntry in preview mode
    function renderLivePreview(qid) {
      const previewTarget = document.getElementById('previewWDTarget');
      if (!previewTarget) return;

      const drawerSub = document.getElementById('previewDrawerSubTitle');
      if (drawerSub) drawerSub.textContent = `${qid} (${activeEntityType})`;

      const dynamicDict = buildActiveClientSettingsDict();
      try {
        const wd = new wikibaseEntry(qid, dynamicDict, 'preview', 'qid', activeEntityType);
        wd.getWikidata()
          .then(() => {
            wd.renderEntities(qid);
          })
          .catch(err => {
            console.error('Live preview rendering error:', err);
            previewTarget.innerHTML = `<p class="text-xs text-rose-500 italic p-4">Error rendering preview: ${escapeHtml(err.message || 'Unknown error')}</p>`;
          });
      } catch (err) {
        console.error('Failed to instantiate preview entry:', err);
        previewTarget.innerHTML = `<p class="text-xs text-rose-500 italic p-4">Initialization error: ${escapeHtml(err.message || 'Unknown error')}</p>`;
      }
    }

    // Utility escape function
    function escapeHtml(str) {
      if (!str) return '';
      return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    // String matching preferences state
    let stringMatchPrioritizeDetected = baseSettingsDict.prioritizeDetectedLanguage !== undefined
      ? Boolean(baseSettingsDict.prioritizeDetectedLanguage)
      : true;
    let fallbackLanguages = Object.keys(baseSettingsDict.stringmatchWikipediaTitles || {});

    const commonLangLabels = {
      enwiki: 'English',
      frwiki: 'French',
      dewiki: 'German',
      nlwiki: 'Dutch',
      eswiki: 'Spanish',
      itwiki: 'Italian',
      lawiki: 'Latin',
      ptwiki: 'Portuguese',
      plwiki: 'Polish',
      ruwiki: 'Russian',
      jawiki: 'Japanese',
      zhwiki: 'Chinese',
      arwiki: 'Arabic',
      svwiki: 'Swedish',
      ukwiki: 'Ukrainian',
      elwiki: 'Greek',
      huwiki: 'Hungarian',
      cswiki: 'Czech',
      dawiki: 'Danish',
      fiwiki: 'Finnish',
      nowiki: 'Norwegian',
      trwiki: 'Turkish',
      hewiki: 'Hebrew'
    };

    function renderFallbackLanguagesList() {
      const container = document.getElementById('fallbackLanguagesContainer');
      const checkbox = document.getElementById('prioritizeDetectedCheckbox');
      if (checkbox) {
        checkbox.checked = stringMatchPrioritizeDetected;
      }
      if (!container) return;

      if (fallbackLanguages.length === 0) {
        container.innerHTML = `
          <div class="text-xs text-slate-400 italic py-1 flex items-center gap-1.5">
            <i class="fas fa-info-circle text-slate-400"></i>
            <span>No fallback languages configured. Lookups will default to English Wikipedia (<code>enwiki</code>).</span>
          </div>
        `;
        return;
      }

      container.innerHTML = fallbackLanguages.map(code => {
        const clean = code.toLowerCase();
        const label = commonLangLabels[clean] || (baseSettingsDict.stringmatchWikipediaTitles?.[clean]?.[1]) || clean;
        return `
          <span class="inline-flex items-center gap-1.5 pl-2.5 pr-1.5 py-1 rounded-lg text-xs font-medium bg-white text-slate-700 border border-slate-300 shadow-2xs">
            <span class="font-bold text-slate-900">${escapeHtml(clean)}</span>
            <span class="text-slate-500 font-normal">(${escapeHtml(label)})</span>
            <button type="button"
                    onclick="handleRemoveFallbackLanguage('${escapeHtml(clean)}')"
                    class="w-4 h-4 ml-0.5 rounded-full inline-flex items-center justify-center text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer"
                    title="Remove ${escapeHtml(clean)}">
              <i class="fas fa-times text-[10px]"></i>
            </button>
          </span>
        `;
      }).join('');
    }

    function handleAddGuidedLanguage() {
      const sel = document.getElementById('guidedLangSelect');
      if (!sel) return;
      const code = sel.value.trim().toLowerCase();
      if (!code) return;
      if (!fallbackLanguages.includes(code)) {
        fallbackLanguages.push(code);
        renderFallbackLanguagesList();
      }
    }

    function handleAddCustomLanguage() {
      const inp = document.getElementById('customLangCodeInput');
      if (!inp) return;
      let val = inp.value.trim().toLowerCase();
      if (!val) return;
      if (!val.endsWith('wiki')) {
        val += 'wiki';
      }
      if (!val.match(/^[a-z0-9_-]+wiki$/)) {
        alert('Please enter a valid language code (e.g. "ca", "ro", or "cawiki").');
        return;
      }
      if (!fallbackLanguages.includes(val)) {
        fallbackLanguages.push(val);
        renderFallbackLanguagesList();
      }
      inp.value = '';
    }

    function handleRemoveFallbackLanguage(code) {
      fallbackLanguages = fallbackLanguages.filter(c => c !== code);
      renderFallbackLanguagesList();
    }

    async function saveTitlePreferences() {
      const checkbox = document.getElementById('prioritizeDetectedCheckbox');
      const prioritize = checkbox ? checkbox.checked : true;
      stringMatchPrioritizeDetected = prioritize;

      try {
        const res = await fetch('/AJAX/update_wd_prefs.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': CSRF_TOKEN
          },
          body: JSON.stringify({
            action: 'save_title_preferences',
            prioritize_detected_language: prioritize,
            fallback_languages: fallbackLanguages
          })
        }).then(r => r.json());

        if (res.success) {
          baseSettingsDict.prioritizeDetectedLanguage = prioritize;
          const newDict = {};
          fallbackLanguages.forEach(code => {
            const clean = code.toLowerCase();
            const langCode = clean.replace(/wiki$/, '');
            newDict[clean] = [
              `https://${langCode}.wikipedia.org/wiki/`,
              commonLangLabels[clean] || `${langCode.toUpperCase()} (${clean})`
            ];
          });
          baseSettingsDict.stringmatchWikipediaTitles = newDict;

          const toast = document.getElementById('saveTitlesToastNotice');
          if (toast) {
            toast.classList.remove('hidden');
            setTimeout(() => {
              toast.classList.add('hidden');
            }, 3500);
          }
        } else {
          alert('Error saving preferences: ' + (res.message || 'Unknown error'));
        }
      } catch (err) {
        console.error('Save title preferences error:', err);
        alert('Failed to save preferences to server.');
      }
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', () => {
      renderEntityConfig();
      renderFallbackLanguagesList();
    });
  </script>
  </body>
</html>
