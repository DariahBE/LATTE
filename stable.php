<?php

if (!defined('ROOT_DIR')) {
  include_once(__DIR__.'/config/config.inc.php');
}
include_once(ROOT_DIR.'/includes/getnode.inc.php');
include_once(ROOT_DIR.'/includes/preparedviews.inc.php'); //replaced entityviews!
include_once(ROOT_DIR.'/includes/navbar.inc.php');
include_once(ROOT_DIR.'/includes/datasilo.inc.php');
include_once(ROOT_DIR.'/includes/user.inc.php');
include_once(ROOT_DIR.'/includes/wikidata_user_prefs.inc.php');


$typeOK = false;
$uuid = false;
if(isset($_GET['type'])){
  $type = ucfirst($_GET['type']);
  $approvedTypes = array_keys(NODEMODEL);
  if(in_array($type, $approvedTypes)){
    $typeOK = true;
  }
}
if(!($typeOK)){
  header('Location: ' . (defined('BASE_URL') ? BASE_URL : '') . '/error.php?type=node');
  exit;
}

if(isset($_GET['uuid'])){
  $uuid = $_GET['uuid'];
}

if(!($uuid)){
  header('Location: /error.php?type=uuid');
  die();
}

$graph = new Node($client);
$silo = new Siloconnector($client); 

$user = new User($client);
$user->checkAccess(ENTITIESAREPUBLIC);
$wikidata = new Wikidata_user($client);
$wikidata->buildPreferences();


//getnode that matches the provided UUID or primary key as defined in the configfile:

//if the config file has a PK defined for the given type, use that.
//otherwise: retain the original uid (UUIDV4)
$propertyWithPK = 'uid';
if (array_key_exists($type, PRIMARIES) && boolval(PRIMARIES[$type])){
  $propertyWithPK = PRIMARIES[$type];
}
$core = $graph->matchSingleNode($type, $propertyWithPK, $uuid);
if ($propertyWithPK !== 'uid' && !(array_key_exists('coreID', $core))) {
  $core = $graph->matchSingleNode($type, 'uid', $uuid);
}
// for Annotation_auto nodes you'll need to perform a separate check: 
if ($type == ANNONODE && !(array_key_exists('coreID', $core))){
  //annotation_auto nodes always have uid set by program.
  $core = $graph->matchSingleNode('Annotation_auto', 'uid', $uuid);
}
if(array_key_exists('coreID', $core)){
  $coreNeoID = $core["neoID"]; 
  $coreId = $core['coreID'];
  $silo->getNeighboursConnectedBy($coreNeoID); 
  $siloArray = $silo->makeURIs('html'); 
  $block = new Blockfactory($type); 
  $textConnections = $graph->listTextsConnectedToEntityWithID((int)$coreNeoID);
  $wdqid = $graph->getwikidataValue($core["neoID"]);
}else{
  include_once(ROOT_DIR . '/includes/entity_merge.inc.php');
  $merger = new EntityMerger($client);
  $redirect = $merger->resolveMergedRedirect($type, (string)$uuid);
  if ($redirect !== null) {
    $baseUrl = defined('BASE_URL') ? BASE_URL : '';
    header('Location: ' . $baseUrl . '/URI/' . rawurlencode($redirect['target_type']) . '/' . rawurlencode($redirect['target_id']), true, 301);
    exit;
  }
  header('Location: ' . (defined('BASE_URL') ? BASE_URL : '') . '/error.php?type=id');
  exit;
}
?>


<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Stable identifier: <?php echo htmlspecialchars($uuid, ENT_QUOTES, 'UTF-8');?></title>
    <link rel="stylesheet" href="/CSS/style_entities.css">
    <link rel="stylesheet" href="/CSS/stylePublic.css">
    <link rel="stylesheet" href="/CSS/overlaystyling.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script type="text/javascript" src="/JS/clipboardcopy.js"> </script>
    <!-- wikidata SDK and custom code! SDK docs: https://github.com/maxlath/wikibase-sdk-->
    <script src="/JS/wikidata_sdk/wikibase-sdk.js"></script>
    <script src="/JS/wikidata_sdk/wikidata-sdk.js"></script>
    <script src="/JS/wikidata.js"></script>
    <script src="/JS/interactWithEntities.js"></script>
    <!-- extra script for wikidata content: -->
    <script src="/JS/caroussel.js"></script>
    <script src="/JS/makeMap.js"></script>
    <script src="/JS/leaflet/leaflet.js"></script>
    <link rel="stylesheet" href="/CSS/leaflet/leaflet.css">
    <?php
      include_once(ROOT_DIR . '/includes/matomo.inc.php');
      echo renderMatomoTracking();
    ?>
  </head>
  <body class="bg-slate-100 text-slate-800 antialiased">
    <?php
      $adminMode = isset($user) && $user ? $user->isAdmin() : false;
      $navbar = new Navbar($adminMode); 
      echo $navbar->getNav();
    ?>
    <div class="max-w-6xl mx-auto my-8 px-4 sm:px-6 space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php 
          echo $block->makeIDBox($core); 
        ?>
      </div>

      <div id='edrbox' class="grid grid-cols-1 md:grid-cols-2 gap-6"></div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <?php
          //datasilo Knowledgebases:
          if (count($siloArray) > 0){
            echo "<div class='bg-white border border-slate-200 rounded-xl shadow-sm p-6'>";
              echo "<h3 class='text-base font-semibold text-slate-900 mb-3'>Connected knowledge bases</h3>";
              echo "<ul class='space-y-2'>";
              foreach($siloArray as $urlBlock){
                echo '<li class="text-xs text-indigo-600 hover:underline break-all">'.$urlBlock.'</li>';
              }
              echo "</ul>";
            echo "</div>";
          }
          ?>
          <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6 lg:col-span-2" id="relationshipsCard">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
              <h3 class="text-base font-semibold text-slate-900">
                Relationships <span id="relTotalBadge" class="text-xs font-normal text-slate-500">(loading...)</span>
              </h3>
            </div>

            <!-- Filter controls -->
            <div class="flex flex-wrap items-center gap-2 mb-3">
              <select id="relFilterRelation" aria-label="Filter by relation" class="bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:ring-1 focus:ring-indigo-500">
                <option value="">All relations</option>
              </select>
              <select id="relFilterNode" aria-label="Filter by node type" class="bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:ring-1 focus:ring-indigo-500">
                <option value="">All nodes</option>
              </select>
              <input id="relFilterQuery" type="text" placeholder="Filter properties..." class="flex-1 min-w-[140px] bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 focus:ring-1 focus:ring-indigo-500" />
              <button id="relFilterReset" type="button" class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium cursor-pointer transition-colors">
                Reset
              </button>
            </div>

            <div class="overflow-x-auto max-h-[420px] overflow-y-auto rounded-lg border border-slate-200">
              <table class="w-full border-collapse text-sm divide-y divide-slate-200">
                <thead class="bg-slate-50 text-slate-700 font-semibold text-xs uppercase tracking-wider sticky top-0">
                  <tr>
                    <th id="relSortRelation" class="py-2.5 px-3 text-left cursor-pointer select-none hover:bg-slate-100 transition-colors" title="Sort by Relation">
                      <span class="inline-flex items-center gap-1">Relation <span id="relSortIconRelation" class="text-indigo-600">▲</span></span>
                    </th>
                    <th id="relSortNode" class="py-2.5 px-3 text-left cursor-pointer select-none hover:bg-slate-100 transition-colors" title="Sort by Node">
                      <span class="inline-flex items-center gap-1">Node <span id="relSortIconNode" class="text-slate-400">⇅</span></span>
                    </th>
                    <th class="py-2.5 px-3 text-left">Properties</th>
                  </tr>
                </thead>
                <tbody id="relTableBody" class="divide-y divide-slate-100">
                  <tr><td colspan="3" class="py-6 text-center text-xs text-slate-400">Loading relationships...</td></tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination controls -->
            <div id="relPaginationBar" class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-600">
              <span id="relRangeInfo"></span>
              <div class="flex items-center gap-2">
                <button id="relPrevBtn" type="button" disabled class="px-3 py-1.5 rounded-lg bg-white border border-slate-300 text-slate-700 font-medium hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer">
                  ← Previous
                </button>
                <span id="relPageInfo" class="font-medium text-slate-700"></span>
                <button id="relNextBtn" type="button" disabled class="px-3 py-1.5 rounded-lg bg-white border border-slate-300 text-slate-700 font-medium hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer">
                  Next →
                </button>
              </div>
            </div>
          </div>

          <script>
            (function () {
              const state = {
                neoid: <?php echo (int)$coreNeoID; ?>,
                page: 1,
                limit: 25,
                sort: 'relation',
                dir: 'asc',
                relation: '',
                node: '',
                q: '',
                facetsLoaded: false
              };

              const badgeEl = document.getElementById('relTotalBadge');
              const relSelect = document.getElementById('relFilterRelation');
              const nodeSelect = document.getElementById('relFilterNode');
              const queryInput = document.getElementById('relFilterQuery');
              const resetBtn = document.getElementById('relFilterReset');
              const tbodyEl = document.getElementById('relTableBody');
              const rangeEl = document.getElementById('relRangeInfo');
              const pageEl = document.getElementById('relPageInfo');
              const prevBtn = document.getElementById('relPrevBtn');
              const nextBtn = document.getElementById('relNextBtn');
              const sortRelTh = document.getElementById('relSortRelation');
              const sortNodeTh = document.getElementById('relSortNode');
              const iconRel = document.getElementById('relSortIconRelation');
              const iconNode = document.getElementById('relSortIconNode');

              function updateSortIcons() {
                iconRel.textContent = state.sort === 'relation' ? (state.dir === 'asc' ? '▲' : '▼') : '⇅';
                iconRel.className = state.sort === 'relation' ? 'text-indigo-600' : 'text-slate-400';
                iconNode.textContent = state.sort === 'node' ? (state.dir === 'asc' ? '▲' : '▼') : '⇅';
                iconNode.className = state.sort === 'node' ? 'text-indigo-600' : 'text-slate-400';
              }

              function populateSelect(selectEl, items, defaultLabel, currentValue) {
                selectEl.innerHTML = '';
                const defOpt = document.createElement('option');
                defOpt.value = '';
                defOpt.textContent = defaultLabel;
                selectEl.appendChild(defOpt);
                items.forEach(item => {
                  const opt = document.createElement('option');
                  opt.value = item;
                  opt.textContent = item;
                  if (item === currentValue) opt.selected = true;
                  selectEl.appendChild(opt);
                });
              }

              function renderRows(rows) {
                tbodyEl.innerHTML = '';
                if (!rows || rows.length === 0) {
                  const tr = document.createElement('tr');
                  const td = document.createElement('td');
                  td.colSpan = 3;
                  td.className = 'py-6 text-center text-xs text-slate-500';
                  td.textContent = 'No relationship links match the current criteria.';
                  tr.appendChild(td);
                  tbodyEl.appendChild(tr);
                  return;
                }

                rows.forEach(row => {
                  const tr = document.createElement('tr');
                  tr.className = 'odd:bg-white even:bg-slate-50/50 hover:bg-slate-100/60 transition-colors';

                  const tdRel = document.createElement('td');
                  tdRel.className = 'py-2.5 px-3 font-medium text-slate-800 text-xs whitespace-nowrap';
                  tdRel.textContent = row.relation || '';

                  const tdNode = document.createElement('td');
                  tdNode.className = 'py-2.5 px-3 text-slate-600 text-xs whitespace-nowrap';
                  const badge = document.createElement('span');
                  badge.className = 'inline-block px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[11px]';
                  badge.textContent = row.node_label || '';
                  tdNode.appendChild(badge);

                  const tdProps = document.createElement('td');
                  tdProps.className = 'py-2.5 px-3';
                  const ul = document.createElement('ul');
                  ul.className = 'space-y-1';
                  (row.properties || []).forEach(p => {
                    const li = document.createElement('li');
                    li.className = 'text-xs';
                    const keySpan = document.createElement('span');
                    keySpan.className = 'font-semibold text-slate-700';
                    keySpan.textContent = p.name + ': ';
                    li.appendChild(keySpan);

                    const rawVal = String(p.value || '').trim();
                    if (p.type === 'uri' && /^https?:\/\//i.test(rawVal)) {
                      const a = document.createElement('a');
                      a.href = rawVal;
                      a.target = '_blank';
                      a.rel = 'noopener noreferrer';
                      a.className = 'text-indigo-600 hover:underline break-all';
                      a.textContent = rawVal;
                      li.appendChild(a);
                    } else {
                      const valSpan = document.createElement('span');
                      valSpan.className = 'text-slate-600 break-all';
                      valSpan.textContent = rawVal;
                      li.appendChild(valSpan);
                    }
                    ul.appendChild(li);
                  });
                  tdProps.appendChild(ul);

                  tr.appendChild(tdRel);
                  tr.appendChild(tdNode);
                  tr.appendChild(tdProps);
                  tbodyEl.appendChild(tr);
                });
              }

              async function loadRelationships() {
                const params = new URLSearchParams({
                  neoid: String(state.neoid),
                  page: String(state.page),
                  limit: String(state.limit),
                  sort: state.sort,
                  dir: state.dir,
                  relation: state.relation,
                  node: state.node,
                  q: state.q
                });
                const res = await fetch('/AJAX/get_relationships.php?' + params.toString());
                const data = await res.json();
                if (!res.ok || data.error) {
                  badgeEl.textContent = '(error)';
                  return;
                }

                if (!state.facetsLoaded) {
                  populateSelect(relSelect, data.available_relations || [], 'All relations', state.relation);
                  populateSelect(nodeSelect, data.available_nodes || [], 'All nodes', state.node);
                  state.facetsLoaded = true;
                }

                badgeEl.textContent = '(' + data.total + ' found)';
                renderRows(data.rows);

                if (data.total === 0) {
                  rangeEl.textContent = 'Showing 0 results';
                  pageEl.textContent = 'Page 1 of 1';
                } else {
                  const startIdx = ((data.page - 1) * data.limit) + 1;
                  const endIdx = Math.min(data.total, startIdx + data.rows.length - 1);
                  rangeEl.textContent = 'Showing ' + startIdx + '–' + endIdx + ' of ' + data.total;
                  pageEl.textContent = 'Page ' + data.page + ' of ' + data.total_pages;
                }
                prevBtn.disabled = data.page <= 1;
                nextBtn.disabled = data.page >= data.total_pages;
                updateSortIcons();
              }

              sortRelTh.addEventListener('click', () => {
                if (state.sort === 'relation') {
                  state.dir = state.dir === 'asc' ? 'desc' : 'asc';
                } else {
                  state.sort = 'relation';
                  state.dir = 'asc';
                }
                state.page = 1;
                loadRelationships();
              });

              sortNodeTh.addEventListener('click', () => {
                if (state.sort === 'node') {
                  state.dir = state.dir === 'asc' ? 'desc' : 'asc';
                } else {
                  state.sort = 'node';
                  state.dir = 'asc';
                }
                state.page = 1;
                loadRelationships();
              });

              relSelect.addEventListener('change', () => {
                state.relation = relSelect.value;
                state.page = 1;
                loadRelationships();
              });

              nodeSelect.addEventListener('change', () => {
                state.node = nodeSelect.value;
                state.page = 1;
                loadRelationships();
              });

              let searchTimer = null;
              queryInput.addEventListener('input', () => {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(() => {
                  state.q = queryInput.value.trim();
                  state.page = 1;
                  loadRelationships();
                }, 250);
              });

              resetBtn.addEventListener('click', () => {
                state.relation = '';
                state.node = '';
                state.q = '';
                state.page = 1;
                relSelect.value = '';
                nodeSelect.value = '';
                queryInput.value = '';
                loadRelationships();
              });

              prevBtn.addEventListener('click', () => {
                if (state.page > 1) {
                  state.page -= 1;
                  loadRelationships();
                }
              });

              nextBtn.addEventListener('click', () => {
                state.page += 1;
                loadRelationships();
              });

              loadRelationships();
            })();
          </script>
          <?php
          if(count($textConnections['annotations'])){
            $annos = $textConnections['annotations']; 
            $texts = $textConnections['texts']; 
            $annostring = count($annos) === 1 ? 'annotation' : 'annotations'; 
            $texstring = count($texts) === 1 ? 'text' : 'texts'; 
            echo "<div class='bg-white border border-slate-200 rounded-xl shadow-sm p-6'>";
            echo "<h3 class='text-base font-semibold text-slate-900 mb-1'>Text mentions</h3>";
            echo "<p class='text-xs text-slate-500 mb-3'>".count($annos)." $annostring across ".count($texts)." $texstring</p>";
            echo "<ul class='space-y-1.5'>"; 
            $baseUrl = defined('BASE_URL') ? BASE_URL : '';
            foreach($texts as $tex){
              $texuri = $baseUrl.'/text/'.$tex;
              $safeTexUri = htmlspecialchars($texuri, ENT_QUOTES, 'UTF-8');
              $safeTex = htmlspecialchars((string)$tex, ENT_QUOTES, 'UTF-8');
              echo '<li><a class="inline-flex items-center gap-1.5 text-xs font-medium text-indigo-600 hover:text-indigo-800 hover:underline" href="'.$safeTexUri.'"><span class="text-slate-400">📄</span> Text #'.$safeTex.'</a></li>'; 
            }
            echo "</ul>"; 
            echo "</div>";
          }
        ?>
      </div>

    <?php
    //conditional block: only echo wikidata on the stable endpoint IF there's a valid identifier (external regex validation)
    if(boolval($wdqid)){
      ?>
      <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6" id="wdwindow">
        <div class="flex items-center gap-3 mb-4 pb-3 border-b border-slate-200">
          <img src="/images/wikidatawiki_small.png" class="h-8 object-contain">
          <div>
            <h3 class="text-base font-semibold text-slate-900">Wikidata authority record</h3>
            <p class="text-xs text-slate-500">External identity: <?php echo htmlspecialchars($wdqid); ?></p>
          </div>
        </div>

        <div id='wdoptionsblock' class="mb-4 p-3.5 bg-slate-50 border border-slate-200 rounded-xl flex flex-wrap items-center gap-4 text-xs text-slate-700">
          <div class="flex items-center gap-2">
            <label for="wdlookuplanguage" class="font-semibold text-slate-600">Language:</label>
            <select id='wdlookuplanguage' class="bg-white border border-slate-300 rounded-md px-2 py-1 text-xs text-slate-700 focus:ring-1 focus:ring-indigo-500"></select>
          </div>
          <label class="inline-flex items-center gap-2 cursor-pointer">
            <input name='returnSameAsLookup' type='checkbox' id='returnSameAsLookup' class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
            <span>Prioritize lookup language</span>
          </label>
          <label class="inline-flex items-center gap-2 cursor-pointer">
            <input name='lookupConstraint' type='checkbox' id='strictLookup' class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
            <span>Use language fallback</span>
          </label>
        </div>

        <div id='insertWDHere'></div>

        <script>
          var qid = '<?php echo htmlspecialchars($wdqid); ?>';
          var entityType = '<?php echo htmlspecialchars($type); ?>';
          var wdProperties = <?php echo json_encode($wikidata->makeSettingsDictionary($type)); ?>;
          helper_setWDLanguages(document.getElementById('wdlookuplanguage')); 
          wd = new wikibaseEntry(qid, wdProperties, 'static', 'qid', entityType);
          wd.getWikidata()
            .then(function(){wd.renderEntities(qid)}); 
        </script>
      </div>
    <?php
    } //end of conditional wikidata block on stable endpoint. 
    ?>
    </div>


  <?php
    //add edit/delete button only to corenodes: 
    if(array_key_exists($type,  CORENODES)){
  ?>
        <script>
            <?php
              if ($type === ANNONODE || $type === 'Annotation_auto'){
                $e = false;
                $a = $coreNeoID;
              }else{
                $e = $coreNeoID;
                $a = false;
              } 
            
            ?>
        checklogin()
          .then(valid => {

            let etid = <?php echo json_encode($e); ?>;
            let annoid = <?php echo json_encode($a); ?>;
            createEditRemoveBox(etid, annoid)
            .then(divelement => {
              document.getElementById('edrbox').appendChild(divelement);
            })

          })
        </script>
  <?php }?>
  </body>
</html>
